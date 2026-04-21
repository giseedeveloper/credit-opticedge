<div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-10 sm:px-6 bg-slate-50 motion-safe:transition-colors">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_85%_55%_at_50%_-15%,rgba(245,130,32,0.09),transparent_55%),radial-gradient(ellipse_70%_50%_at_100%_100%,rgba(45,55,72,0.04),transparent_45%),radial-gradient(ellipse_60%_40%_at_0%_100%,rgba(245,130,32,0.05),transparent_45%)] pointer-events-none" aria-hidden="true"></div>
    <div class="absolute inset-0 opacity-[0.4] pointer-events-none" style="background-image: linear-gradient(rgba(148,163,184,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(148,163,184,0.1) 1px, transparent 1px); background-size: 44px 44px;" aria-hidden="true"></div>

    <div class="relative z-10 w-full max-w-md flex flex-col items-center">
        <div class="mb-6 sm:mb-7 text-center w-full select-none">
            <x-brand.wordmark :href="route('login')" class="text-[1.65rem] sm:text-[1.85rem] motion-safe:transition-opacity hover:opacity-90 focus-visible:ring-offset-slate-50" />
        </div>

        <div class="w-full rounded-2xl bg-white p-8 sm:p-9 shadow-xl shadow-slate-200/60 border border-slate-200/80 ring-1 ring-slate-100/80">
                <div class="mb-8">
                    <h2 class="text-xl sm:text-2xl font-semibold text-[#2D3748] tracking-tight">{{ __('Welcome back') }}</h2>
                    <p class="text-slate-600 text-sm mt-1.5 leading-relaxed">{{ __('Sign in to open your dashboard.') }}</p>
                </div>

                <div class="flex p-1 bg-slate-100 rounded-xl border border-slate-200/80 mb-6" role="tablist" aria-label="{{ __('Sign-in method') }}">
                    <button type="button" wire:click="swapMethod('email')"
                            class="cursor-pointer flex-1 flex items-center justify-center gap-1.5 text-sm font-semibold py-2.5 rounded-lg motion-safe:transition-colors motion-safe:duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F58220]/40 focus-visible:ring-offset-2
                                   {{ $method === 'email' ? 'bg-white text-[#2D3748] shadow-sm border border-slate-200/90' : 'text-slate-500 hover:text-slate-700 border border-transparent' }}">
                        <svg class="w-4 h-4 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        {{ __('Email') }}
                    </button>
                    <button type="button" wire:click="swapMethod('phone')"
                            class="cursor-pointer flex-1 flex items-center justify-center gap-1.5 text-sm font-semibold py-2.5 rounded-lg motion-safe:transition-colors motion-safe:duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F58220]/40 focus-visible:ring-offset-2
                                   {{ $method === 'phone' ? 'bg-white text-[#2D3748] shadow-sm border border-slate-200/90' : 'text-slate-500 hover:text-slate-700 border border-transparent' }}">
                        <svg class="w-4 h-4 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3"/></svg>
                        {{ __('Phone') }}
                    </button>
                </div>

                <form wire:submit.prevent="authenticate" class="space-y-5">
                    <div>
                        <label for="login-identifier" class="block text-xs font-semibold text-[#2D3748] uppercase tracking-wide mb-1.5">
                            {{ $method === 'email' ? __('Email address') : __('Phone number') }}
                        </label>
                        @if($method === 'email')
                            <flux:input
                                id="login-identifier"
                                wire:model="login_identifier"
                                type="email"
                                icon="envelope"
                                placeholder="you@company.com"
                                class="!bg-white !text-slate-900 !border-slate-200 !placeholder-slate-400 focus:!border-[#F58220] focus:!ring-[#F58220]/20"
                            />
                        @else
                            <div class="flex items-center bg-white rounded-lg border border-slate-200 overflow-hidden focus-within:border-[#F58220] focus-within:ring-2 focus-within:ring-[#F58220]/15 motion-safe:transition-shadow motion-safe:duration-200">
                                <div class="flex items-center gap-1.5 pl-3 pr-2.5 py-2.5 border-r border-slate-200 text-slate-600 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3"/></svg>
                                    <span class="text-sm font-semibold tabular-nums">+255</span>
                                </div>
                                <input id="login-identifier" wire:model="login_identifier" type="tel" placeholder="7XX XXX XXX" autocomplete="tel"
                                       class="flex-1 min-w-0 bg-transparent border-0 py-2.5 px-3 text-slate-900 placeholder-slate-400 focus:ring-0 text-sm" />
                            </div>
                        @endif
                        @error('login_identifier')
                            <p class="mt-1.5 text-xs text-red-600 flex items-start gap-1.5" role="alert">
                                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="login-password" class="block text-xs font-semibold text-[#2D3748] uppercase tracking-wide mb-1.5">{{ __('Password') }}</label>
                        <div class="relative">
                            <flux:input
                                id="login-password"
                                wire:model="password"
                                type="{{ $showPassword ? 'text' : 'password' }}"
                                icon="key"
                                placeholder="••••••••••"
                                class="!bg-white !text-slate-900 !border-slate-200 !placeholder-slate-400 focus:!border-[#F58220] focus:!ring-[#F58220]/20 !pr-11"
                            />
                            <button type="button" wire:click="togglePassword" aria-pressed="{{ $showPassword ? 'true' : 'false' }}"
                                    class="cursor-pointer absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-md text-slate-400 hover:text-slate-600 motion-safe:transition-colors motion-safe:duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F58220]/35">
                                @if($showPassword)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                    <span class="sr-only">{{ __('Hide password') }}</span>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="sr-only">{{ __('Show password') }}</span>
                                @endif
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-600 flex items-start gap-1.5" role="alert">
                                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-0.5">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer group">
                            <input id="remember-me" wire:model="remember" type="checkbox"
                                   class="h-4 w-4 rounded border-slate-300 text-[#F58220] focus:ring-[#F58220]/30 focus:ring-offset-0 cursor-pointer" />
                            <span class="text-sm text-slate-600 group-hover:text-slate-800 motion-safe:transition-colors motion-safe:duration-200">{{ __('Keep me signed in') }}</span>
                        </label>
                        <a href="{{ route('password.request') }}" wire:navigate
                           class="cursor-pointer text-sm font-semibold text-[#F58220] hover:text-[#d96d16] motion-safe:transition-colors motion-safe:duration-200 focus-visible:outline-none focus-visible:underline rounded-sm focus-visible:ring-2 focus-visible:ring-[#F58220]/35 focus-visible:ring-offset-2">
                            {{ __('Forgot password?') }}
                        </a>
                    </div>

                    <div class="pt-1">
                        <button type="submit" wire:loading.attr="disabled"
                                class="cursor-pointer w-full relative flex items-center justify-center gap-2 rounded-xl bg-[#F58220] hover:bg-[#e67818] active:bg-[#cf6c15] disabled:opacity-65 disabled:cursor-not-allowed text-white font-semibold text-sm py-3.5 shadow-md shadow-[#F58220]/25 motion-safe:transition-[background-color,box-shadow] motion-safe:duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#F58220]/50 focus-visible:ring-offset-2">
                            <span wire:loading.remove wire:target="authenticate" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                {{ __('Sign in to console') }}
                            </span>
                            <span wire:loading wire:target="authenticate" class="flex items-center gap-2">
                                <svg class="w-4 h-4 motion-safe:animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                {{ __('Signing in…') }}
                            </span>
                        </button>
                    </div>
                </form>
        </div>

        <div class="mt-8 text-center space-y-4 w-full">
            <p class="text-xs text-slate-500 leading-relaxed max-w-sm mx-auto">
                {{ __('Need an account?') }}
                <span class="text-slate-600">{{ __('Ask your administrator to invite you.') }}</span>
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    {{ __('TLS encrypted') }}
                </span>
                <span class="hidden sm:inline w-px h-3 bg-slate-200" aria-hidden="true"></span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>
                    {{ __('Audited sessions') }}
                </span>
            </div>
        </div>
    </div>
</div>
