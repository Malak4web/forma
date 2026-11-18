<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $admin = (bool) $request->user()?->is_admin;

        return [
            'id' => $this->ref,
            'plan' => $this->plan?->slug,
            'plan_name' => $this->plan?->name,
            'amount' => $this->amount,
            'method' => $this->method,
            'status' => $this->status,
            'payer_phone' => $this->payer_phone,
            'note' => $this->status === Order::REJECTED ? $this->admin_note : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            $this->mergeWhen($admin, fn () => [
                'user' => ['name' => $this->user?->name, 'phone' => $this->user?->phone],
                'admin_note' => $this->admin_note,
                'has_receipt' => (bool) $this->receipt_path,
                'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            ]),
        ];
    }
}
