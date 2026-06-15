<x-layouts.landing>
    <div class="relative overflow-x-hidden">
        {{-- Background --}}
        <div class="pointer-events-none absolute -left-40 top-0 h-[28rem] w-[28rem] rounded-full bg-brand-blue/8 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 top-1/4 h-96 w-96 rounded-full bg-brand-accent/6 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[520px] bg-[radial-gradient(ellipse_90%_60%_at_50%_-10%,rgba(16,52,84,0.12),transparent_65%)]"></div>

        {{-- Header --}}
        <header class="relative z-20 mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-5 lg:px-10">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue/40">
                <img src="{{ asset('opticedge_credit_website_logo.png') }}" alt="Opticedge Credit" class="h-10 w-10 rounded-xl object-contain shadow-sm ring-1 ring-brand-navy/10" width="40" height="40">
                <span class="text-lg font-extrabold tracking-tight text-brand-ink">
                    Opticedge <span class="text-brand-blue">Credit</span>
                </span>
            </a>

            <nav class="hidden items-center gap-7 text-sm font-semibold text-zinc-600 md:flex" aria-label="Primary">
                <a href="#product" class="transition hover:text-brand-navy">Product</a>
                <a href="#workflow" class="transition hover:text-brand-navy">Workflow</a>
                <a href="#portals" class="transition hover:text-brand-navy">Portals</a>
                <a href="#trust" class="transition hover:text-brand-navy">Trust</a>
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-brand-navy px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-brand-navy/20 transition hover:bg-brand-navy-mid">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl border border-zinc-200 bg-white px-5 py-2.5 text-sm font-bold text-brand-ink transition hover:border-brand-navy hover:text-brand-navy">
                        HQ Login
                    </a>
                @endauth
            </div>
        </header>

        {{-- Hero --}}
        <section id="product" class="relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-6 pb-16 pt-2 lg:grid-cols-2 lg:gap-10 lg:px-10 lg:pb-24 lg:pt-4">
            <div class="max-w-xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-brand-navy shadow-sm ring-1 ring-brand-navy/10">
                    <span class="h-2 w-2 rounded-full bg-brand-accent"></span>
                    Tanzania · Device credit platform
                </p>

                <h1 class="text-4xl font-extrabold leading-[1.08] tracking-tight text-brand-ink sm:text-5xl lg:text-[3.1rem]">
                    KYC hadi mkopo —<br>
                    <span class="bg-gradient-to-r from-brand-navy to-brand-blue bg-clip-text text-transparent">mfumo mmoja</span>
                </h1>

                <p class="mt-5 max-w-lg text-lg font-medium leading-relaxed text-zinc-600">
                    Mfumo wa mikopo ya simu kwa maduka na wakala wa uwanjani:
                    <span class="font-semibold text-brand-navy">NIDA, face match, Selcom, na HQ approval</span>
                    — katika FO app, customer portal, na dashboard.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="{{ route('login') }}" class="inline-flex h-12 items-center justify-center rounded-xl bg-brand-navy px-8 text-sm font-bold text-white shadow-lg shadow-brand-navy/25 transition hover:bg-brand-navy-mid">
                        Ingia HQ Console
                    </a>
                    <a href="#portals" class="inline-flex h-12 items-center justify-center rounded-xl border border-zinc-200 bg-white px-8 text-sm font-bold text-brand-ink transition hover:border-brand-blue hover:text-brand-blue">
                        Angalia portals
                    </a>
                </div>

                <p class="mt-4 text-sm font-medium text-zinc-500">
                    FO app &amp; Customer app — simu za wakala na wateja (APK / internal release).
                </p>
            </div>

            <x-landing.hero-phones />
        </section>

        {{-- Trust strip --}}
        <section id="trust" class="relative z-10 border-y border-zinc-200/80 bg-white/80 py-6 backdrop-blur-sm">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-8 gap-y-3 px-6 text-center text-xs font-bold uppercase tracking-wide text-zinc-600 lg:px-10">
                @foreach ([
                    'NIDA KYC (7 hatua)',
                    'Face match 75%',
                    'Selcom deposits',
                    'Offline FO queue',
                    'HQ approval',
                    'IMEI & stock',
                ] as $badge)
                    <span class="inline-flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-blue"></span>
                        {{ $badge }}
                    </span>
                @endforeach
            </div>
        </section>

        {{-- Portals --}}
        <section id="portals" class="relative z-10 py-20">
            <div class="mx-auto max-w-7xl px-6 lg:px-10">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-extrabold tracking-tight text-brand-ink sm:text-4xl">Tatu za mfumo — UI moja ya brand</h2>
                    <p class="mt-3 text-base font-medium text-zinc-600">Navy hero, orange accent, na Plus Jakarta Sans — sawa na app zetu za simu.</p>
                </div>

                <div class="mt-12 grid gap-6 lg:grid-cols-3">
                    @foreach ([
                        [
                            'title' => 'Field Officer app',
                            'sw' => 'Wakala wa uwanjani',
                            'body' => '7-step KYC wizard, face scanner, offline draft queue, action inbox, na Selcom deposit prompts.',
                            'accent' => 'bg-brand-navy text-white',
                            'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                        ],
                        [
                            'title' => 'Customer portal',
                            'sw' => 'Mteja wa simu',
                            'body' => 'Phone + PIN login, ratiba ya mkopo, malipo, na makubaliano — UI ya navy hero kama FO.',
                            'accent' => 'bg-brand-blue text-white',
                            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                        ],
                        [
                            'title' => 'HQ web console',
                            'sw' => 'Timu ya ofisi',
                            'body' => 'KYC approvals, lending panel, compliance exports, face match review, na role-based access.',
                            'accent' => 'bg-brand-accent text-white',
                            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                        ],
                    ] as $portal)
                        <article class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-zinc-200/90 transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-center gap-3 px-6 py-4 {{ $portal['accent'] }}">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $portal['icon'] }}"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold">{{ $portal['title'] }}</h3>
                                    <p class="text-xs font-semibold opacity-85">{{ $portal['sw'] }}</p>
                                </div>
                            </div>
                            <p class="flex-1 px-6 py-5 text-sm leading-relaxed text-zinc-600">{{ $portal['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Workflow --}}
        <section id="workflow" class="relative z-10 border-t border-zinc-200/80 bg-white/60 py-20 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-6 lg:px-10">
                <div class="lg:grid lg:grid-cols-2 lg:gap-16 lg:items-center">
                    <div>
                        <h2 class="text-3xl font-extrabold tracking-tight text-brand-ink sm:text-4xl">Mzunguko kamili wa mkopo</h2>
                        <p class="mt-3 text-base font-medium leading-relaxed text-zinc-600">
                            Kutoka usajili wa kifaa hadi kuidhinishwa na HQ, kutoa stock, na kukusanya malipo —
                            bila Excel, bila mchakato ulioachana.
                        </p>
                        <ul class="mt-8 space-y-4">
                            @foreach ([
                                ['Hatua 1–2', 'Kifaa, NIDA, na face verification (75% pass)'],
                                ['Hatua 3–5', 'Makubaliano, deposit Selcom, na compliance'],
                                ['Hatua 6–7', 'Pre-handover checklist na submission'],
                                ['HQ', 'Stage 1 & 2 approval, manual face verify ikiwa lazima'],
                                ['Release', 'IMEI assignment, loan provisioning, collections'],
                            ] as $step)
                                <li class="flex gap-3">
                                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-blue/10 text-brand-blue">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <div>
                                        <p class="text-sm font-extrabold text-brand-ink">{{ $step[0] }}</p>
                                        <p class="text-sm text-zinc-600">{{ $step[1] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-12 rounded-3xl landing-hero-gradient p-8 text-white shadow-xl shadow-brand-navy/30 lg:mt-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-white/70">Built for Tanzania</p>
                        <h3 class="mt-2 text-2xl font-extrabold">Kwa nini dealers hutuamini</h3>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            @foreach ([
                                ['75%', 'Face match pass'],
                                ['7', 'KYC wizard steps'],
                                ['3×', 'Approval stages'],
                                ['24/7', 'API + audit trail'],
                            ] as $metric)
                                <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/15">
                                    <p class="text-2xl font-extrabold">{{ $metric[0] }}</p>
                                    <p class="text-xs font-semibold text-white/75">{{ $metric[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-6 text-sm font-medium leading-relaxed text-white/80">
                            FO inafanya kazi offline kwa drafts; sync inarudi mtandaoni.
                            HQ inaona kila hatua — hakuna foleni isiyojulikana.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section class="relative z-10 py-20">
            <div class="mx-auto max-w-7xl px-6 lg:px-10">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-extrabold tracking-tight text-brand-ink">Nguvu za kiufundi</h2>
                    <p class="mt-3 text-base font-medium text-zinc-600">Si marketing tu — hizi ni features halisi zilizopo production.</p>
                </div>

                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['Face match service', 'InsightFace buffalo_l, NIDA right-portrait ROI, na reason codes kwa FO.'],
                        ['KYC API v1', 'Wizard steps, catalog matcher, stage-flow, na signed media URLs.'],
                        ['Selcom integration', 'Deposit prompts na payment confirmation kwenye FO flow.'],
                        ['Action inbox', 'FO dashboard inaonyesha KYC, face review, na stock alerts.'],
                        ['Manual verification', 'HQ inaweza kuidhinisha face match kwa mkono ikiwa score iko review.'],
                        ['Dealer isolation', 'Kila dealer anaona data yake tu — role-based kwa HQ.'],
                    ] as $feature)
                        <article class="rounded-2xl bg-white p-6 ring-1 ring-zinc-200/90">
                            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-brand-accent-soft text-brand-accent">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h3 class="text-base font-extrabold text-brand-ink">{{ $feature[0] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-600">{{ $feature[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="relative z-10 mx-auto max-w-7xl px-6 pb-20 lg:px-10">
            <div class="overflow-hidden rounded-3xl landing-hero-gradient px-8 py-12 text-white shadow-2xl shadow-brand-navy/25 lg:flex lg:items-center lg:justify-between lg:gap-12 lg:px-14">
                <div class="max-w-xl">
                    <h2 class="text-3xl font-extrabold tracking-tight">Tayari kuendesha mikopo ya vifaa kwa kiwango?</h2>
                    <p class="mt-3 text-base font-medium text-white/80">
                        Ingia HQ console au wasiliana na timu yetu kuhusu FO app na customer portal kwa mtandao wako wa maduka.
                    </p>
                </div>
                <div class="mt-8 flex shrink-0 flex-wrap gap-4 lg:mt-0">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-8 py-3.5 text-sm font-bold text-brand-navy transition hover:bg-brand-surface">
                        HQ Login
                    </a>
                    <a href="mailto:support@opticedgeafrica.net" class="inline-flex items-center justify-center rounded-xl border border-white/35 px-8 py-3.5 text-sm font-bold transition hover:bg-white/10">
                        Wasiliana nasi
                    </a>
                </div>
            </div>
        </section>

        <footer class="relative z-10 border-t border-zinc-200/80 bg-white px-6 py-10 lg:px-10">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 text-center sm:flex-row sm:text-left">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('opticedge_credit_website_logo.png') }}" alt="" class="h-8 w-8 rounded-lg object-contain" width="32" height="32">
                    <p class="text-sm font-semibold text-zinc-600">
                        &copy; {{ date('Y') }} {{ config('app.name') }} · Opticedge Africa
                    </p>
                </div>
                <p class="text-xs font-medium text-zinc-500">
                    Fast. Secure. Verified. — Device credit for Tanzania.
                </p>
            </div>
        </footer>
    </div>
</x-layouts.landing>
