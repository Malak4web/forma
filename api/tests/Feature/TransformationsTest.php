<?php

namespace Tests\Feature;

use App\Models\Transformation;
use App\Models\User;
use Database\Seeders\TransformationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransformationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_public_content_returns_only_active_ordered_transformations(): void
    {
        Transformation::create(['image_path' => 'transformations/b.webp', 'sort' => 2, 'is_active' => true, 'title' => 'تاني']);
        Transformation::create(['image_path' => 'transformations/a.webp', 'sort' => 1, 'is_active' => true, 'title' => 'أول']);
        Transformation::create(['image_path' => 'transformations/hidden.webp', 'sort' => 3, 'is_active' => false]);
        $deleted = Transformation::create(['image_path' => 'transformations/del.webp', 'sort' => 4, 'is_active' => true]);
        $deleted->delete();

        $res = $this->getJson('/api/content')->assertOk();

        $res->assertJsonCount(2, 'transformations');
        $res->assertJsonPath('transformations.0.title', 'أول');
        $res->assertJsonPath('transformations.0.image_url', '/api/media/transformations/a.webp');
        $res->assertJsonPath('transformations.1.title', 'تاني');
    }

    public function test_non_admin_cannot_manage_transformations(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson('/api/admin/transformations')
            ->assertForbidden();
    }

    public function test_admin_can_crud_transformations_with_soft_delete(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        // Create
        $file = UploadedFile::fake()->image('before-after.png', 400, 500);
        $create = $this->actingAs($admin)->post('/api/admin/transformations', [
            'image' => $file,
            'title' => 'كريم محمود',
            'age' => 28,
            'subtitle' => '-10 كيلو',
            'is_active' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.title', 'كريم محمود')
            ->assertJsonPath('data.age', 28)
            ->assertJsonPath('data.subtitle', '-10 كيلو');

        $id = $create->json('data.id');
        $created = Transformation::findOrFail($id);
        Storage::disk('public')->assertExists($created->image_path);

        // Update (with new image via _method=PUT)
        $newFile = UploadedFile::fake()->image('updated.jpg', 400, 500);
        $update = $this->actingAs($admin)->post("/api/admin/transformations/{$id}", [
            '_method' => 'PUT',
            'title' => 'كريم — 12 أسبوع',
            'age' => 29,
            'sort' => 5,
            'is_active' => false,
            'image' => $newFile,
        ]);

        $update->assertOk()
            ->assertJsonPath('data.title', 'كريم — 12 أسبوع')
            ->assertJsonPath('data.age', 29)
            ->assertJsonPath('data.sort', 5)
            ->assertJsonPath('data.is_active', false);

        // Soft delete
        $this->actingAs($admin)
            ->deleteJson("/api/admin/transformations/{$id}")
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertSoftDeleted('transformations', ['id' => $id]);
    }

    public function test_seeder_uploads_all_25_transformations_idempotently(): void
    {
        Storage::fake('public');

        $this->seed(TransformationSeeder::class);
        $this->seed(TransformationSeeder::class);

        $this->assertSame(25, Transformation::count());
        Storage::disk('public')->assertExists('transformations/forma-before-after-01.webp');
        Storage::disk('public')->assertExists('transformations/forma-before-after-25.webp');

        $this->get('/api/media/transformations/forma-before-after-01.webp')->assertOk();
    }
}
