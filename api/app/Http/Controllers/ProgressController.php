<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function complete(Request $request, Program $program, int $day, MeController $me): JsonResponse
    {
        $user = $request->user();
        abort_unless($day >= 1 && $day <= $program->days, 404, 'اليوم ده مش موجود.');
        abort_unless($program->isFreeDay($day) || $user->hasAccessTo($program), 403, 'اليوم ده للمشتركين.');

        $this->mark($user, $program, $day);

        return response()->json($me->state($user));
    }

    /** Guests train before signing up; after register/login the SPA hands us their local progress. */
    public function import(Request $request, MeController $me): JsonResponse
    {
        $data = $request->validate([
            'completed' => ['array', 'max:20'],
            'completed.*' => ['array', 'max:60'],
            'completed.*.*' => ['integer', 'min:1', 'max:365'],
        ]);
        $user = $request->user();
        $programs = Program::whereIn('slug', array_keys($data['completed'] ?? []))->get()->keyBy('slug');

        foreach ($data['completed'] ?? [] as $slug => $days) {
            $program = $programs[$slug] ?? null;
            if (! $program) {
                continue;
            }
            foreach (array_unique($days) as $day) {
                if ($day <= $program->days && ($program->isFreeDay($day) || $user->hasAccessTo($program))) {
                    $this->mark($user, $program, $day);
                }
            }
        }

        return response()->json($me->state($user));
    }

    private function mark(User $user, Program $program, int $day): void
    {
        $user->completions()->firstOrCreate(
            ['program_id' => $program->id, 'day' => $day],
            ['completed_at' => now()],
        );
    }
}
