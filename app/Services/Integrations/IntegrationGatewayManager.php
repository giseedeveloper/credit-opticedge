<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\MdmGateway;
use App\Contracts\Integrations\SmsGateway;
use App\Services\IntegrationDriverResolver;
use InvalidArgumentException;

class IntegrationGatewayManager
{
    public function __construct(
        private IntegrationDriverResolver $driverResolver,
    ) {}

    public function sms(?string $configuredDriver = null): SmsGateway
    {
        $driver = $this->driverResolver->resolve('sms', $configuredDriver ?? config('services.sms.driver'));

        return match ($driver) {
            'log' => app(LogSmsGateway::class),
            'http' => app(HttpSmsGateway::class),
            'beem' => app(BeemSmsGateway::class),
            default => app(LogSmsGateway::class),
        };
    }

    public function mdm(?string $configuredDriver = null): MdmGateway
    {
        $driver = $this->driverResolver->resolve('mdm', $configuredDriver ?? config('services.mdm.driver'));

        return match ($driver) {
            'log' => app(LogMdmGateway::class),
            'http' => app(HttpMdmGateway::class),
            default => app(LogMdmGateway::class),
        };
    }

    /**
     * @return array{
     *     sms: array<string, mixed>,
     *     mdm: array<string, mixed>,
     *     face_match: array<string, mixed>
     * }
     */
    public function statusSnapshot(): array
    {
        $smsDriver = (string) config('services.sms.driver', 'log');
        $mdmDriver = (string) config('services.mdm.driver', 'log');
        $resolvedSms = $this->driverResolver->resolve('sms', $smsDriver);
        $resolvedMdm = $this->driverResolver->resolve('mdm', $mdmDriver);

        return [
            'sms' => [
                'configured_driver' => $smsDriver,
                'active_driver' => $resolvedSms,
                'ready' => $this->smsIsReady($smsDriver, $resolvedSms),
                'mode' => $resolvedSms === 'log' ? 'simulation' : 'live',
                'label' => $this->smsLabel($smsDriver, $resolvedSms),
                'hint' => $this->smsHint($smsDriver, $resolvedSms),
            ],
            'mdm' => [
                'configured_driver' => $mdmDriver,
                'active_driver' => $resolvedMdm,
                'ready' => $this->mdmIsReady($mdmDriver, $resolvedMdm),
                'mode' => $resolvedMdm === 'log' ? 'simulation' : 'live',
                'label' => $this->mdmLabel($mdmDriver, $resolvedMdm),
                'hint' => $this->mdmHint($mdmDriver, $resolvedMdm),
            ],
            'face_match' => [
                'required' => (bool) config('services.face_match.required', false),
                'url_configured' => filled(config('services.face_match.url')),
                'ready' => ! config('services.face_match.required', false)
                    || filled(config('services.face_match.url')),
                'label' => filled(config('services.face_match.url'))
                    ? 'Face match service online'
                    : 'FACE_MATCH_URL not set',
            ],
        ];
    }

    private function smsIsReady(string $configured, string $resolved): bool
    {
        if ($resolved === 'log') {
            return true;
        }

        if ($resolved === 'beem') {
            return filled(config('services.beem.api_key'))
                && filled(config('services.beem.secret_key'))
                && filled(config('services.beem.sender_id'));
        }

        if ($resolved === 'http') {
            return filled(config('services.sms.http.url'));
        }

        return $configured === $resolved;
    }

    private function mdmIsReady(string $configured, string $resolved): bool
    {
        if ($resolved === 'log') {
            return true;
        }

        if ($resolved === 'http') {
            return filled(config('services.mdm.http.lock_url'))
                && filled(config('services.mdm.http.unlock_url'));
        }

        return $configured === $resolved;
    }

    private function smsLabel(string $configured, string $resolved): string
    {
        if ($resolved !== $configured) {
            return "Fallback to {$resolved} ({$configured} not wired)";
        }

        return match ($resolved) {
            'beem' => 'BEEM Africa SMS',
            'http' => 'Custom HTTP SMS vendor',
            default => 'Log only (dev / staging)',
        };
    }

    private function mdmLabel(string $configured, string $resolved): string
    {
        if ($resolved !== $configured) {
            return "Fallback to {$resolved} ({$configured} not wired)";
        }

        return match ($resolved) {
            'http' => 'Custom HTTP MDM vendor',
            default => 'Log only (simulated lock/unlock)',
        };
    }

    private function smsHint(string $configured, string $resolved): string
    {
        if ($resolved === 'log') {
            return 'Set SMS_DRIVER=http or beem when your vendor credentials are ready.';
        }

        if ($resolved === 'beem' && ! $this->smsIsReady($configured, $resolved)) {
            return 'Add BEEM_API_KEY, BEEM_SECRET_KEY, and BEEM_SENDER_ID to .env';
        }

        if ($resolved === 'http' && ! $this->smsIsReady($configured, $resolved)) {
            return 'Set SMS_HTTP_URL to your provider send endpoint.';
        }

        return 'Live SMS dispatch enabled.';
    }

    private function mdmHint(string $configured, string $resolved): string
    {
        if ($resolved === 'log') {
            return 'Set MDM_DRIVER=http when your device-lock vendor API is ready.';
        }

        if ($resolved === 'http' && ! $this->mdmIsReady($configured, $resolved)) {
            return 'Set MDM_HTTP_LOCK_URL and MDM_HTTP_UNLOCK_URL.';
        }

        return 'Live MDM commands enabled.';
    }

    public function assertSmsDriver(string $driver): void
    {
        $implemented = (array) config('services.sms.implemented_drivers', ['log']);

        if (! in_array($driver, $implemented, true)) {
            throw new InvalidArgumentException("SMS driver [{$driver}] is not registered.");
        }
    }
}
