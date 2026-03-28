<?php

namespace App\Livewire\Stock;

use App\Imports\InventoryImport;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\InventoryUnit;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class StockDashboard extends Component
{
    use WithFileUploads, WithPagination;

    // ── Filters ──────────────────────────────────────────────────────
    public string $search = '';

    public string $statusFilter = '';

    public string $brandFilter = '';

    public string $branchFilter = '';

    // ── Import ───────────────────────────────────────────────────────
    public $importFile;

    public bool $showImportModal = false;

    // ── Detail slide-over ────────────────────────────────────────────
    public bool $showDetail = false;

    public ?string $detailUnitId = null;

    // ── Status update ────────────────────────────────────────────────
    public bool $showStatusModal = false;

    public string $newStatus = '';

    public string $statusNote = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('devices.view'), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function updatedBrandFilter(): void { $this->resetPage(); }

    public function updatedBranchFilter(): void { $this->resetPage(); }

    // ── Detail panel ─────────────────────────────────────────────────
    public function openDetail(string $unitId): void
    {
        $this->detailUnitId = $unitId;
        $this->showDetail   = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail   = false;
        $this->detailUnitId = null;
    }

    // ── Status change ─────────────────────────────────────────────────
    public function openStatusModal(string $unitId): void
    {
        abort_unless(auth()->user()->canAccess('devices.edit'), 403);
        $this->detailUnitId  = $unitId;
        $this->newStatus     = InventoryUnit::find($unitId)?->status ?? '';
        $this->statusNote    = '';
        $this->showStatusModal = true;
    }

    public function updateStatus(): void
    {
        abort_unless(auth()->user()->canAccess('devices.edit'), 403);

        $this->validate([
            'newStatus'  => 'required|in:available,hq_stock,vendor_stock,in_transit,sold,returned,lost',
            'statusNote' => 'nullable|string|max:255',
        ]);

        $unit = InventoryUnit::findOrFail($this->detailUnitId);
        $old  = $unit->status;
        $unit->update(['status' => $this->newStatus]);

        activity('stock')
            ->performedOn($unit)
            ->causedBy(auth()->user())
            ->withProperties(['old' => $old, 'new' => $this->newStatus, 'note' => $this->statusNote])
            ->log("Status changed: {$old} → {$this->newStatus}");

        $this->showStatusModal = false;
        $this->showDetail      = false;
        $this->dispatch('toast', message: 'Status updated to '.str_replace('_', ' ', $this->newStatus).'.', type: 'success');
    }

    // ── Import ────────────────────────────────────────────────────────
    public function importInventory(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,csv,xls|max:5120',
        ]);

        try {
            Excel::import(new InventoryImport, $this->importFile);
            $this->reset(['importFile', 'showImportModal']);
            $this->dispatch('toast', message: 'Inventory imported successfully!', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Import failed: '.$e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $query = InventoryUnit::with(['phoneModel.brand', 'branch', 'vendor', 'loan.customer'])
            ->when($this->search, fn ($q) => $q->where(function ($b) {
                $b->where('imei_1', 'ilike', "%{$this->search}%")
                    ->orWhere('imei_2', 'ilike', "%{$this->search}%")
                    ->orWhere('serial_number', 'ilike', "%{$this->search}%")
                    ->orWhereHas('phoneModel', fn ($m) => $m->where('name', 'ilike', "%{$this->search}%"))
                    ->orWhereHas('phoneModel.brand', fn ($m) => $m->where('name', 'ilike', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->brandFilter, fn ($q) => $q->whereHas('phoneModel', fn ($m) => $m->where('brand_id', $this->brandFilter)))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->latest();

        $summary = InventoryUnit::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $detailUnit = $this->detailUnitId
            ? InventoryUnit::with(['phoneModel.brand', 'branch', 'vendor', 'loan.customer', 'stockTransfers'])
                ->find($this->detailUnitId)
            : null;

        return view('livewire.stock.stock-dashboard', [
            'units'      => $query->paginate(20),
            'summary'    => $summary,
            'brands'     => Brand::orderBy('name')->get(),
            'branches'   => Branch::orderBy('name')->get(),
            'detailUnit' => $detailUnit,
        ])->layout('layouts.app', ['title' => 'Stock Overview']);
    }
}
