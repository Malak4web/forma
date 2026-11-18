<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(['pending', 'paid', 'rejected', 'cancelled', 'all'])]])['status'] ?? 'pending';

        return OrderResource::collection(
            Order::with(['plan', 'user'])
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->oldest()
                ->paginate(30)
        );
    }

    public function receipt(Order $order): StreamedResponse
    {
        $disk = Storage::disk(config('payments.receipt.disk'));
        abort_unless($order->receipt_path && $disk->exists($order->receipt_path), 404, 'مفيش إيصال.');

        return $disk->response($order->receipt_path, headers: ['Cache-Control' => 'private, no-store']);
    }

    public function approve(Request $request, Order $order, OrderService $orders): OrderResource
    {
        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;

        return new OrderResource($orders->approve($order, $request->user(), $note)->load('plan', 'user'));
    }

    public function reject(Request $request, Order $order, OrderService $orders): OrderResource
    {
        $note = $request->validate(['note' => ['required', 'string', 'max:500']])['note'];

        return new OrderResource($orders->reject($order, $request->user(), $note)->load('plan', 'user'));
    }
}
