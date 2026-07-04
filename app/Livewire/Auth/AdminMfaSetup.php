<?php

namespace App\Livewire\Auth;

use Exception;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Admin MFA setup')]
class AdminMfaSetup extends Component
{
    public string $qrCodeSvg = '';

    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $showRecoveryCodes = false;

    public array $recoveryCodes = [];

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public function mount(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $user = auth()->user();

        abort_unless($user?->requiresMandatoryTwoFactorAuthentication(), 403);

        if (! session()->get('admin_mfa_setup_required', false) && ! $user->mustConfigureTwoFactorAuthentication()) {
            $this->redirectRoute($user->firstAccessibleRouteName(), navigate: true);

            return;
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            session()->forget('admin_mfa_setup_required');
            $this->showRecoveryCodes = true;
            $this->loadRecoveryCodes();

            return;
        }

        if (! $user->two_factor_secret) {
            $enableTwoFactorAuthentication($user);
            $user->refresh();
        }

        $this->loadSetupData();
    }

    public function continueToVerification(): void
    {
        $this->showVerificationStep = true;
        $this->resetErrorBag();
    }

    public function backToQrCode(): void
    {
        $this->reset('code', 'showVerificationStep');
        $this->resetErrorBag();
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        session()->forget('admin_mfa_setup_required');

        $this->reset('code', 'qrCodeSvg', 'manualSetupKey', 'showVerificationStep');
        $this->showRecoveryCodes = true;
        $this->loadRecoveryCodes();
    }

    public function continueToConsole()
    {
        if (auth()->user()?->mustConfigureTwoFactorAuthentication()) {
            return;
        }

        return redirect()->route(auth()->user()->firstAccessibleRouteName());
    }

    private function loadSetupData(): void
    {
        $user = auth()->user();

        try {
            $this->qrCodeSvg = $user?->twoFactorQrCodeSvg() ?? '';
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data. Please refresh and try again.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if (! $user?->hasEnabledTwoFactorAuthentication() || ! $user->two_factor_recovery_codes) {
            $this->recoveryCodes = [];

            return;
        }

        try {
            $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];
        } catch (Exception) {
            $this->addError('recoveryCodes', 'Failed to load recovery codes.');
            $this->recoveryCodes = [];
        }
    }

    public function render()
    {
        return view('livewire.auth.admin-mfa-setup');
    }
}
