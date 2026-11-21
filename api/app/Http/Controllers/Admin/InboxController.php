<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CoachController;
use App\Http\Controllers\Controller;
use App\Models\CoachMessage;
use App\Models\DailyLog;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Coach side: member conversations + member check-ins waiting for a review. */
class InboxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $pending = fn ($qq) => $qq->where('source', 'member')->whereNull('coach_notes')->whereNull('plan_adjustments');

        $users = User::query()
            ->where(fn ($w) => $w->whereHas('coachMessages')->orWhereHas('followUps', $pending))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
            ->withCount([
                'coachMessages as unread' => fn ($m) => $m->where('sender', 'member')->whereNull('read_at'),
                'followUps as pending_checkins' => $pending,
            ])
            ->withMax('coachMessages as last_message_at', 'created_at')
            ->withMax(['followUps as last_checkin_at' => $pending], 'created_at')
            ->limit(200)
            ->get();

        $last = CoachMessage::whereIn('id', CoachMessage::selectRaw('max(id)')->whereIn('user_id', $users->pluck('id'))->groupBy('user_id'))
            ->get()->keyBy('user_id');

        $threads = $users->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'phone' => $u->phone,
            'unread' => (int) $u->unread,
            'pending_checkins' => (int) $u->pending_checkins,
            'last' => isset($last[$u->id]) ? ['from' => $last[$u->id]->sender, 'body' => mb_substr($last[$u->id]->body, 0, 90)] : null,
            'at' => max((string) $u->last_message_at, (string) $u->last_checkin_at),
        ])->sortByDesc(fn ($t) => [$t['unread'] + $t['pending_checkins'] > 0 ? 1 : 0, $t['at']])->values();

        return response()->json([
            'data' => $threads,
            'counts' => [
                'unread' => CoachMessage::where('sender', 'member')->whereNull('read_at')->count(),
                'pending_checkins' => $pending(FollowUp::query())->count(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->coachMessages()->where('sender', 'member')->whereNull('read_at')->update(['read_at' => now()]);
        $from = today()->subDays(6);
        $logs = $user->dailyLogs()->where('date', '>=', $from->toDateString())->get()->keyBy(fn (DailyLog $l) => $l->date->toDateString());
        $workouts = $user->completions()->where('completed_at', '>=', $from)->pluck('completed_at')->map(fn ($d) => $d->toDateString())->unique();
        $sub = $user->activeSubscriptions()->with('plan')->latest('starts_at')->first();

        return response()->json([
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'goal' => $user->goal,
                'plan' => $sub?->plan->name,
                'nutrition' => $user->nutrition ? $user->nutrition + ['since' => $user->nutrition_since?->toDateString()] : null,
                'weights' => $user->weights()->orderByDesc('logged_on')->limit(12)->get()->reverse()->values()
                    ->map(fn ($w) => ['date' => $w->logged_on->toDateString(), 'kg' => $w->kg]),
                'week' => collect(range(6, 0))->map(function ($i) use ($logs, $workouts) {
                    $d = today()->subDays($i)->toDateString();

                    return ['date' => $d, 'water' => $logs[$d]->water ?? 0, 'meals' => count($logs[$d]->meals ?? []), 'workout' => $workouts->contains($d)];
                }),
            ],
            'messages' => $user->coachMessages()->with('admin:id,name')->latest('id')->limit(200)->get()
                ->reverse()->values()->map(fn (CoachMessage $m) => $m->toClient()),
            'checkins' => $user->followUps()->latest('followup_date')->latest('id')->limit(10)->get()
                ->map(fn (FollowUp $f) => CoachController::present($f) + ['status' => $f->status]),
        ]);
    }

    public function reply(Request $request, User $user): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $m = $user->coachMessages()->create(['sender' => 'coach', 'admin_id' => $request->user()->id, 'body' => trim($data['body'])]);

        return response()->json(['message' => $m->load('admin:id,name')->toClient()], 201);
    }

    public function review(Request $request, User $user, FollowUp $followUp): JsonResponse
    {
        abort_unless($followUp->user_id === $user->id, 404);
        $data = $request->validate([
            'coach_notes' => ['nullable', 'string', 'max:3000'],
            'plan_adjustments' => ['nullable', 'string', 'max:3000'],
            'next_followup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['required', Rule::in(FollowUp::STATUSES)],
        ]);
        $followUp->update($data + ['admin_id' => $request->user()->id]);

        return response()->json(['checkin' => CoachController::present($followUp->fresh()) + ['status' => $followUp->status]]);
    }
}
