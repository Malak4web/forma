<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ContentTest extends TestCase
{
    public function test_content_payload(): void
    {
        $this->getJson('/api/content')->assertOk()
            ->assertJsonCount(18, 'exercises')
            ->assertJsonCount(4, 'programs')
            ->assertJsonCount(3, 'plans')
            ->assertJsonPath('programs.0.id', '30')
            ->assertJsonPath('programs.0.plan', 'challenge')
            ->assertJsonPath('programs.1.plan', 'pro')
            ->assertJsonPath('plans.1.unlocks', ['30'])
            ->assertJsonPath('payment_methods.0.id', 'vodafone_cash')
            ->assertJsonPath('payment_methods.1.id', 'instapay')
            ->assertJsonPath('free_days', 3);
    }

    public function test_disabled_payment_method_is_hidden(): void
    {
        config(['payments.methods.instapay.enabled' => false]);
        $this->getJson('/api/content')->assertJsonCount(1, 'payment_methods');
    }

    public function test_free_days_are_open_to_guests(): void
    {
        $this->getJson('/api/programs/30/days/1')->assertOk()
            ->assertJsonPath('n', 1)
            ->assertJsonPath('rounds', 3)
            ->assertJsonCount(3, 'items')
            ->assertJsonPath('items.0.id', 'squat')
            ->assertJsonPath('items.0.work', 30);
    }

    public function test_paid_days_are_locked_and_pitch_the_right_plan(): void
    {
        $this->getJson('/api/programs/30/days/4')->assertForbidden()
            ->assertJsonPath('locked', true)
            ->assertJsonPath('plan.id', 'challenge');

        $this->actingAs(User::factory()->create())
            ->getJson('/api/programs/nojump/days/5')->assertForbidden()->assertJsonPath('plan.id', 'pro');
    }

    public function test_subscriber_opens_unlocked_programs_only(): void
    {
        $user = $this->subscriber('challenge');

        $this->actingAs($user)->getJson('/api/programs/30/days/14')->assertOk()
            ->assertJsonPath('rest', true)->assertJsonPath('items.0.id', 'toetouch');
        $this->actingAs($user)->getJson('/api/programs/10min/days/9')->assertForbidden();
    }

    public function test_out_of_range_day_is_404(): void
    {
        $this->getJson('/api/programs/30/days/31')->assertNotFound();
        $this->getJson('/api/programs/nope/days/1')->assertNotFound();
    }
}
