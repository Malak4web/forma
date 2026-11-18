<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Plan;
use App\Payments\PaymentManager;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return OrderResource::collection($request->user()->orders()->with('plan')->latest()->get());
    }

    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return new OrderResource($order->load('plan'));
    }

    public function store(Request $request, PaymentManager $payments, OrderService $orders): JsonResponse
    {
        if ($request->filled('payer_phone')) {
            $request->merge(['payer_phone' => preg_replace('/\D/', '', $request->input('payer_phone'))]);
        }
        $request->validate([
            'plan' => ['required', Rule::exists('plans', 'slug')->where('is_active', true)->whereNull('deleted_at')],
            'method' => ['required', Rule::in($payments->keys())],
        ]);
        $request->validate($payments->driver($request->input('method'))->rules());

        $plan = Plan::where('slug', $request->input('plan'))->firstOrFail();
        [$order, $payload] = $orders->create($request->user(), $plan, $request->input('method'), $request);

        return response()->json(['order' => (new OrderResource($order->load('plan')))->resolve()] + $payload, 201);
    }
}
