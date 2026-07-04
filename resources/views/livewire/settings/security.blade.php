<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="overflow-hidden rounded-4xl border border-zinc-200/80 bg-white shadow-sm ring-1 ring-black/5">
            <div class="relative isolate p-6 sm:p-8">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.18),transparent_34%),linear-gradient(135deg,#ffffff_0%,#f8fafc_55%,#fff7ed_100%)]"></div>

                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl space-y-3">
                        <div class="inline-flex items-center gap-2 rounded-full border border-brand-orange/20 bg-brand-orange/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-brand-orange">
                            <flux:icon.shield-check class="size-4" />
                            {{ __('Admin security') }}
                        </div>

                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-brand-charcoal sm:text-3xl">
                                {{ __('Security center') }}
                            </h1>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-zinc-600">
                                {{ __('Control password changes, authenticator protection, email OTP backup, and recovery access from one place.') }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:min-w-120">
                        <div class="rounded-2xl border border-zinc-200 bg-white/80 p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('Authenticator') }}</p>
                            <p class="mt-1 text-sm font-bold {{ $twoFactorEnabled ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $twoFactorEnabled ? __('Enabled') : __('Setup needed') }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white/80 p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('Email OTP') }}</p>
                            <p class="mt-1 text-sm font-bold {{ $emailOtpEnabled ? 'text-emerald-700' : 'text-zinc-700' }}">
                                {{ $emailOtpEnabled ? __('Enabled') : __('Optional') }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-zinc-200 bg-white/80 p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('Admin policy') }}</p>
                            <p class="mt-1 text-sm font-bold text-brand-charcoal">
                                {{ auth()->user()?->requiresMandatoryTwoFactorAuthentication() ? __('MFA required') : __('Standard') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (session('mfa_setup_required') || (bool) session()->get('admin_mfa_setup_required', false))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                {{ __('Admin accounts must set up Google Authenticator or another authenticator app before using the console.') }}
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.25fr)]">
            <div class="space-y-6">
                <div class="rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-sm ring-1 ring-black/5">
                    <div class="flex items-start gap-4">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-zinc-100 text-brand-charcoal">
                            <flux:icon.lock-closed class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-brand-charcoal">{{ __('Update password') }}</h2>
                            <p class="mt-1 text-sm text-zinc-600">{{ __('Use a strong password that is not shared with any other system.') }}</p>
                        </div>
                    </div>

                    <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-4">
                        <flux:input
                            wire:model="current_password"
                            :label="__('Current password')"
                            type="password"
                            required
                            autocomplete="current-password"
                            viewable
                        />
                        <flux:input
                            wire:model="password"
                            :label="__('New password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            viewable
                        />
                        <flux:input
                            wire:model="password_confirmation"
                            :label="__('Confirm password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            viewable
                        />

                        <div class="flex items-center gap-4">
                            <flux:button variant="primary" type="submit" data-test="update-password-button">
                                {{ __('Save password') }}
                            </flux:button>

                            <x-action-message on="password-updated">
                                {{ __('Saved.') }}
                            </x-action-message>
                        </div>
                    </form>
                </div>

                @if ($twoFactorEnabled)
                    <div class="rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-sm ring-1 ring-black/5">
                        <div class="flex items-start gap-4">
                            <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <flux:icon.key class="size-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-brand-charcoal">{{ __('Recovery codes') }}</h2>
                                <p class="mt-1 text-sm text-zinc-600">{{ __('Keep these offline so you can recover access if your device is lost.') }}</p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <livewire:settings.two-factor.recovery-codes :$requiresConfirmation/>
                        </div>
                    </div>
                @endif
            </div>

            @if ($canManageTwoFactor)
                <div class="rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-sm ring-1 ring-black/5" wire:cloak>
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h2 class="text-base font-bold text-brand-charcoal">{{ __('Two-factor methods') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-zinc-600">
                                {{ __('Authenticator app is the default method. Email OTP can be enabled as a backup after authenticator setup.') }}
                            </p>
                        </div>

                        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-brand-orange/10 px-3 py-1 text-xs font-bold text-brand-orange">
                            <span class="size-1.5 rounded-full bg-brand-orange"></span>
                            {{ __('Password required for changes') }}
                        </span>
                    </div>

                    <div class="mt-5 rounded-2xl border border-zinc-200 bg-zinc-50/80 p-4">
                        <flux:input
                            wire:model="email_otp_password"
                            :label="__('Confirm security changes with your password')"
                            type="password"
                            autocomplete="current-password"
                            viewable
                        />
                        @error('email_otp_password')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                        <x-action-message class="mt-2" on="security-method-updated">
                            {{ __('Security method updated.') }}
                        </x-action-message>
                    </div>

                    <div class="mt-5 divide-y divide-zinc-200 overflow-hidden rounded-2xl border border-zinc-200">
                        <div class="grid gap-4 bg-white p-5 md:grid-cols-[auto_minmax(0,1fr)_auto] md:items-center">
                            <div class="flex size-12 items-center justify-center rounded-2xl bg-brand-orange/10 text-brand-orange">
                                <flux:icon.device-phone-mobile class="size-6" />
                            </div>

                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-bold text-brand-charcoal">{{ __('Authenticator app') }}</h3>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-emerald-100 text-emerald-800' => $twoFactorEnabled,
                                        'bg-amber-100 text-amber-800' => ! $twoFactorEnabled,
                                    ])>
                                        {{ $twoFactorEnabled ? __('Default') : __('Required') }}
                                    </span>
                                </div>
                                <p class="text-sm leading-6 text-zinc-600">
                                    {{ __('Use Google Authenticator or any TOTP app to generate rotating 6-digit login codes.') }}
                                </p>

                                @if (auth()->user()?->requiresMandatoryTwoFactorAuthentication() && $twoFactorEnabled)
                                    <p class="text-sm font-medium text-emerald-700">
                                        {{ __('Required for admin accounts and cannot be disabled here.') }}
                                    </p>
                                @endif

                                @error('twoFactor')
                                    <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-start md:justify-end">
                                @if ($twoFactorEnabled)
                                    @if (auth()->user()?->requiresMandatoryTwoFactorAuthentication())
                                        <flux:button variant="outline" disabled>
                                            {{ __('Required') }}
                                        </flux:button>
                                    @else
                                        <flux:button variant="danger" wire:click="disable">
                                            {{ __('Disable') }}
                                        </flux:button>
                                    @endif
                                @else
                                    <flux:button variant="primary" wire:click="enable">
                                        {{ __('Enable') }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-4 bg-white p-5 md:grid-cols-[auto_minmax(0,1fr)_auto] md:items-center">
                            <div class="flex size-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                                <flux:icon.envelope class="size-6" />
                            </div>

                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-bold text-brand-charcoal">{{ __('Email OTP backup') }}</h3>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-emerald-100 text-emerald-800' => $emailOtpEnabled,
                                        'bg-zinc-100 text-zinc-700' => ! $emailOtpEnabled,
                                    ])>
                                        {{ $emailOtpEnabled ? __('Enabled') : __('Off') }}
                                    </span>
                                </div>
                                <p class="text-sm leading-6 text-zinc-600">
                                    {{ __('Send one-time login codes to') }}
                                    <span class="font-semibold text-brand-charcoal">{{ $this->maskedEmail }}</span>
                                    {{ __('when your authenticator app is unavailable.') }}
                                </p>

                                @if (! $twoFactorEnabled)
                                    <p class="text-sm font-medium text-amber-700">
                                        {{ __('Enable authenticator app first before adding email OTP.') }}
                                    </p>
                                @endif

                                @error('emailOtp')
                                    <p class="text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-start md:justify-end">
                                @if ($emailOtpEnabled)
                                    <flux:button variant="danger" wire:click="disableEmailOtp">
                                        {{ __('Disable') }}
                                    </flux:button>
                                @else
                                    <flux:button variant="primary" wire:click="enableEmailOtp" :disabled="! $twoFactorEnabled">
                                        {{ __('Enable') }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if ($canManageTwoFactor)
            <flux:modal
                name="two-factor-setup-modal"
                class="max-w-md md:min-w-md"
                @close="closeModal"
                wire:model="showModal"
            >
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="rounded-full border border-brand-orange/20 bg-brand-orange/10 p-3 text-brand-orange">
                            <flux:icon.qr-code class="size-6"/>
                        </div>

                        <div class="space-y-2 text-center">
                            <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                            <flux:text>{{ $this->modalConfig['description'] }}</flux:text>
                        </div>
                    </div>

                    @if ($showVerificationStep)
                        <div class="space-y-6">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <flux:otp
                                    name="code"
                                    wire:model="code"
                                    length="6"
                                    label="OTP Code"
                                    label:sr-only
                                    class="mx-auto"
                                />
                            </div>

                            <div class="flex items-center space-x-3">
                                <flux:button
                                    variant="outline"
                                    class="flex-1"
                                    wire:click="resetVerification"
                                >
                                    {{ __('Back') }}
                                </flux:button>

                                <flux:button
                                    variant="primary"
                                    class="flex-1"
                                    wire:click="confirmTwoFactor"
                                    x-bind:disabled="$wire.code.length < 6"
                                >
                                    {{ __('Confirm') }}
                                </flux:button>
                            </div>
                        </div>
                    @else
                        @error('setupData')
                            <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}"/>
                        @enderror

                        <div class="flex justify-center">
                            <div class="relative aspect-square w-64 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                                @empty($qrCodeSvg)
                                    <div class="absolute inset-0 flex items-center justify-center bg-white animate-pulse">
                                        <flux:icon.loading/>
                                    </div>
                                @else
                                    <div x-data class="flex h-full items-center justify-center p-4">
                                        <div
                                            class="rounded bg-white p-3"
                                            :style="($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'filter: invert(1) brightness(1.5)' : ''"
                                        >
                                            {!! $qrCodeSvg !!}
                                        </div>
                                    </div>
                                @endempty
                            </div>
                        </div>

                        <flux:button
                            :disabled="$errors->has('setupData')"
                            variant="primary"
                            class="w-full"
                            wire:click="showVerificationIfNecessary"
                        >
                            {{ $this->modalConfig['buttonText'] }}
                        </flux:button>

                        <details class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                            <summary class="cursor-pointer text-sm font-semibold text-brand-charcoal">
                                {{ __("Can't scan the QR code?") }}
                            </summary>
                            <div
                                class="mt-3 flex items-center space-x-2"
                                x-data="{
                                    copied: false,
                                    async copy() {
                                        try {
                                            await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 1500);
                                        } catch (e) {
                                            console.warn('Could not copy to clipboard');
                                        }
                                    }
                                }"
                            >
                                <div class="flex w-full items-stretch rounded-xl border bg-white">
                                    @empty($manualSetupKey)
                                        <div class="flex w-full items-center justify-center p-3">
                                            <flux:icon.loading variant="mini"/>
                                        </div>
                                    @else
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ $manualSetupKey }}"
                                            class="w-full bg-transparent p-3 text-sm text-stone-900 outline-none"
                                        />

                                        <button
                                            type="button"
                                            @click="copy()"
                                            class="cursor-pointer border-l border-stone-200 px-3 transition-colors"
                                        >
                                            <flux:icon.document-duplicate x-show="!copied" variant="outline"></flux:icon>
                                            <flux:icon.check
                                                x-show="copied"
                                                variant="solid"
                                                class="text-green-500"
                                            ></flux:icon>
                                        </button>
                                    @endempty
                                </div>
                            </div>
                        </details>
                    @endif
                </div>
            </flux:modal>
        @endif
    </div>
</section>
