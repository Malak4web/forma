<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    public function test_complete_free_day_but_not_locked_day(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/progress/30/1')->assertOk()->assertJsonPath('completed.30', [1]);
        $this->actingAs($user)->postJson('/api/progress/30/1')->assertOk()->assertJsonPath('completed.30', [1]);
        $this->actingAs($user)->postJson('/api/progress/30/5')->assertForbidden();
    }

    public function test_import_guest_progress_keeps_only_allowed_days(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/progress/import', ['completed' => ['30' => [1, 2, 9], 'nope' => [1], 'back' => [3]]])
            ->assertOk()
            ->assertJsonPath('completed.30', [1, 2])
            ->assertJsonPath('completed.back', [3]);
    }

    public function test_weight_is_one_entry_per_day(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/weights', ['kg' => 85.44])->assertOk()->assertJsonPath('weights.0.kg', 85.4);
        $this->actingAs($user)->postJson('/api/weights', ['kg' => 84])->assertOk()->assertJsonCount(1, 'weights')->assertJsonPath('weights.0.kg', 84);
        $this->actingAs($user)->postJson('/api/weights', ['kg' => 900])->assertUnprocessable();
    }

    public function test_lead_from_calculator(): void
    {
        $this->postJson('/api/leads', [
            'name' => 'كريم', 'phone' => '01000000000', 'goal' => 'loss', 'sex' => 'm',
            'age' => 28, 'weight' => 85, 'height' => 175, 'activity' => 1.375, 'kcal' => 2000, 'protein' => 136, 'carbs' => 196, 'fat' => 60,
        ])->assertCreated();

        $this->assertDatabaseHas('leads', ['phone' => '01000000000', 'kcal' => 2000, 'source' => 'calculator']);
        $this->postJson('/api/leads', ['name' => 'x', 'phone' => '123'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'phone']);
        $this->assertSame(1, Lead::count());
    }
}
