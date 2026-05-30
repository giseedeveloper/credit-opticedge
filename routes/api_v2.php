<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FraudApiController;
use App\Http\Controllers\Api\KycApiController;
use App\Http\Controllers\Api\RecoveryApiController;
use App\Http\Controllers\Api\StaffApiController;
use App\Http\Controllers\Api\StockApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v2 — stable surface for new mobile/web clients
|--------------------------------------------------------------------------
| Breaking changes ship here only. v1 remains for legacy clients with
| Deprecation headers (see AddApiVersionHeaders middleware).
*/
Route::prefix('v2')->middleware(['api.version:2'])->group(function () {
    Route::get('/meta', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'version' => 2,
                'status' => 'stable',
                'documentation' => 'Prefer /api/v2 for new FO and portal builds.',
                'sunset_v1' => 'v1 receives Deprecation headers; migrate before major releases.',
            ],
        ]);
    });

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

    Route::middleware(['auth:sanctum', 'active.user', 'audit'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('staff')->middleware('permission:staff.view')->group(function () {
            Route::get('/metrics', [StaffApiController::class, 'metrics']);
            Route::get('/commissions', [StaffApiController::class, 'commissions']);
        });

        Route::prefix('stock')->middleware('permission:devices.view')->group(function () {
            Route::get('/search', [StockApiController::class, 'search']);
            Route::get('/vendor-list', [StockApiController::class, 'vendorStock']);
        });

        Route::prefix('recovery')->middleware('permission:returned_devices.view')->group(function () {
            Route::get('/tickets', [RecoveryApiController::class, 'fieldTickets']);
        });

        Route::prefix('fraud')->middleware('permission:loans.view')->group(function () {
            Route::get('/alerts/{customer}', [FraudApiController::class, 'scanApplication']);
        });

        Route::get('/kyc/dashboard', [KycApiController::class, 'dashboard'])
            ->middleware('permission:loans.view');
    });
});
