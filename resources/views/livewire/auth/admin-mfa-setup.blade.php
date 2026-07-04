<div class="mx-auto w-full max-w-md space-y-6">
    <div class="space-y-3 text-center">
        <x-brand.wordmark :href="route('home')" :wire-navigate="false" class="text-2xl" />
        <div class="inline-flex items-center gap-2 rounded-full border border-brand-orange/20 bg-brand-orange/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-brand-orange">
            <flux:icon.shield-check class="size-3.5"/>
            {{ __('Admin security') }}
        </div>
    </div>

    @if ($showRecoveryCodes)
        <div class="space-y-5">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-center text-sm font-semibold text-emerald-900">
                {{ __('MFA is enabled. One last step: save your recovery codes.') }}
            </div>

            <flux:callout
                variant="warning"
                icon="key"
                heading="{{ __('Save your recovery codes now') }}"
            >
                {{ __('These one-time backup codes let you sign in if you lose access to your authenticator app. Store them in a secure password manager before continuing.') }}
            </flux:callout>

            @error('recoveryCodes')
                <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}"/>
            @enderror

            @if (filled($recoveryCodes))
                <div
                    class="grid gap-1 rounded-lg bg-zinc-100 p-4 font-mono text-sm dark:bg-white/5"
                    role="list"
                    aria-label="{{ __('Recovery codes') }}"
                >
                    @foreach ($recoveryCodes as $recoveryCode)
                        <div role="listitem" class="select-text">{{ $recoveryCode }}</div>
                    @endforeach
                </div>
            @endif

            <flux:button variant="primary" class="w-full bg-brand-orange! hover:bg-brand-orange-hover!" wire:click="continueToConsole">
                {{ __('I have saved these codes') }}
            </flux:button>
        </div>
    @elseif ($showVerificationStep)
        <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-xl shadow-black/5 ring-1 ring-black/5 dark:border-stone-800 dark:bg-stone-950">
            <div class="mb-6 space-y-2 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-orange/10 text-brand-orange">
                    <flux:icon.lock-closed class="size-6"/>
                </div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-orange">{{ __('Step 2 of 3') }}</p>
                <h1 class="text-2xl font-extrabold tracking-tight text-zinc-950 dark:text-white">
                    {{ __('Enter verification code') }}
                </h1>
                <p class="mx-auto max-w-xs text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                    {{ __('Use the 6-digit code currently showing in your authenticator app.') }}
                </p>
            </div>

            <div class="space-y-6">
                <div class="flex justify-center">
                    <flux:otp
                        name="code"
                        wire:model="code"
                        length="6"
                        label="OTP Code"
                        label:sr-only
                        class="mx-auto"
                    />
                </div>

                <div class="flex gap-3">
                    <flux:button variant="outline" class="flex-1" wire:click="backToQrCode">
                        {{ __('Back') }}
                    </flux:button>
                    <flux:button
                        variant="primary"
                        class="flex-1 bg-brand-orange! hover:bg-brand-orange-hover!"
                        wire:click="confirmTwoFactor"
                        x-bind:disabled="$wire.code.length < 6"
                    >
                        {{ __('Verify') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-xl shadow-black/5 ring-1 ring-black/5 dark:border-stone-800 dark:bg-stone-950">
            <div class="space-y-2 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-orange/10 text-brand-orange">
                    <flux:icon.qr-code class="size-6"/>
                </div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-orange">{{ __('Step 1 of 3') }}</p>
                <h1 class="text-2xl font-extrabold tracking-tight text-zinc-950 dark:text-white">
                    {{ __('Set up authenticator') }}
                </h1>
                <p class="mx-auto max-w-xs text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                    {{ __('Scan once. Use the rotating 6-digit code for future admin sign-ins.') }}
                </p>
            </div>

            <div class="mt-6 space-y-6">
            @error('setupData')
                <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}"/>
            @enderror

            <div class="flex justify-center">
                <div class="relative aspect-square w-64 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-900">
                    @empty($qrCodeSvg)
                        <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-stone-700">
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
                class="w-full bg-brand-orange! hover:bg-brand-orange-hover!"
                wire:click="continueToVerification"
            >
                {{ __('I have scanned the QR code') }}
            </flux:button>

            <div class="grid grid-cols-3 gap-2 text-center text-[11px] font-semibold text-zinc-500 dark:text-zinc-400">
                <div class="rounded-xl bg-zinc-50 px-2 py-2 dark:bg-stone-900">{{ __('No email code') }}</div>
                <div class="rounded-xl bg-zinc-50 px-2 py-2 dark:bg-stone-900">{{ __('Works offline') }}</div>
                <div class="rounded-xl bg-zinc-50 px-2 py-2 dark:bg-stone-900">{{ __('Admin only') }}</div>
            </div>

            <details class="group rounded-2xl border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-stone-700 dark:bg-stone-900/60">
                <summary class="cursor-pointer list-none font-semibold text-zinc-700 marker:hidden dark:text-zinc-200">
                    <span class="inline-flex items-center gap-2">
                        <flux:icon.key class="size-4 text-brand-orange"/>
                        {{ __("Can't scan the QR code?") }}
                    </span>
                </summary>
                <div class="mt-3 space-y-3 text-zinc-600 dark:text-zinc-400">
                    <p>{{ __('Use this setup key only if your authenticator app cannot scan the QR code.') }}</p>
                    <input
                        type="text"
                        readonly
                        value="{{ $manualSetupKey }}"
                        class="w-full rounded-xl border border-stone-200 bg-white p-3 text-center font-mono text-sm text-stone-900 outline-none dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100"
                    />
                </div>
            </details>
            </div>
        </div>
    @endif
</div>
