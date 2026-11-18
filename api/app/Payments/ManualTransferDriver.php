<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Vodafone Cash / InstaPay: the customer transfers, uploads a receipt screenshot,
 * the order stays pending until an admin approves it.
 */
class ManualTransferDriver implements PaymentDriver
{
    public function __construct(private string $key, private array $config) {}

    public function rules(): array
    {
        $kb = config('payments.receipt.max_kb');

        return [
            'receipt' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', "max:{$kb}"],
            'payer_phone' => [$this->config['payer_phone_required'] ? 'required' : 'nullable', 'regex:/^01[0125]\d{8}$/'],
        ];
    }

    public function publicInfo(): array
    {
        return [
            'id' => $this->key,
            'label' => $this->config['label'],
            'hint' => $this->config['hint'],
            'account' => $this->config['account'],
            'payer_phone_required' => $this->config['payer_phone_required'],
            'review_hours' => config('payments.review_hours'),
        ];
    }

    public function start(Order $order, Request $request): array
    {
        $order->payer_phone = $request->input('payer_phone');
        $order->receipt_path = $request->file('receipt')->store('receipts/'.now()->format('Y/m'), config('payments.receipt.disk'));
        $order->status = Order::PENDING;

        return [];
    }
}
