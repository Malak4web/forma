<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'active');
        $q = trim((string) $request->query('q', ''));
        $now = now();

        $base = Subscription::with(['user:id,name,phone', 'plan:id,slug,name,price,duration_days', 'order:id,ref,amount,method'])
            ->when($q !== '', fn ($qu) => $qu->whereHas('user', fn ($uq) => $uq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")));

        $filtered = (clone $base)
            ->when($status === 'active', fn ($qu) => $qu
                ->where('starts_at', '<=', $now)
                ->where(fn ($sub) => $sub->whereNull('ends_at')->orWhere('ends_at', '>', $now)))
            ->when($status === 'expiring', fn ($qu) => $qu
                ->where('starts_at', '<=', $now)
                ->whereNotNull('ends_at')
                ->whereBetween('ends_at', [$now, $now->copy()->addDays(7)]))
            ->when($status === 'lifetime', fn ($qu) => $qu->whereNull('ends_at'))
            ->when($status === 'expired', fn ($qu) => $qu->whereNotNull('ends_at')->where('ends_at', '<=', $now))
            ->latest('starts_at')
            ->paginate(30);

        $counts = [
            'active' => Subscription::where('starts_at', '<=', $now)
                ->where(fn ($sub) => $sub->whereNull('ends_at')->orWhere('ends_at', '>', $now))->count(),
            'expiring' => Subscription::where('starts_at', '<=', $now)
                ->whereNotNull('ends_at')
                ->whereBetween('ends_at', [$now, $now->copy()->addDays(7)])->count(),
            'lifetime' => Subscription::whereNull('ends_at')->count(),
            'expired' => Subscription::whereNotNull('ends_at')->where('ends_at', '<=', $now)->count(),
            'all' => Subscription::count(),
        ];

        $plans = Plan::where('price', '>', 0)->orderBy('sort')->get(['id', 'slug', 'name', 'price', 'duration_days']);

        return response()->json([
            'data' => $filtered->getCollection()->map(fn (Subscription $s) => $this->serialize($s)),
            'counts' => $counts,
            'plans' => $plans,
            'meta' => [
                'current_page' => $filtered->currentPage(),
                'last_page' => $filtered->lastPage(),
                'total' => $filtered->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'lifetime' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);
        $start = isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : now();

        if ($request->boolean('lifetime')) {
            $end = null;
        } elseif (! empty($data['ends_at'])) {
            $end = Carbon::parse($data['ends_at']);
        } else {
            $end = $plan->duration_days ? $start->copy()->addDays($plan->duration_days) : null;
        }

        $sub = Subscription::create([
            'user_id' => $data['user_id'],
            'plan_id' => $plan->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'data' => $this->serialize($sub->load(['user:id,name,phone', 'plan:id,slug,name,price,duration_days', 'order:id,ref,amount,method'])),
        ], 201);
    }

    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['sometimes', 'integer', 'exists:plans,id'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['nullable', 'date'],
            'lifetime' => ['nullable', 'boolean'],
            'extend_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $updates = [];
        if (isset($data['plan_id'])) {
            $updates['plan_id'] = $data['plan_id'];
        }
        if (isset($data['starts_at'])) {
            $updates['starts_at'] = Carbon::parse($data['starts_at']);
        }
        if ($request->boolean('lifetime')) {
            $updates['ends_at'] = null;
        } elseif (! empty($data['extend_days'])) {
            $base = $subscription->ends_at && $subscription->ends_at->isFuture()
                ? $subscription->ends_at->copy()
                : now();
            $updates['ends_at'] = $base->addDays((int) $data['extend_days']);
        } elseif (array_key_exists('ends_at', $data)) {
            $updates['ends_at'] = $data['ends_at'] ? Carbon::parse($data['ends_at']) : null;
        }
        if (array_key_exists('notes', $data)) {
            $updates['notes'] = $data['notes'];
        }

        $subscription->update($updates);

        return response()->json([
            'data' => $this->serialize($subscription->fresh(['user:id,name,phone', 'plan:id,slug,name,price,duration_days', 'order:id,ref,amount,method'])),
        ]);
    }

    public function destroy(Subscription $subscription): JsonResponse
    {
        $subscription->delete(); // soft delete only

        return response()->json(['deleted' => true]);
    }

    private function serialize(Subscription $s): array
    {
        $now = now();
        $daysLeft = $s->ends_at ? (int) $now->diffInDays($s->ends_at, false) : null;

        return [
            'id' => $s->id,
            'user_id' => $s->user_id,
            'user' => $s->user ? ['id' => $s->user->id, 'name' => $s->user->name, 'phone' => $s->user->phone] : null,
            'plan_id' => $s->plan_id,
            'plan' => $s->plan ? ['id' => $s->plan->id, 'slug' => $s->plan->slug, 'name' => $s->plan->name, 'price' => $s->plan->price] : null,
            'order' => $s->order ? ['ref' => $s->order->ref, 'amount' => $s->order->amount, 'method' => $s->order->method] : null,
            'starts_at' => $s->starts_at?->toIso8601String(),
            'ends_at' => $s->ends_at?->toIso8601String(),
            'is_active' => $s->isActive(),
            'is_lifetime' => $s->ends_at === null,
            'days_left' => $daysLeft,
            'notes' => $s->notes,
            'created_at' => $s->created_at?->toIso8601String(),
        ];
    }
}
