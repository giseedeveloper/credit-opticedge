<?php

namespace App\Livewire\Credit;

use App\Models\Loan;
use Livewire\Component;
use Livewire\WithPagination;

class Defaulters extends Component
{
    use WithPagination;

    public string $search = '';

    public string $riskFilter = '';

    public bool $showDetail = false;

    public ?string $detailLoanId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.view'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRiskFilter(): void
    {
        $this->resetPage();
    }

    public function openDetail(string $id): void
    {
        $this->detailLoanId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailLoanId = null;
    }

    public function getDetailLoanProperty(): ?Loan
    {
        if (! $this->detailLoanId) {
            return null;
        }

        return Loan::with([
            'customer.phoneModel.brand',
            'customer.dealer',
            'inventoryUnit.phoneModel.brand',
            'dealer',
            'disbursedBy',
            'approvedBy',
            'repaymentSchedules',
            'transactions' => fn ($q) => $q->latest('transacted_at')->take(10),
            'recoveryTickets.agent',
        ])->find($this->detailLoanId);
    }

    public function render()
    {
        $query = Loan::with(['customer.phoneModel.brand', 'customer.dealer', 'dealer'])
            ->whereIn('status', ['overdue', 'defaulted'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('loan_number', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', fn ($q) => $q
                        ->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%"));
            }))
            ->when($this->riskFilter === 'moderate', fn ($q) => $q->whereRaw('due_date >= NOW() - INTERVAL 30 DAY'))
            ->when($this->riskFilter === 'high', fn ($q) => $q->whereRaw('due_date BETWEEN NOW() - INTERVAL 60 DAY AND NOW() - INTERVAL 31 DAY'))
            ->when($this->riskFilter === 'critical', fn ($q) => $q->whereRaw('due_date < NOW() - INTERVAL 60 DAY'));

        $defaulters = (clone $query)->orderByRaw('due_date IS NULL ASC, due_date ASC')->paginate(20);

        $stats = [
            'total' => Loan::whereIn('status', ['overdue', 'defaulted'])->count(),
            'outstanding' => Loan::whereIn('status', ['overdue', 'defaulted'])->sum('outstanding_balance'),
            'penalty' => Loan::whereIn('status', ['overdue', 'defaulted'])->sum('penalty_amount'),
            'exposure' => Loan::whereIn('status', ['overdue', 'defaulted'])->selectRaw('SUM(outstanding_balance + COALESCE(penalty_amount, 0))')->value('sum') ?? 0,
        ];

        return view('livewire.credit.defaulters', compact('defaulters', 'stats'))
            ->layout('layouts.app', ['title' => 'Defaulters List']);
    }
}
