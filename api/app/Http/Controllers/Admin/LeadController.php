<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'new');
        $q = trim((string) $request->query('q', ''));

        $page = Lead::with('user:id,name,phone')
            ->when($status && $status !== 'all', fn ($qu) => $qu->where('status', $status))
            ->when($q !== '', fn ($qu) => $qu->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")))
            ->latest()
            ->paginate(30);

        $counts = [
            'new' => Lead::where('status', 'new')->count(),
            'contacted' => Lead::where('status', 'contacted')->count(),
            'converted' => Lead::where('status', 'converted')->count(),
            'all' => Lead::count(),
        ];

        return response()->json([
            'data' => $page->getCollection()->map(fn (Lead $l) => $this->serialize($l)),
            'counts' => $counts,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function update(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(Lead::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($data['status']) && $data['status'] !== 'new' && $lead->contacted_at === null) {
            $data['contacted_at'] = now();
        }

        $lead->update($data);

        return response()->json(['data' => $this->serialize($lead->fresh('user:id,name,phone'))]);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete(); // soft delete only

        return response()->json(['deleted' => true]);
    }

    private function serialize(Lead $l): array
    {
        return [
            'id' => $l->id,
            'name' => $l->name,
            'phone' => $l->phone,
            'user_id' => $l->user_id,
            'goal' => $l->goal,
            'diet' => $l->diet,
            'sex' => $l->sex,
            'age' => $l->age,
            'weight' => $l->weight,
            'height' => $l->height,
            'activity' => $l->activity,
            'kcal' => $l->kcal,
            'protein' => $l->protein,
            'carbs' => $l->carbs,
            'fat' => $l->fat,
            'source' => $l->source,
            'status' => $l->status,
            'notes' => $l->notes,
            'contacted_at' => $l->contacted_at?->toIso8601String(),
            'created_at' => $l->created_at?->toIso8601String(),
        ];
    }
}
