<?php

namespace App\Livewire\Stock;

use App\Models\Branch;
use App\Models\InventoryUnit;
use App\Models\StockTransfer;
use App\Models\Vendor;
use Livewire\Component;
use Livewire\WithPagination;

class StockTransfers extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $search = '';

    // ── Detail slide-over ──────────────────────────────────────────────────
    public bool $showDetail = false;

    public ?string $detailTransferId = null;

    // ── Status update ──────────────────────────────────────────────────────
    public bool $showStatusModal = false;

    public string $updateStatusValue = '';

    public string $updateStatusNote = '';

    /** @var array<string,int> */
    public array $statCounts = [];

    // ── Create Transfer ────────────────────────────────────────────────────
    public bool $showCreateModal = false;

    public string $transferUnitSearch = '';

    public string $transferUnitId = '';

    public string $transferToType = 'vendor';

    public string $transferToId = '';

    public string $transferNotes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('devices.view'), 403);
        $this->loadStats();
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function updatedTransferUnitSearch(): void
    {
        $this->transferUnitId = '';
    }

    // ── Detail ────────────────────────────────────────────────────────────
    public function openDetail(string $id): void
    {
        $this->detailTransferId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailTransferId = null;
    }

    // ── Mark Delivered ────────────────────────────────────────────────────
    public function markDelivered(string $id): void
    {
        abort_unless(auth()->user()->canAccess('devices.edit'), 403);
        $transfer = StockTransfer::findOrFail($id);
        $transfer->update(['status' => 'delivered', 'received_at' => now()]);
        $transfer->inventoryUnit?->update(['status' => $transfer->to_type === \App\Models\Vendor::class ? 'vendor_stock' : 'available']);
        $this->loadStats();
        $this->dispatch('toast', message: "Transfer {$transfer->reference} marked as delivered.", type: 'success');
    }

    // ── Cancel Transfer ───────────────────────────────────────────────────
    public function cancelTransfer(string $id): void
    {
        abort_unless(auth()->user()->canAccess('devices.edit'), 403);
        $transfer = StockTransfer::findOrFail($id);
        $transfer->update(['status' => 'cancelled']);
        $this->loadStats();
        $this->dispatch('toast', message: "Transfer {$transfer->reference} cancelled.", type: 'success');
    }

    private function loadStats(): void
    {
        $this->statCounts = StockTransfer::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function openCreateModal(): void
    {
        $this->reset(['transferUnitSearch', 'transferUnitId', 'transferToId', 'transferNotes']);
        $this->transferToType = 'vendor';
        $this->showCreateModal = true;
    }

    public function createTransfer(): void
    {
        abort_unless(auth()->user()->canAccess('devices.create'), 403);
        $this->validate([
            'transferUnitId' => 'required|exists:inventory_units,id',
            'transferToType' => 'required|in:vendor,branch',
            'transferToId'   => 'required',
        ]);

        $unit = InventoryUnit::findOrFail($this->transferUnitId);

        $toModelClass   = $this->transferToType === 'vendor' ? Vendor::class : Branch::class;
        $toEntity       = $toModelClass::findOrFail($this->transferToId);
        $fromModelClass = $unit->vendor_id ? Vendor::class : Branch::class;
        $fromId         = $unit->vendor_id ?? $unit->branch_id;

        StockTransfer::create([
            'inventory_unit_id' => $unit->id,
            'from_type'         => $fromModelClass,
            'from_id'           => $fromId,
            'to_type'           => $toModelClass,
            'to_id'             => $toEntity->id,
            'transferred_by'    => auth()->id(),
            'reference'         => 'TRF-'.strtoupper(substr(uniqid(), -8)),
            'status'            => 'in_transit',
            'notes'             => $this->transferNotes ?: null,
            'shipped_at'        => now(),
        ]);

        if ($this->transferToType === 'vendor') {
            $unit->update(['vendor_id' => $toEntity->id, 'branch_id' => null, 'status' => 'vendor_stock']);
        } else {
            $unit->update(['branch_id' => $toEntity->id, 'vendor_id' => null, 'status' => 'hq_stock']);
        }

        activity('stock')
            ->performedOn($unit)
            ->causedBy(auth()->user())
            ->log("Stock transfer to {$toEntity->name}: IMEI {$unit->imei_1}");

        $this->showCreateModal = false;
        $this->loadStats();
        $this->dispatch('toast', message: "Transfer initiated for IMEI {$unit->imei_1}!", type: 'success');
    }

    public function render()
    {
        $transfers = StockTransfer::with(['from', 'to', 'transferredBy', 'inventoryUnit.phoneModel.brand'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where('reference', 'ilike', "%{$this->search}%")
                    ->orWhereHas('inventoryUnit', fn ($u) => $u
                        ->where('imei_1', 'ilike', "%{$this->search}%")
                        ->orWhere('imei_2', 'ilike', "%{$this->search}%")
                        ->orWhereHas('phoneModel', fn ($m) => $m->where('name', 'ilike', "%{$this->search}%")));
            })
            ->latest()
            ->paginate(20);

        $availableUnits = InventoryUnit::with('phoneModel.brand')
            ->when($this->transferUnitSearch, function ($q) {
                $q->where('imei_1', 'ilike', "%{$this->transferUnitSearch}%")
                    ->orWhere('imei_2', 'ilike', "%{$this->transferUnitSearch}%")
                    ->orWhereHas('phoneModel', fn ($m) => $m->where('name', 'ilike', "%{$this->transferUnitSearch}%"));
            })
            ->whereIn('status', ['hq_stock', 'vendor_stock'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $detailTransfer = $this->detailTransferId
            ? StockTransfer::with(['from', 'to', 'transferredBy', 'inventoryUnit.phoneModel.brand', 'inventoryUnit.branch'])
                ->find($this->detailTransferId)
            : null;

        $vendors  = Vendor::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('livewire.stock.stock-transfers', compact(
            'transfers', 'availableUnits', 'vendors', 'branches', 'detailTransfer'
        ))->layout('layouts.app', ['title' => 'Stock Transfers']);
    }
}
