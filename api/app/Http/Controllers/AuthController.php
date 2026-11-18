<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public const PHONE = 'regex:/^01[0125]\d{8}$/';

    /** Touching any "web" route sets the XSRF-TOKEN cookie; the SPA calls this before its first write. */
    public function csrf(): Response
    {
        return response()->noContent();
    }

    public function register(Request $request, MeController $me): JsonResponse
    {
        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'phone' => ['required', self::PHONE, Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')],
            'password' => ['required', Password::min(8)],
            'goal' => ['nullable', Rule::in(['loss', 'cut', 'gain'])],
            'terms' => ['accepted'],
        ]);

        $user = User::create($data);
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return response()->json($me->state($user), 201);
    }

    public function login(Request $request, MeController $me): JsonResponse
    {
        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);
        $data = $request->validate([
            'phone' => ['required', self::PHONE],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.$data['phone'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'phone' => 'محاولات كتير. جرّب تاني بعد '.RateLimiter::availableIn($key).' ثانية.',
            ]);
        }
        if (! Auth::attempt($data, remember: true)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['phone' => 'الرقم أو الباسورد غلط.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return response()->json($me->state($request->user()));
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
