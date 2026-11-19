<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\User;
use App\Models\WeightLog;
use App\Models\WorkoutCompletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $type = $request->query('type', 'all');

        $now = now();
        $query = User::query()
            ->with(['activeSubscriptions.plan', 'weights' => fn ($wq) => $wq->latest('logged_on')->limit(1)])
            ->withCount(['completions', 'orders', 'followUps', 'weights'])
            ->when($q !== '', fn ($qu) => $qu->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->when($type === 'subscribed', fn ($qu) => $qu->whereHas('subscriptions', fn ($sq) => $sq
                ->where('starts_at', '<=', $now)
                ->where(fn ($s2) => $s2->whereNull('ends_at')->orWhere('ends_at', '>', $now))))
            ->when($type === 'free', fn ($qu) => $qu->whereDoesntHave('subscriptions', fn ($sq) => $sq
                ->where('starts_at', '<=', $now)
                ->where(fn ($s2) => $s2->whereNull('ends_at')->orWhere('ends_at', '>', $now))))
            ->when($type === 'admin', fn ($qu) => $qu->where('is_admin', true))
            ->latest();

        $page = $query->paginate(30);

        return response()->json([
            'data' => $page->getCollection()->map(fn (User $u) => $this->summary($u)),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'regex:/^01[0125]\d{8}$/', Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'goal' => ['nullable', Rule::in(['loss', 'cut', 'gain'])],
            'is_admin' => ['nullable', 'boolean'],
        ], [
            'phone.regex' => 'رقم الموبايل لازم يكون 11 رقم مصري يبدأ بـ 01.',
            'phone.unique' => 'الرقم ده متسجل قبل كده.',
        ]);

        $user = new User([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'goal' => $data['goal'] ?? null,
        ]);
        $user->is_admin = (bool) ($data['is_admin'] ?? false);
        $user->save();

        return response()->json(['data' => $this->summary($user->load('activeSubscriptions.plan', 'weights'))], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'subscriptions' => fn ($q) => $q->with('plan', 'order')->latest('starts_at'),
            'orders' => fn ($q) => $q->with('plan')->latest(),
            'weights' => fn ($q) => $q->orderBy('logged_on'),
            'completions' => fn ($q) => $q->with('program:id,slug,title,days')->orderBy('day'),
            'followUps' => fn ($q) => $q->with('admin:id,name')->latest('followup_date')->latest('id'),
            'leads' => fn ($q) => $q->latest(),
        ]);

        $programs = Program::orderBy('sort')->get(['id', 'slug', 'title', 'days']);
        $byProgram = $user->completions->groupBy('program_id');

        $progress = $programs->map(fn (Program $p) => [
            'program_id' => $p->id,
            'slug' => $p->slug,
            'title' => $p->title,
            'total_days' => $p->days,
            'completed_days' => ($byProgram->get($p->id) ?? collect())->pluck('day')->sort()->values(),
        ])->values();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'goal' => $user->goal,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at?->toIso8601String(),
                'subscriptions' => $user->subscriptions->map(fn ($s) => [
                    'id' => $s->id,
                    'plan_id' => $s->plan_id,
                    'plan_name' => $s->plan?->name,
                    'plan_slug' => $s->plan?->slug,
                    'order_ref' => $s->order?->ref,
                    'starts_at' => $s->starts_at?->toIso8601String(),
                    'ends_at' => $s->ends_at?->toIso8601String(),
                    'is_active' => $s->isActive(),
                    'notes' => $s->notes,
                ])->values(),
                'orders' => $user->orders->map(fn ($o) => [
                    'id' => $o->ref,
                    'plan_name' => $o->plan?->name,
                    'amount' => $o->amount,
                    'method' => $o->method,
                    'status' => $o->status,
                    'admin_note' => $o->admin_note,
                    'created_at' => $o->created_at?->toIso8601String(),
                ])->values(),
                'weights' => $user->weights->map(fn ($w) => [
                    'id' => $w->id,
                    'logged_on' => $w->logged_on?->format('Y-m-d'),
                    'kg' => $w->kg,
                ])->values(),
                'progress' => $progress,
                'followups' => $user->followUps->map(fn ($f) => [
                    'id' => $f->id,
                    'followup_date' => $f->followup_date?->format('Y-m-d'),
                    'next_followup_date' => $f->next_followup_date?->format('Y-m-d'),
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
                    'admin_name' => $f->admin?->name,
                ])->values(),
                'leads' => $user->leads->map(fn ($l) => [
                    'id' => $l->id,
                    'goal' => $l->goal,
                    'kcal' => $l->kcal,
                    'protein' => $l->protein,
                    'carbs' => $l->carbs,
                    'fat' => $l->fat,
                    'status' => $l->status,
                    'notes' => $l->notes,
                    'created_at' => $l->created_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'phone' => ['sometimes', 'required', 'regex:/^01[0125]\d{8}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'goal' => ['nullable', Rule::in(['loss', 'cut', 'gain'])],
            'is_admin' => ['sometimes', 'boolean'],
        ], [
            'phone.regex' => 'رقم الموبايل لازم يكون 11 رقم مصري يبدأ بـ 01.',
            'phone.unique' => 'الرقم ده متسجل قبل كده.',
        ]);

        if (array_key_exists('is_admin', $data) && ! $data['is_admin'] && $user->id === $request->user()->id) {
            throw ValidationException::withMessages(['is_admin' => 'مينفعش تشيل صلاحية الأدمن من نفسك.']);
        }

        $fillable = array_intersect_key($data, array_flip(['name', 'phone', 'email', 'goal']));
        if (! empty($data['password'])) {
            $fillable['password'] = $data['password'];
        }
        $user->fill($fillable);
        if (array_key_exists('is_admin', $data)) {
            $user->is_admin = (bool) $data['is_admin'];
        }
        $user->save();

        return response()->json(['data' => $this->summary($user->fresh(['activeSubscriptions.plan', 'weights']))]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['user' => 'مينفعش تحذف حسابك إنت.']);
        }

        $user->delete(); // soft delete only

        return response()->json(['deleted' => true]);
    }

    public function storeWeight(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'kg' => ['required', 'numeric', 'min:25', 'max:350'],
            'logged_on' => ['nullable', 'date'],
        ]);

        $date = $data['logged_on'] ?? now()->toDateString();
        $log = WeightLog::withTrashed()->updateOrCreate(
            ['user_id' => $user->id, 'logged_on' => $date],
            ['kg' => $data['kg'], 'deleted_at' => null]
        );

        return response()->json(['data' => [
            'id' => $log->id,
            'logged_on' => $log->logged_on?->format('Y-m-d'),
            'kg' => $log->kg,
        ]], 201);
    }

    public function destroyWeight(User $user, WeightLog $weight): JsonResponse
    {
        abort_unless($weight->user_id === $user->id, 404);
        $weight->delete(); // soft delete only

        return response()->json(['deleted' => true]);
    }

    public function toggleCompletion(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'day' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $existing = WorkoutCompletion::where('user_id', $user->id)
            ->where('program_id', $data['program_id'])
            ->where('day', $data['day'])
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['completed' => false]);
        }

        WorkoutCompletion::create([
            'user_id' => $user->id,
            'program_id' => $data['program_id'],
            'day' => $data['day'],
            'completed_at' => now(),
        ]);

        return response()->json(['completed' => true], 201);
    }

    private function summary(User $u): array
    {
        $activeSub = $u->activeSubscriptions->sortByDesc(fn ($s) => $s->ends_at?->timestamp ?? PHP_INT_MAX)->first();
        $latestWeight = $u->weights->first();

        return [
            'id' => $u->id,
            'name' => $u->name,
            'phone' => $u->phone,
            'email' => $u->email,
            'goal' => $u->goal,
            'is_admin' => $u->is_admin,
            'active_plan' => $activeSub?->plan?->name,
            'active_ends_at' => $activeSub?->ends_at?->toIso8601String(),
            'has_active_sub' => $activeSub !== null,
            'latest_weight' => $latestWeight?->kg,
            'completions_count' => $u->completions_count ?? 0,
            'orders_count' => $u->orders_count ?? 0,
            'followups_count' => $u->follow_ups_count ?? 0,
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}
