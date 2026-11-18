<?php

use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('forma:make-admin {phone}', function (string $phone) {
    $user = User::where('phone', $phone)->first();
    if (! $user) {
        return $this->error("No user with phone {$phone}. Register on the site first.");
    }
    $user->forceFill(['is_admin' => true])->save();
    $this->info("{$user->name} ({$phone}) is now an admin.");
})->purpose('Give a registered user admin access (order review).');

Artisan::command('forma:orders', function () {
    $rows = Order::with(['user', 'plan'])->where('status', Order::PENDING)->oldest()->get()
        ->map(fn ($o) => [$o->ref, $o->user->name, $o->user->phone, $o->plan->name, $o->amount, $o->method, $o->payer_phone, $o->created_at]);
    $this->table(['ref', 'name', 'phone', 'plan', 'EGP', 'method', 'payer', 'created'], $rows);
})->purpose('List pending orders.');

Artisan::command('forma:approve {ref} {--admin= : phone of the approving admin}', function (OrderService $orders, string $ref) {
    $order = Order::where('ref', $ref)->firstOrFail();
    $admin = User::where('phone', $this->option('admin'))->where('is_admin', true)->first()
        ?? User::where('is_admin', true)->firstOrFail();
    $orders->approve($order, $admin, 'approved from CLI');
    $this->info("Order {$ref} approved — subscription active.");
})->purpose('Approve a pending order from the terminal.');
