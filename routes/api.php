<?php

use App\Exports\CollectionsExport;
use App\Exports\DelinquencyExport;
use App\Exports\InventoryExport;
use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CollectionApiController;
use App\Http\Controllers\Api\ComplianceApiController;
use App\Http\Controllers\Api\FinanceApiController;
use App\Http\Controllers\Api\FraudApiController;
use App\Http\Controllers\Api\KycApiController;
use App\Http\Controllers\Api\RecoveryApiController;
use App\Http\Controllers\Api\RefurbishmentApiController;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\SecurityApiController;
use App\Http\Controllers\Api\SelcomWebhookController;
use App\Http\Controllers\Api\StaffApiController;
use App\Http\Controllers\Api\StockApiController;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

// Version 1 API
Route::prefix('v1')->group(function () {

    // Auth Routes
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

    // Webhook (unauthenticated because it is server-to-server callback)
    Route::post('/collection/webhook', [CollectionApiController::class, 'webhook'])->middleware('throttle:webhooks');
    Route::post('/payments/selcom/webhook', SelcomWebhookController::class)
        ->middleware('throttle:webhooks')
        ->name('api.payments.selcom.webhook');

    // Protected Routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // KYC — FO Mobile App: dashboard, customers, branches
        Route::prefix('kyc')->middleware('permission:loans.view')->group(function () {
            Route::get('/branches', [KycApiController::class, 'branches']);
            Route::get('/dashboard', [KycApiController::class, 'dashboard']);
            Route::get('/customers', [KycApiController::class, 'myCustomers']);
            Route::get('/customers/{id}', [KycApiController::class, 'customerDetail']);
        });

        Route::prefix('kyc/customers')
            ->middleware('permission:loans.create')
            ->group(function () {
                Route::post('/{id}/release-asset', [KycApiController::class, 'releaseAsset']);
            });

        // KYC — Agent Registration Steps (requires loans.create permission)
        Route::prefix('kyc/application')
            ->middleware('permission:loans.create')
            ->group(function () {
                Route::get('/phone-countries', [KycApiController::class, 'phoneCountries']);
                Route::get('/device/brands', [KycApiController::class, 'deviceBrands']);
                Route::get('/device/models', [KycApiController::class, 'deviceModels']);
                Route::get('/device/inventory', [KycApiController::class, 'deviceInventory']);
                // Step 1: creates the draft customer, returns customer_id
                Route::post('/step1', [KycApiController::class, 'step1Device']);
                // Steps 2-7: enrich the draft using the customer_id from step 1
                Route::post('/{customer_id}/step2', [KycApiController::class, 'step2Identity']);
                Route::post('/{customer_id}/step3', [KycApiController::class, 'step3Contact']);
                Route::post('/{customer_id}/step4', [KycApiController::class, 'step4Income']);
                Route::post('/{customer_id}/step5', [KycApiController::class, 'step5Nok']);
                Route::post('/{customer_id}/step6', [KycApiController::class, 'step6Consent']);
                Route::post('/{customer_id}/payment/request', [KycApiController::class, 'paymentRequest']);
                Route::get('/{customer_id}/payment/status', [KycApiController::class, 'paymentStatus']);
                Route::post('/{customer_id}/step7', [KycApiController::class, 'step7Submit']);
                Route::get('/{customer_id}/status', [KycApiController::class, 'applicationStatus']);
            });

        // Stock Search (Vendor Shop Floor)
        Route::prefix('stock')->middleware('permission:devices.view')->group(function () {
            Route::get('/search', [StockApiController::class, 'search']);
            Route::get('/vendor-list', [StockApiController::class, 'vendorStock']);
        });

        // Staff / Sales Agent Tracking
        Route::prefix('staff')->middleware('permission:staff.view')->group(function () {
            Route::get('/metrics', [StaffApiController::class, 'metrics']);
            Route::get('/commissions', [StaffApiController::class, 'commissions']);
        });

        // HQ Admin Security & Risk Ops
        Route::prefix('security')->middleware('role:admin')->group(function () {
            Route::post('/mdm/lock/{unit}', [SecurityApiController::class, 'lockDevice']);
            Route::post('/mdm/unlock/{unit}', [SecurityApiController::class, 'unlockDevice']);
            Route::post('/reconcile/manual', [SecurityApiController::class, 'manualReconciliation']);
        });

        Route::prefix('inventory')->middleware('permission:devices.edit')->group(function () {
            Route::post('/refurbish/{unit}', [RefurbishmentApiController::class, 'refurbishDevice']);
        });

        // Field Recovery API
        Route::prefix('recovery')->middleware('permission:returned_devices.view')->group(function () {
            Route::get('/tickets', [RecoveryApiController::class, 'fieldTickets']);
        });

        // Finance & Early Settlement
        Route::prefix('finance')->middleware('permission:loans.view')->group(function () {
            Route::get('/settlement-quote/{loanId}', [FinanceApiController::class, 'settlementQuote']);
        });

        // Geo-Spatial Analytics & Profitability
        Route::prefix('analytics')->middleware('permission:reports.view')->group(function () {
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
        Route::prefix('fraud')->middleware('permission:loans.view')->group(function () {
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
