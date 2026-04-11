<?php

namespace App\Livewire\Partnership;

use App\Models\CommissionLedger as CommissionLedgerModel;
use App\Models\Vendor;
use Livewire\Component;
use Livewire\WithPagination;

class CommissionLedger extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $vendorFilter = '';

    public bool $showDetail = false;

    public ?string $detailRecordId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('accounting.view'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedVendorFilter(): void
    {
        $this->resetPage();
    }

    public function openDetail(string $id): void
    {
        $this->detailRecordId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailRecordId = null;
    }

    public function getDetailRecordProperty(): ?CommissionLedgerModel
    {
        if (! $this->detailRecordId) {
            return null;
        }

        return CommissionLedgerModel::with([
            'vendor.branch',
            'loan.customer',
            'loan.inventoryUnit.phoneModel.brand',
            'transaction',
        ])->find($this->detailRecordId);
    }

    public function render()
    {
        $records = CommissionLedgerModel::with(['vendor', 'loan.customer'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereHas('vendor', fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
                    ->orWhereHas('loan', fn ($q) => $q->where('loan_number', 'ilike', "%{$this->search}%"))
                    ->orWhere('description', 'ilike', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->vendorFilter, fn ($q) => $q->where('vendor_id', $this->vendorFilter))
            ->latest('posted_at')
            ->paginate(20);

        $stats = [
            'total_count' => CommissionLedgerModel::count(),
            'paid_sum' => CommissionLedgerModel::where('status', 'paid')->sum('commission_amount'),
            'paid_count' => CommissionLedgerModel::where('status', 'paid')->count(),
            'pending_sum' => CommissionLedgerModel::where('status', 'pending')->sum('commission_amount'),
            'pending_count' => CommissionLedgerModel::where('status', 'pending')->count(),
            'this_month_sum' => CommissionLedgerModel::whereMonth('posted_at', now()->month)
                ->whereYear('posted_at', now()->year)
                ->sum('commission_amount'),
        ];

        $vendors = Vendor::orderBy('name')->get(['id', 'name']);

        return view('livewire.partnership.commission-ledger', compact('records', 'stats', 'vendors'))
            ->layout('layouts.app', ['title' => 'Commission Ledger']);
    }
}
