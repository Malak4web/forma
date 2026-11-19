<?php

/*
| Loaded with the "web" middleware group under /api (see bootstrap/app.php):
| session auth + CSRF (X-XSRF-TOKEN header), no token package.
*/

use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TransformationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminClaimController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProgressController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('csrf', [AuthController::class, 'csrf']);

// public
Route::get('content', [ContentController::class, 'index']);
Route::get('media/transformations/{file}', function (string $file) {
    $path = 'transformations/'.basename($file);
    $disk = Storage::disk('public');

    abort_unless($disk->exists($path), 404);

    return response()->file($disk->path($path), [
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('file', '[A-Za-z0-9._-]+');
Route::get('programs/{program}/days/{day}', [ContentController::class, 'day'])->whereNumber('day');
Route::post('leads', [LeadController::class, 'store'])->middleware('throttle:10,1');
Route::get('me', [MeController::class, 'show']);

// auth
Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('admin/claim', AdminClaimController::class)->middleware('throttle:5,1');

    Route::post('weights', [MeController::class, 'storeWeight']);
    Route::post('progress/import', [ProgressController::class, 'import']);
    Route::post('progress/{program}/{day}', [ProgressController::class, 'complete'])->whereNumber('day');

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:6,1');

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/{order}/receipt', [AdminOrderController::class, 'receipt']);
        Route::post('orders/{order}/approve', [AdminOrderController::class, 'approve']);
        Route::post('orders/{order}/reject', [AdminOrderController::class, 'reject']);

        Route::get('settings', [SettingsController::class, 'show']);
        Route::put('plans/{plan}', [SettingsController::class, 'updatePlan']);
        Route::put('payment-methods/{method}', [SettingsController::class, 'updatePayment']);

        Route::get('transformations', [TransformationController::class, 'index']);
        Route::post('transformations', [TransformationController::class, 'store']);
        Route::put('transformations/{transformation}', [TransformationController::class, 'update']);
        Route::delete('transformations/{transformation}', [TransformationController::class, 'destroy']);
    });
});
