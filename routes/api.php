<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KycApiController;
use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Api\CollectionApiController;
use App\Http\Controllers\Api\StaffApiController;
use App\Http\Controllers\Api\SecurityApiController;
use App\Http\Controllers\Api\RecoveryApiController;
use App\Http\Controllers\Api\FinanceApiController;
use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\RoleApiController;
use App\Exports\CollectionsExport;
use App\Exports\InventoryExport;
use App\Exports\DelinquencyExport;
use Maatwebsite\Excel\Facades\Excel;

// Version 1 API
Route::prefix('v1')->group(function () {
    
    // Auth Routes
    Route::post('/login', [AuthController::class, 'login']);
    
    // Webhook (unauthenticated because it is server-to-server callback)
    Route::post('/collection/webhook', [CollectionApiController::class, 'webhook']);

    // Protected Routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/logout', [AuthController::class, 'logout']);
        
        // KYC (Agent Mobile App)
        Route::prefix('kyc')->group(function () {
            Route::post('/{customer_id}/upload-nida', [KycApiController::class, 'uploadNida']);
            Route::post('/{customer_id}/upload-photo', [KycApiController::class, 'uploadPhoto']);
            Route::post('/{customer_id}/finalize', [KycApiController::class, 'finalizeVerification']);
        });

        // Stock Search (Vendor Shop Floor)
        Route::prefix('stock')->group(function () {
            Route::get('/search', [StockApiController::class, 'search']);
            Route::get('/vendor-list', [StockApiController::class, 'vendorStock']);
        });

        // Staff / Sales Agent Tracking
        Route::prefix('staff')->group(function () {
            Route::get('/metrics', [StaffApiController::class, 'metrics']);
            Route::get('/commissions', [StaffApiController::class, 'commissions']);
        });

        // HQ Admin Security & Risk Ops
        Route::prefix('security')->middleware('role:admin')->group(function () {
            Route::post('/mdm/lock/{unit}', [SecurityApiController::class, 'lockDevice']);
            Route::post('/mdm/unlock/{unit}', [SecurityApiController::class, 'unlockDevice']);
            Route::post('/reconcile/manual', [SecurityApiController::class, 'manualReconciliation']);
        });

        Route::prefix('inventory')->group(function () {
            Route::post('/refurbish/{unit}', [RefurbishmentApiController::class, 'refurbishDevice']);
        });

        // Field Recovery API
        Route::prefix('recovery')->group(function () {
            Route::get('/tickets', [RecoveryApiController::class, 'fieldTickets']);
        });

        // Finance & Early Settlement
        Route::prefix('finance')->group(function () {
            Route::get('/settlement-quote/{loanId}', [FinanceApiController::class, 'settlementQuote']);
        });

        // Geo-Spatial Analytics & Profitability
        Route::prefix('analytics')->group(function () {
            Route::get('/risk-map', [AnalyticsApiController::class, 'riskMap']);
            Route::get('/roi-analysis', [AnalyticsApiController::class, 'profitabilityAnalysis']);
        });

        // IFRS 9 Compliance & Reports (Excel Exports)
        Route::prefix('compliance')->middleware('role:admin|accountant|owner')->group(function () {
            Route::get('/reports', [ComplianceApiController::class, 'report']);
            
            Route::middleware('can:reports.export')->group(function () {
                Route::get('/export/collections', function () {
                    return Excel::download(new CollectionsExport, 'Opticedge_Collections_Report.xlsx');
                });
                Route::get('/export/inventory', function () {
                    return Excel::download(new InventoryExport, 'Opticedge_Inventory_Valuation.xlsx');
                });
                Route::get('/export/delinquency', function () {
                    return Excel::download(new DelinquencyExport, 'Opticedge_IFRS9_Defaults.xlsx');
                });
            });
        });

        // Fraud & KYC Intelligence
        Route::prefix('fraud')->group(function () {
            Route::get('/alerts/{customer}', [FraudApiController::class, 'scanApplication']);
        });

        // RBAC Access Control
        Route::prefix('access')->group(function () {
            Route::get('/me', [RoleApiController::class, 'currentAccess']);
            
            // Strictly protected role management ops
            Route::middleware('role:admin|owner')->group(function () {
                Route::get('/roles', [RoleApiController::class, 'index']);
                Route::post('/roles/{role}/sync', [RoleApiController::class, 'sync']);
            });
        });
    });
});
