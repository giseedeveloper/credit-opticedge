<?php

namespace App\Livewire\Inventory;

use App\Models\Brand;
use App\Models\InventoryUnit;
use App\Models\PhoneModel;
use App\Models\Vendor;
use Livewire\Component;
use Livewire\WithPagination;

class StockGrid extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $brandFilter = '';

    // ── Detail slide-over ──────────────────────────────────────────────────
    public bool $showDetail = false;

    public ?string $detailUnitId = null;

    // ── Edit Status modal ─────────────────────────────────────────────────
    public bool $showEditModal = false;

    public ?string $editUnitId = null;

    public string $editStatus = '';

    public string $editPurchasePrice = '';

    /** @var array<string,int> */
    public array $statCounts = [];

    // ── Receive New Stock ──────────────────────────────────────────────────
    public bool $showReceiveModal = false;

    public string $newPhoneModelId = '';

    public string $newImei1 = '';

    public string $newImei2 = '';

    public string $newSerial = '';

    public string $newPurchasePrice = '';

    public string $newVendorId = '';

    public string $newStatus = 'hq_stock';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('devices.view'), 403);
        $this->loadStats();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBrandFilter(): void
    {
        $this->resetPage();
    }

    private function loadStats(): void
    {
        $this->statCounts = InventoryUnit::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    // ── Detail ───────────────────────────────────────────────────────────
    public function openDetail(string $id): void
    {
        $this->detailUnitId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailUnitId = null;
    }

    // ── Edit Status ──────────────────────────────────────────────────────
    public function openEditModal(string $id): void
    {
        abort_unless(auth()->user()->canAccess('devices.edit'), 403);
        $unit = InventoryUnit::findOrFail($id);
        $this->editUnitId = $id;
        $this->editStatus = $unit->status;
        $this->editPurchasePrice = (string) $unit->purchase_price;
        $this->showEditModal = true;
    }

    public function updateUnit(): void
    {
        abort_unless(auth()->user()->canAccess('devices.edit'), 403);
        $this->validate([
            'editStatus' => 'required|in:available,hq_stock,vendor_stock,in_transit,sold,returned,lost',
            'editPurchasePrice' => 'nullable|numeric|min:0',
        ]);

        $unit = InventoryUnit::findOrFail($this->editUnitId);
        $old = $unit->status;
        $unit->update([
            'status' => $this->editStatus,
            'purchase_price' => $this->editPurchasePrice ?: $unit->purchase_price,
        ]);

        activity('inventory')
            ->performedOn($unit)
            ->causedBy(auth()->user())
            ->withProperties(['old_status' => $old, 'new_status' => $this->editStatus])
            ->log('unit_updated');

        $this->reset(['editUnitId', 'editStatus', 'editPurchasePrice', 'showEditModal']);
        $this->loadStats();
        $this->dispatch('toast', message: 'Unit updated successfully.', type: 'success');
    }

    public function openReceiveModal(): void
    {
        $this->reset(['newPhoneModelId', 'newImei1', 'newImei2', 'newSerial', 'newPurchasePrice', 'newVendorId']);
        $this->newStatus = 'hq_stock';
        $this->showReceiveModal = true;
    }

    public function receiveStock(): void
    {
        $this->validate([
            'newPhoneModelId' => 'required|exists:phone_models,id',
            'newImei1' => 'required|string|max:20|unique:inventory_units,imei_1',
            'newImei2' => 'nullable|string|max:20|unique:inventory_units,imei_2',
            'newPurchasePrice' => 'required|numeric|min:0',
            'newStatus' => 'required|in:hq_stock,vendor_stock',
        ]);

        InventoryUnit::create([
            'phone_model_id' => $this->newPhoneModelId,
            'vendor_id' => $this->newVendorId ?: null,
            'imei_1' => $this->newImei1,
            'imei_2' => $this->newImei2 ?: null,
            'serial_number' => $this->newSerial ?: null,
            'purchase_price' => (float) $this->newPurchasePrice,
            'status' => $this->newStatus,
            'received_at' => now(),
        ]);

        activity('stock')
            ->causedBy(auth()->user())
            ->log("New stock received: IMEI {$this->newImei1}");

        $this->showReceiveModal = false;
        $this->loadStats();
        $this->dispatch('toast', message: "Stock unit IMEI {$this->newImei1} registered!", type: 'success');
    }

    public function render()
    {
        $units = InventoryUnit::query()
            ->with(['phoneModel.brand', 'vendor', 'branch', 'loan.customer'])
            ->when($this->search, function ($q) {
                $q->where(function ($b) {
                    $b->whereInsensitiveLike('imei_1', "%{$this->search}%")
                        ->orWhereInsensitiveLike('imei_2', "%{$this->search}%")
                        ->orWhereInsensitiveLike('serial_number', "%{$this->search}%")
                        ->orWhereHas('phoneModel', fn ($m) => $m->whereInsensitiveLike('name', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->brandFilter, fn ($q) => $q->whereHas('phoneModel', fn ($m) => $m->where('brand_id', $this->brandFilter)))
            ->latest()
            ->paginate(15);

        $detailUnit = $this->detailUnitId
            ? InventoryUnit::with([
                'phoneModel.brand',
                'vendor',
                'branch',
                'loan.customer',
                'loan.repaymentSchedules' => fn ($q) => $q->orderBy('due_date')->take(5),
                'stockTransfers' => fn ($q) => $q->latest()->take(6),
            ])->find($this->detailUnitId)
            : null;

        $phoneModels = PhoneModel::with('brand')->where('is_active', true)->orderBy('name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('livewire.inventory.stock-grid', compact(
            'units', 'phoneModels', 'vendors', 'brands', 'detailUnit'
        ))->layout('layouts.app', ['title' => 'Master Stock']);
    }
}
