<?php

namespace App\Livewire\Stock;

use App\Services\ImeiLookupService;
use Livewire\Component;

class ImeiSearch extends Component
{
    public string $query = '';

    /** @var array<string, mixed>|null */
    public ?array $profile = null;

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
        $this->searched = true;
        $this->profile = app(ImeiLookupService::class)->lookup($term);

        if (($this->profile['match'] ?? 'none') !== 'none' && ! in_array($term, $this->recentSearches, true)) {
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
}
