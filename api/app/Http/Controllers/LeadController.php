<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'phone' => ['required', AuthController::PHONE],
            'goal' => ['nullable', Rule::in(['loss', 'cut', 'gain'])],
            'sex' => ['nullable', Rule::in(['m', 'f'])],
            'age' => ['nullable', 'integer', 'between:12,90'],
            'weight' => ['nullable', 'numeric', 'between:30,300'],
            'height' => ['nullable', 'integer', 'between:100,230'],
            'activity' => ['nullable', 'numeric', 'between:1,2'],
            'kcal' => ['nullable', 'integer', 'between:800,6000'],
            'protein' => ['nullable', 'integer', 'max:500'],
            'carbs' => ['nullable', 'integer', 'max:1000'],
            'fat' => ['nullable', 'integer', 'max:400'],
        ]);

        // TODO(whatsapp): queue the starter plan message to $lead->phone once a WhatsApp provider is chosen.
        $lead = Lead::create($data + [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'source' => 'calculator',
        ]);

        return response()->json(['id' => $lead->id], 201);
    }
}
