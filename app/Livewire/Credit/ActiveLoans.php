<?php

namespace App\Livewire\Credit;

use App\Models\Branch;
use App\Models\Loan;
use Livewire\Component;
use Livewire\WithPagination;

class ActiveLoans extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $statusFilter = 'active';
    public string $branchFilter = '';

    public bool $showDetail    = false;
    public ?string $detailLoanId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.view'), 403);
    }

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedBranchFilter(): void { $this->resetPage(); }

    public function openDetail(string $id): void
    {
        $this->detailLoanId = $id;
        $this->showDetail   = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail   = false;
        $this->detailLoanId = null;
    }

    public function getDetailLoanProperty(): ?Loan
    {
        if (! $this->detailLoanId) {
            return null;
        }

        return Loan::with([
            'customer.branch',
            'inventoryUnit.phoneModel.brand',
            'vendor',
            'branch',
            'disbursedBy',
            'approvedBy',
            'repaymentSchedules',
            'transactions' => fn ($q) => $q->latest('transacted_at')->take(10),
        ])->find($this->detailLoanId);
    }

    public function render()
    {
        $loans = Loan::with(['customer', 'inventoryUnit.phoneModel.brand', 'branch'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('loan_number', 'ilike', "%{$this->search}%")
                    ->orWhereHas('customer', fn ($q) => $q
                        ->where('first_name', 'ilike', "%{$this->search}%")
                        ->orWhere('last_name', 'ilike', "%{$this->search}%")
                        ->orWhere('phone', 'ilike', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->latest()
            ->paginate(20);

        $counts = [
            'active'    => Loan::where('status', 'active')->count(),
            'overdue'   => Loan::where('status', 'overdue')->count(),
            'completed' => Loan::where('status', 'completed')->count(),
            'pending'   => Loan::where('status', 'pending')->count(),
        ];

        $stats = [
            'portfolio'   => Loan::whereIn('status', ['active', 'overdue'])->sum('principal_amount'),
            'collected'   => Loan::sum('amount_paid'),
            'outstanding' => Loan::whereIn('status', ['active', 'overdue'])->sum('outstanding_balance'),
            'overdue_amt' => Loan::where('status', 'overdue')->sum('outstanding_balance'),
        ];

        $branches = Branch::orderBy('name')->get();

        return view('livewire.credit.active-loans', compact('loans', 'counts', 'stats', 'branches'))
            ->layout('layouts.app', ['title' => 'Active Loans']);
    }
}
