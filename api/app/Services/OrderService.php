<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private PaymentManager $payments) {}

    /** @return array{0: Order, 1: array} the order and driver payload for the client */
    public function create(User $user, Plan $plan, string $method, Request $request): array
    {
        if ($plan->isFree() || ! $plan->is_active) {
            throw ValidationException::withMessages(['plan' => 'الباقة دي مش متاحة للشراء.']);
        }
        if ($user->orders()->where('plan_id', $plan->id)->where('status', Order::PENDING)->exists()) {
            throw ValidationException::withMessages(['plan' => 'عندك طلب للباقة دي لسه بيتراجع. استنى التأكيد أو كلّمنا.']);
        }
        if ($plan->duration_days === null && $user->activeSubscriptions()->where('plan_id', $plan->id)->exists()) {
            throw ValidationException::withMessages(['plan' => 'الباقة دي معاك بالفعل.']);
        }

        $order = new Order([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,   // price is always taken from the DB, never from the client
            'method' => $method,
        ]);
        $payload = $this->payments->driver($method)->start($order, $request);
        $order->save();

        return [$order, $payload];
    }

    public function approve(Order $order, User $admin, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $admin, $note) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! $order->isPending()) {
                throw ValidationException::withMessages(['order' => 'الطلب ده اتراجع قبل كده.']);
            }

            $order->forceFill([
                'status' => Order::PAID,
                'paid_at' => now(),
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_note' => $note,
            ])->save();

            $plan = $order->plan;
            // monthly renewals stack on top of the current period instead of overlapping it
            $current = Subscription::where('user_id', $order->user_id)->where('plan_id', $plan->id)
                ->whereNotNull('ends_at')->where('ends_at', '>', now())->max('ends_at');
            $start = $current ? \Illuminate\Support\Carbon::parse($current) : now();

            Subscription::create([
                'user_id' => $order->user_id,
                'plan_id' => $plan->id,
                'order_id' => $order->id,
                'starts_at' => $start,
                'ends_at' => $plan->duration_days ? $start->copy()->addDays($plan->duration_days) : null,
            ]);

            return $order->refresh();
        });
    }

    public function reject(Order $order, User $admin, string $note): Order
    {
        if (! $order->isPending()) {
            throw ValidationException::withMessages(['order' => 'الطلب ده اتراجع قبل كده.']);
        }
        $order->forceFill([
            'status' => Order::REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_note' => $note,
        ])->save();

        return $order;
    }
}
