<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Program;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admins_are_blocked_from_all_new_admin_routes(): void
    {
        $user = User::factory()->create();

        foreach ([
            '/api/admin/dashboard',
            '/api/admin/exercises',
            '/api/admin/programs',
            '/api/admin/members',
            '/api/admin/subscriptions',
            '/api/admin/followups',
            '/api/admin/leads',
        ] as $url) {
            $this->actingAs($user)->getJson($url)->assertForbidden();
        }
    }

    public function test_dashboard_returns_live_kpis(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'kpis' => [
                    'members_total',
                    'subscriptions_active',
                    'orders_pending',
                    'revenue_total',
                    'leads_new',
                    'followups_due',
                    'exercises_total',
                    'programs_total',
                ],
                'pending_orders',
                'due_followups',
                'recent_leads',
            ]);
    }

    public function test_exercises_and_videos_full_crud_with_soft_delete(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $create = $this->actingAs($admin)->post('/api/admin/exercises', [
            'title' => 'Plank Hold',
            'hook' => 'شد البطن في 60 ثانية',
            'category' => 'belly',
            'seconds' => 45,
            'sets' => 4,
            'work_seconds' => 45,
            'rest_seconds' => 15,
            'reps' => '60 ثانية',
            'level' => 'متوسط',
            'muscles' => 'البطن + الكور',
            'equipment' => 'بدون أدوات',
            'calories_est' => 35,
            'tips_text' => "ضهرك مفرود\nشد بطنك",
            'description' => 'تمرين ممتاز لتقوية الكور.',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'thumb_file' => UploadedFile::fake()->image('plank.png', 400, 700),
            'is_home' => '1',
            'is_active' => '1',
        ])->assertCreated();

        $id = $create->json('data.id');
        $slug = $create->json('data.slug');
        $this->assertSame('plank-hold', $slug);
        $this->assertSame(['ضهرك مفرود', 'شد بطنك'], $create->json('data.tips'));
        $this->assertStringStartsWith('/api/media/exercises/', $create->json('data.thumb'));

        // Hide exercise → disappears from public /api/content
        $this->actingAs($admin)->putJson("/api/admin/exercises/{$id}", [
            'is_active' => false,
            'sets' => 5,
        ])->assertOk()->assertJsonPath('data.is_active', false)->assertJsonPath('data.sets', 5);

        $publicSlugs = collect($this->getJson('/api/content')->json('exercises'))->pluck('id');
        $this->assertFalse($publicSlugs->contains($slug));

        // Soft delete only
        $this->actingAs($admin)->deleteJson("/api/admin/exercises/{$id}")->assertOk();
        $this->assertSoftDeleted('exercises', ['id' => $id]);
    }

    public function test_programs_and_weeks_crud_with_plan_sync(): void
    {
        $admin = $this->admin();
        $pro = Plan::where('slug', 'pro')->firstOrFail();

        $create = $this->actingAs($admin)->postJson('/api/admin/programs', [
            'slug' => 'abs-14',
            'title' => 'تحدي البطن 14 يوم',
            'subtitle' => 'من البيت في 12 دقيقة',
            'pitch' => 'برنامج سريع لشد البطن.',
            'days' => 14,
            'minutes' => '12',
            'level' => 'مبتدئ',
            'plan_ids' => [$pro->id],
            'weeks' => [
                ['title' => 'الأسبوع الأول', 'pool' => ['crunches', 'climbers', 'twist']],
                ['title' => 'الأسبوع الثاني', 'pool' => ['climbers', 'twist', 'squat']],
            ],
        ])->assertCreated();

        $id = $create->json('data.id');
        $this->assertCount(2, $create->json('data.weeks'));
        $this->assertContains($pro->id, $create->json('data.plan_ids'));

        $this->actingAs($admin)->putJson("/api/admin/programs/{$id}", [
            'title' => 'تحدي البطن المطوّر',
            'weeks' => [
                ['title' => 'أسبوع واحد مكثّف', 'pool' => ['crunches', 'squat', 'bridge']],
            ],
        ])->assertOk()
            ->assertJsonPath('data.title', 'تحدي البطن المطوّر')
            ->assertJsonCount(1, 'data.weeks');

        $this->actingAs($admin)->deleteJson("/api/admin/programs/{$id}")->assertOk();
        $this->assertSoftDeleted('programs', ['id' => $id]);
    }

    public function test_members_management_dossier_weights_completions_and_guards(): void
    {
        $admin = $this->admin();

        $create = $this->actingAs($admin)->postJson('/api/admin/members', [
            'name' => 'أحمد سامي',
            'phone' => '01099887766',
            'password' => 'secret123',
            'goal' => 'loss',
        ])->assertCreated();
        $memberId = $create->json('data.id');

        // Weight log & completion toggle
        $w = $this->actingAs($admin)->postJson("/api/admin/members/{$memberId}/weights", [
            'kg' => 92.5,
            'logged_on' => '2026-11-19',
        ])->assertCreated();

        $program = Program::firstOrFail();
        $this->actingAs($admin)->postJson("/api/admin/members/{$memberId}/completions", [
            'program_id' => $program->id,
            'day' => 1,
        ])->assertCreated()->assertJsonPath('completed', true);

        // Full dossier
        $this->actingAs($admin)->getJson("/api/admin/members/{$memberId}")
            ->assertOk()
            ->assertJsonPath('data.name', 'أحمد سامي')
            ->assertJsonCount(1, 'data.weights');

        // Delete weight (soft delete)
        $this->actingAs($admin)->deleteJson("/api/admin/members/{$memberId}/weights/{$w->json('data.id')}")->assertOk();
        $this->assertSoftDeleted('weight_logs', ['id' => $w->json('data.id')]);

        // Admin cannot demote or delete themselves
        $this->actingAs($admin)->putJson("/api/admin/members/{$admin->id}", ['is_admin' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('is_admin');
        $this->actingAs($admin)->deleteJson("/api/admin/members/{$admin->id}")
            ->assertUnprocessable()->assertJsonValidationErrors('user');

        // Soft delete member
        $this->actingAs($admin)->deleteJson("/api/admin/members/{$memberId}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $memberId]);
    }

    public function test_subscriptions_manual_grant_extend_and_cancel(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $pro = Plan::where('slug', 'pro')->firstOrFail();

        $sub = $this->actingAs($admin)->postJson('/api/admin/subscriptions', [
            'user_id' => $member->id,
            'plan_id' => $pro->id,
            'notes' => 'تحويل واتساب',
        ])->assertCreated();

        $subId = $sub->json('data.id');
        $this->assertTrue($sub->json('data.is_active'));
        $this->assertFalse($sub->json('data.is_lifetime'));

        // Extend +30 days
        $this->actingAs($admin)->putJson("/api/admin/subscriptions/{$subId}", [
            'extend_days' => 30,
        ])->assertOk();

        // Convert to lifetime
        $this->actingAs($admin)->putJson("/api/admin/subscriptions/{$subId}", [
            'lifetime' => true,
        ])->assertOk()->assertJsonPath('data.is_lifetime', true);

        // Soft delete
        $this->actingAs($admin)->deleteJson("/api/admin/subscriptions/{$subId}")->assertOk();
        $this->assertSoftDeleted('subscriptions', ['id' => $subId]);
    }

    public function test_followups_with_fine_details_sync_weight_and_leads_crm(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();

        $fu = $this->actingAs($admin)->postJson('/api/admin/followups', [
            'user_id' => $member->id,
            'followup_date' => '2026-11-19',
            'next_followup_date' => '2026-11-26',
            'status' => 'needs_attention',
            'weight_kg' => 84.5,
            'waist_cm' => 91.0,
            'chest_cm' => 102.0,
            'arm_cm' => 36.5,
            'thigh_cm' => 58.0,
            'hips_cm' => 99.0,
            'body_fat' => 21.5,
            'diet_adherence' => 8,
            'workout_adherence' => 9,
            'sleep_hours' => 7.5,
            'water_liters' => 3.0,
            'energy_level' => 4,
            'member_feedback' => 'جعان شوية بالليل',
            'coach_notes' => 'التقدم ممتاز',
            'plan_adjustments' => 'زودنا بروتين في العشا',
            'sync_weight' => true,
        ])->assertCreated();

        $fuId = $fu->json('data.id');
        $this->assertEquals(84.5, $fu->json('data.weight_kg'));
        $this->assertEquals(91.0, $fu->json('data.waist_cm'));
        $this->assertDatabaseHas('weight_logs', ['user_id' => $member->id, 'kg' => 84.5]);

        $this->actingAs($admin)->putJson("/api/admin/followups/{$fuId}", [
            'status' => 'completed',
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $this->actingAs($admin)->deleteJson("/api/admin/followups/{$fuId}")->assertOk();
        $this->assertSoftDeleted('follow_ups', ['id' => $fuId]);

        // Lead CRM
        $lead = Lead::create([
            'name' => 'كريم طارق',
            'phone' => '01122334455',
            'goal' => 'loss',
            'kcal' => 2100,
            'status' => 'new',
        ]);

        $this->actingAs($admin)->putJson("/api/admin/leads/{$lead->id}", [
            'status' => 'contacted',
            'notes' => 'اتكلمنا على واتساب وهيشترك بكرة',
        ])->assertOk()
            ->assertJsonPath('data.status', 'contacted')
            ->assertJsonPath('data.notes', 'اتكلمنا على واتساب وهيشترك بكرة');

        $this->assertNotNull($lead->fresh()->contacted_at);

        $this->actingAs($admin)->deleteJson("/api/admin/leads/{$lead->id}")->assertOk();
        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }
}
