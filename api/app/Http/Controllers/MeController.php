<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\DailyLog;
use App\Models\Program;
use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /** Everything the SPA needs about the signed-in user in one payload. */
    public function state(?User $user): array
    {
        if (! $user) {
            return ['user' => null];
        }

        $sub = $user->activeSubscriptions()->with('plan')->orderByRaw('ends_at is null desc')->latest('ends_at')->first();
        $slugs = Program::pluck('slug', 'id');

        return [
            'user' => [
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'goal' => $user->goal,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'subscription' => $sub ? [
                'plan' => $sub->plan->slug,
                'since' => $sub->starts_at->toIso8601String(),
                'ends_at' => $sub->ends_at?->toIso8601String(),
            ] : null,
            'unlocked' => $user->unlockedProgramIds()->map(fn ($id) => $slugs[$id])->values(),
            'completed' => $user->completions()->get(['program_id', 'day'])
                ->groupBy(fn ($c) => $slugs[$c->program_id])
                ->map(fn ($g) => $g->pluck('day')->sort()->values()),
            'weights' => $user->weights()->orderBy('logged_on')->get()
                ->map(fn (WeightLog $w) => ['date' => $w->logged_on->toDateString(), 'kg' => $w->kg]),
            'orders' => OrderResource::collection($user->orders()->with('plan')->latest()->get())->resolve(),
            'nutrition' => $user->nutrition ? $user->nutrition + ['since' => $user->nutrition_since?->toDateString()] : null,
            ...$this->tracking($user),
        ];
    }

    /** Last 35 days of activity (daily logs + workout dates) and the coach summary for the dashboard. */
    private function tracking(User $user): array
    {
        $from = today()->subDays(34);
        $logs = $user->dailyLogs()->where('date', '>=', $from->toDateString())->orderBy('date')->get()
            ->map(fn (DailyLog $l) => $l->toClient())->values();
        $workouts = $user->completions()->where('completed_at', '>=', $from)->pluck('completed_at')
            ->map(fn ($d) => $d->toDateString())->unique()->sort()->values();

        $access = $user->canReachCoach();
        $last = $access ? $user->coachMessages()->with('admin:id,name')->latest('id')->first() : null;
        $lastCheckin = $user->followUps()->latest('followup_date')->first();

        return [
            'logs' => $logs,
            'workout_dates' => $workouts,
            'coach' => [
                'access' => $access,
                'unread' => $access ? $user->coachMessages()->where('sender', 'coach')->whereNull('read_at')->count() : 0,
                'last' => $last?->toClient(),
                'last_checkin' => $lastCheckin?->followup_date->toDateString(),
                'next_checkin' => $lastCheckin?->next_followup_date?->toDateString(),
                'notes' => $user->followUps()->where(fn ($q) => $q->whereNotNull('coach_notes')->orWhereNotNull('plan_adjustments'))
                    ->latest('followup_date')->first()?->only(['coach_notes', 'plan_adjustments', 'followup_date']),
            ],
        ];
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->state($request->user()));
    }

    public function storeWeight(Request $request): JsonResponse
    {
        $data = $request->validate(['kg' => ['required', 'numeric', 'between:30,300']]);
        $request->user()->weights()->updateOrCreate(
            ['logged_on' => today()->toDateString()],
            ['kg' => round($data['kg'], 1)],
        );

        return response()->json($this->state($request->user()));
    }
}
