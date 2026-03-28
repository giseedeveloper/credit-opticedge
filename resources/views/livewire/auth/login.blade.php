<div class="min-h-screen bg-[#0f0022] flex">

    {{-- ══ LEFT BRAND PANEL ══ --}}
    <div class="hidden lg:flex lg:w-[55%] xl:w-[60%] relative flex-col bg-gradient-to-br from-[#1a0033] via-[#2d0057] to-[#0f0022] overflow-hidden">
        {{-- Ambient orbs --}}
        <div class="absolute -top-32 -left-32 w-[500px] h-[500px] bg-indigo-700/25 rounded-full blur-[140px] pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-purple-800/20 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] h-[300px] bg-violet-600/10 rounded-full blur-[100px] pointer-events-none"></div>

        {{-- Subtle grid overlay --}}
        <div class="absolute inset-0 opacity-[0.03]"
             style="background-image: linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 40px 40px;"></div>

        {{-- Top edge glow --}}
        <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-purple-400/40 to-transparent"></div>

        <div class="relative z-10 flex flex-col justify-between h-full px-12 py-12">

            {{-- Brand --}}
            <div>
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-700 flex items-center justify-center shadow-[0_0_20px_rgba(99,102,241,0.5)]">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    </div>
                    <div>
                        <p class="text-white font-black text-lg tracking-tight leading-none">Opticedge Credit</p>
                        <p class="text-indigo-300/60 text-xs font-medium tracking-widest uppercase mt-0.5">Secure Console</p>
                    </div>
                </div>
            </div>

            {{-- Hero text --}}
            <div>
                <div class="mb-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-teal-500/10 border border-teal-500/20 mb-6">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-400 animate-pulse"></span>
                        <span class="text-xs font-bold text-teal-300 tracking-wider uppercase">All systems operational</span>
                    </div>
                    <h1 class="text-4xl xl:text-5xl font-black text-white leading-[1.1] tracking-tight mb-4">
                        Enterprise<br>
                        <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-violet-300 bg-clip-text text-transparent">Credit Management</span>
                    </h1>
                    <p class="text-indigo-200/60 text-base leading-relaxed max-w-sm">
                        A unified platform for loan origination, disbursements, collections, and full financial operations management.
                    </p>
                </div>

                {{-- Security feature highlights --}}
                <div class="grid grid-cols-2 gap-3 max-w-sm">
                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-white/5 border border-white/8 backdrop-blur-sm">
                        <div class="p-1.5 rounded-lg bg-indigo-500/20 text-indigo-400 shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white">Brute Force Guard</p>
                            <p class="text-[10px] text-indigo-200/50 mt-0.5">IP-based rate limiting active</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-white/5 border border-white/8 backdrop-blur-sm">
                        <div class="p-1.5 rounded-lg bg-teal-500/20 text-teal-400 shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white">Session Audit Log</p>
                            <p class="text-[10px] text-indigo-200/50 mt-0.5">Every login recorded</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-white/5 border border-white/8 backdrop-blur-sm">
                        <div class="p-1.5 rounded-lg bg-violet-500/20 text-violet-400 shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white">RBAC Permissions</p>
                            <p class="text-[10px] text-indigo-200/50 mt-0.5">Granular role-based access</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-white/5 border border-white/8 backdrop-blur-sm">
                        <div class="p-1.5 rounded-lg bg-amber-500/20 text-amber-400 shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white">Multi-Channel Auth</p>
                            <p class="text-[10px] text-indigo-200/50 mt-0.5">Email or phone login</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between">
                <p class="text-[11px] text-indigo-200/30 font-medium">
                    &copy; {{ date('Y') }} Opticedge Credit Ltd. All rights reserved.
                </p>
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-400"></span>
                    <span class="text-[10px] text-indigo-200/40 font-semibold uppercase tracking-wider">Secure</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ RIGHT FORM PANEL ══ --}}
    <div class="flex-1 flex flex-col justify-center relative bg-[#100020] overflow-hidden">
        {{-- Ambient orb --}}
        <div class="absolute -bottom-20 -right-20 w-[350px] h-[350px] bg-purple-900/30 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-purple-500/20 to-transparent lg:hidden"></div>

        <div class="relative z-10 w-full max-w-sm mx-auto px-8 py-12 sm:px-10">

            {{-- Mobile brand (hidden on lg) --}}
            <div class="lg:hidden text-center mb-10">
                <div class="inline-flex items-center gap-2.5 mb-4">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-700 flex items-center justify-center shadow-[0_0_15px_rgba(99,102,241,0.4)]">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    </div>
                    <span class="text-white font-black text-lg">Opticedge Credit</span>
                </div>
            </div>

            {{-- Form heading --}}
            <div class="mb-8">
                <h2 class="text-2xl font-black text-white tracking-tight">Welcome back</h2>
                <p class="text-indigo-200/50 text-sm mt-1">Sign in to access the Secure Console</p>
            </div>

            {{-- Dual Auth Switcher --}}
            <div class="flex p-1 bg-white/5 rounded-xl border border-white/8 mb-6">
                <button type="button" wire:click="swapMethod('email')"
                        class="flex-1 flex items-center justify-center gap-1.5 text-sm font-bold py-2.5 rounded-lg transition-all
                               {{ $method === 'email' ? 'bg-[#4b0082] text-white shadow-md ring-1 ring-purple-500/30' : 'text-indigo-300/50 hover:text-indigo-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    Email
                </button>
                <button type="button" wire:click="swapMethod('phone')"
                        class="flex-1 flex items-center justify-center gap-1.5 text-sm font-bold py-2.5 rounded-lg transition-all
                               {{ $method === 'phone' ? 'bg-[#4b0082] text-white shadow-md ring-1 ring-purple-500/30' : 'text-indigo-300/50 hover:text-indigo-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3"/></svg>
                    Phone
                </button>
            </div>

            <form wire:submit.prevent="authenticate" class="space-y-4">

                {{-- Identifier field --}}
                <div>
                    <label class="block text-[11px] font-bold text-indigo-300/60 uppercase tracking-wider mb-1.5">
                        {{ $method === 'email' ? 'Email Address' : 'Phone Number' }}
                    </label>
                    @if($method === 'email')
                        <flux:input
                            wire:model="login_identifier"
                            type="email"
                            icon="envelope"
                            placeholder="you@opticedge.co"
                            class="!bg-white/8 dark:!bg-white/8 !text-white !border-white/10 !placeholder-indigo-300/30 focus:!border-indigo-500/50 focus:!ring-indigo-500/20"
                        />
                    @else
                        <div class="flex items-center bg-white/8 rounded-lg border border-white/10 overflow-hidden focus-within:border-indigo-500/50 focus-within:ring-2 focus-within:ring-indigo-500/20">
                            <div class="flex items-center gap-1.5 pl-3 pr-2.5 py-2.5 border-r border-white/10 text-indigo-300/70 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3"/></svg>
                                <span class="text-sm font-semibold">+255</span>
                            </div>
                            <input wire:model="login_identifier" type="tel" placeholder="7XX XXX XXX"
                                   class="flex-1 bg-transparent border-0 py-2.5 px-3 text-white placeholder-indigo-300/30 focus:ring-0 text-sm" />
                        </div>
                    @endif
                    @error('login_identifier')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Password field --}}
                <div>
                    <label class="block text-[11px] font-bold text-indigo-300/60 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <flux:input
                            wire:model="password"
                            type="{{ $showPassword ? 'text' : 'password' }}"
                            icon="key"
                            placeholder="••••••••••"
                            class="!bg-white/8 dark:!bg-white/8 !text-white !border-white/10 !placeholder-indigo-300/30 focus:!border-indigo-500/50 focus:!ring-indigo-500/20"
                        />
                        <button type="button" wire:click="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-indigo-300/40 hover:text-indigo-200 transition-colors">
                            @if($showPassword)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            @endif
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Remember me + forgot --}}
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input id="remember-me" wire:model="remember" type="checkbox"
                               class="h-3.5 w-3.5 rounded border-white/20 bg-white/8 text-[#4b0082] focus:ring-[#4b0082] focus:ring-offset-0">
                        <span class="text-xs text-indigo-300/50 group-hover:text-indigo-300/80 transition-colors">Keep me signed in</span>
                    </label>
                    <a href="#" class="text-xs font-semibold text-indigo-400 hover:text-white transition-colors">Forgot password?</a>
                </div>

                {{-- Submit --}}
                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                            class="w-full relative flex items-center justify-center gap-2 bg-gradient-to-r from-[#4b0082] to-[#6b21a8] hover:from-[#3a0066] hover:to-[#5b1898] disabled:opacity-70 text-white rounded-xl font-bold text-sm py-3.5 transition-all shadow-[0_0_24px_rgba(107,33,168,0.5)] hover:shadow-[0_0_32px_rgba(107,33,168,0.7)] overflow-hidden group">
                        <div class="absolute inset-0 bg-white/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300 rounded-xl"></div>
                        <span wire:loading.remove wire:target="authenticate" class="relative z-10 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            Sign In to Console
                        </span>
                        <span wire:loading wire:target="authenticate" class="relative z-10 flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            Authenticating…
                        </span>
                    </button>
                </div>
            </form>

            {{-- Footer --}}
            <div class="mt-10 pt-8 border-t border-white/5 space-y-4">
                <p class="text-[11px] text-indigo-200/30 text-center leading-relaxed">
                    Need access?
                    <a href="#" class="text-indigo-400/60 hover:text-indigo-300 transition-colors underline underline-offset-2">Contact your administrator</a>.
                </p>
                <div class="flex items-center justify-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-400"></span>
                        <span class="text-[10px] text-indigo-200/30 font-semibold uppercase tracking-wider">TLS Encrypted</span>
                    </div>
                    <div class="w-px h-3 bg-white/10"></div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                        <span class="text-[10px] text-indigo-200/30 font-semibold uppercase tracking-wider">Sessions Audited</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
