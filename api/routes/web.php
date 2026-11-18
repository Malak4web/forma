<?php

use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    if (request()->is('api/*')) {
        return response()->json(['message' => 'Not found'], 404);
    }

    $spa = public_path('index.html');
    if (is_file($spa)) {
        return response()->file($spa);
    }

    return view('welcome');
});
