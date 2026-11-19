<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** One-time promotion of the signed-in user to admin, using the bootstrap token (config/forma.php). */
class AdminClaimController extends Controller
{
    public function __invoke(Request $request, MeController $me): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:200']]);
        $hash = (string) config('forma.admin_claim_hash');

        if ($hash === '' || Setting::get('admin.claimed')) {
            abort(410, 'التفعيل ده اتستخدم قبل كده. لو محتاج أدمن جديد، أدمن موجود يقدر يضيفه.');
        }
        if (! hash_equals($hash, hash('sha256', $data['token']))) {
            throw ValidationException::withMessages(['token' => 'الكود ده مش صح.']);
        }

        $user = $request->user();
        DB::transaction(function () use ($user) {
            // re-check inside the transaction so two parallel claims can't both win
            if (Setting::query()->where('key', 'admin.claimed')->lockForUpdate()->exists()) {
                abort(410, 'التفعيل ده اتستخدم قبل كده.');
            }
            $user->forceFill(['is_admin' => true])->save();
            Setting::put('admin.claimed', ['user_id' => $user->id, 'at' => now()->toIso8601String()], $user->id);
        });

        return response()->json($me->state(User::find($user->id)));
    }
}
