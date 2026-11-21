<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** The member's own day-to-day tracking: saved nutrition plan + daily log (water, meals eaten). */
class TrackingController extends Controller
{
    public function nutrition(Request $request, MeController $me): JsonResponse
    {
        $data = $request->validate([
            'input' => ['required', 'array'],
            'input.sex' => ['required', 'in:m,f'],
            'input.age' => ['required', 'integer', 'between:12,90'],
            'input.weight' => ['required', 'numeric', 'between:30,300'],
            'input.height' => ['required', 'numeric', 'between:120,230'],
            'input.act' => ['required', 'numeric', 'between:1,2'],
            'input.goal' => ['required', 'in:loss,cut,gain'],
            'input.diet' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/'],
            'kcal' => ['required', 'integer', 'between:800,6000'],
            'protein' => ['required', 'integer', 'between:0,600'],
            'carbs' => ['required', 'integer', 'between:0,1000'],
            'fat' => ['required', 'integer', 'between:0,400'],
        ]);
        $user = $request->user();
        $old = $user->nutrition;
        // a new diet restarts the 30-day plan from day 1; re-tuning the numbers keeps the current day
        $restart = ! $old || ($old['input']['diet'] ?? null) !== ($data['input']['diet'] ?? null);

        $user->forceFill([
            'nutrition' => $data,
            'nutrition_since' => $restart ? today() : ($user->nutrition_since ?? today()),
            'goal' => $user->goal ?: $data['input']['goal'],
        ])->save();

        return response()->json($me->state($user->fresh()));
    }

    public function log(Request $request, string $date, MeController $me): JsonResponse
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 404);
        $day = Carbon::parse($date)->startOfDay();
        abort_if($day->isAfter(today()) || $day->lt(today()->subDays(6)), 422, 'تقدر تعدّل آخر 7 أيام بس.');

        $data = $request->validate([
            'water' => ['sometimes', 'integer', 'min:0', 'max:'.DailyLog::MAX_WATER],
            'meals' => ['sometimes', 'array', 'max:8'],
            'meals.*' => ['integer', 'min:0', 'max:7', 'distinct'],
        ]);
        if (isset($data['meals'])) {
            $data['meals'] = array_map('intval', $data['meals']);
            sort($data['meals']);
        }

        $log = DailyLog::withTrashed()->firstOrNew(['user_id' => $request->user()->id, 'date' => $day->toDateString()]);
        $log->fill($data);
        $log->deleted_at = null;
        $log->save();

        return response()->json($me->state($request->user()));
    }
}
