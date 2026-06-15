{{-- iPhone-style hero devices — FO dashboard + Customer portal mockups --}}
<div class="relative mx-auto flex h-[520px] w-full max-w-xl items-center justify-center sm:h-[560px] lg:h-[600px] lg:max-w-none">
    <div class="animate-landing-drift absolute h-64 w-64 rounded-full bg-gradient-to-br from-brand-blue/12 via-white to-brand-accent-soft shadow-[0_40px_80px_-24px_rgba(16,52,84,0.22)] ring-1 ring-brand-navy/5 lg:h-80 lg:w-80"></div>

    {{-- iPhone 1: Field Officer — Action inbox --}}
    <div class="iphone-device animate-landing-float relative z-10 w-[218px] -translate-x-3 sm:w-[240px] lg:w-[258px]" role="img" aria-label="Demo: Field Officer dashboard on iPhone">
        <span class="iphone-btn iphone-btn--mute" aria-hidden="true"></span>
        <span class="iphone-btn iphone-btn--vol-up" aria-hidden="true"></span>
        <span class="iphone-btn iphone-btn--vol-down" aria-hidden="true"></span>
        <span class="iphone-btn iphone-btn--power" aria-hidden="true"></span>

        <div class="iphone-frame">
            <div class="iphone-bezel">
                <div class="iphone-screen flex flex-col">
                    <div class="iphone-dynamic-island" aria-hidden="true"></div>

                    <div class="iphone-status iphone-status--dark text-zinc-900">
                        <span>9:41</span>
                        <span class="iphone-icons text-zinc-800">
                            <svg class="h-2.5 w-3.5" viewBox="0 0 16 12" fill="currentColor" aria-hidden="true">
                                <path d="M1 9.5h1.5V12H1V9.5zm3-2.5h1.5V12H4V7zm3-2h1.5V12H7V5zm3-2.5h1.5V12H10V2.5z"/>
                            </svg>
                            <svg class="h-2.5 w-3.5" viewBox="0 0 16 12" fill="currentColor" aria-hidden="true">
                                <path d="M8 2.5C5.5 2.5 3.2 3.6 1.5 5.5l1.2 1.2C4.2 5 6 4 8 4s3.8 1 5.3 2.7l1.2-1.2C12.8 3.6 10.5 2.5 8 2.5zm0 3c-1.6 0-3 .8-3.9 2l1.2 1.2c.6-.8 1.5-1.2 2.7-1.2s2.1.4 2.7 1.2l1.2-1.2C11 6.3 9.6 5.5 8 5.5zM8 9.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/>
                            </svg>
                            <svg class="h-3 w-5" viewBox="0 0 24 12" fill="none" aria-hidden="true">
                                <rect x="0.5" y="0.5" width="20" height="11" rx="2.5" stroke="currentColor" stroke-width="1"/>
                                <rect x="2" y="2" width="15" height="8" rx="1.5" fill="currentColor"/>
                                <rect x="21.5" y="4" width="2" height="4" rx="1" fill="currentColor"/>
                            </svg>
                        </span>
                    </div>

                    <div class="landing-hero-gradient mx-2.5 rounded-2xl px-3.5 pb-3.5 pt-2 shadow-lg shadow-brand-navy/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[7px] font-bold uppercase tracking-widest text-white/65">Opticedge FO</p>
                                <p class="text-[14px] font-extrabold leading-tight text-white">Action inbox</p>
                            </div>
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/20">
                                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </div>
                        </div>
                        <p class="mt-0.5 text-[8px] font-medium text-white/75">3 vinahitaji hatua leo</p>
                        <div class="mt-2.5 grid grid-cols-3 gap-1.5">
                            @foreach ([['KYC', '2', 'bg-white/14'], ['Face', '1', 'bg-brand-accent/25 ring-1 ring-brand-accent/40'], ['Stock', '0', 'bg-white/14']] as $stat)
                                <div class="rounded-xl {{ $stat[2] }} px-1.5 py-2 text-center">
                                    <p class="text-[7px] font-semibold text-white/70">{{ $stat[0] }}</p>
                                    <p class="text-[12px] font-extrabold text-white">{{ $stat[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mx-2.5 -mt-1.5 flex-1 space-y-1.5 px-0.5">
                        <div class="rounded-2xl border border-zinc-200/80 bg-white/95 p-2.5 shadow-sm backdrop-blur-sm">
                            <div class="flex items-start gap-2">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-accent-soft to-white text-brand-accent shadow-sm ring-1 ring-brand-accent/15">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[9px] font-extrabold text-zinc-900">Face match · review</p>
                                    <p class="text-[7px] font-medium text-zinc-500">Asha M. · 62% · NIDA blur</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[6px] font-extrabold text-amber-800">HQ</span>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-zinc-200/80 bg-white/95 p-2.5 shadow-sm">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[9px] font-extrabold text-zinc-900">KYC step 4 · ready</p>
                                    <p class="text-[7px] font-medium text-zinc-500">Samsung A15 · IMEI verified</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="iphone-tab-bar mx-2.5 mb-1 mt-1 rounded-2xl">
                        @foreach ([
                            ['Home', true, 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ['KYC', false, 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['Mkopo', false, 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['Zaidi', false, 'M4 6h16M4 12h16M4 18h16'],
                        ] as $tab)
                            <div class="iphone-tab {{ $tab[1] ? 'text-brand-navy' : 'text-zinc-400' }}">
                                <svg fill="none" stroke="currentColor" stroke-width="{{ $tab[1] ? '2.2' : '1.8' }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab[2] }}"/></svg>
                                <span>{{ $tab[0] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="iphone-home-bar"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- iPhone 2: Customer — loan + face scan --}}
    <div class="iphone-device animate-landing-float-delayed absolute right-0 z-20 w-[208px] translate-y-14 sm:w-[228px] sm:translate-y-16 lg:right-4 lg:w-[248px] lg:translate-y-20" role="img" aria-label="Demo: customer loan and face verification on iPhone">
        <span class="iphone-btn iphone-btn--mute" aria-hidden="true"></span>
        <span class="iphone-btn iphone-btn--vol-up" aria-hidden="true"></span>
        <span class="iphone-btn iphone-btn--vol-down" aria-hidden="true"></span>
        <span class="iphone-btn iphone-btn--power" aria-hidden="true"></span>

        <div class="iphone-frame">
            <div class="iphone-bezel">
                <div class="iphone-screen iphone-screen--dark flex flex-col">
                    <div class="iphone-dynamic-island" aria-hidden="true"></div>

                    <div class="iphone-status iphone-status--light">
                        <span>9:41</span>
                        <span class="iphone-icons text-white">
                            <svg class="h-2.5 w-3.5" viewBox="0 0 16 12" fill="currentColor" aria-hidden="true">
                                <path d="M1 9.5h1.5V12H1V9.5zm3-2.5h1.5V12H4V7zm3-2h1.5V12H7V5zm3-2.5h1.5V12H10V2.5z"/>
                            </svg>
                            <svg class="h-2.5 w-3.5" viewBox="0 0 16 12" fill="currentColor" aria-hidden="true">
                                <path d="M8 2.5C5.5 2.5 3.2 3.6 1.5 5.5l1.2 1.2C4.2 5 6 4 8 4s3.8 1 5.3 2.7l1.2-1.2C12.8 3.6 10.5 2.5 8 2.5zm0 3c-1.6 0-3 .8-3.9 2l1.2 1.2c.6-.8 1.5-1.2 2.7-1.2s2.1.4 2.7 1.2l1.2-1.2C11 6.3 9.6 5.5 8 5.5zM8 9.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/>
                            </svg>
                            <svg class="h-3 w-5" viewBox="0 0 24 12" fill="none" aria-hidden="true">
                                <rect x="0.5" y="0.5" width="20" height="11" rx="2.5" stroke="currentColor" stroke-width="1"/>
                                <rect x="2" y="2" width="15" height="8" rx="1.5" fill="currentColor"/>
                                <rect x="21.5" y="4" width="2" height="4" rx="1" fill="currentColor"/>
                            </svg>
                        </span>
                    </div>

                    <div class="landing-hero-gradient mx-2.5 rounded-2xl p-3 shadow-lg shadow-brand-navy/30">
                        <p class="text-[8px] font-bold uppercase tracking-wider text-white/65">Mkopo wangu</p>
                        <p class="mt-0.5 text-[1.35rem] font-extrabold leading-none tracking-tight text-white">TZS 408K</p>
                        <p class="mt-0.5 text-[8px] font-medium text-white/70">salio · Samsung A15</p>
                        <div class="mt-2.5 h-2 overflow-hidden rounded-full bg-white/15 ring-1 ring-white/10">
                            <span class="block h-full w-[68%] rounded-full bg-gradient-to-r from-brand-accent to-orange-300 shadow-sm"></span>
                        </div>
                        <p class="mt-1 text-[7px] font-semibold text-white/60">68% umelipwa · malipo 4/6</p>
                    </div>

                    {{-- Face scan viewport (iOS camera style) --}}
                    <div class="relative mx-2.5 mt-2.5 flex-1 overflow-hidden rounded-2xl bg-gradient-to-b from-zinc-900 to-black ring-1 ring-white/10">
                        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(16,52,84,0.35),transparent_70%)]"></div>
                        <div class="relative flex h-full flex-col items-center justify-center px-3 py-4">
                            <div class="animate-scan-pulse relative">
                                <div class="h-[4.5rem] w-[3.75rem] rounded-[48%] bg-gradient-to-b from-zinc-600/80 to-zinc-800/90 shadow-inner ring-2 ring-emerald-400/70"></div>
                                <span class="absolute -left-2 -top-2 h-4 w-4 border-l-2 border-t-2 border-emerald-400 rounded-tl-sm"></span>
                                <span class="absolute -right-2 -top-2 h-4 w-4 border-r-2 border-t-2 border-emerald-400 rounded-tr-sm"></span>
                                <span class="absolute -bottom-2 -left-2 h-4 w-4 border-b-2 border-l-2 border-emerald-400 rounded-bl-sm"></span>
                                <span class="absolute -bottom-2 -right-2 h-4 w-4 border-b-2 border-r-2 border-emerald-400 rounded-br-sm"></span>
                            </div>
                            <p class="mt-3 text-[9px] font-extrabold text-emerald-400">Uso umeonekana</p>
                            <p class="text-[7px] font-medium text-white/50">Skani ya NIDA · 75% pass</p>
                            <div class="mt-2.5 flex items-center gap-1 rounded-full bg-white/8 px-2.5 py-1 ring-1 ring-white/10">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                                <p class="text-[6px] font-bold text-white/70">Live preview · si matokeo ya server</p>
                            </div>
                        </div>
                    </div>

                    <div class="mx-2.5 mt-2 grid grid-cols-2 gap-1.5">
                        <div class="rounded-xl bg-white/8 p-2 text-center ring-1 ring-white/10 backdrop-blur-sm">
                            <p class="text-[7px] font-semibold text-white/45">Malipo</p>
                            <p class="text-sm font-extrabold text-white">85K</p>
                        </div>
                        <div class="rounded-xl bg-white/8 p-2 text-center ring-1 ring-white/10 backdrop-blur-sm">
                            <p class="text-[7px] font-semibold text-white/45">Siku</p>
                            <p class="text-sm font-extrabold text-brand-accent">12</p>
                        </div>
                    </div>

                    <div class="mx-2.5 mt-2 mb-1 flex items-center justify-center gap-1.5 rounded-xl bg-brand-accent/15 py-2 ring-1 ring-brand-accent/25">
                        <svg class="h-3 w-3 text-brand-accent" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                        <p class="text-[7px] font-extrabold text-brand-accent">Selcom · deposit confirmed</p>
                    </div>
                    <div class="iphone-home-bar iphone-home-bar--light"></div>
                </div>
            </div>
        </div>
    </div>
</div>
