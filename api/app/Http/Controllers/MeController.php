<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
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
