<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    public function index(): JsonResponse
    {
        $programs = Program::with(['weeks', 'plans'])
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (Program $p) => $this->serialize($p));

        $plans = Plan::orderBy('sort')->get()->map(fn (Plan $pl) => [
            'id' => $pl->id,
            'slug' => $pl->slug,
            'name' => $pl->name,
            'price' => $pl->price,
        ]);

        return response()->json([
            'data' => $programs,
            'plans' => $plans,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $slug = $data['slug'] ?? Str::slug($data['title']);
        if (! $slug) {
            $slug = 'prog-'.Str::lower(Str::random(6));
        }

        $thumb = $request->hasFile('thumb_file')
            ? '/api/media/'.$this->storeThumbWebp($request->file('thumb_file'))
            : ($data['thumb'] ?? '/img/thumbs/cover-karsh.webp');

        $program = DB::transaction(function () use ($request, $data, $slug, $thumb) {
            $program = Program::create([
                'slug' => $slug,
                'title' => $data['title'],
                'subtitle' => $data['subtitle'],
                'pitch' => $data['pitch'],
                'days' => $data['days'],
                'minutes' => $data['minutes'],
                'level' => $data['level'],
                'thumb' => $thumb,
                'tag' => $data['tag'] ?? null,
                'sort' => $data['sort'] ?? ((int) Program::max('sort') + 1),
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            ]);

            $this->syncWeeks($program, $data['weeks'] ?? []);
            if (array_key_exists('plan_ids', $data)) {
                $program->plans()->sync($data['plan_ids']);
            }

            return $program;
        });

        return response()->json(['data' => $this->serialize($program->load('weeks', 'plans'))], 201);
    }

    public function update(Request $request, Program $program): JsonResponse
    {
        $data = $this->validatePayload($request, $program);

        DB::transaction(function () use ($request, $program, $data) {
            $updates = [];
            foreach (['slug', 'title', 'subtitle', 'pitch', 'days', 'minutes', 'level', 'tag', 'sort'] as $f) {
                if (array_key_exists($f, $data)) {
                    $updates[$f] = $data[$f];
                }
            }
            if ($request->has('is_active')) {
                $updates['is_active'] = $request->boolean('is_active');
            }
            if ($request->hasFile('thumb_file')) {
                $updates['thumb'] = '/api/media/'.$this->storeThumbWebp($request->file('thumb_file'));
            } elseif (! empty($data['thumb'])) {
                $updates['thumb'] = $data['thumb'];
            }

            if ($updates) {
                $program->update($updates);
            }

            if (array_key_exists('weeks', $data)) {
                $this->syncWeeks($program, $data['weeks']);
            }
            if (array_key_exists('plan_ids', $data)) {
                $program->plans()->sync($data['plan_ids']);
            }
        });

        return response()->json(['data' => $this->serialize($program->fresh(['weeks', 'plans']))]);
    }

    public function destroy(Program $program): JsonResponse
    {
        $program->delete(); // soft delete only

        return response()->json(['deleted' => true]);
    }

    private function validatePayload(Request $request, ?Program $program = null): array
    {
        // Support JSON-encoded weeks/plan_ids when sent via multipart FormData
        if (is_string($request->input('weeks'))) {
            $decoded = json_decode($request->input('weeks'), true);
            if (is_array($decoded)) {
                $request->merge(['weeks' => $decoded]);
            }
        }
        if (is_string($request->input('plan_ids'))) {
            $decoded = json_decode($request->input('plan_ids'), true);
            if (is_array($decoded)) {
                $request->merge(['plan_ids' => $decoded]);
            }
        }

        $req = $program ? 'sometimes' : 'required';

        return $request->validate([
            'slug' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/', Rule::unique('programs', 'slug')->ignore($program?->id)],
            'title' => [$req, 'string', 'min:2', 'max:120'],
            'subtitle' => [$req, 'string', 'min:2', 'max:160'],
            'pitch' => [$req, 'string', 'min:4', 'max:1500'],
            'days' => [$req, 'integer', 'min:1', 'max:365'],
            'minutes' => [$req, 'string', 'max:16'],
            'level' => [$req, 'string', 'max:64'],
            'thumb' => ['nullable', 'string', 'max:300'],
            'thumb_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'tag' => ['nullable', 'string', 'max:40'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'plan_ids' => ['nullable', 'array'],
            'plan_ids.*' => ['integer', 'exists:plans,id'],
            'weeks' => [$program ? 'sometimes' : 'required', 'array', 'min:1', 'max:20'],
            'weeks.*.title' => ['required', 'string', 'max:100'],
            'weeks.*.pool' => ['required', 'array', 'min:1', 'max:30'],
            'weeks.*.pool.*' => ['required', 'string', 'max:80'],
        ]);
    }

    private function syncWeeks(Program $program, array $weeks): void
    {
        $program->weeks()->delete();
        foreach (array_values($weeks) as $idx => $w) {
            $program->weeks()->create([
                'number' => $idx + 1,
                'title' => $w['title'],
                'pool' => array_values($w['pool']),
            ]);
        }
    }

    private function serialize(Program $p): array
    {
        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'title' => $p->title,
            'subtitle' => $p->subtitle,
            'pitch' => $p->pitch,
            'days' => $p->days,
            'minutes' => $p->minutes,
            'level' => $p->level,
            'thumb' => $p->thumb,
            'tag' => $p->tag,
            'sort' => $p->sort,
            'is_active' => $p->is_active,
            'plan_ids' => $p->plans->pluck('id')->values(),
            'plan_names' => $p->plans->pluck('name')->values(),
            'weeks' => $p->weeks->map(fn ($w) => [
                'id' => $w->id,
                'number' => $w->number,
                'title' => $w->title,
                'pool' => $w->pool,
            ])->values(),
        ];
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
}
