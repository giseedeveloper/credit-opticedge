<?php

namespace App\Livewire\Communications;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class SmsLogs extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showDetail = false;

    public ?string $detailLogId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('sms_campaign.view'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function openDetail(string $id): void
    {
        $this->detailLogId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
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
        return match (true) {
            str_starts_with($description, 'Bulk SMS:') => 'bulk',
            str_starts_with($description, 'Automated SMS:') => 'automated',
            stripos($description, 'welcome') !== false => 'welcome',
            default => 'system',
        };
    }

    public static function subjectDisplayName(mixed $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        $name = null;

        if (method_exists($subject, 'getAttribute')) {
            $name = $subject->getAttribute('full_name')
                ?? $subject->getAttribute('name');
        } else {
            $name = data_get($subject, 'full_name')
                ?? data_get($subject, 'name');
        }

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        return preg_replace('/\s+/', ' ', trim($name));
    }

    public static function subjectDisplayPhone(mixed $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        if (method_exists($subject, 'getAttribute')) {
            return $subject->getAttribute('phone');
        }

        return data_get($subject, 'phone');
    }

    /**
     * @param  Builder<Activity>  $query
     */
    protected function applyTypeFilter(Builder $query, string $type): Builder
    {
        return match ($type) {
            'bulk' => $query->whereInsensitiveLike('description', 'Bulk SMS:%'),
            'automated' => $query->whereInsensitiveLike('description', 'Automated SMS:%'),
            'welcome' => $query->whereInsensitiveLike('description', '%welcome%'),
            'system' => $query
                ->whereInsensitiveNotLike('description', 'Bulk SMS:%')
                ->whereInsensitiveNotLike('description', 'Automated SMS:%')
                ->whereInsensitiveNotLike('description', '%welcome%'),
            default => $query,
        };
    }

    /**
     * @return Builder<Activity>
     */
    protected function smsBaseQuery(): Builder
    {
        return Activity::query()->whereInsensitiveLike('description', '%sms%');
    }

    public function render()
    {
        $logs = $this->smsBaseQuery()
            ->with(['causer'])
            ->when($this->search, fn ($query) => $query->whereInsensitiveLike('description', "%{$this->search}%"))
            ->when($this->typeFilter, fn ($query) => $this->applyTypeFilter($query, $this->typeFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(25);

        $base = $this->smsBaseQuery();
        $bulkCount = $this->applyTypeFilter(clone $base, 'bulk')->count();
        $automatedCount = $this->applyTypeFilter(clone $base, 'automated')->count();
        $welcomeCount = $this->applyTypeFilter(clone $base, 'welcome')->count();
        $systemCount = $this->applyTypeFilter(clone $base, 'system')->count();

        $stats = [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
            'this_week' => (clone $base)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $base)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'bulk' => $bulkCount,
            'automated' => $automatedCount,
            'welcome' => $welcomeCount,
            'system' => $systemCount,
        ];

        return view('livewire.communications.sms-logs', compact('logs', 'stats'))
            ->layout('layouts.app', ['title' => 'SMS Logs']);
    }
}
