<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Prices, plan copy and payment account details — editable from the admin dashboard. */
class SettingsController extends Controller
{
    public function show(PaymentManager $payments): JsonResponse
    {
        return response()->json([
            'plans' => Plan::with('programs')->orderBy('sort')->get()->map(fn (Plan $p) => $this->plan($p)),
            'payment_methods' => $payments->adminList(),
            'coach' => ['open_to_free' => (bool) Setting::get('coach.open_to_free', false)],
        ]);
    }

    /** Who can chat with the coach / send check-ins: paid members only (default) or everyone. */
    public function updateCoach(Request $request): JsonResponse
    {
        $data = $request->validate(['open_to_free' => ['required', 'boolean']]);
        Setting::put('coach.open_to_free', $data['open_to_free'], $request->user()->id);

        return response()->json(['coach' => $data]);
    }

    public function updatePlan(Request $request, Plan $plan): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'price' => ['required', 'integer', 'min:0', 'max:100000'],
            'features' => ['required', 'array', 'min:1', 'max:8'],
            'features.*' => ['required', 'string', 'max:80'],
            'cta' => ['required', 'string', 'max:30'],
            'tag' => ['nullable', 'string', 'max:30'],
            'highlight' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        // the free plan is the entry point — keep it free and visible
        if ($plan->slug === 'free') {
            $data['price'] = 0;
            $data['is_active'] = true;
        } elseif ($data['price'] < 1) {
            throw ValidationException::withMessages(['price' => 'الباقة المدفوعة لازم سعرها يبقى أكبر من صفر.']);
        }

        $plan->update($data);

        return response()->json($this->plan($plan->fresh('programs')));
    }

    public function updatePayment(Request $request, PaymentManager $payments, string $method): JsonResponse
    {
        $all = $payments->all();
        abort_unless(isset($all[$method]), 404, 'طريقة الدفع دي مش موجودة.');

        $accountRule = $method === 'vodafone_cash'
            ? ['regex:/^01[0125]\d{8}$/']
            : ['string', 'max:100'];
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'account' => array_merge([Rule::requiredIf((bool) $request->boolean('enabled'))], ['nullable'], $accountRule),
            'hint' => ['nullable', 'string', 'max:120'],
        ], [
            'account.required' => 'اكتب '.($method === 'vodafone_cash' ? 'رقم فودافون كاش' : 'عنوان InstaPay').' قبل ما تفعّلها.',
            'account.regex' => 'رقم فودافون كاش لازم يكون 11 رقم يبدأ بـ 01.',
        ]);

        $othersOn = collect($all)->except($method)->contains(fn ($m) => $m['enabled']);
        if (! $data['enabled'] && ! $othersOn) {
            throw ValidationException::withMessages(['enabled' => 'لازم طريقة دفع واحدة على الأقل تفضل شغالة.']);
        }

        Setting::put("payments.{$method}", [
            'enabled' => $data['enabled'],
            'account' => $data['account'] ?? null,
            'hint' => ($data['hint'] ?? null) ?: config("payments.methods.{$method}.hint"),
        ], $request->user()->id);

        return response()->json(collect($payments->adminList())->firstWhere('id', $method));
    }

    private function plan(Plan $p): array
    {
        return [
            'id' => $p->slug,
            'name' => $p->name,
            'price' => $p->price,
            'period' => $p->period,
            'period_label' => $p->periodLabel(),
            'duration_days' => $p->duration_days,
            'features' => $p->features,
            'cta' => $p->cta,
            'tag' => $p->tag,
            'highlight' => $p->highlight,
            'is_active' => $p->is_active,
            'unlocks' => $p->programs->pluck('title')->values(),
        ];
    }
}
