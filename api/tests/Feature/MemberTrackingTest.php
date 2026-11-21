<?php

namespace Tests\Feature;

use App\Models\FollowUp;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Tests\TestCase;

class MemberTrackingTest extends TestCase
{
    private function nutrition(array $over = []): array
    {
        return array_replace_recursive([
            'input' => ['sex' => 'm', 'age' => 30, 'weight' => 90, 'height' => 178, 'act' => 1.375, 'goal' => 'loss', 'diet' => 'high-protein'],
            'kcal' => 2100, 'protein' => 210, 'carbs' => 184, 'fat' => 58,
        ], $over);
    }

    private function paid(): User
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id, 'plan_id' => Plan::where('slug', 'pro')->value('id'),
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29),
        ]);

        return $user;
    }

    public function test_nutrition_is_saved_on_the_account_and_a_new_diet_restarts_the_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/api/me/nutrition', $this->nutrition())->assertOk()
            ->assertJsonPath('nutrition.kcal', 2100)
            ->assertJsonPath('nutrition.input.diet', 'high-protein')
            ->assertJsonPath('nutrition.since', today()->toDateString());

        $user->forceFill(['nutrition_since' => today()->subDays(5)])->save();
        // same diet, new numbers → keeps the day
        $this->putJson('/api/me/nutrition', $this->nutrition(['kcal' => 2000]))->assertJsonPath('nutrition.since', today()->subDays(5)->toDateString());
        // different diet → day 1 again
        $this->putJson('/api/me/nutrition', $this->nutrition(['input' => ['diet' => 'keto']]))->assertJsonPath('nutrition.since', today()->toDateString());

        $this->putJson('/api/me/nutrition', $this->nutrition(['input' => ['diet' => '../x']]))->assertUnprocessable();
    }

    public function test_daily_log_tracks_water_and_meals_for_the_last_week_only(): void
    {
        $user = User::factory()->create();
        $today = today()->toDateString();

        $this->actingAs($user)->putJson("/api/logs/{$today}", ['water' => 3])->assertOk()
            ->assertJsonPath('logs.0.water', 3);
        $this->putJson("/api/logs/{$today}", ['meals' => [2, 0]])->assertOk()
            ->assertJsonPath('logs.0.meals', [0, 2])
            ->assertJsonPath('logs.0.water', 3);

        $this->putJson('/api/logs/'.today()->subDays(10)->toDateString(), ['water' => 1])->assertUnprocessable();
        $this->putJson('/api/logs/'.today()->addDay()->toDateString(), ['water' => 1])->assertUnprocessable();
        $this->putJson("/api/logs/{$today}", ['water' => 99])->assertUnprocessable();
        $this->assertDatabaseCount('daily_logs', 1);
    }

    public function test_free_members_cannot_reach_the_coach_unless_admin_opens_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/coach')->assertOk()->assertJsonPath('access', false);
        $this->postJson('/api/coach/messages', ['body' => 'ازيك'])->assertForbidden();
        $this->getJson('/api/me')->assertJsonPath('coach.access', false);

        Setting::put('coach.open_to_free', true);
        $this->getJson('/api/coach')->assertJsonPath('access', true);
        $this->postJson('/api/coach/messages', ['body' => 'ازيك'])->assertCreated();
    }

    public function test_paid_member_chats_with_coach_and_sees_replies(): void
    {
        $member = $this->paid();
        $admin = User::factory()->admin()->create(['name' => 'الكوتش']);

        $this->actingAs($member)->postJson('/api/coach/messages', ['body' => 'ينفع أبدّل الرز ببطاطس؟'])->assertCreated()
            ->assertJsonPath('messages.0.from', 'member');

        $this->actingAs($admin)->getJson('/api/admin/inbox')->assertOk()
            ->assertJsonPath('data.0.id', $member->id)
            ->assertJsonPath('data.0.unread', 1);
        $this->getJson("/api/admin/inbox/{$member->id}")->assertOk()->assertJsonCount(1, 'messages');
        $this->getJson('/api/admin/inbox')->assertJsonPath('data.0.unread', 0);
        $this->postJson("/api/admin/inbox/{$member->id}", ['body' => 'أيوه، نفس الكمية تقريبًا'])->assertCreated();

        $this->actingAs($member)->getJson('/api/me')->assertJsonPath('coach.unread', 1)->assertJsonPath('coach.last.from', 'coach');
        $this->getJson('/api/coach')->assertJsonCount(2, 'messages');
        $this->getJson('/api/me')->assertJsonPath('coach.unread', 0);

        // members can't use the coach inbox
        $this->getJson('/api/admin/inbox')->assertForbidden();
    }

    public function test_member_checkin_logs_weight_and_coach_review_reaches_the_member(): void
    {
        $member = $this->paid();
        $admin = User::factory()->admin()->create();

        $id = $this->actingAs($member)->postJson('/api/coach/checkins', [
            'weight_kg' => 88.4, 'waist_cm' => 96, 'diet_adherence' => 8, 'workout_adherence' => 6, 'member_feedback' => 'الأسبوع كان تقيل',
        ])->assertCreated()->assertJsonPath('checkin.reviewed', false)->json('checkin.id');

        $this->assertDatabaseHas('weight_logs', ['user_id' => $member->id, 'kg' => 88.4]);
        $this->assertSame('member', FollowUp::find($id)->source);

        $this->actingAs($admin)->getJson('/api/admin/inbox')->assertJsonPath('counts.pending_checkins', 1);
        $this->putJson("/api/admin/inbox/{$member->id}/checkins/{$id}", [
            'coach_notes' => 'شغل حلو', 'plan_adjustments' => 'زوّد المية لـ 3 لتر', 'status' => 'on_track',
            'next_followup_date' => today()->addWeek()->toDateString(),
        ])->assertOk()->assertJsonPath('checkin.reviewed', true);
        $this->getJson('/api/admin/inbox')->assertJsonPath('counts.pending_checkins', 0);

        $this->actingAs($member)->getJson('/api/me')
            ->assertJsonPath('coach.notes.plan_adjustments', 'زوّد المية لـ 3 لتر')
            ->assertJsonPath('coach.next_checkin', today()->addWeek()->toDateString());
    }

    public function test_admin_toggles_coach_access_for_free_members(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->putJson('/api/admin/coach-settings', ['open_to_free' => true])->assertOk();
        $this->getJson('/api/admin/settings')->assertJsonPath('coach.open_to_free', true);
        $this->actingAs(User::factory()->create())->putJson('/api/admin/coach-settings', ['open_to_free' => false])->assertForbidden();
    }
}
