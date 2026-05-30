<?php

namespace App\Livewire\Settings;

use App\Jobs\SendSmsJob;
use App\Services\Integrations\IntegrationGatewayManager;
use Livewire\Component;

class IntegrationsHub extends Component
{
    public string $testPhone = '';

    public string $testMessage = 'Opticedge: ujumbe wa majaribio kutoka Integration Hub.';

    /** @var array<string, mixed> */
    public array $snapshot = [];

    public function mount(IntegrationGatewayManager $gateways): void
    {
        abort_unless(auth()->user()->canAccess('settings.view'), 403);
        $this->refreshSnapshot($gateways);
    }

    public function refreshSnapshot(IntegrationGatewayManager $gateways): void
    {
        $this->snapshot = $gateways->statusSnapshot();
    }

    public function refresh(): void
    {
        $this->refreshSnapshot(app(IntegrationGatewayManager::class));
        $this->dispatch('toast', message: 'Integration status refreshed.', type: 'success');
    }

    public function sendTestSms(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'owner']), 403);

        $this->validate([
            'testPhone' => ['required', 'string', 'min:9', 'max:20'],
            'testMessage' => ['required', 'string', 'min:5', 'max:320'],
        ]);

        SendSmsJob::dispatch(
            $this->testPhone,
            $this->testMessage,
            ['source' => 'integrations_hub', 'user_id' => auth()->id()],
        );

        $driver = app(IntegrationGatewayManager::class)->sms()->driverName();

        $this->dispatch(
            'toast',
            message: "Test SMS queued via [{$driver}] driver. Check SMS logs or your vendor dashboard.",
            type: 'success',
        );
    }

    public function render()
    {
        return view('livewire.settings.integrations-hub')
            ->layout('layouts.app', ['title' => 'Integrations']);
    }
}
