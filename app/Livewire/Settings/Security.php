<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Security settings')]
class Security extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    #[Locked]
    public bool $canManageTwoFactor = false;

    #[Locked]
    public bool $twoFactorEnabled = false;

    #[Locked]
    public bool $requiresConfirmation = false;

    #[Locked]
    public bool $emailOtpEnabled = false;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showModal = false;

    public bool $showVerificationStep = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public string $email_otp_password = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
            $this->emailOtpEnabled = auth()->user()->hasEnabledEmailOtpAuthentication();
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enable(EnableTwoFactorAuthentication $enableTwoFactorAuthentication): void
    {
        $this->confirmSecurityPassword();

        $enableTwoFactorAuthentication(auth()->user());

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }

        $this->loadSetupData();
        $this->reset('email_otp_password');

        $this->showModal = true;
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user();

        try {
            $this->qrCodeSvg = $user?->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        session()->forget('admin_mfa_setup_required');
        session()->flash('mfa_recovery_codes_ready', true);

        $this->closeModal();

        $this->twoFactorEnabled = true;
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        if (auth()->user()->requiresMandatoryTwoFactorAuthentication()) {
            $this->addError('twoFactor', __('Admin accounts must keep two-factor authentication enabled.'));

            return;
        }

        $this->confirmSecurityPassword();

        $disableTwoFactorAuthentication(auth()->user());
        auth()->user()->forceFill(['email_otp_enabled' => false])->save();

        $this->twoFactorEnabled = false;
        $this->emailOtpEnabled = false;
        $this->reset('email_otp_password');
        $this->dispatch('security-method-updated');
    }

    public function enableEmailOtp(): void
    {
        $user = auth()->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            $this->addError('emailOtp', __('Enable authenticator app MFA before adding email OTP as a backup.'));

            return;
        }

        $this->confirmSecurityPassword();

        $user->forceFill(['email_otp_enabled' => true])->save();

        $this->emailOtpEnabled = true;
        $this->reset('email_otp_password');
        $this->dispatch('security-method-updated');
    }

    public function disableEmailOtp(): void
    {
        $this->confirmSecurityPassword();

        auth()->user()->forceFill(['email_otp_enabled' => false])->save();

        $this->emailOtpEnabled = false;
        $this->reset('email_otp_password');
        $this->dispatch('security-method-updated');
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showModal',
            'showVerificationStep',
        );

        $this->resetErrorBag();

        if (! $this->requiresConfirmation) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }
    }

    /**
     * Get the current modal configuration state.
     */
    public function getModalConfigProperty(): array
    {
        if ($this->twoFactorEnabled) {
            return [
                'title' => __('Two-factor authentication enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify authentication code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable two-factor authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }

    public function getMaskedEmailProperty(): string
    {
        $email = auth()->user()?->email ?? '';

        if (! str_contains($email, '@')) {
            return $email;
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $visible = substr($localPart, 0, min(2, strlen($localPart)));

        return $visible.str_repeat('*', max(strlen($localPart) - strlen($visible), 3)).'@'.$domain;
    }

    private function confirmSecurityPassword(): void
    {
        if ($this->email_otp_password === '') {
            throw ValidationException::withMessages([
                'email_otp_password' => __('Please enter your password to confirm this security change.'),
            ]);
        }

        if (! Hash::check($this->email_otp_password, auth()->user()->password)) {
            $this->reset('email_otp_password');

            throw ValidationException::withMessages([
                'email_otp_password' => __('The provided password does not match your current password.'),
            ]);
        }
    }
}
