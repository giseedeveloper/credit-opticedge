{{-- 3D-style hero phones: user analysis + payment countdown (static demo data) --}}
<div class="relative mx-auto flex h-[480px] w-full max-w-xl items-center justify-center sm:h-[540px] lg:h-[580px] lg:max-w-none">
    <div class="animate-landing-drift absolute h-72 w-72 rounded-full bg-gradient-to-br from-white via-zinc-50 to-zinc-100 shadow-[0_50px_100px_-24px_rgba(0,0,0,0.2)] ring-1 ring-white lg:h-[22rem] lg:w-[22rem]"></div>

    <span class="animate-landing-drift absolute -left-1 top-20 flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl font-bold text-zinc-800 shadow-[0_12px_40px_rgba(0,0,0,0.12)]" style="animation-delay: 0.4s" aria-hidden="true">$</span>
    <span class="animate-landing-drift absolute right-2 top-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl font-bold text-zinc-800 shadow-[0_12px_40px_rgba(0,0,0,0.12)]" style="animation-delay: 0.9s" aria-hidden="true">€</span>
    <span class="animate-landing-drift absolute bottom-28 left-6 flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-lg font-bold text-zinc-800 shadow-[0_12px_40px_rgba(0,0,0,0.12)]" style="animation-delay: 1.4s" aria-hidden="true">£</span>

    {{-- Phone 1: User analysis --}}
    <div class="animate-landing-float relative z-10 w-[210px] -translate-x-6 sm:w-[232px] lg:w-[252px]" role="img" aria-label="Demo: customer analysis screen on smartphone">
        <div class="phone-frame">
            <div class="phone-screen">
                {{-- Status bar --}}
                <div class="flex items-center justify-between px-3.5 pt-2 text-[8px] font-semibold text-zinc-500">
                    <span>9:41</span>
                    <div class="phone-island" aria-hidden="true"></div>
                    <span class="flex items-center gap-0.5">
                        <svg class="h-2.5 w-3" viewBox="0 0 12 8" fill="currentColor"><rect x="0" y="5" width="2" height="3" rx="0.5"/><rect x="3" y="3" width="2" height="5" rx="0.5"/><rect x="6" y="1" width="2" height="7" rx="0.5"/><rect x="9" y="0" width="2" height="8" rx="0.5"/></svg>
                        <svg class="h-2.5 w-3.5" viewBox="0 0 14 10" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="0.5" y="2" width="10" height="6" rx="1"/><path d="M11.5 4v2M13 3.5v3"/></svg>
                    </span>
                </div>

                <div class="flex items-center justify-between px-3.5 pb-1 pt-0.5">
                    <button type="button" class="flex h-6 w-6 items-center justify-center rounded-md text-zinc-700" tabindex="-1" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <p class="text-[11px] font-bold text-zinc-900">User analysis</p>
                    <button type="button" class="flex h-6 w-6 items-center justify-center text-zinc-500" tabindex="-1" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                    </button>
                </div>

                <div class="mx-3.5 mb-2 flex items-center gap-2 rounded-xl bg-zinc-50 px-2.5 py-2 ring-1 ring-zinc-100">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-orange/15 text-[10px] font-extrabold text-brand-orange">AM</div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[10px] font-bold text-zinc-900">Asha Mwangi</p>
                        <p class="text-[8px] font-medium text-zinc-500">Samsung A15 · On-time payer</p>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[7px] font-bold text-emerald-700">A+</span>
                </div>

                <div class="mx-3.5 flex rounded-lg bg-zinc-100 p-0.5 text-[8px] font-bold">
                    <span class="flex-1 rounded-md py-1 text-center text-zinc-500">Daily</span>
                    <span class="flex-1 rounded-md bg-zinc-900 py-1 text-center text-white shadow-sm">Monthly</span>
                    <span class="flex-1 rounded-md py-1 text-center text-zinc-500">Yearly</span>
                </div>

                <div class="relative mx-3.5 mt-2.5 h-[72px]">
                    <svg viewBox="0 0 220 72" class="h-full w-full" aria-hidden="true">
                        <line x1="0" y1="58" x2="220" y2="58" stroke="#f4f4f5" stroke-width="1"/>
                        <polyline fill="none" stroke="#2d3748" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" points="0,52 28,44 56,48 84,32 112,36 140,24 168,28 196,14 220,18"/>
                        <circle cx="140" cy="24" r="5" fill="#18181b"/>
                        <circle cx="140" cy="24" r="2.5" fill="#fff"/>
                    </svg>
                    <div class="absolute left-[48%] top-0 rounded-full bg-zinc-900 px-1.5 py-0.5 text-[7px] font-bold text-white shadow-md">237</div>
                </div>

                <div class="mx-3.5 mt-1 grid grid-cols-3 gap-1 text-center">
                    <div>
                        <p class="text-[7px] font-semibold text-zinc-400">Paid</p>
                        <p class="text-[10px] font-extrabold text-zinc-900">TZS 1.2M</p>
                        <p class="text-[7px] font-bold text-emerald-600">+32%</p>
                    </div>
                    <div>
                        <p class="text-[7px] font-semibold text-zinc-400">Balance</p>
                        <p class="text-[10px] font-extrabold text-zinc-900">TZS 408K</p>
                        <p class="text-[7px] font-bold text-rose-500">-15%</p>
                    </div>
                    <div>
                        <p class="text-[7px] font-semibold text-zinc-400">Score</p>
                        <p class="text-[10px] font-extrabold text-zinc-900">92</p>
                        <p class="text-[7px] font-bold text-emerald-600">+4</p>
                    </div>
                </div>

                <div class="mx-3.5 mt-2 space-y-1 border-t border-zinc-100 pt-2">
                    @foreach ([['Dar es Salaam', '44%'], ['Mwanza', '28%'], ['Arusha', '18%']] as $region)
                        <div class="flex items-center gap-1.5 text-[7px] font-semibold text-zinc-600">
                            <span class="w-[4.5rem] truncate">{{ $region[0] }}</span>
                            <span class="h-1 flex-1 overflow-hidden rounded-full bg-zinc-100"><span class="block h-full rounded-full bg-brand-orange" style="width: {{ $region[1] }}"></span></span>
                            <span class="w-6 text-right">{{ $region[1] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Phone 2: Payment time remaining --}}
    <div class="animate-landing-float-delayed absolute right-0 z-20 w-[200px] translate-y-14 sm:w-[220px] sm:translate-y-16 lg:right-2 lg:w-[240px] lg:translate-y-20" role="img" aria-label="Demo: payment countdown and loan statistics on smartphone">
        <div class="phone-frame">
            <div class="phone-screen">
                <div class="flex items-center justify-between px-3.5 pt-2 text-[8px] font-semibold text-zinc-500">
                    <span>9:41</span>
                    <div class="phone-island" aria-hidden="true"></div>
                    <span class="flex items-center gap-0.5">
                        <svg class="h-2.5 w-3" viewBox="0 0 12 8" fill="currentColor"><rect x="0" y="5" width="2" height="3" rx="0.5"/><rect x="3" y="3" width="2" height="5" rx="0.5"/><rect x="6" y="1" width="2" height="7" rx="0.5"/><rect x="9" y="0" width="2" height="8" rx="0.5"/></svg>
                        <svg class="h-2.5 w-3.5" viewBox="0 0 14 10" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="0.5" y="2" width="10" height="6" rx="1"/><path d="M11.5 4v2M13 3.5v3"/></svg>
                    </span>
                </div>

                <div class="flex items-center gap-1.5 px-3.5 pb-1 pt-0.5">
                    <button type="button" class="text-zinc-600" tabindex="-1" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <p class="flex-1 text-[11px] font-bold text-zinc-900">My loan</p>
                    <button type="button" class="text-zinc-500" tabindex="-1" aria-hidden="true">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                </div>

                <p class="px-3.5 text-[8px] font-medium text-zinc-500">Payment overview · <span class="font-bold text-zinc-700">28 days</span></p>

                {{-- Countdown card --}}
                <div class="mx-3.5 mt-2 rounded-2xl bg-gradient-to-br from-brand-charcoal to-zinc-800 p-3 text-white shadow-lg">
                    <p class="text-[8px] font-semibold uppercase tracking-wider text-zinc-300">Muda uliobaki kulipia</p>
                    <div class="mt-1 flex items-end justify-between gap-2">
                        <div>
                            <p class="text-3xl font-extrabold leading-none tabular-nums">12</p>
                            <p class="text-[9px] font-bold text-zinc-300">siku zilizobaki</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold">TZS 85,000</p>
                            <p class="text-[8px] text-zinc-400">12 Jun 2026</p>
                        </div>
                    </div>
                    <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-white/20">
                        <span class="block h-full w-[68%] rounded-full bg-brand-orange"></span>
                    </div>
                    <p class="mt-1 text-[7px] font-medium text-zinc-400">68% ya mkopo umelipwa · malipo 4/6</p>
                </div>

                <div class="relative mx-3.5 mt-2.5 h-[58px]">
                    <svg viewBox="0 0 220 58" class="h-full w-full" aria-hidden="true">
                        <defs>
                            <linearGradient id="landing-pay-fill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f58220" stop-opacity="0.4"/>
                                <stop offset="100%" stop-color="#f58220" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <path d="M0 42 L30 38 L60 40 L90 28 L120 32 L150 22 L180 26 L210 16 L220 18 L220 58 L0 58 Z" fill="url(#landing-pay-fill)"/>
                        <polyline fill="none" stroke="#2d3748" stroke-width="2" stroke-linecap="round" points="0,42 30,38 60,40 90,28 120,32 150,22 180,26 210,16 220,18"/>
                        <circle cx="150" cy="22" r="4.5" fill="#18181b"/>
                    </svg>
                    <div class="absolute left-[62%] top-[-2px] rounded-full bg-zinc-900 px-1.5 py-0.5 text-[7px] font-bold text-white">+TZS 47K</div>
                </div>
                <div class="mx-3.5 flex justify-between text-[6px] font-semibold text-zinc-400">
                    <span>01 May</span><span>08 May</span><span>15 May</span><span>22 May</span><span>29 May</span>
                </div>

                <div class="mx-3.5 mt-2 grid grid-cols-2 gap-1.5">
                    <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-2 text-center">
                        <p class="text-[7px] font-semibold text-zinc-400">Imelipwa</p>
                        <p class="text-base font-extrabold text-emerald-600">79.8%</p>
                        <p class="text-[7px] font-bold text-emerald-600">0.6% ↑</p>
                    </div>
                    <div class="rounded-xl border border-zinc-100 bg-zinc-50 p-2 text-center">
                        <p class="text-[7px] font-semibold text-zinc-400">Inabaki</p>
                        <p class="text-base font-extrabold text-zinc-800">20.2%</p>
                        <p class="text-[7px] font-bold text-rose-500">12% ↓</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
