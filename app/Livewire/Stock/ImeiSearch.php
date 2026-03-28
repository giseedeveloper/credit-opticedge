<?php

namespace App\Livewire\Stock;

use App\Models\InventoryUnit;
use Livewire\Component;

class ImeiSearch extends Component
{
    public string $query = '';

    public ?InventoryUnit $result = null;

    public bool $searched = false;

    public bool $showStatusModal = false;

    public string $newStatus = '';

    public string $statusNote = '';

    /** @var array<int,string> */
    public array $recentSearches = [];

    /** @var array<string,int> */
    public array $statusCounts = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('devices.view'), 403);
        $this->loadStatusCounts();
    }

    public function search(): void
    {
        $this->validate(['query' => 'required|string|min:4']);

        $term = trim($this->query);

        $this->result = InventoryUnit::with([
            'phoneModel.brand',
            'branch',
            'vendor',
            'loan.customer',
            'loan.repaymentSchedules' => fn ($q) => $q->orderBy('due_date')->take(5),
            'stockTransfers'          => fn ($q) => $q->latest()->take(8),
        ])
            ->where('imei_1', $term)
            ->orWhere('imei_2', $term)
            ->orWhere('serial_number', $term)
            ->first();

        $this->searched = true;

        if ($this->result && ! in_array($term, $this->recentSearches)) {
            array_unshift($this->recentSearches, $term);
            $this->recentSearches = array_slice($this->recentSearches, 0, 5);
        }

        if ($this->result) {
            $this->newStatus = $this->result->status;
        }
    }

    public function searchRecent(string $term): void
    {
        $this->query = $term;
        $this->search();
    }

    public function openStatusModal(): void
    {
        abort_unless(auth()->user()->canAccess('devices.edit'), 403);
        $this->showStatusModal = true;
    }

    public function updateStatus(): void
    {
        abort_unless(auth()->user()->canAccess('devices.edit'), 403);
        $this->validate([
            'newStatus'  => 'required|in:available,hq_stock,vendor_stock,in_transit,sold,returned,lost',
            'statusNote' => 'nullable|string|max:255',
        ]);

        $old = $this->result->status;
        $this->result->update(['status' => $this->newStatus]);

        activity('inventory')
            ->performedOn($this->result)
            ->causedBy(auth()->user())
            ->withProperties(['old' => $old, 'new' => $this->newStatus, 'note' => $this->statusNote])
            ->log('status_changed');

        $this->result->refresh();
        $this->showStatusModal = false;
        $this->statusNote      = '';
        $this->dispatch('toast', message: 'Status updated to '.str_replace('_', ' ', $this->newStatus).'.', type: 'success');
        $this->loadStatusCounts();
    }

    private function loadStatusCounts(): void
    {
        $this->statusCounts = InventoryUnit::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.stock.imei-search')
            ->layout('layouts.app', ['title' => 'IMEI Search']);
    }
}
