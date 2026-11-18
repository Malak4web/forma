<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_vodafone_cash_order_needs_receipt_and_wallet_number(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/orders', ['plan' => 'challenge', 'method' => 'vodafone_cash'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['receipt', 'payer_phone'])
            ->assertJsonPath('errors.receipt.0', 'ارفع صورة إيصال التحويل.');
    }

    public function test_creates_pending_order_with_server_side_price(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->post('/api/orders', [
            'plan' => 'challenge', 'method' => 'vodafone_cash', 'payer_phone' => '010-9999-8888',
            'amount' => 1, // ignored
            'receipt' => $this->receipt(),
        ])->assertCreated()
            ->assertJsonPath('order.status', 'pending')
            ->assertJsonPath('order.amount', 199)
            ->assertJsonPath('order.payer_phone', '01099998888')
            ->assertJsonMissingPath('order.user');

        $order = Order::where('ref', $res->json('order.id'))->first();
        Storage::disk('local')->assertExists($order->receipt_path);
        $this->assertFalse($user->fresh()->hasAccessTo(\App\Models\Program::where('slug', '30')->first()));
    }

    public function test_instapay_does_not_need_wallet_number(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/api/orders', ['plan' => 'pro', 'method' => 'instapay', 'receipt' => $this->receipt()])
            ->assertCreated()->assertJsonPath('order.method', 'instapay');
    }

    public function test_rejects_bad_plan_method_and_duplicates(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/api/orders', ['plan' => 'free', 'method' => 'instapay', 'receipt' => $this->receipt()])
            ->assertUnprocessable()->assertJsonValidationErrors('plan');
        $this->post('/api/orders', ['plan' => 'pro', 'method' => 'card', 'receipt' => $this->receipt()])
            ->assertUnprocessable()->assertJsonValidationErrors('method');
        $this->post('/api/orders', ['plan' => 'pro', 'method' => 'instapay', 'receipt' => \Illuminate\Http\UploadedFile::fake()->create('x.pdf', 10)])
            ->assertUnprocessable()->assertJsonValidationErrors('receipt');

        $this->post('/api/orders', ['plan' => 'pro', 'method' => 'instapay', 'receipt' => $this->receipt()])->assertCreated();
        $this->post('/api/orders', ['plan' => 'pro', 'method' => 'instapay', 'receipt' => $this->receipt()])
            ->assertUnprocessable()->assertJsonPath('errors.plan.0', fn ($m) => str_contains($m, 'لسه بيتراجع'));
    }

    public function test_users_only_see_their_own_orders(): void
    {
        $a = User::factory()->create();
        $ref = $this->actingAs($a)->post('/api/orders', ['plan' => 'pro', 'method' => 'instapay', 'receipt' => $this->receipt()])->json('order.id');

        $this->actingAs($a)->getJson("/api/orders/{$ref}")->assertOk();
        $this->actingAs(User::factory()->create())->getJson("/api/orders/{$ref}")->assertNotFound();
    }

    public function test_admin_approves_and_subscription_unlocks_program(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $ref = $this->actingAs($user)->post('/api/orders', ['plan' => 'challenge', 'method' => 'instapay', 'receipt' => $this->receipt()])->json('order.id');

        $this->actingAs($user)->postJson("/api/admin/orders/{$ref}/approve")->assertForbidden();

        $this->actingAs($admin)->getJson('/api/admin/orders')->assertOk()
            ->assertJsonPath('data.0.id', $ref)->assertJsonPath('data.0.user.phone', $user->phone);
        $this->actingAs($admin)->get("/api/admin/orders/{$ref}/receipt")->assertOk();

        $this->actingAs($admin)->postJson("/api/admin/orders/{$ref}/approve")->assertOk()->assertJsonPath('data.status', 'paid');
        $this->actingAs($admin)->postJson("/api/admin/orders/{$ref}/approve")->assertUnprocessable();

        $this->actingAs($user)->getJson('/api/me')
            ->assertJsonPath('subscription.plan', 'challenge')
            ->assertJsonPath('subscription.ends_at', null)
            ->assertJsonPath('unlocked', ['30']);
        $this->actingAs($user)->getJson('/api/programs/30/days/20')->assertOk();
    }

    public function test_monthly_plan_expires_and_renewals_stack(): void
    {
        $user = $this->subscriber('pro');
        $sub = Subscription::where('user_id', $user->id)->first();
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $sub->ends_at->timestamp, 5);

        // renewal while active starts where the current one ends
        $admin = User::factory()->admin()->create();
        $order = Order::create(['user_id' => $user->id, 'plan_id' => $sub->plan_id, 'amount' => 149, 'method' => 'instapay', 'status' => 'pending']);
        app(\App\Services\OrderService::class)->approve($order, $admin);
        $this->assertEqualsWithDelta(now()->addDays(60)->timestamp, Subscription::where('user_id', $user->id)->max('ends_at') ? \Illuminate\Support\Carbon::parse(Subscription::where('user_id', $user->id)->max('ends_at'))->timestamp : 0, 5);

        $this->travel(61)->days();
        $this->actingAs($user->fresh())->getJson('/api/me')->assertJsonPath('subscription', null)->assertJsonPath('unlocked', []);
        $this->actingAs($user->fresh())->getJson('/api/programs/10min/days/9')->assertForbidden();
    }

    public function test_reject_needs_a_note_and_is_shown_to_user(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $ref = $this->actingAs($user)->post('/api/orders', ['plan' => 'pro', 'method' => 'instapay', 'receipt' => $this->receipt()])->json('order.id');

        $this->actingAs($admin)->postJson("/api/admin/orders/{$ref}/reject")->assertUnprocessable()->assertJsonValidationErrors('note');
        $this->actingAs($admin)->postJson("/api/admin/orders/{$ref}/reject", ['note' => 'المبلغ ناقص'])->assertOk();

        $this->actingAs($user)->getJson("/api/orders/{$ref}")
            ->assertJsonPath('data.status', 'rejected')->assertJsonPath('data.note', 'المبلغ ناقص');
        // a rejected order doesn't block trying again
        $this->actingAs($user)->post('/api/orders', ['plan' => 'pro', 'method' => 'instapay', 'receipt' => $this->receipt()])->assertCreated();
    }
}
