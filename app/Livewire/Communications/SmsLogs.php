<?php

namespace App\Livewire\Communications;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Activity;

class SmsLogs extends Component
{
    use WithPagination;

    public string $search     = '';
    public string $typeFilter = '';
    public string $dateFrom   = '';
    public string $dateTo     = '';

    public bool    $showDetail   = false;
    public ?string $detailLogId  = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('sms.view'), 403);
    }

    public function updatedSearch(): void     { $this->resetPage(); }
    public function updatedTypeFilter(): void { $this->resetPage(); }
    public function updatedDateFrom(): void   { $this->resetPage(); }
    public function updatedDateTo(): void     { $this->resetPage(); }

    public function openDetail(string $id): void
    {
        $this->detailLogId = $id;
        $this->showDetail  = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail  = false;
        $this->detailLogId = null;
    }

    public function getDetailLogProperty(): ?Activity
    {
        if (! $this->detailLogId) {
            return null;
        }

        return Activity::with(['causer', 'subject'])->find($this->detailLogId);
    }

    public static function smsTypeFromDescription(string $description): string
    {
        return match(true) {
            str_starts_with($description, 'Bulk SMS:')      => 'bulk',
            str_starts_with($description, 'Automated SMS:') => 'automated',
            stripos($description, 'welcome') !== false       => 'welcome',
            default                                          => 'system',
        };
    }

    public function render()
    {
        $logs = Activity::query()
            ->where('description', 'ilike', '%sms%')
            ->with(['causer'])
            ->when($this->search, fn ($q) => $q->where('description', 'ilike', "%{$this->search}%"))
            ->when($this->typeFilter === 'bulk',      fn ($q) => $q->where('description', 'ilike', 'Bulk SMS:%'))
            ->when($this->typeFilter === 'automated', fn ($q) => $q->where('description', 'ilike', 'Automated SMS:%'))
            ->when($this->typeFilter === 'welcome',   fn ($q) => $q->where('description', 'ilike', '%welcome%'))
            ->when($this->typeFilter === 'system',    fn ($q) => $q
                ->where('description', 'not ilike', 'Bulk SMS:%')
                ->where('description', 'not ilike', 'Automated SMS:%')
                ->where('description', 'not ilike', '%welcome%'))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(25);

        $base = Activity::query()->where('description', 'ilike', '%sms%');

        $stats = [
            'total'      => (clone $base)->count(),
            'today'      => (clone $base)->whereDate('created_at', today())->count(),
            'this_week'  => (clone $base)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $base)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'bulk'       => (clone $base)->where('description', 'ilike', 'Bulk SMS:%')->count(),
            'automated'  => (clone $base)->where('description', 'ilike', 'Automated SMS:%')->count(),
        ];

        return view('livewire.communications.sms-logs', compact('logs', 'stats'))
            ->layout('layouts.app', ['title' => 'SMS Logs']);
    }
}
