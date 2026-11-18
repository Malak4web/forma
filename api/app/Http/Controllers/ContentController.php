<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Plan;
use App\Models\Program;
use App\Models\Transformation;
use App\Payments\PaymentManager;
use App\Support\ProgramDays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /** Library + programs + plans + payment methods in one cacheable call. */
    public function index(PaymentManager $payments): JsonResponse
    {
        return response()->json([
            'exercises' => Exercise::orderBy('sort')->get()->map->toPublic(),
            'programs' => Program::where('is_active', true)->with(['weeks', 'plans' => fn ($q) => $q->withCount('programs')])->orderBy('sort')->get()->map->toPublic(),
            'plans' => Plan::where('is_active', true)->with('programs')->orderBy('sort')->get()->map->toPublic(),
            'transformations' => Transformation::where('is_active', true)->orderBy('sort')->orderBy('id')->get()->map->toPublic(),
            'payment_methods' => $payments->publicList(),
            'free_days' => Program::FREE_DAYS,
        ])->header('Cache-Control', 'no-cache'); // admins edit prices/payment details live
    }

    /** One workout day. Days after FREE_DAYS need a subscription that unlocks the program. */
    public function day(Request $request, Program $program, int $day): JsonResponse
    {
        abort_unless($program->is_active && $day >= 1 && $day <= $program->days, 404, 'اليوم ده مش موجود.');

        $user = $request->user();
        if (! $program->isFreeDay($day) && ! $user?->hasAccessTo($program)) {
            $plan = $program->primaryPlan()?->load('programs');

            return response()->json([
                'message' => 'اليوم ده للمشتركين.',
                'locked' => true,
                'plan' => $plan?->toPublic(),
            ], 403);
        }

        $program->load('weeks');

        return response()->json(ProgramDays::day($program, $day));
    }
}
