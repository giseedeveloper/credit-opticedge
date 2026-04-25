<?php

namespace App\Livewire\Stock;

use App\Models\Customer;
use App\Models\InventoryUnit;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ImeiSearch extends Component
{
    public string $query = '';

    public ?Customer $result = null;

    /**
     * Stock unit matched by IMEI/serial when no customer row exists yet.
     */
    public ?InventoryUnit $inventoryHit = null;

    public bool $searched = false;

    /** @var array<int,string> */
    public array $recentSearches = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('devices.view'), 403);
    }

    public function search(): void
    {
        $this->validate(['query' => ['required', 'string', 'min:4']]);

        $term = trim($this->query);
        $variants = $this->deviceIdentifierVariants($term);

        $this->result = null;
        $this->inventoryHit = null;
        $this->searched = true;

        $this->result = Customer::query()
            ->with([
                'phoneModel.brand',
                'loans' => fn ($q) => $q->latest('created_at'),
            ])
            ->where(function (Builder $q) use ($variants): void {
                foreach ($variants as $v) {
                    $q->orWhere(function (Builder $inner) use ($v): void {
                        $inner->where('imei_number', $v)
                            ->orWhere('imei_2', $v)
                            ->orWhere('serial_number', $v);
                    });
                }
            })
            ->first();

        $inventoryUnit = InventoryUnit::query()
            ->with(['phoneModel.brand', 'dealer'])
            ->where(function (Builder $q) use ($variants): void {
                foreach ($variants as $v) {
                    $q->orWhere(function (Builder $inner) use ($v): void {
                        $inner->where('imei_1', $v)
                            ->orWhere('imei_2', $v)
                            ->orWhere('serial_number', $v);
                    });
                }
            })
            ->first();

        if (! $this->result && $inventoryUnit) {
            $this->result = Customer::query()
                ->with([
                    'phoneModel.brand',
                    'loans' => fn ($q) => $q->latest('created_at'),
                ])
                ->where('inventory_unit_id', $inventoryUnit->id)
                ->first();
        }

        if (! $this->result && $inventoryUnit) {
            $this->inventoryHit = $inventoryUnit;
        }

        if (($this->result || $this->inventoryHit) && ! in_array($term, $this->recentSearches, true)) {
            array_unshift($this->recentSearches, $term);
            $this->recentSearches = array_slice($this->recentSearches, 0, 5);
        }
    }

    public function searchRecent(string $term): void
    {
        $this->query = $term;
        $this->search();
    }

    public function render()
    {
        return view('livewire.stock.imei-search')
            ->layout('layouts.app', ['title' => 'IMEI Search']);
    }

    /**
     * @return list<string>
     */
    private function deviceIdentifierVariants(string $raw): array
    {
        $trimmed = trim($raw);
        $upper = strtoupper($trimmed);
        $digitsOnly = preg_replace('/\D+/', '', $trimmed) ?? '';

        $variants = array_values(array_unique(array_filter([
            $trimmed,
            $upper,
            $digitsOnly !== '' ? $digitsOnly : null,
        ])));

        return $variants;
    }
}
