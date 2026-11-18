<?php

namespace Tests;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = ContentSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Accept' => 'application/json']);
    }

    protected function receipt(): UploadedFile
    {
        return UploadedFile::fake()->image('receipt.jpg', 600, 900);
    }

    /** A user with an approved order for the given plan. */
    protected function subscriber(string $plan = 'challenge'): User
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => Plan::where('slug', $plan)->value('id'),
            'amount' => 1, 'method' => 'instapay', 'status' => Order::PENDING,
        ]);
        app(OrderService::class)->approve($order, $admin);

        return $user->fresh();
    }
}
