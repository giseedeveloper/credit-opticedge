<?php

namespace App\Livewire\Audits;

use App\Models\Activity;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogDashboard extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterLogName = 'all';
    public string $eventFilter   = '';
    public string $dateFrom      = '';
    public string $dateTo        = '';

    public bool    $showDetail  = false;
    public ?string $detailLogId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('audit.view'), 403);
    }

    public function updatedSearch(): void        { $this->resetPage(); }
    public function updatedFilterLogName(): void { $this->resetPage(); }
    public function updatedEventFilter(): void   { $this->resetPage(); }
    public function updatedDateFrom(): void      { $this->resetPage(); }
    public function updatedDateTo(): void        { $this->resetPage(); }

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

    public function render()
    {
        $activities = Activity::with(['causer'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('description', 'ilike', "%{$this->search}%")
                    ->orWhere('log_name', 'ilike', "%{$this->search}%")
                    ->orWhere('subject_type', 'ilike', "%{$this->search}%");
            }))
            ->when($this->filterLogName !== 'all', fn ($q) => $q->where('log_name', $this->filterLogName))
            ->when($this->eventFilter, fn ($q) => $q->where('event', $this->eventFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(20);

        $base = Activity::query();

        $stats = [
            'total'        => (clone $base)->count(),
            'today'        => (clone $base)->whereDate('created_at', today())->count(),
            'this_week'    => (clone $base)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'unique_users' => (clone $base)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id'),
            'created'      => (clone $base)->where('event', 'created')->count(),
            'updated'      => (clone $base)->where('event', 'updated')->count(),
            'deleted'      => (clone $base)->where('event', 'deleted')->count(),
        ];

        $logNames = Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        return view('livewire.audits.audit-log-dashboard', compact('activities', 'stats', 'logNames'))
            ->layout('layouts.app', ['title' => 'Forensic Logs']);
    }
}
