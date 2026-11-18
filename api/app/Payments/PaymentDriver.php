<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentDriver
{
    /** Extra validation rules this method needs on checkout. */
    public function rules(): array;

    /** What the customer sees on the checkout page (account number, hint…). */
    public function publicInfo(): array;

    /**
     * Attach method-specific data to the (unsaved) order. Return extra payload for the client,
     * e.g. ['redirect_url' => …] for a hosted gateway, or [] for manual review.
     */
    public function start(Order $order, Request $request): array;
}
