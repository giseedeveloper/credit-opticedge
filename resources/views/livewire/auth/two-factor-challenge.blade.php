@php
    $emailOtpAvailable = $challengedUser?->canUseEmailOtpForTwoFactorChallenge() ?? false;
    $initialMethod = $errors->has('recovery_code')
        ? 'recovery'
        : (($emailOtpAvailable && ($errors->has('email_code') || session('mfa_challenge_method') === 'email')) ? 'email' : 'authenticator');

    $email = $challengedUser?->email ?? '';
    $maskedEmail = $email;

    if (str_contains($email, '@')) {
        [$localPart, $domain] = explode('@', $email, 2);
        $visible = substr($localPart, 0, min(2, strlen($localPart)));
        $maskedEmail = $visible.str_repeat('*', max(strlen($localPart) - strlen($visible), 3)).'@'.$domain;
    }
@endphp

<x-layouts::auth :title="__('Two-factor authentication')">
    <div
        class="mx-auto w-full max-w-md space-y-5"
        x-cloak
        x-data="{
            method: @js($initialMethod),
            code: '',
            email_code: '',
            recovery_code: '',
            switchTo(nextMethod) {
                this.method = nextMethod;
                this.code = '';
                this.email_code = '';
                this.recovery_code = '';

                $dispatch('clear-2fa-auth-code');

                $nextTick(() => {
                    if (nextMethod === 'recovery') {
                        this.$refs.recovery_code?.focus();
                    } else if (nextMethod === 'email') {
                        this.$refs.email_code?.focus();
                    } else {
                        $dispatch('focus-2fa-auth-code');
                    }
                });
            },
        }"
    >
        <div class="space-y-3 text-center">
            <x-brand.wordmark :href="route('home')" :wire-navigate="false" class="text-2xl" />
            <div class="inline-flex items-center gap-2 rounded-full border border-brand-orange/20 bg-brand-orange/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-brand-orange">
                <flux:icon.shield-check class="size-3.5"/>
                {{ __('Secure checkpoint') }}
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-2xl shadow-black/8 ring-1 ring-black/5">
            <div class="relative isolate px-6 pb-6 pt-7">
                <div class="absolute inset-x-0 top-0 -z-10 h-36 bg-[radial-gradient(circle_at_top,rgba(249,115,22,0.18),transparent_60%)]"></div>

                <div class="space-y-3 text-center">
                    <div
                        x-show="method === 'authenticator'"
                        class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-brand-orange/10 text-brand-orange"
                    >
                        <flux:icon.device-phone-mobile class="size-7"/>
                    </div>
                    @if ($emailOtpAvailable)
                        <div
                            x-show="method === 'email'"
                            class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-sky-50 text-sky-700"
                        >
                            <flux:icon.envelope class="size-7"/>
                        </div>
                    @endif
                    <div
                        x-show="method === 'recovery'"
                        class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"
                    >
                        <flux:icon.key class="size-7"/>
                    </div>

                    <div x-show="method === 'authenticator'" class="space-y-2">
                        <h1 class="text-2xl font-extrabold tracking-tight text-zinc-950">
                            {{ __('Authenticator code') }}
                        </h1>
                        <p class="mx-auto max-w-xs text-sm leading-6 text-zinc-500">
                            {{ __('Enter the 6-digit code from your authenticator app. This is the default admin method.') }}
                        </p>
                    </div>

                    @if ($emailOtpAvailable)
                        <div x-show="method === 'email'" class="space-y-2">
                            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-950">
                                {{ __('Email OTP code') }}
                            </h1>
                            <p class="mx-auto max-w-xs text-sm leading-6 text-zinc-500">
                                {{ __('Use a short-lived code sent to your verified account email.') }}
                            </p>
                        </div>
                    @endif

                    <div x-show="method === 'recovery'" class="space-y-2">
                        <h1 class="text-2xl font-extrabold tracking-tight text-zinc-950">
                            {{ __('Recovery code') }}
                        </h1>
                        <p class="mx-auto max-w-xs text-sm leading-6 text-zinc-500">
                            {{ __('Use one of your saved emergency codes if your MFA device is unavailable.') }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid gap-2 rounded-2xl bg-zinc-100 p-1.5">
                    <button
                        type="button"
                        @click="switchTo('authenticator')"
                        :class="method === 'authenticator' ? 'bg-white text-brand-orange shadow-sm ring-1 ring-black/5' : 'text-zinc-600 hover:text-zinc-950'"
                        class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition"
                    >
                        <flux:icon.device-phone-mobile class="size-5 shrink-0"/>
                        <span class="flex-1">{{ __('Authenticator app') }}</span>
                        <span class="rounded-full bg-brand-orange/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-brand-orange">
                            {{ __('Default') }}
                        </span>
                    </button>

                    @if ($emailOtpAvailable)
                        <button
                            type="button"
                            @click="switchTo('email')"
                            :class="method === 'email' ? 'bg-white text-sky-700 shadow-sm ring-1 ring-black/5' : 'text-zinc-600 hover:text-zinc-950'"
                            class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition"
                        >
                            <flux:icon.envelope class="size-5 shrink-0"/>
                            <span class="flex-1">{{ __('Email OTP') }}</span>
                            <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-sky-700">
                                {{ __('Backup') }}
                            </span>
                        </button>
                    @endif

                    <button
                        type="button"
                        @click="switchTo('recovery')"
                        :class="method === 'recovery' ? 'bg-white text-emerald-700 shadow-sm ring-1 ring-black/5' : 'text-zinc-600 hover:text-zinc-950'"
                        class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold transition"
                    >
                        <flux:icon.key class="size-5 shrink-0"/>
                        <span class="flex-1">{{ __('Recovery code') }}</span>
                    </button>
                </div>

                <div class="mt-6">
                    <form x-show="method === 'authenticator'" method="POST" action="{{ route('two-factor.login.store') }}">
                        @csrf

                        <div class="space-y-5 text-center">
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-5">
                                <div class="mb-4 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wide text-zinc-500">
                                    <flux:icon.lock-closed class="size-4"/>
                                    {{ __('Rotating 6-digit code') }}
                                </div>
                                <flux:otp
                                    x-model="code"
                                    length="6"
                                    name="code"
                                    label="OTP Code"
                                    label:sr-only
                                    class="mx-auto"
                                />
                            </div>

                            <flux:button
                                variant="primary"
                                type="submit"
                                class="w-full bg-brand-orange! hover:bg-brand-orange-hover!"
                            >
                                {{ __('Verify and continue') }}
                            </flux:button>
                        </div>
                    </form>

                    @if ($emailOtpAvailable)
                        <div x-show="method === 'email'" class="space-y-4">
                            <div class="rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                                <div class="flex items-start gap-3">
                                    <flux:icon.envelope class="mt-0.5 size-5 shrink-0"/>
                                    <p>
                                        {{ __('Codes are sent to') }}
                                        <span class="font-bold">{{ $maskedEmail }}</span>
                                        {{ __('and expire after 5 minutes.') }}
                                    </p>
                                </div>
                            </div>

                            @if (session('email_otp_sent'))
                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900">
                                    {{ session('email_otp_sent') }}
                                </div>
                            @endif

                            @error('email_code')
                                <p class="text-center text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror

                            <form method="POST" action="{{ route('two-factor.email.store') }}" class="space-y-4">
                                @csrf

                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-5 text-center">
                                    <div class="mb-4 flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wide text-zinc-500">
                                        <flux:icon.envelope class="size-4"/>
                                        {{ __('Email verification code') }}
                                    </div>
                                    <flux:otp
                                        x-model="email_code"
                                        x-ref="email_code"
                                        length="6"
                                        name="email_code"
                                        label="Email OTP Code"
                                        label:sr-only
                                        class="mx-auto"
                                    />
                                </div>

                                <flux:button
                                    variant="primary"
                                    type="submit"
                                    class="w-full bg-brand-orange! hover:bg-brand-orange-hover!"
                                >
                                    {{ __('Verify email code') }}
                                </flux:button>
                            </form>

                            <form method="POST" action="{{ route('two-factor.email.send') }}">
                                @csrf

                                <flux:button
                                    variant="outline"
                                    type="submit"
                                    class="w-full"
                                >
                                    <span class="inline-flex items-center justify-center gap-2">
                                        <flux:icon.paper-airplane class="size-4"/>
                                        {{ session('email_otp_sent') ? __('Resend email code') : __('Send email code') }}
                                    </span>
                                </flux:button>
                            </form>
                        </div>
                    @endif

                    <form x-show="method === 'recovery'" method="POST" action="{{ route('two-factor.login.store') }}">
                        @csrf

                        <div class="space-y-5">
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                                <flux:input
                                    type="text"
                                    name="recovery_code"
                                    x-ref="recovery_code"
                                    autocomplete="one-time-code"
                                    x-model="recovery_code"
                                    :label="__('Recovery code')"
                                    placeholder="XXXX-XXXX-XXXX"
                                />
                            </div>

                            @error('recovery_code')
                                <p class="text-center text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror

                            <flux:button
                                variant="primary"
                                type="submit"
                                class="w-full bg-brand-orange! hover:bg-brand-orange-hover!"
                            >
                                {{ __('Use recovery code') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <p class="text-center text-xs leading-5 text-zinc-500">
            {{ __('Your password was accepted. Complete this second step to open the admin console.') }}
        </p>
    </div>
</x-layouts::auth>
