<?php

namespace App\Livewire\Notifications;

use App\Services\DashboardNotificationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AlertBell extends Component
{
    public function getFeedProperty(): array
    {
        return app(DashboardNotificationService::class)->feed(auth()->user());
    }

    public function getCountProperty(): int
    {
        return (int) ($this->feed['count'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAlertsProperty(): array
    {
        return $this->feed['items'] ?? [];
    }

    public function render(): View
    {
        return view('livewire.notifications.alert-bell');
    }
}
