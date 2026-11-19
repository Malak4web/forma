<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AdminClaimTest extends TestCase
{
    private string $token = 'test-claim-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['forma.admin_claim_hash' => hash('sha256', $this->token)]);
    }

    public function test_requires_login(): void
    {
        $this->postJson('/api/admin/claim', ['token' => $this->token])->assertUnauthorized();
    }

    public function test_wrong_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/admin/claim', ['token' => 'nope'])
            ->assertUnprocessable()->assertJsonPath('errors.token.0', 'الكود ده مش صح.');
        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_first_claim_wins_then_locks_forever(): void
    {
        $first = User::factory()->create();
        $this->actingAs($first)->postJson('/api/admin/claim', ['token' => $this->token])
            ->assertOk()->assertJsonPath('user.is_admin', true);
        $this->assertTrue($first->fresh()->is_admin);
        $this->actingAs($first)->getJson('/api/admin/orders')->assertOk();

        $second = User::factory()->create();
        $this->actingAs($second)->postJson('/api/admin/claim', ['token' => $this->token])->assertStatus(410);
        $this->assertFalse($second->fresh()->is_admin);
    }

    public function test_disabled_when_no_hash(): void
    {
        config(['forma.admin_claim_hash' => '']);
        $this->actingAs(User::factory()->create())->postJson('/api/admin/claim', ['token' => $this->token])->assertStatus(410);
    }
}
