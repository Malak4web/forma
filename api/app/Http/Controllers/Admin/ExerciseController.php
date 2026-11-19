<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExerciseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        $hasVideo = $request->query('has_video');
        $active = $request->query('is_active');

        $items = Exercise::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($sub) => $sub
                ->where('title', 'like', "%{$q}%")
                ->orWhere('hook', 'like', "%{$q}%")
                ->orWhere('slug', 'like', "%{$q}%")
                ->orWhere('muscles', 'like', "%{$q}%")))
            ->when($category && $category !== 'all', fn ($query) => $query->where('category', $category))
            ->when($hasVideo === '1', fn ($query) => $query->whereNotNull('video_url')->where('video_url', '!=', ''))
            ->when($hasVideo === '0', fn ($query) => $query->where(fn ($sub) => $sub->whereNull('video_url')->orWhere('video_url', '')))
            ->when($active === '1' || $active === '0', fn ($query) => $query->where('is_active', $active === '1'))
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map->toAdmin();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $slug = $data['slug'] ?? Str::slug($data['title']);
        if (! $slug) {
            $slug = 'ex-'.Str::lower(Str::random(6));
        }

        // Ensure unique slug if auto-generated
        if (empty($data['slug'])) {
            $base = $slug;
            $i = 2;
            while (Exercise::withTrashed()->where('slug', $slug)->exists()) {
                $slug = "{$base}-{$i}";
                $i++;
            }
        }

        $thumb = $request->hasFile('thumb_file')
            ? '/api/media/'.$this->storeThumbWebp($request->file('thumb_file'))
            : ($data['thumb'] ?? '/img/thumbs/squat.webp');

        $videoUrl = $request->hasFile('video_file')
            ? '/api/media/'.$this->storeVideoFile($request->file('video_file'))
            : ($data['video_url'] ?? null);

        $exercise = Exercise::create([
            'slug' => $slug,
            'title' => $data['title'],
            'hook' => $data['hook'],
            'category' => $data['category'],
            'seconds' => $data['seconds'],
            'sets' => $data['sets'] ?? 3,
            'work_seconds' => $data['work_seconds'] ?? 40,
            'rest_seconds' => $data['rest_seconds'] ?? 20,
            'reps' => $data['reps'] ?? null,
            'level' => $data['level'] ?? null,
            'muscles' => $data['muscles'] ?? null,
            'equipment' => $data['equipment'] ?? null,
            'calories_est' => $data['calories_est'] ?? null,
            'tips' => $this->normalizeTips($request),
            'description' => $data['description'] ?? null,
            'thumb' => $thumb,
            'video_url' => $videoUrl ?: null,
            'is_home' => $request->has('is_home') ? $request->boolean('is_home') : ! in_array($data['category'], ['food', 'body']),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'sort' => $data['sort'] ?? ((int) Exercise::max('sort') + 1),
        ]);

        return response()->json(['data' => $exercise->toAdmin()], 201);
    }

    public function update(Request $request, Exercise $exercise): JsonResponse
    {
        $data = $this->validatePayload($request, $exercise);

        $updates = [];
        foreach ([
            'slug', 'title', 'hook', 'category', 'seconds', 'sets',
            'work_seconds', 'rest_seconds', 'reps', 'level', 'muscles',
            'equipment', 'calories_est', 'description', 'sort',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if ($request->has('tips') || $request->has('tips_text')) {
            $updates['tips'] = $this->normalizeTips($request);
        }
        if ($request->has('is_home')) {
            $updates['is_home'] = $request->boolean('is_home');
        }
        if ($request->has('is_active')) {
            $updates['is_active'] = $request->boolean('is_active');
        }
        if ($request->hasFile('thumb_file')) {
            $updates['thumb'] = '/api/media/'.$this->storeThumbWebp($request->file('thumb_file'));
        } elseif (array_key_exists('thumb', $data) && $data['thumb'] !== null && $data['thumb'] !== '') {
            $updates['thumb'] = $data['thumb'];
        }
        if ($request->hasFile('video_file')) {
            $updates['video_url'] = '/api/media/'.$this->storeVideoFile($request->file('video_file'));
        } elseif (array_key_exists('video_url', $data)) {
            $updates['video_url'] = $data['video_url'] ?: null;
        }

        $exercise->update($updates);

        return response()->json(['data' => $exercise->fresh()->toAdmin()]);
    }

    public function destroy(Exercise $exercise): JsonResponse
    {
        $exercise->delete(); // soft delete only

        return response()->json(['deleted' => true]);
    }

    private function validatePayload(Request $request, ?Exercise $exercise = null): array
    {
        $creating = $exercise === null;
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', Rule::unique('exercises', 'slug')->ignore($exercise?->id)],
            'title' => [$req, 'string', 'min:2', 'max:120'],
            'hook' => [$req, 'string', 'min:2', 'max:200'],
            'category' => [$req, Rule::in(Exercise::CATEGORIES)],
            'seconds' => [$req, 'integer', 'min:1', 'max:3600'],
            'sets' => ['nullable', 'integer', 'min:1', 'max:20'],
            'work_seconds' => ['nullable', 'integer', 'min:5', 'max:600'],
            'rest_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
            'reps' => ['nullable', 'string', 'max:32'],
            'level' => ['nullable', 'string', 'max:32'],
            'muscles' => ['nullable', 'string', 'max:160'],
            'equipment' => ['nullable', 'string', 'max:80'],
            'calories_est' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'tips' => ['nullable', 'array', 'max:12'],
            'tips.*' => ['string', 'max:200'],
            'tips_text' => ['nullable', 'string', 'max:2500'],
            'description' => ['nullable', 'string', 'max:3000'],
            'thumb' => ['nullable', 'string', 'max:300'],
            'thumb_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,mov,m4v', 'max:102400'],
            'is_home' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], [
            'slug.regex' => 'الـ slug لازم يكون حروف إنجليزي صغيرة وأرقام وشرطة بس.',
            'slug.unique' => 'الـ slug ده مستخدم قبل كده.',
            'thumb_file.image' => 'ملف البوستر لازم يكون صورة.',
            'video_file.mimes' => 'الفيديو لازم يكون mp4 أو webm أو mov.',
            'video_file.max' => 'حجم الفيديو لازم يكون أقل من 100 ميجا.',
        ]);
    }

    private function normalizeTips(Request $request): ?array
    {
        if ($request->has('tips') && is_array($request->input('tips'))) {
            $list = array_values(array_filter(array_map('trim', $request->input('tips'))));

            return $list ?: null;
        }
        if ($request->has('tips_text')) {
            $lines = preg_split('/\r\n|\r|\n/', (string) $request->input('tips_text'));
            $list = array_values(array_filter(array_map('trim', $lines ?: [])));

            return $list ?: null;
        }

        return null;
    }

    private function storeThumbWebp(UploadedFile $file): string
    {
        $raw = @file_get_contents($file->getRealPath());
        $src = $raw !== false ? @imagecreatefromstring($raw) : false;

        if (! $src || ! function_exists('imagewebp')) {
            return $file->store('exercises', 'public');
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $targetW = min(720, $w);
        $targetH = max(1, (int) round($h * ($targetW / max(1, $w))));

        $dst = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $w, $h);

        ob_start();
        imagewebp($dst, null, 82);
        $webpBytes = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        if (! $webpBytes) {
            return $file->store('exercises', 'public');
        }

        $path = 'exercises/'.Str::ulid().'.webp';
        Storage::disk('public')->put($path, $webpBytes);

        return $path;
    }

    private function storeVideoFile(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $name = Str::ulid().'.'.$ext;

        return $file->storeAs('videos', $name, 'public');
    }
}
