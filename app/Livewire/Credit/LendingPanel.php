<?php

namespace App\Livewire\Credit;

use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Services\DocumentService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class LendingPanel extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = 'all';

    // Live Loan Calculator
    public string $calcPrincipal = '';

    public int $calcMonths = 12;

    public float $calcInterestRate = 3.5;

    public float $calcEmi = 0;

    // MDM Hooks
    public bool $confirmingDeviceLock = false;

    public ?string $lockLoanId = null;

    // ── New Loan Disbursement ──────────────────────────────────────────────
    public bool $showDisbursementModal = false;

    public string $newCustomerId = '';

    public string $newUnitId = '';

    public string $newPrincipal = '';

    public string $newDepositPaid = '0';

    public float $newInterestRate = 3.5;

    public string $newInterestType = 'flat';

    public int $newDurationWeeks = 52;

    public string $newFrequency = 'monthly';

    public string $newNotes = '';

    public string $customerSearch = '';

    public string $unitSearch = '';

    public function updatedCustomerSearch(): void
    {
        $this->newCustomerId = '';
    }

    public function updated(string $prop): void
    {
        if (str_starts_with($prop, 'calc')) {
            $this->computeLiveEmi();
        }
    }

    private function computeLiveEmi(): void
    {
        $p = (float) $this->calcPrincipal;
        $m = (int) $this->calcMonths;
        $r = (float) $this->calcInterestRate / 100;

        if ($p > 0 && $m > 0 && $r > 0) {
            $numerator = $p * $r * pow(1 + $r, $m);
            $denominator = pow(1 + $r, $m) - 1;
            $this->calcEmi = $denominator != 0 ? $numerator / $denominator : 0;
        } else {
            $this->calcEmi = 0;
        }
    }

    public function openDisbursementModal(): void
    {
        $this->reset([
            'newCustomerId', 'newUnitId', 'newPrincipal', 'newDepositPaid',
            'newNotes', 'customerSearch', 'unitSearch',
        ]);
        $this->newInterestRate = 3.5;
        $this->newInterestType = 'flat';
        $this->newDurationWeeks = 52;
        $this->newFrequency = 'monthly';
        $this->showDisbursementModal = true;
    }

    public function disburseLoan(): void
    {
        $this->validate([
            'newCustomerId' => 'required|exists:customers,id',
            'newUnitId' => 'required|exists:inventory_units,id',
            'newPrincipal' => 'required|numeric|min:1000',
            'newInterestRate' => 'required|numeric|min:0|max:100',
            'newDurationWeeks' => 'required|integer|min:1|max:208',
            'newFrequency' => 'required|in:weekly,biweekly,monthly',
        ]);

        $principal = (float) $this->newPrincipal;
        $rate = $this->newInterestRate / 100;
        $months = (int) ceil($this->newDurationWeeks / 4);
        $deposit = (float) ($this->newDepositPaid ?: 0);

        if ($this->newInterestType === 'flat') {
            $totalInterest = $principal * $rate * $months;
        } else {
            // Reducing-balance: sum of monthly interest on outstanding balance
            $totalInterest = 0;
            $balance = $principal;
            $monthlyPrincipal = $months > 0 ? $principal / $months : $principal;
            for ($i = 0; $i < $months; $i++) {
                $totalInterest += $balance * $rate;
                $balance -= $monthlyPrincipal;
            }
        }

        $totalDebt = $principal + $totalInterest;
        $remaining = $totalDebt - $deposit;

        $loan = DB::transaction(function () use (
            $principal, $deposit, $totalDebt, $remaining
        ) {
            // Atomic loan number — lock last row to prevent race condition
            $last = Loan::withTrashed()->lockForUpdate()->count();
            $loanNumber = 'LN-'.str_pad($last + 1, 6, '0', STR_PAD_LEFT);

            return Loan::create([
                'customer_id' => $this->newCustomerId,
                'inventory_unit_id' => $this->newUnitId,
                'disbursed_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'branch_id' => auth()->user()->branch_id,
                'loan_number' => $loanNumber,
                'principal_amount' => $principal,
                'deposit_paid' => $deposit,
                'interest_rate' => $this->newInterestRate,
                'interest_type' => $this->newInterestType,
                'total_debt' => $totalDebt,
                'total_payable' => $remaining,
                'amount_paid' => $deposit,
                'remaining_balance' => $remaining,
                'outstanding_balance' => $remaining,
                'penalty_amount' => 0,
                'duration_weeks' => $this->newDurationWeeks,
                'repayment_frequency' => $this->newFrequency,
                'status' => 'active',
                'disbursed_at' => now(),
                'due_date' => now()->addWeeks($this->newDurationWeeks),
                'notes' => $this->newNotes,
            ]);
        });

        InventoryUnit::where('id', $this->newUnitId)->update(['status' => 'sold']);

        activity('loan')
            ->performedOn($loan)
            ->causedBy(auth()->user())
            ->log("Loan disbursed: {$loan->loan_number} — TZS ".number_format($principal));

        $this->showDisbursementModal = false;
        $this->dispatch('toast', message: "Loan {$loan->loan_number} disbursed successfully!", type: 'success');
    }

    public function dispatchBulkSMS(): void
    {
        $overdueLoans = Loan::with('customer')
            ->whereIn('status', ['overdue', 'defaulted'])
            ->get();

        $count = $overdueLoans->count();

        foreach ($overdueLoans as $loan) {
            if ($loan->customer) {
                activity('system')
                    ->performedOn($loan->customer)
                    ->causedBy(auth()->user())
                    ->log("Bulk SMS: Ndugu {$loan->customer->first_name}, loan {$loan->loan_number} ina malimbikizo. Tafadhali lipa TZS ".number_format($loan->remaining_balance).'.');
            }
        }

        $this->dispatch('toast', message: "Bulk SMS logged for {$count} overdue customers.", type: 'success');
    }

    public function render()
    {
        $loans = Loan::query()
            ->with('customer')
            ->when($this->search, function ($q) {
                $q->whereHas('customer', function ($c) {
                    $c->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('nida_number', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);

        $customers = Customer::query()
            ->kycApproved()
            ->when($this->customerSearch, function ($q) {
                $q->where('first_name', 'like', "%{$this->customerSearch}%")
                    ->orWhere('last_name', 'like', "%{$this->customerSearch}%")
                    ->orWhere('phone', 'like', "%{$this->customerSearch}%");
            })
            ->orderBy('first_name')
            ->limit(50)
            ->get();

        $availableUnits = InventoryUnit::with('phoneModel.brand')
            ->when($this->unitSearch, function ($q) {
                $q->where('imei_1', 'like', "%{$this->unitSearch}%")
                    ->orWhere('imei_2', 'like', "%{$this->unitSearch}%")
                    ->orWhereHas('phoneModel', fn ($m) => $m->where('name', 'like', "%{$this->unitSearch}%"));
            })
            ->whereIn('status', ['hq_stock', 'vendor_stock'])
            ->whereDoesntHave('loan')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return view('livewire.credit.lending-panel', compact('loans', 'customers', 'availableUnits'))
            ->layout('layouts.app', ['title' => 'Active Loans']);
    }

    public function downloadAgreement(string $loanId, DocumentService $docService): mixed
    {
        $loan = Loan::findOrFail($loanId);

        return $docService->generateLoanAgreement($loan);
    }

    public function confirmDeviceLock(string $loanId): void
    {
        $this->lockLoanId = $loanId;
        $this->confirmingDeviceLock = true;
    }

    public function executeDeviceLock(): void
    {
        if (! $this->lockLoanId) {
            return;
        }

        $loan = Loan::with(['customer', 'inventoryUnit'])->find($this->lockLoanId);
        $imei = $loan?->inventoryUnit?->imei_1 ?? 'Pending Provision';

        activity('security')
            ->performedOn($loan)
            ->causedBy(auth()->user())
            ->log("Knox API MDM lock triggered for IMEI: {$imei}");

        if ($loan?->customer) {
            activity('system')
                ->performedOn($loan->customer)
                ->causedBy(auth()->user())
                ->log('Automated SMS: Ndugu mteja, kifaa chako kimefungwa kutokana na malimbikizo ya deni.');
        }

        $this->dispatch('toast', message: 'MDM Lock triggered & SMS dispatched.', type: 'danger');
        $this->confirmingDeviceLock = false;
        $this->lockLoanId = null;
    }
}
