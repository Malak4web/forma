<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_guest_me_returns_null_user(): void
    {
        $this->getJson('/api/me')->assertOk()->assertExactJson(['user' => null]);
    }

    public function test_register_logs_in_and_returns_state(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'كريم', 'phone' => '010 1234 5678', 'password' => 'secret-123', 'goal' => 'loss', 'terms' => true,
        ])->assertCreated()
            ->assertJsonPath('user.name', 'كريم')
            ->assertJsonPath('user.phone', '01012345678')
            ->assertJsonPath('subscription', null)
            ->assertJsonMissingPath('user.password');

        $this->assertAuthenticated();
        $this->getJson('/api/me')->assertJsonPath('user.phone', '01012345678');
    }

    public function test_register_validates_in_arabic(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        $this->postJson('/api/auth/register', ['name' => 'ك', 'phone' => '01012345678', 'password' => 'short'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'password', 'terms'])
            ->assertJsonPath('errors.phone.0', 'الرقم ده عليه حساب بالفعل — ادخل بيه.');

        $this->postJson('/api/auth/register', ['name' => 'كريم', 'phone' => '0201234', 'password' => 'secret-123', 'terms' => true])
            ->assertJsonPath('errors.phone.0', 'رقم موبايل مصري صح (11 رقم يبدأ بـ 01).');
    }

    public function test_login_and_logout(): void
    {
        User::factory()->create(['phone' => '01111111111', 'password' => 'secret-123']);

        $this->postJson('/api/auth/login', ['phone' => '01111111111', 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonPath('errors.phone.0', 'الرقم أو الباسورد غلط.');
        $this->assertGuest();

        $this->postJson('/api/auth/login', ['phone' => '01111111111', 'password' => 'secret-123'])
            ->assertOk()->assertJsonPath('user.phone', '01111111111');
        $this->assertAuthenticated();

        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->assertGuest('web');
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['phone' => '01111111111']);
        foreach (range(1, 5) as $_) {
            $this->postJson('/api/auth/login', ['phone' => '01111111111', 'password' => 'nope']);
        }
        $this->postJson('/api/auth/login', ['phone' => '01111111111', 'password' => 'nope'])
            ->assertUnprocessable()->assertJsonPath('errors.phone.0', fn ($m) => str_contains($m, 'محاولات كتير'));
    }

    public function test_protected_routes_need_auth(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized()->assertJsonPath('message', 'لازم تسجّل دخول الأول');
        $this->postJson('/api/weights', ['kg' => 80])->assertUnauthorized();
    }
}
