<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExecutiveDashboard extends Component
{
    public float $portfolioValue = 0;

    public float $collectionEfficiency = 0;

    public float $parPercentage = 0;

    public int $activeDevices = 0;

    public int $totalActiveLoans = 0;

    public int $totalCustomers = 0;

    public int $newLoansThisMonth = 0;

    public float $todayCollections = 0;

    public float $monthCollections = 0;

    public int $overdueCount = 0;

    public int $pendingKycCount = 0;

    public int $availableStockCount = 0;

    public array $weeklyCollections = [];

    public array $weeklyDisbursements = [];

    public array $weeklyLabels = [];

    public array $inventoryDistribution = [];

    public array $riskMeterData = [];

    public array $liveSalesFeed = [];

    public array $todayDuePayments = [];

    public array $recentTransactions = [];

    public array $branchStats = [];

    public array $overdueLoansList = [];

    public bool $readyToLoad = false;

    public function loadData(): void
    {
        $analytics = app(AnalyticsService::class);

        $this->portfolioValue = Cache::remember('dash.portfolio_value', 300, fn () => (float) Loan::whereIn('status', ['active', 'defaulted', 'overdue'])->sum('remaining_balance')
        );

        $this->collectionEfficiency = Cache::remember('dash.collection_efficiency', 300, function () use ($analytics) {
            $e = $analytics->collectionEfficiency(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

            return $e['efficiency_percent'];
        });

        $this->parPercentage = Cache::remember('dash.par', 300, function () use ($analytics) {
            $par = $analytics->portfolioAtRisk();

            return $par['par_30'];
        });

        $this->activeDevices = Cache::remember('dash.active_devices', 300, fn () => InventoryUnit::whereIn('status', ['sold', 'active'])->count());
        $this->totalActiveLoans = Cache::remember('dash.active_loans', 300, fn () => Loan::where('status', 'active')->count());
        $this->totalCustomers = Cache::remember('dash.total_customers', 300, fn () => Customer::count());

        $this->newLoansThisMonth = Cache::remember('dash.new_loans_month', 300, fn () => Loan::whereMonth('disbursed_at', now()->month)->whereYear('disbursed_at', now()->year)->count()
        );

        $this->todayCollections = (float) Transaction::where('type', 'repayment')
            ->whereDate('transacted_at', today())
            ->sum('amount');

        $this->monthCollections = (float) Transaction::where('type', 'repayment')
            ->whereMonth('transacted_at', now()->month)
            ->whereYear('transacted_at', now()->year)
            ->sum('amount');

        $this->overdueCount = Cache::remember('dash.overdue_count', 120, fn () => Loan::whereIn('status', ['overdue', 'defaulted'])->count()
        );

        $this->pendingKycCount = Cache::remember('dash.pending_kyc', 120, fn () => Customer::query()->kycNotApproved()->count()
        );

        $this->availableStockCount = Cache::remember('dash.available_stock', 120, fn () => InventoryUnit::whereIn('status', ['available', 'hq_stock'])->count()
        );

        // Top overdue loans for alert panel
        $this->overdueLoansList = Loan::with(['customer', 'inventoryUnit.phoneModel'])
            ->whereIn('status', ['overdue', 'defaulted'])
            ->orderByDesc('outstanding_balance')
            ->take(5)
            ->get()
            ->map(fn ($l) => [
                'loan_number' => $l->loan_number,
                'status' => $l->status,
                'outstanding_balance' => (float) $l->outstanding_balance,
                'customer_name' => trim(($l->customer?->first_name ?? '').' '.($l->customer?->last_name ?? '')),
                'phone' => $l->customer?->phone ?? '—',
                'device' => ($l->inventoryUnit?->phoneModel?->name ?? 'Device'),
                'days_overdue' => $l->disbursed_at
                    ? (int) now()->diffInDays($l->disbursed_at->addDays((int) $l->loan_term_weeks * 7))
                    : 0,
            ])->toArray();

        // Repayment schedules due today or overdue (next 10)
        $this->todayDuePayments = RepaymentSchedule::with(['loan.customer'])
            ->whereIn('status', ['pending', 'overdue'])
            ->whereDate('due_date', '<=', today())
            ->orderBy('due_date')
            ->take(8)
            ->get()
            ->map(fn ($s) => [
                'loan_number' => $s->loan?->loan_number ?? '—',
                'customer_name' => trim(($s->loan?->customer?->first_name ?? '').' '.($s->loan?->customer?->last_name ?? '')),
                'phone' => $s->loan?->customer?->phone ?? '—',
                'amount_due' => (float) $s->amount_due,
                'amount_paid' => (float) $s->amount_paid,
                'balance' => (float) ($s->amount_due - $s->amount_paid),
                'due_date' => $s->due_date?->format('d M Y') ?? '—',
                'status' => $s->status,
            ])->toArray();

        // Recent repayment transactions (last 7 days)
        $this->recentTransactions = Transaction::with(['loan.customer'])
            ->where('type', 'repayment')
            ->where('transacted_at', '>=', now()->subDays(7))
            ->latest('transacted_at')
            ->take(6)
            ->get()
            ->map(fn ($t) => [
                'reference' => $t->reference ?? '—',
                'loan_number' => $t->loan?->loan_number ?? '—',
                'customer_name' => trim(($t->loan?->customer?->first_name ?? '').' '.($t->loan?->customer?->last_name ?? '')),
                'amount' => (float) $t->amount,
                'channel' => $t->channel ?? 'cash',
                'transacted_at' => $t->transacted_at?->diffForHumans() ?? '—',
            ])->toArray();

        // Branch performance
        $this->branchStats = Branch::withCount(['loans as active_loans' => fn ($q) => $q->where('status', 'active')])
            ->get()
            ->map(fn ($b) => [
                'name' => $b->name,
                'active_loans' => $b->active_loans,
                'collections' => (float) Transaction::whereHas('loan', fn ($q) => $q->where('branch_id', $b->id))
                    ->where('type', 'repayment')
                    ->whereMonth('transacted_at', now()->month)
                    ->sum('amount'),
            ])
            ->sortByDesc('active_loans')
            ->values()
            ->toArray();

        $this->generateLiveSalesFeed();
        $this->loadChartData();

        $this->readyToLoad = true;

        $this->dispatch('charts-loaded',
            collections: $this->weeklyCollections,
            disbursements: $this->weeklyDisbursements,
            labels: $this->weeklyLabels,
            risk: $this->riskMeterData[0] ?? 0,
            inventory: $this->inventoryDistribution,
        );
    }

    private function generateLiveSalesFeed(): void
    {
        $this->liveSalesFeed = Loan::with(['customer', 'inventoryUnit.phoneModel.brand'])
            ->latest()
            ->take(8)
            ->get()
            ->map(fn ($loan) => [
                'loan_number' => $loan->loan_number,
                'principal_amount' => (float) $loan->principal_amount,
                'status' => $loan->status,
                'created_at' => $loan->created_at?->toISOString(),
                'customer' => [
                    'first_name' => $loan->customer?->first_name,
                    'last_name' => $loan->customer?->last_name,
                ],
                'device' => [
                    'brand' => $loan->inventoryUnit?->phoneModel?->brand?->name ?? 'OEM',
                    'model' => $loan->inventoryUnit?->phoneModel?->name ?? 'Device',
                ],
            ])
            ->toArray();
    }

    private function loadChartData(): void
    {
        $weeks = collect(range(4, 0))->map(function (int $i) {
            $start = Carbon::now()->startOfWeek()->subWeeks($i);
            $end = (clone $start)->endOfWeek();

            return [
                'label' => 'W'.$start->weekOfYear,
                'collected' => (float) Transaction::where('type', 'repayment')
                    ->whereBetween('transacted_at', [$start, $end])
                    ->sum('amount'),
                'disbursed' => (float) Loan::whereBetween('disbursed_at', [$start, $end])
                    ->sum('principal_amount'),
            ];
        });

        $this->weeklyLabels = $weeks->pluck('label')->values()->toArray();
        $this->weeklyCollections = $weeks->pluck('collected')->values()->toArray();
        $this->weeklyDisbursements = $weeks->pluck('disbursed')->values()->toArray();

        $total = Loan::whereIn('status', ['active', 'defaulted', 'overdue'])->count();
        $atRisk = $total > 0 ? Loan::whereIn('ifrs_stage', [2, 3])->count() : 0;
        $this->riskMeterData = [$total > 0 ? round(($atRisk / $total) * 100, 1) : 0];

        $brands = DB::table('inventory_units')
            ->join('phone_models', 'inventory_units.phone_model_id', '=', 'phone_models.id')
            ->join('brands', 'phone_models.brand_id', '=', 'brands.id')
            ->select('brands.name as brand_name', DB::raw('count(*) as total'))
            ->whereNull('inventory_units.deleted_at')
            ->groupBy('brands.name')
            ->pluck('total', 'brand_name')
            ->toArray();

        $this->inventoryDistribution = ! empty($brands) ? $brands : ['No Stock' => 1];
    }

    public function refreshFeeds(): void
    {
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.executive-dashboard')->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
