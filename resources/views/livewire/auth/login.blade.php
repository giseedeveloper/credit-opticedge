<div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6 motion-safe:transition-colors">
    {{-- Landing-aligned background orbs --}}
    <div class="pointer-events-none absolute -left-32 top-16 h-80 w-80 rounded-full bg-white/70 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-24 bottom-20 h-96 w-96 rounded-full bg-white/80 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-10%,rgba(245,130,32,0.08),transparent_50%)]" aria-hidden="true"></div>

    <div class="relative z-10 flex w-full max-w-md flex-col items-center">
        <a href="{{ route('home') }}" wire:navigate class="mb-6 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-zinc-500 transition hover:text-brand-charcoal focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange/40 rounded-md px-1">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ __('Back to home') }}
        </a>

        <div class="mb-6 w-full select-none text-center sm:mb-7">
            <x-brand.wordmark :href="route('home')" class="text-[1.65rem] sm:text-[1.85rem] motion-safe:transition-opacity hover:opacity-90 focus-visible:ring-offset-[#eef0f4]" />
        </div>

        <div class="w-full rounded-2xl border border-zinc-200/80 bg-white p-8 shadow-xl shadow-black/5 ring-1 ring-black/5 sm:p-9">
            <div class="mb-8">
                <h2 class="text-xl font-extrabold tracking-tight text-black sm:text-2xl">{{ __('Welcome back') }}</h2>
                <p class="mt-1.5 text-sm font-medium leading-relaxed text-zinc-600">{{ __('Sign in to open your dashboard.') }}</p>
            </div>

            <div class="mb-6 flex rounded-xl border border-zinc-200/80 bg-zinc-100 p-1" role="tablist" aria-label="{{ __('Sign-in method') }}">
                <button type="button" wire:click="swapMethod('email')"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg py-2.5 text-sm font-bold motion-safe:transition-colors motion-safe:duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange/40 focus-visible:ring-offset-2
                               {{ $method === 'email' ? 'border border-zinc-200/90 bg-white text-brand-charcoal shadow-sm' : 'border border-transparent text-zinc-500 hover:text-zinc-700' }}">
                    <svg class="h-4 w-4 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    {{ __('Email') }}
                </button>
                <button type="button" wire:click="swapMethod('phone')"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg py-2.5 text-sm font-bold motion-safe:transition-colors motion-safe:duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange/40 focus-visible:ring-offset-2
                               {{ $method === 'phone' ? 'border border-zinc-200/90 bg-white text-brand-charcoal shadow-sm' : 'border border-transparent text-zinc-500 hover:text-zinc-700' }}">
                    <svg class="h-4 w-4 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3"/></svg>
                    {{ __('Phone') }}
                </button>
            </div>

            <form wire:submit.prevent="authenticate" class="space-y-5">
                <div>
                    <label for="login-identifier" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-brand-charcoal">
                        {{ $method === 'email' ? __('Email address') : __('Phone number') }}
                    </label>
                    @if($method === 'email')
                        <flux:input
                            id="login-identifier"
                            wire:model="login_identifier"
                            type="email"
                            icon="envelope"
                            placeholder="you@company.com"
                            class="!border-zinc-200 !bg-white !text-zinc-900 !placeholder-zinc-400 focus:!border-black focus:!ring-black/10"
                        />
                    @else
                        <div class="flex items-center overflow-hidden rounded-xl border border-zinc-200 bg-white focus-within:border-black focus-within:ring-2 focus-within:ring-black/10 motion-safe:transition-shadow motion-safe:duration-200">
                            <div class="flex shrink-0 items-center gap-1.5 border-r border-zinc-200 py-2.5 pl-3 pr-2.5 text-zinc-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3"/></svg>
                                <span class="text-sm font-bold tabular-nums">+255</span>
                            </div>
                            <input id="login-identifier" wire:model="login_identifier" type="tel" placeholder="7XX XXX XXX" autocomplete="tel"
                                   class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 focus:ring-0" />
                        </div>
                    @endif
                    @error('login_identifier')
                        <p class="mt-1.5 flex items-start gap-1.5 text-xs text-red-600" role="alert">
                            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="login-password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-brand-charcoal">{{ __('Password') }}</label>
                    <div class="relative">
                        <flux:input
                            id="login-password"
                            wire:model="password"
                            type="{{ $showPassword ? 'text' : 'password' }}"
                            icon="key"
                            placeholder="••••••••••"
                            class="!border-zinc-200 !bg-white !pr-11 !text-zinc-900 !placeholder-zinc-400 focus:!border-black focus:!ring-black/10"
                        />
                        <button type="button" wire:click="togglePassword" aria-pressed="{{ $showPassword ? 'true' : 'false' }}"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer rounded-md p-1 text-zinc-400 hover:text-zinc-600 motion-safe:transition-colors motion-safe:duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange/35">
                            @if($showPassword)
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                <span class="sr-only">{{ __('Hide password') }}</span>
                            @else
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span class="sr-only">{{ __('Show password') }}</span>
                            @endif
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 flex items-start gap-1.5 text-xs text-red-600" role="alert">
                            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <div class="flex flex-col gap-3 pt-0.5 sm:flex-row sm:items-center sm:justify-between">
                    <label class="group inline-flex cursor-pointer items-center gap-2.5">
                        <input id="remember-me" wire:model="remember" type="checkbox"
                               class="h-4 w-4 cursor-pointer rounded border-zinc-300 text-brand-orange focus:ring-brand-orange/30 focus:ring-offset-0" />
                        <span class="text-sm font-medium text-zinc-600 group-hover:text-zinc-800 motion-safe:transition-colors motion-safe:duration-200">{{ __('Keep me signed in') }}</span>
                    </label>
                    <a href="{{ route('password.request') }}" wire:navigate
                       class="cursor-pointer rounded-sm text-sm font-bold text-brand-orange hover:text-brand-orange-hover motion-safe:transition-colors motion-safe:duration-200 focus-visible:outline-none focus-visible:underline focus-visible:ring-2 focus-visible:ring-brand-orange/35 focus-visible:ring-offset-2">
                        {{ __('Forgot password?') }}
                    </a>
                </div>

                <div class="pt-1">
                    <button type="submit" wire:loading.attr="disabled"
                            class="relative flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-black py-3.5 text-sm font-bold tracking-wide text-white shadow-lg shadow-black/20 hover:bg-zinc-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black/30 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-65 motion-safe:transition-[background-color,box-shadow] motion-safe:duration-200">
                        <span wire:loading.remove wire:target="authenticate" class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            {{ __('Sign in to console') }}
                        </span>
                        <span wire:loading wire:target="authenticate" class="flex items-center gap-2">
                            <svg class="h-4 w-4 motion-safe:animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            {{ __('Signing in…') }}
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-8 w-full space-y-4 text-center">
            <p class="mx-auto max-w-sm text-xs font-medium leading-relaxed text-zinc-500">
                {{ __('Need an account?') }}
                <span class="text-zinc-600">{{ __('Ask your administrator to invite you.') }}</span>
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[11px] font-bold uppercase tracking-wider text-zinc-400">
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-orange" aria-hidden="true"></span>
                    {{ __('TLS encrypted') }}
                </span>
                <span class="hidden h-3 w-px bg-zinc-200 sm:inline" aria-hidden="true"></span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-zinc-400" aria-hidden="true"></span>
                    {{ __('Audited sessions') }}
                </span>
            </div>
        </div>
    </div>
</div>
