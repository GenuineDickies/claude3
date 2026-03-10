<?php

use App\Http\Controllers\LocationShareController;
use App\Http\Controllers\Webhooks\TelnyxWebhookController;
use Illuminate\Support\Facades\Route;

// ── Public endpoints (rate-limited) ───────────────────────────
Route::middleware('throttle:10,1')->group(function () {
    // Receives GPS coordinates from customer's browser
    Route::post('/locate/{token}', [LocationShareController::class, 'store'])->name('locate.store');
});

// ── Webhooks (no auth, no CSRF, moderate throttle) ────────────
Route::middleware('throttle:120,1')->group(function () {
    Route::post('/webhooks/telnyx', TelnyxWebhookController::class);
    // Backwards-compatible route for subdirectory deployments
    Route::post('/webhook.php', TelnyxWebhookController::class);
});
