<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\InventoryUnit;
use App\Models\PhoneModel;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KycDeviceCatalogService
{
    /**
     * @return Collection<int, Brand>
     */
    public function brandsFor(User $user): Collection
    {
        $brandIds = $this->scopedInventoryQuery($user)
            ->join('phone_models', 'inventory_units.phone_model_id', '=', 'phone_models.id')
            ->distinct()
            ->pluck('phone_models.brand_id');

        if ($brandIds->isEmpty()) {
            return collect();
        }

        return Brand::query()
            ->whereIn('id', $brandIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, PhoneModel>
     */
    public function modelsFor(User $user, ?string $brandId = null): Collection
    {
        $modelIds = $this->scopedInventoryQuery($user)
            ->when($brandId, function (Builder $query) use ($brandId): void {
                $query->whereHas('phoneModel', fn (Builder $modelQuery) => $modelQuery->where('brand_id', $brandId));
            })
            ->distinct()
            ->pluck('phone_model_id');

        if ($modelIds->isEmpty()) {
            return collect();
        }

        return PhoneModel::query()
            ->with('brand')
            ->whereIn('id', $modelIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, InventoryUnit>
     */
    public function unitsFor(User $user, ?string $phoneModelId = null, string $search = ''): Collection
    {
        return $this->scopedInventoryQuery($user)
            ->with(['phoneModel.brand', 'branch', 'vendor'])
            ->when($phoneModelId, fn (Builder $query) => $query->where('phone_model_id', $phoneModelId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('imei_1', 'like', "%{$search}%")
                        ->orWhere('imei_2', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('phoneModel', fn (Builder $modelQuery) => $modelQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('received_at')
            ->orderByDesc('created_at')
            ->limit(80)
            ->get();
    }

    public function accessibleModel(User $user, ?string $phoneModelId): ?PhoneModel
    {
        if (! $phoneModelId) {
            return null;
        }

        return $this->modelsFor($user)->firstWhere('id', $phoneModelId);
    }

    public function accessibleUnit(User $user, ?string $inventoryUnitId): ?InventoryUnit
    {
        if (! $inventoryUnitId) {
            return null;
        }

        return $this->scopedInventoryQuery($user)
            ->with(['phoneModel.brand', 'branch', 'vendor'])
            ->whereKey($inventoryUnitId)
            ->first();
    }

    public function buildDeviceSpecs(PhoneModel $phoneModel): string
    {
        $parts = [
            $phoneModel->brand?->name,
            $phoneModel->name,
        ];

        $specs = $phoneModel->specifications ?? [];
        $descriptor = collect([
            $specs['ram'] ?? null,
            $specs['storage'] ?? null,
            $specs['color'] ?? null,
        ])->filter()->implode('/');

        if ($descriptor !== '') {
            $parts[] = $descriptor;
        }

        return collect($parts)->filter()->implode(' - ');
    }

    public function hasAvailableUnitsFor(User $user, ?string $phoneModelId): bool
    {
        if (! $phoneModelId) {
            return false;
        }

        return $this->scopedInventoryQuery($user)
            ->where('phone_model_id', $phoneModelId)
            ->exists();
    }

    /**
     * @return array{branch_id: ?string, vendor_id: ?string}
     */
    public function scopeContextFor(User $user): array
    {
        return [
            'branch_id' => $user->branch_id,
            'vendor_id' => $this->resolveVendorId($user),
        ];
    }

    public function scopedInventoryQuery(User $user): Builder
    {
        $query = InventoryUnit::query()
            ->whereIn('status', ['available', 'hq_stock', 'vendor_stock'])
            ->whereDoesntHave('loan');

        if ($user->isAdmin() || $user->isOwner() || $user->isManager() || $user->isSupervisor()) {
            return $query;
        }

        $vendorId = $this->resolveVendorId($user);

        if ($user->branch_id && $vendorId) {
            return $query->where(function (Builder $scopeQuery) use ($user, $vendorId): void {
                $scopeQuery->where('branch_id', $user->branch_id)
                    ->orWhere('vendor_id', $vendorId);
            });
        }

        if ($vendorId) {
            return $query->where('vendor_id', $vendorId);
        }

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1 = 0');
    }

    private function resolveVendorId(User $user): ?string
    {
        $managedVendorId = $user->managedVendors()->value('id');

        if ($managedVendorId) {
            return $managedVendorId;
        }

        if (! $user->branch_id) {
            return null;
        }

        return Vendor::query()
            ->where('branch_id', $user->branch_id)
            ->value('id');
    }
}
