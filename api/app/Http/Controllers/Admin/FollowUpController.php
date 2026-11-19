<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\WeightLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowUpController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'all');
        $due = $request->query('due');
        $userId = $request->query('user_id');
        $q = trim((string) $request->query('q', ''));
        $today = now()->toDateString();

        $page = FollowUp::with(['user:id,name,phone,goal', 'admin:id,name'])
            ->when($status && $status !== 'all', fn ($qu) => $qu->where('status', $status))
            ->when($due === '1', fn ($qu) => $qu
                ->whereNotNull('next_followup_date')
                ->whereDate('next_followup_date', '<=', $today)
                ->where('status', '!=', 'completed'))
            ->when($userId, fn ($qu) => $qu->where('user_id', $userId))
            ->when($q !== '', fn ($qu) => $qu->whereHas('user', fn ($uq) => $uq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")))
            ->latest('followup_date')
            ->latest('id')
            ->paginate(30);

        $counts = [
            'all' => FollowUp::count(),
            'due' => FollowUp::whereNotNull('next_followup_date')
                ->whereDate('next_followup_date', '<=', $today)
                ->where('status', '!=', 'completed')->count(),
            'needs_attention' => FollowUp::where('status', 'needs_attention')->count(),
            'delayed' => FollowUp::where('status', 'delayed')->count(),
            'on_track' => FollowUp::where('status', 'on_track')->count(),
            'completed' => FollowUp::where('status', 'completed')->count(),
        ];

        return response()->json([
            'data' => $page->getCollection()->map(fn (FollowUp $f) => $this->serialize($f)),
            'counts' => $counts,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request, creating: true);
        $data['admin_id'] = $request->user()->id;

        $followUp = FollowUp::create($data);

        if ($request->boolean('sync_weight') && ! empty($data['weight_kg'])) {
            WeightLog::withTrashed()->updateOrCreate(
                ['user_id' => $followUp->user_id, 'logged_on' => $data['followup_date']],
                ['kg' => $data['weight_kg'], 'deleted_at' => null]
            );
        }

        return response()->json([
            'data' => $this->serialize($followUp->load(['user:id,name,phone,goal', 'admin:id,name'])),
        ], 201);
    }

    public function update(Request $request, FollowUp $followUp): JsonResponse
    {
        $data = $this->validatePayload($request, creating: false);
        $followUp->update($data);

        if ($request->boolean('sync_weight') && ! empty($followUp->weight_kg)) {
            WeightLog::withTrashed()->updateOrCreate(
                ['user_id' => $followUp->user_id, 'logged_on' => $followUp->followup_date->format('Y-m-d')],
                ['kg' => $followUp->weight_kg, 'deleted_at' => null]
            );
        }

        return response()->json([
            'data' => $this->serialize($followUp->fresh(['user:id,name,phone,goal', 'admin:id,name'])),
        ]);
    }

    public function destroy(FollowUp $followUp): JsonResponse
    {
        $followUp->delete(); // soft delete only

        return response()->json(['deleted' => true]);
    }

    private function validatePayload(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'user_id' => [$req, 'integer', 'exists:users,id'],
            'followup_date' => [$req, 'date'],
            'next_followup_date' => ['nullable', 'date'],
            'status' => [$req, Rule::in(FollowUp::STATUSES)],
            'weight_kg' => ['nullable', 'numeric', 'min:25', 'max:350'],
            'waist_cm' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'chest_cm' => ['nullable', 'numeric', 'min:40', 'max:250'],
            'arm_cm' => ['nullable', 'numeric', 'min:15', 'max:100'],
            'thigh_cm' => ['nullable', 'numeric', 'min:25', 'max:150'],
            'hips_cm' => ['nullable', 'numeric', 'min:40', 'max:250'],
            'body_fat' => ['nullable', 'numeric', 'min:3', 'max:70'],
            'diet_adherence' => ['nullable', 'integer', 'min:1', 'max:10'],
            'workout_adherence' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sleep_hours' => ['nullable', 'numeric', 'min:1', 'max:20'],
            'water_liters' => ['nullable', 'numeric', 'min:0', 'max:15'],
            'energy_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'member_feedback' => ['nullable', 'string', 'max:3000'],
            'coach_notes' => ['nullable', 'string', 'max:3000'],
            'plan_adjustments' => ['nullable', 'string', 'max:3000'],
            'sync_weight' => ['nullable', 'boolean'],
        ]);
    }

    private function serialize(FollowUp $f): array
    {
        $isDue = $f->next_followup_date
            && $f->status !== 'completed'
            && $f->next_followup_date->lte(now()->startOfDay());

        return [
            'id' => $f->id,
            'user_id' => $f->user_id,
            'user' => $f->user ? [
                'id' => $f->user->id,
                'name' => $f->user->name,
                'phone' => $f->user->phone,
                'goal' => $f->user->goal,
            ] : null,
            'admin_name' => $f->admin?->name,
            'followup_date' => $f->followup_date?->format('Y-m-d'),
            'next_followup_date' => $f->next_followup_date?->format('Y-m-d'),
            'is_due' => $isDue,
            'status' => $f->status,
            'weight_kg' => $f->weight_kg,
            'waist_cm' => $f->waist_cm,
            'chest_cm' => $f->chest_cm,
            'arm_cm' => $f->arm_cm,
            'thigh_cm' => $f->thigh_cm,
            'hips_cm' => $f->hips_cm,
            'body_fat' => $f->body_fat,
            'diet_adherence' => $f->diet_adherence,
            'workout_adherence' => $f->workout_adherence,
            'sleep_hours' => $f->sleep_hours,
            'water_liters' => $f->water_liters,
            'energy_level' => $f->energy_level,
            'member_feedback' => $f->member_feedback,
            'coach_notes' => $f->coach_notes,
            'plan_adjustments' => $f->plan_adjustments,
            'created_at' => $f->created_at?->toIso8601String(),
        ];
    }
}
