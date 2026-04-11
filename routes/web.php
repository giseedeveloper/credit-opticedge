<?php

use App\Livewire\Access\RoleManager;
use App\Livewire\Accounting\AccountingWorkspace;
use App\Livewire\Audits\AuditLogDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Communications\AuditTrail;
use App\Livewire\Communications\SmsLogs;
use App\Livewire\Credit\Defaulters;
use App\Livewire\Credit\LendingPanel;
use App\Livewire\Credit\LoanCalculator;
use App\Livewire\Credit\PaymentSchedules;
use App\Livewire\ExecutiveDashboard;
use App\Livewire\Financials\DailyCollections;
use App\Livewire\Inventory\StockGrid;
use App\Livewire\Kyc\CustomerProfiles;
use App\Livewire\Kyc\PendingVerifications;
use App\Livewire\Kyc\VerificationWizard;
use App\Livewire\Partnership\CommissionLedger;
use App\Livewire\Partnership\VendorDirectory;
use App\Livewire\Settings\SystemHealthDashboard;
use App\Livewire\Staff\StaffManager;
use App\Livewire\Stock\BrandModelIndex;
use App\Livewire\Stock\ImeiSearch;
use App\Livewire\Stock\StockDashboard;
use App\Livewire\Stock\StockTransfers;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // V2 Dashboards - Executive UI
    Route::get('/dashboard', ExecutiveDashboard::class)->name('dashboard')->middleware('can:dashboard.view');
    Route::get('/access', RoleManager::class)->name('access')->middleware('can:access.view');

    // Advanced Matrix Components
    Route::get('/inventory', StockGrid::class)->name('inventory.grid')->middleware('can:devices.view');
    Route::get('/loans', LendingPanel::class)->name('credit.panel')->middleware('can:loans.view');
    Route::get('/kyc/wizard', VerificationWizard::class)->name('kyc.wizard')->middleware('can:loans.create');

    // Corporate Audits & Maintenance
    Route::get('/audits', AuditLogDashboard::class)->name('audits.logs')->middleware('can:reports.view');
    Route::get('/health', SystemHealthDashboard::class)->name('settings.health')->middleware('can:settings.view');

    // Stock Manager
    Route::get('/stock', StockDashboard::class)->name('stock.index')->middleware('can:devices.view');
    Route::get('/stock/brands', BrandModelIndex::class)->name('stock.brands')->middleware('can:products.view');
    Route::get('/stock/imei-search', ImeiSearch::class)->name('stock.imei')->middleware('can:devices.view');
    Route::get('/stock/transfers', StockTransfers::class)->name('stock.transfers')->middleware('can:devices.view');

    // KYC Vault Legacy
    Route::get('/kyc/pending', PendingVerifications::class)->name('kyc.pending')->middleware('can:loans.view');
    Route::get('/kyc/customers', CustomerProfiles::class)->name('kyc.customers')->middleware('can:loans.view');

    // Credit Control Legacy
    Route::get('/credit/defaulters', Defaulters::class)->name('credit.defaulters')->middleware('can:loans.view');
    Route::get('/credit/schedules', PaymentSchedules::class)->name('credit.schedules')->middleware('can:loans.view');
    Route::get('/credit/calculator', LoanCalculator::class)->name('credit.calculator')->middleware('can:calculator.view');

    // Partnership
    Route::get('/partners/vendors', VendorDirectory::class)->name('partners.vendors')->middleware('can:vendors.view');
    Route::get('/partners/commissions', CommissionLedger::class)->name('partners.commissions')->middleware('can:accounting.view');

    // Financials
    Route::get('/financials/collections', DailyCollections::class)->name('financials.collections')->middleware('can:accounting.view');
    Route::get('/financials/accounting', AccountingWorkspace::class)->name('financials.accounting')->middleware('can:accounting.view');

    // Communications
    Route::get('/comms/sms', SmsLogs::class)->name('comms.sms')->middleware('can:sms_campaign.view');
    Route::get('/comms/audit', AuditTrail::class)->name('comms.audit')->middleware('can:reports.view');

    // Staff Management
    Route::get('/staff', StaffManager::class)->name('staff.index')->middleware('can:staff.view');
});

require __DIR__.'/settings.php';
