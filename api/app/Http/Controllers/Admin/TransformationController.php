<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransformationController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Transformation::orderBy('sort')->orderBy('id')->get()->map->toPublic();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'title' => ['nullable', 'string', 'max:80'],
            'age' => ['nullable', 'integer', 'min:10', 'max:99'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'image.required' => 'اختار صورة التحول الأول.',
            'image.image' => 'الملف لازم يكون صورة.',
            'image.max' => 'حجم الصورة لازم يكون أقل من 10 ميجا.',
        ]);

        $path = $this->storeAsWebp($request->file('image'));
        $nextSort = $data['sort'] ?? ((int) Transformation::max('sort') + 1);

        $item = Transformation::create([
            'title' => $data['title'] ?? null,
            'age' => $data['age'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'image_path' => $path,
            'sort' => $nextSort,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return response()->json(['data' => $item->toPublic()], 201);
    }

    public function update(Request $request, Transformation $transformation): JsonResponse
    {
        $data = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'title' => ['nullable', 'string', 'max:80'],
            'age' => ['nullable', 'integer', 'min:10', 'max:99'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updates = [
            'title' => array_key_exists('title', $data) ? $data['title'] : $transformation->title,
            'age' => array_key_exists('age', $data) ? $data['age'] : $transformation->age,
            'subtitle' => array_key_exists('subtitle', $data) ? $data['subtitle'] : $transformation->subtitle,
            'sort' => $data['sort'] ?? $transformation->sort,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $transformation->is_active,
        ];

        if ($request->hasFile('image')) {
            $updates['image_path'] = $this->storeAsWebp($request->file('image'));
        }

        $transformation->update($updates);

        return response()->json(['data' => $transformation->fresh()->toPublic()]);
    }

    public function destroy(Transformation $transformation): JsonResponse
    {
        $transformation->delete(); // soft delete only

        return response()->json(['deleted' => true]);
    }

    /** Convert uploaded image to a 720px-max WebP on the public disk, falling back to raw store if GD fails. */
    private function storeAsWebp(UploadedFile $file): string
    {
        $raw = @file_get_contents($file->getRealPath());
        $src = $raw !== false ? @imagecreatefromstring($raw) : false;

        if (! $src || ! function_exists('imagewebp')) {
            return $file->store('transformations', 'public');
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
            return $file->store('transformations', 'public');
        }

        $path = 'transformations/'.Str::ulid().'.webp';
        Storage::disk('public')->put($path, $webpBytes);

        return $path;
    }
}
