<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function planPayload(array $over = []): array
    {
        return array_merge([
            'name' => 'تحدّي 30 يوم', 'price' => 249, 'features' => ['البرنامج كامل', 'مؤقّت'],
            'cta' => 'ابدأ', 'tag' => null, 'highlight' => false, 'is_active' => true,
        ], $over);
    }

    public function test_only_admins_reach_settings(): void
    {
        $this->getJson('/api/admin/settings')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/api/admin/settings')->assertForbidden();
        $this->actingAs($this->admin())->getJson('/api/admin/settings')->assertOk()
            ->assertJsonCount(3, 'plans')
            ->assertJsonPath('payment_methods.0.id', 'vodafone_cash')
            ->assertJsonPath('plans.1.unlocks', ['تحدّي 30 يوم']);
    }

    public function test_price_change_applies_to_new_orders_only(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $old = $this->actingAs($user)->post('/api/orders', ['plan' => 'challenge', 'method' => 'instapay', 'receipt' => $this->receipt()])->json('order.id');

        $this->actingAs($this->admin())->putJson('/api/admin/plans/challenge', $this->planPayload())
            ->assertOk()->assertJsonPath('price', 249)->assertJsonPath('features', ['البرنامج كامل', 'مؤقّت']);

        $this->getJson('/api/content')->assertJsonPath('plans.1.price', 249);
        $this->actingAs($user)->getJson("/api/orders/{$old}")->assertJsonPath('data.amount', 199);
    }

    public function test_plan_validation_and_free_plan_guard(): void
    {
        $this->actingAs($admin = $this->admin());

        $this->putJson('/api/admin/plans/pro', $this->planPayload(['price' => 0]))
            ->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->putJson('/api/admin/plans/pro', $this->planPayload(['features' => []]))
            ->assertUnprocessable()->assertJsonValidationErrors('features');

        $this->putJson('/api/admin/plans/free', $this->planPayload(['price' => 99, 'is_active' => false]))
            ->assertOk()->assertJsonPath('price', 0)->assertJsonPath('is_active', true);
    }

    public function test_hidden_plan_cannot_be_bought(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin())->putJson('/api/admin/plans/pro', $this->planPayload(['is_active' => false]))->assertOk();

        $this->getJson('/api/content')->assertJsonCount(2, 'plans');
        $this->actingAs(User::factory()->create())
            ->post('/api/orders', ['plan' => 'pro', 'method' => 'instapay', 'receipt' => $this->receipt()])
            ->assertUnprocessable()->assertJsonValidationErrors('plan');
    }

    public function test_payment_account_is_editable_and_public(): void
    {
        $this->actingAs($this->admin());

        $this->putJson('/api/admin/payment-methods/vodafone_cash', ['enabled' => true, 'account' => '0101'])
            ->assertUnprocessable()->assertJsonPath('errors.account.0', 'رقم فودافون كاش لازم يكون 11 رقم يبدأ بـ 01.');
        $this->putJson('/api/admin/payment-methods/vodafone_cash', ['enabled' => true, 'account' => '01155556666'])
            ->assertOk()->assertJsonPath('account', '01155556666');
        $this->putJson('/api/admin/payment-methods/instapay', ['enabled' => true, 'account' => 'forma@instapay', 'hint' => 'من أي بنك'])
            ->assertOk();

        $this->getJson('/api/content')
            ->assertJsonPath('payment_methods.0.account', '01155556666')
            ->assertJsonPath('payment_methods.1.hint', 'من أي بنك');
    }

    public function test_cannot_disable_every_payment_method(): void
    {
        $this->actingAs($this->admin());

        $this->putJson('/api/admin/payment-methods/instapay', ['enabled' => false])->assertOk();
        $this->getJson('/api/content')->assertJsonCount(1, 'payment_methods');

        $this->putJson('/api/admin/payment-methods/vodafone_cash', ['enabled' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('enabled');
        $this->putJson('/api/admin/payment-methods/instapay', ['enabled' => true, 'account' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors('account');
        $this->putJson('/api/admin/payment-methods/paypal', ['enabled' => true])->assertNotFound();
    }
}
