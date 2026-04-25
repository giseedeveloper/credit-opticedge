<?php

namespace App\Livewire\Credit;

use App\Models\Loan;
use App\Models\RepaymentSchedule;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentSchedules extends Component
{
    use WithPagination;

    public string $loanSearch = '';

    public string $statusFilter = '';

    public ?string $selectedLoanId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.view'), 403);
    }

    public function updatedLoanSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function selectLoan(string $loanId): void
    {
        $this->selectedLoanId = $loanId;
    }

    public function clearSelection(): void
    {
        $this->selectedLoanId = null;
    }

    public function getDetailLoanProperty(): ?Loan
    {
        if (! $this->selectedLoanId) {
            return null;
        }

        return Loan::with([
            'customer.dealer',
            'inventoryUnit.phoneModel.brand',
            'dealer',
            'disbursedBy',
            'repaymentSchedules',
            'transactions' => fn ($q) => $q->latest('transacted_at')->take(8),
        ])->find($this->selectedLoanId);
    }

    public function render()
    {
        $loans = Loan::with(['customer.dealer', 'inventoryUnit.phoneModel.brand', 'dealer'])
            ->when($this->loanSearch, fn ($q) => $q->where(function ($q) {
                $q->whereInsensitiveLike('loan_number', "%{$this->loanSearch}%")
                    ->orWhereHas('customer', fn ($q) => $q
                        ->whereInsensitiveLike('first_name', "%{$this->loanSearch}%")
                        ->orWhereInsensitiveLike('last_name', "%{$this->loanSearch}%")
                        ->orWhereInsensitiveLike('phone', "%{$this->loanSearch}%"));
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when(! $this->statusFilter, fn ($q) => $q->where('status', '!=', 'completed'))
            ->latest()
            ->paginate(15);

        $stats = [
            'due_this_week' => RepaymentSchedule::where('status', '!=', 'paid')
                ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
                ->count(),
            'overdue_count' => RepaymentSchedule::where('status', '!=', 'paid')
                ->where('due_date', '<', now()->startOfDay())
                ->count(),
            'collected_month' => RepaymentSchedule::whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount_paid'),
            'active_loans' => Loan::whereIn('status', ['active', 'overdue', 'pending'])->count(),
        ];

        return view('livewire.credit.payment-schedules', compact('loans', 'stats'))
            ->layout('layouts.app', ['title' => 'Payment Schedules']);
    }
}
