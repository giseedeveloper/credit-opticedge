<?php

namespace App\Livewire\Partnership;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\Vendor;
use Livewire\Component;
use Livewire\WithPagination;

class VendorDirectory extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $statusFilter  = '';
    public string $branchFilter  = '';

    public bool    $showDetail      = false;
    public ?string $detailVendorId  = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('vendors.view'), 403);
    }

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedBranchFilter(): void { $this->resetPage(); }

    public function openDetail(string $id): void
    {
        $this->detailVendorId = $id;
        $this->showDetail     = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail     = false;
        $this->detailVendorId = null;
    }

    public function getDetailVendorProperty(): ?Vendor
    {
        if (! $this->detailVendorId) {
            return null;
        }

        return Vendor::with([
            'branch',
            'ownerUser',
            'wallet',
            'commissionLedgers' => fn ($q) => $q->latest('posted_at')->take(8),
            'loans'             => fn ($q) => $q->with('customer')->latest()->take(5),
        ])
            ->withCount(['inventoryUnits', 'loans'])
            ->withSum('loans', 'principal_amount')
            ->find($this->detailVendorId);
    }

    public function render()
    {
        $vendors = Vendor::withCount(['inventoryUnits', 'loans'])
            ->withSum('loans', 'principal_amount')
            ->with(['branch', 'wallet'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'ilike', "%{$this->search}%")
                    ->orWhere('phone', 'ilike', "%{$this->search}%")
                    ->orWhere('email', 'ilike', "%{$this->search}%")
                    ->orWhere('code', 'ilike', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->latest()
            ->paginate(20);

        $stats = [
            'total'       => Vendor::count(),
            'active'      => Vendor::where('status', 'active')->count(),
            'total_stock' => \App\Models\InventoryUnit::count(),
            'loan_portfolio' => Loan::whereIn('status', ['active', 'overdue'])->sum('principal_amount'),
        ];

        $branches = Branch::orderBy('name')->get();

        return view('livewire.partnership.vendor-directory', compact('vendors', 'stats', 'branches'))
            ->layout('layouts.app', ['title' => 'Vendor Directory']);
    }
}
