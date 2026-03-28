<?php

namespace App\Livewire\Stock;

use App\Models\Brand;
use App\Models\InventoryUnit;
use App\Models\PhoneModel;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class BrandModelIndex extends Component
{
    use WithPagination;

    // ── Filters ──────────────────────────────────────────────────────
    public string $search = '';

    public string $tab = 'brands';

    // ── Create / Edit Brand ───────────────────────────────────────────
    public bool $showCreateBrand = false;

    public bool $showEditBrand = false;

    public string $brandName = '';

    public ?string $editBrandId = null;

    public string $editBrandName = '';

    // ── Create / Edit Model ───────────────────────────────────────────
    public bool $showCreateModel = false;

    public bool $showEditModel = false;

    public string $modelName = '';

    public string $selectedBrandId = '';

    public string $retailPrice = '';

    public string $costPrice = '';

    public string $specRam = '';

    public string $specStorage = '';

    public string $specColor = '';

    public string $specDisplay = '';

    public string $specBattery = '';

    public ?string $editModelId = null;

    public string $editModelName = '';

    public string $editSelectedBrandId = '';

    public string $editRetailPrice = '';

    public string $editCostPrice = '';

    public string $editSpecRam = '';

    public string $editSpecStorage = '';

    public string $editSpecColor = '';

    public string $editSpecDisplay = '';

    public string $editSpecBattery = '';

    // ── Detail slide-overs ────────────────────────────────────────────
    public bool $showBrandDetail = false;

    public ?string $detailBrandId = null;

    public bool $showModelDetail = false;

    public ?string $detailModelId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('products.view'), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedTab(): void { $this->resetPage(); }

    // ── Brand Detail ─────────────────────────────────────────────────
    public function openBrandDetail(string $brandId): void
    {
        $this->detailBrandId  = $brandId;
        $this->showBrandDetail = true;
    }

    public function closeBrandDetail(): void
    {
        $this->showBrandDetail = false;
        $this->detailBrandId  = null;
    }

    // ── Model Detail ─────────────────────────────────────────────────
    public function openModelDetail(string $modelId): void
    {
        $this->detailModelId  = $modelId;
        $this->showModelDetail = true;
    }

    public function closeModelDetail(): void
    {
        $this->showModelDetail = false;
        $this->detailModelId  = null;
    }

    // ── Create Brand ─────────────────────────────────────────────────
    public function createBrand(): void
    {
        abort_unless(auth()->user()->canAccess('products.create'), 403);
        $this->validate(['brandName' => 'required|string|max:100|unique:brands,name']);
        Brand::create(['name' => $this->brandName]);
        $this->reset(['brandName', 'showCreateBrand']);
        $this->dispatch('toast', message: 'Brand created successfully.', type: 'success');
    }

    // ── Edit Brand ───────────────────────────────────────────────────
    public function openEditBrand(string $brandId): void
    {
        abort_unless(auth()->user()->canAccess('products.edit'), 403);
        $brand              = Brand::findOrFail($brandId);
        $this->editBrandId  = $brandId;
        $this->editBrandName = $brand->name;
        $this->showEditBrand = true;
    }

    public function updateBrand(): void
    {
        abort_unless(auth()->user()->canAccess('products.edit'), 403);
        $this->validate(['editBrandName' => 'required|string|max:100|unique:brands,name,'.$this->editBrandId]);
        Brand::findOrFail($this->editBrandId)->update(['name' => $this->editBrandName]);
        $this->reset(['editBrandId', 'editBrandName', 'showEditBrand']);
        $this->dispatch('toast', message: 'Brand updated.', type: 'success');
    }

    // ── Create Model ─────────────────────────────────────────────────
    public function createModel(): void
    {
        abort_unless(auth()->user()->canAccess('products.create'), 403);
        $this->validate([
            'modelName'       => 'required|string|max:150',
            'selectedBrandId' => 'required|exists:brands,id',
            'retailPrice'     => 'nullable|numeric|min:0',
            'costPrice'       => 'nullable|numeric|min:0',
        ]);

        PhoneModel::create([
            'brand_id'       => $this->selectedBrandId,
            'name'           => $this->modelName,
            'slug'           => Str::slug($this->modelName.'-'.$this->selectedBrandId),
            'retail_price'   => $this->retailPrice ?: 0,
            'cost_price'     => $this->costPrice ?: 0,
            'is_active'      => true,
            'specifications' => $this->buildSpecs($this->specRam, $this->specStorage, $this->specColor, $this->specDisplay, $this->specBattery),
        ]);

        $this->reset(['modelName', 'selectedBrandId', 'retailPrice', 'costPrice', 'specRam', 'specStorage', 'specColor', 'specDisplay', 'specBattery', 'showCreateModel']);
        $this->dispatch('toast', message: 'Model created successfully.', type: 'success');
    }

    // ── Edit Model ───────────────────────────────────────────────────
    public function openEditModel(string $modelId): void
    {
        abort_unless(auth()->user()->canAccess('products.edit'), 403);
        $model = PhoneModel::findOrFail($modelId);
        $specs = $model->specifications ?? [];

        $this->editModelId        = $modelId;
        $this->editModelName      = $model->name;
        $this->editSelectedBrandId = (string) $model->brand_id;
        $this->editRetailPrice    = (string) $model->retail_price;
        $this->editCostPrice      = (string) $model->cost_price;
        $this->editSpecRam        = $specs['ram'] ?? '';
        $this->editSpecStorage    = $specs['storage'] ?? '';
        $this->editSpecColor      = $specs['color'] ?? '';
        $this->editSpecDisplay    = $specs['display'] ?? '';
        $this->editSpecBattery    = $specs['battery'] ?? '';
        $this->showEditModel      = true;
    }

    public function updateModel(): void
    {
        abort_unless(auth()->user()->canAccess('products.edit'), 403);
        $this->validate([
            'editModelName'       => 'required|string|max:150',
            'editSelectedBrandId' => 'required|exists:brands,id',
            'editRetailPrice'     => 'nullable|numeric|min:0',
            'editCostPrice'       => 'nullable|numeric|min:0',
        ]);

        PhoneModel::findOrFail($this->editModelId)->update([
            'brand_id'       => $this->editSelectedBrandId,
            'name'           => $this->editModelName,
            'retail_price'   => $this->editRetailPrice ?: 0,
            'cost_price'     => $this->editCostPrice ?: 0,
            'specifications' => $this->buildSpecs($this->editSpecRam, $this->editSpecStorage, $this->editSpecColor, $this->editSpecDisplay, $this->editSpecBattery),
        ]);

        $this->reset(['editModelId', 'editModelName', 'editSelectedBrandId', 'editRetailPrice', 'editCostPrice', 'editSpecRam', 'editSpecStorage', 'editSpecColor', 'editSpecDisplay', 'editSpecBattery', 'showEditModel']);
        $this->showModelDetail = false;
        $this->dispatch('toast', message: 'Model updated.', type: 'success');
    }

    // ── Toggle Model Active ───────────────────────────────────────────
    public function toggleModelActive(string $modelId): void
    {
        abort_unless(auth()->user()->canAccess('products.edit'), 403);
        $model = PhoneModel::findOrFail($modelId);
        $model->update(['is_active' => ! $model->is_active]);
        $this->dispatch('toast', message: 'Model '.($model->is_active ? 'activated' : 'deactivated').'.', type: 'success');
    }

    private function buildSpecs(string $ram, string $storage, string $color, string $display, string $battery): array
    {
        return array_filter([
            'ram'     => $ram,
            'storage' => $storage,
            'color'   => $color,
            'display' => $display,
            'battery' => $battery,
        ]);
    }

    public function render()
    {
        $brands = Brand::withCount('phoneModels')
            ->withCount(['inventoryUnits as stock_count'])
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $models = PhoneModel::with('brand')
            ->withCount(['inventoryUnits as stock_total'])
            ->withCount(['inventoryUnits as stock_available' => fn ($q) => $q->whereIn('status', ['available', 'hq_stock'])])
            ->withCount(['inventoryUnits as stock_sold'      => fn ($q) => $q->where('status', 'sold')])
            ->when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $detailBrand = $this->detailBrandId
            ? Brand::with(['phoneModels' => fn ($q) => $q->withCount([
                'inventoryUnits as stock_total',
                'inventoryUnits as stock_available' => fn ($q) => $q->whereIn('status', ['available', 'hq_stock']),
                'inventoryUnits as stock_sold'      => fn ($q) => $q->where('status', 'sold'),
            ])])->withCount('phoneModels')->find($this->detailBrandId)
            : null;

        $detailModel = $this->detailModelId
            ? PhoneModel::with('brand')
                ->withCount([
                    'inventoryUnits as stock_total',
                    'inventoryUnits as stock_available'    => fn ($q) => $q->whereIn('status', ['available', 'hq_stock']),
                    'inventoryUnits as stock_vendor'       => fn ($q) => $q->where('status', 'vendor_stock'),
                    'inventoryUnits as stock_in_transit'   => fn ($q) => $q->where('status', 'in_transit'),
                    'inventoryUnits as stock_sold'         => fn ($q) => $q->where('status', 'sold'),
                    'inventoryUnits as stock_returned'     => fn ($q) => $q->where('status', 'returned'),
                ])
                ->find($this->detailModelId)
            : null;

        $stats = [
            'total_brands'  => Brand::count(),
            'total_models'  => PhoneModel::count(),
            'active_models' => PhoneModel::where('is_active', true)->count(),
            'total_units'   => InventoryUnit::count(),
        ];

        $allBrands = Brand::orderBy('name')->get();

        return view('livewire.stock.brand-model-index', compact(
            'brands', 'models', 'detailBrand', 'detailModel', 'stats', 'allBrands'
        ))->layout('layouts.app', ['title' => 'Brands & Models']);
    }
}
