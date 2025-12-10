<?php

use App\Http\Controllers\Api\AdScriptController;
use App\Http\Middleware\ThrottleAdScriptRequests;
use App\Http\Middleware\ValidateN8nHmacSignature;
use App\Http\Middleware\ValidateN8nSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('ad-scripts')->group(function () {
    // Public health check
    Route::get('health', [AdScriptController::class, 'health'])->name('api.ad-scripts.health');

    // Authenticated endpoints
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [AdScriptController::class, 'store'])
            ->middleware([ThrottleAdScriptRequests::class])
            ->name('api.ad-scripts.store');
        Route::get('/', [AdScriptController::class, 'index'])->name('api.ad-scripts.index');
        Route::get('{id}', [AdScriptController::class, 'show'])->name('api.ad-scripts.show');
    });

    // n8n webhook endpoint (uses HMAC signature validation with API key fallback)
    Route::post('{id}/result', [AdScriptController::class, 'updateResult'])
        ->middleware([ValidateN8nHmacSignature::class, ValidateN8nSignature::class])
        ->name('api.ad-scripts.result');
});
