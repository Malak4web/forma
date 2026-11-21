<?php

namespace App\Http\Controllers;

use App\Models\CoachMessage;
use App\Models\FollowUp;
use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Member side of coaching: chat thread + weekly check-ins (stored as follow_ups with source=member). */
class CoachController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->canReachCoach()) {
            return response()->json(['access' => false]);
        }

        $user->coachMessages()->where('sender', 'coach')->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'access' => true,
            'messages' => $this->messages($user),
            'checkins' => $user->followUps()->latest('followup_date')->latest('id')->limit(20)->get()
                ->map(fn (FollowUp $f) => self::present($f)),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canReachCoach(), 403, 'الكلام مع الكوتش للمشتركين.');
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $user->coachMessages()->create(['sender' => 'member', 'body' => trim($data['body'])]);

        return response()->json(['messages' => $this->messages($user)], 201);
    }

    public function checkin(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canReachCoach(), 403, 'متابعة الكوتش للمشتركين.');

        $data = $request->validate([
            'weight_kg' => ['required', 'numeric', 'between:30,300'],
            'waist_cm' => ['nullable', 'numeric', 'between:30,250'],
            'chest_cm' => ['nullable', 'numeric', 'between:40,250'],
            'arm_cm' => ['nullable', 'numeric', 'between:15,100'],
            'hips_cm' => ['nullable', 'numeric', 'between:40,250'],
            'diet_adherence' => ['required', 'integer', 'between:1,10'],
            'workout_adherence' => ['required', 'integer', 'between:1,10'],
            'sleep_hours' => ['nullable', 'numeric', 'between:1,20'],
            'energy_level' => ['nullable', 'integer', 'between:1,5'],
            'member_feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        // one member check-in per day: resubmitting the same day updates it
        $f = FollowUp::updateOrCreate(
            ['user_id' => $user->id, 'followup_date' => today()->toDateString(), 'source' => 'member'],
            $data + ['status' => 'needs_attention'],
        );
        WeightLog::withTrashed()->updateOrCreate(
            ['user_id' => $user->id, 'logged_on' => today()->toDateString()],
            ['kg' => round($data['weight_kg'], 1), 'deleted_at' => null],
        );

        return response()->json(['checkin' => self::present($f->fresh())], 201);
    }

    private function messages(User $user): array
    {
        return $user->coachMessages()->with('admin:id,name')->latest('id')->limit(200)->get()
            ->reverse()->values()->map(fn (CoachMessage $m) => $m->toClient())->all();
    }

    /** What the member sees of a follow-up (no internal status). */
    public static function present(FollowUp $f): array
    {
        return [
            'id' => $f->id,
            'date' => $f->followup_date->toDateString(),
            'source' => $f->source,
            'weight_kg' => $f->weight_kg,
            'waist_cm' => $f->waist_cm,
            'chest_cm' => $f->chest_cm,
            'arm_cm' => $f->arm_cm,
            'hips_cm' => $f->hips_cm,
            'diet_adherence' => $f->diet_adherence,
            'workout_adherence' => $f->workout_adherence,
            'sleep_hours' => $f->sleep_hours,
            'energy_level' => $f->energy_level,
            'member_feedback' => $f->member_feedback,
            'coach_notes' => $f->coach_notes,
            'plan_adjustments' => $f->plan_adjustments,
            'next' => $f->next_followup_date?->toDateString(),
            'reviewed' => $f->source === 'coach' || filled($f->coach_notes) || filled($f->plan_adjustments),
        ];
    }
}
