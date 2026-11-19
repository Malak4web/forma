<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Exercise;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Program;
use App\Models\Subscription;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function show(): JsonResponse
    {
        $now = now();
        $today = $now->toDateString();

        $activeSubsQuery = Subscription::query()
            ->where('starts_at', '<=', $now)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));

        $kpis = [
            'members_total' => User::count(),
            'members_new_week' => User::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'subscriptions_active' => (clone $activeSubsQuery)->count(),
            'subscriptions_expiring' => (clone $activeSubsQuery)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<=', $now->copy()->addDays(7))
                ->count(),
            'orders_pending' => Order::where('status', Order::PENDING)->count(),
            'revenue_total' => (int) Order::where('status', Order::PAID)->sum('amount'),
            'revenue_month' => (int) Order::where('status', Order::PAID)
                ->where('paid_at', '>=', $now->copy()->startOfMonth())
                ->sum('amount'),
            'leads_new' => Lead::where('status', 'new')->count(),
            'leads_total' => Lead::count(),
            'followups_due' => FollowUp::whereNotNull('next_followup_date')
                ->whereDate('next_followup_date', '<=', $today)
                ->where('status', '!=', 'completed')
                ->count(),
            'followups_attention' => FollowUp::whereIn('status', ['needs_attention', 'delayed'])->count(),
            'exercises_total' => Exercise::count(),
            'exercises_with_video' => Exercise::whereNotNull('video_url')->where('video_url', '!=', '')->count(),
            'programs_total' => Program::count(),
            'programs_active' => Program::where('is_active', true)->count(),
            'transformations_total' => Transformation::count(),
        ];

        $pendingOrders = OrderResource::collection(
            Order::with(['plan', 'user'])
                ->where('status', Order::PENDING)
                ->oldest()
                ->limit(5)
                ->get()
        )->resolve();

        $dueFollowUps = FollowUp::with('user:id,name,phone')
            ->where(fn ($q) => $q
                ->whereIn('status', ['needs_attention', 'delayed'])
                ->orWhere(fn ($q2) => $q2
                    ->whereNotNull('next_followup_date')
                    ->whereDate('next_followup_date', '<=', $today)
                    ->where('status', '!=', 'completed')))
            ->orderByRaw('next_followup_date IS NULL, next_followup_date ASC')
            ->limit(6)
            ->get()
            ->map(fn (FollowUp $f) => [
                'id' => $f->id,
                'user' => $f->user ? ['id' => $f->user->id, 'name' => $f->user->name, 'phone' => $f->user->phone] : null,
                'status' => $f->status,
                'followup_date' => $f->followup_date?->format('Y-m-d'),
                'next_followup_date' => $f->next_followup_date?->format('Y-m-d'),
                'weight_kg' => $f->weight_kg,
                'coach_notes' => $f->coach_notes,
            ]);

        $recentLeads = Lead::where('status', 'new')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Lead $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'phone' => $l->phone,
                'goal' => $l->goal,
                'kcal' => $l->kcal,
                'created_at' => $l->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'kpis' => $kpis,
            'pending_orders' => $pendingOrders,
            'due_followups' => $dueFollowUps,
            'recent_leads' => $recentLeads,
        ]);
    }
}
