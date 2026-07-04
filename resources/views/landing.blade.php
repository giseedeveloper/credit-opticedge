<x-layouts.landing>
    <div class="relative overflow-x-hidden">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[760px] bg-[radial-gradient(circle_at_20%_10%,rgba(31,90,136,0.20),transparent_30%),radial-gradient(circle_at_85%_18%,rgba(248,120,29,0.16),transparent_28%),linear-gradient(180deg,#f8fbff_0%,#eef5fb_54%,#f8fafc_100%)]"></div>
        <div class="pointer-events-none absolute left-1/2 top-24 h-80 w-2xl -translate-x-1/2 rounded-full bg-white/70 blur-3xl"></div>

        <header class="relative z-20 mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-5 lg:px-10">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue/40">
                <img src="{{ asset('opticedge_credit_website_logo.png') }}" alt="Opticedge Credit" class="h-10 w-10 rounded-xl object-contain shadow-sm ring-1 ring-brand-navy/10" width="40" height="40">
                <span class="text-lg font-extrabold tracking-tight text-brand-ink">
                    Opticedge <span class="text-brand-blue">Credit</span>
                </span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-bold text-zinc-600 md:flex" aria-label="Primary">
                <a href="#platform" class="transition hover:text-brand-navy">Platform</a>
                <a href="#operations" class="transition hover:text-brand-navy">Operations</a>
                <a href="#control" class="transition hover:text-brand-navy">Control</a>
                <a href="#security" class="transition hover:text-brand-navy">Security</a>
            </nav>

            @auth
                <a href="{{ route('dashboard') }}" class="rounded-full bg-brand-navy px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-brand-navy/20 transition hover:bg-brand-navy-mid">
                    Open dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="rounded-full border border-zinc-200 bg-white/90 px-5 py-2.5 text-sm font-bold text-brand-ink shadow-sm backdrop-blur transition hover:border-brand-navy hover:text-brand-navy">
                    Staff sign in
                </a>
            @endauth
        </header>

        <section id="platform" class="relative z-10 mx-auto grid max-w-7xl items-center gap-10 px-6 pb-16 pt-6 lg:grid-cols-[minmax(0,1.02fr)_minmax(0,0.98fr)] lg:px-10 lg:pb-24">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-brand-navy/10 bg-white/80 px-4 py-2 text-xs font-black uppercase tracking-[0.22em] text-brand-navy shadow-sm backdrop-blur">
                    <span class="h-2 w-2 rounded-full bg-brand-accent"></span>
                    Device financing infrastructure
                </div>

                <h1 class="mt-6 text-5xl font-black leading-[0.98] tracking-[-0.055em] text-brand-ink sm:text-6xl lg:text-7xl">
                    Credit operations,
                    <span class="block bg-linear-to-r from-brand-navy via-brand-blue to-brand-accent bg-clip-text text-transparent">beautifully controlled.</span>
                </h1>

                <p class="mt-6 max-w-xl text-lg font-medium leading-8 text-zinc-600">
                    Opticedge Credit brings KYC, device inventory, face verification, payment collection, approvals, and field operations into one secure platform for modern device lenders.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}" class="inline-flex h-13 items-center justify-center rounded-2xl bg-brand-navy px-8 text-sm font-black text-white shadow-xl shadow-brand-navy/25 transition hover:-translate-y-0.5 hover:bg-brand-navy-mid">
                        Access HQ console
                    </a>
                    <a href="#operations" class="inline-flex h-13 items-center justify-center rounded-2xl border border-zinc-200 bg-white px-8 text-sm font-black text-brand-ink shadow-sm transition hover:-translate-y-0.5 hover:border-brand-blue hover:text-brand-blue">
                        Explore workflow
                    </a>
                </div>

                <div class="mt-8 grid max-w-xl grid-cols-3 gap-3">
                    @foreach ([
                        ['7-step', 'digital KYC'],
                        ['75%+', 'face match review'],
                        ['24/7', 'audit trail'],
                    ] as $stat)
                        <div class="rounded-2xl border border-white/80 bg-white/70 p-4 shadow-sm backdrop-blur">
                            <p class="text-xl font-black text-brand-ink">{{ $stat[0] }}</p>
                            <p class="mt-1 text-xs font-bold uppercase tracking-wide text-zinc-500">{{ $stat[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <x-landing.hero-phones />
        </section>

        <section class="relative z-10 border-y border-zinc-200/80 bg-white/75 py-5 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-8 gap-y-3 px-6 text-center text-xs font-black uppercase tracking-[0.16em] text-zinc-500 lg:px-10">
                @foreach (['KYC orchestration', 'Face match review', 'Selcom payments', 'Device inventory', 'Role-based access', 'Recovery workflows'] as $badge)
                    <span class="inline-flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-blue"></span>
                        {{ $badge }}
                    </span>
                @endforeach
            </div>
        </section>

        <section id="operations" class="relative z-10 py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-10">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-blue">One operating system</p>
                    <h2 class="mt-3 text-4xl font-black tracking-[-0.04em] text-brand-ink sm:text-5xl">Built for the full credit lifecycle.</h2>
                    <p class="mt-4 text-base font-medium leading-7 text-zinc-600">
                        From first customer contact to approval, device handover, repayments, and collections, every team works from the same verified record.
                    </p>
                </div>

                <div class="mt-14 grid gap-5 lg:grid-cols-3">
                    @foreach ([
                        ['Field acquisition', 'Capture customer data, verify devices, queue drafts offline, and submit clean KYC files from the field.', 'device-phone-mobile', 'bg-brand-navy text-white'],
                        ['Credit decisioning', 'Review KYC, face match results, loan readiness, and exception cases from a controlled HQ workspace.', 'shield-check', 'bg-brand-blue text-white'],
                        ['Portfolio control', 'Track balances, repayment schedules, dealer performance, stock movement, and operational risk.', 'chart-bar-square', 'bg-brand-accent text-white'],
                    ] as $card)
                        <article class="group overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-navy/10">
                            <div class="{{ $card[3] }} flex items-center justify-between p-6">
                                <div>
                                    <h3 class="text-lg font-black">{{ $card[0] }}</h3>
                                    <p class="mt-1 text-xs font-bold uppercase tracking-wide opacity-75">Production workflow</p>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                                    <flux:icon :name="$card[2]" class="size-6" />
                                </div>
                            </div>
                            <p class="p-6 text-sm font-medium leading-7 text-zinc-600">{{ $card[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="control" class="relative z-10 border-y border-zinc-200/80 bg-white/65 py-24 backdrop-blur-sm">
            <div class="mx-auto grid max-w-7xl gap-12 px-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:px-10">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-blue">Command center</p>
                    <h2 class="mt-3 text-4xl font-black tracking-[-0.04em] text-brand-ink sm:text-5xl">
                        Clear decisions, fewer blind spots.
                    </h2>
                    <p class="mt-4 text-base font-medium leading-8 text-zinc-600">
                        Give operations, credit, compliance, and management a single source of truth with permissioned access and traceable decisions.
                    </p>

                    <div class="mt-8 space-y-4">
                        @foreach ([
                            'Every KYC stage is timestamped and reviewable.',
                            'Face match exceptions can be escalated and manually approved.',
                            'Dealer and staff access is separated by role and permission.',
                            'Payments, inventory, customers, and loans stay connected.',
                        ] as $item)
                            <div class="flex gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-blue/10 text-brand-blue">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <p class="text-sm font-bold leading-6 text-zinc-700">{{ $item }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-4xl bg-brand-navy p-4 shadow-2xl shadow-brand-navy/25">
                    <div class="rounded-3xl border border-white/10 bg-white/8 p-5 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-white/55">Live overview</p>
                                <h3 class="mt-1 text-2xl font-black">Credit portfolio health</h3>
                            </div>
                            <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-black text-emerald-200">Operational</span>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                ['TZS 125.4M', 'Active principal'],
                                ['428', 'Verified customers'],
                                ['96.8%', 'KYC completion'],
                                ['12 min', 'Median approval time'],
                            ] as $metric)
                                <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                                    <p class="text-2xl font-black">{{ $metric[0] }}</p>
                                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-white/55">{{ $metric[1] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-5 rounded-2xl bg-white p-4 text-brand-ink">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-black">Approval queue</p>
                                <p class="text-xs font-bold text-zinc-500">Today</p>
                            </div>
                            <div class="mt-4 space-y-3">
                                @foreach ([
                                    ['Face review', '3 cases', 'bg-amber-100 text-amber-800'],
                                    ['Deposit confirmed', '9 customers', 'bg-emerald-100 text-emerald-800'],
                                    ['Device release', '5 ready', 'bg-sky-100 text-sky-800'],
                                ] as $row)
                                    <div class="flex items-center justify-between rounded-xl bg-zinc-50 px-3 py-2">
                                        <span class="text-sm font-bold">{{ $row[0] }}</span>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $row[2] }}">{{ $row[1] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="security" class="relative z-10 py-24">
            <div class="mx-auto max-w-7xl px-6 lg:px-10">
                <div class="grid gap-5 lg:grid-cols-4">
                    <div class="rounded-3xl bg-brand-ink p-7 text-white lg:col-span-2">
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-white/50">Enterprise posture</p>
                        <h2 class="mt-3 text-4xl font-black tracking-[-0.04em]">Designed for high-trust credit operations.</h2>
                        <p class="mt-4 text-sm font-medium leading-7 text-white/70">
                            Admin MFA, recovery codes, activity logging, signed media access, approval controls, and role-aware navigation reduce operational risk.
                        </p>
                    </div>

                    @foreach ([
                        ['Admin MFA', 'Authenticator-first sign-in with optional email OTP fallback.'],
                        ['Auditability', 'Security, approvals, KYC, and operational changes are traceable.'],
                        ['Data control', 'Dealer and staff views respect permissions and ownership boundaries.'],
                        ['Resilience', 'Offline field capture and recovery workflows keep teams moving.'],
                    ] as $feature)
                        <article class="rounded-3xl border border-zinc-200 bg-white p-7 shadow-sm">
                            <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-accent-soft text-brand-accent">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h3 class="text-base font-black text-brand-ink">{{ $feature[0] }}</h3>
                            <p class="mt-2 text-sm font-medium leading-7 text-zinc-600">{{ $feature[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="relative z-10 mx-auto max-w-7xl px-6 pb-24 lg:px-10">
            <div class="overflow-hidden rounded-4xl landing-hero-gradient p-8 text-white shadow-2xl shadow-brand-navy/25 sm:p-10 lg:flex lg:items-center lg:justify-between lg:gap-12">
                <div class="max-w-2xl">
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-white/55">Ready for operations</p>
                    <h2 class="mt-3 text-3xl font-black tracking-[-0.035em] sm:text-4xl">Run device financing with the control your team deserves.</h2>
                    <p class="mt-4 text-sm font-medium leading-7 text-white/75">
                        Sign in to the HQ console or contact Opticedge Africa to configure the full field and customer experience.
                    </p>
                </div>
                <div class="mt-8 flex shrink-0 flex-wrap gap-3 lg:mt-0">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-7 py-3.5 text-sm font-black text-brand-navy transition hover:bg-brand-surface">
                        Staff sign in
                    </a>
                    <a href="mailto:support@opticedgeafrica.net" class="inline-flex items-center justify-center rounded-2xl border border-white/30 px-7 py-3.5 text-sm font-black transition hover:bg-white/10">
                        Contact support
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
                <p class="text-xs font-bold uppercase tracking-wide text-zinc-400">
                    Secure device credit infrastructure.
                </p>
            </div>
        </footer>
    </div>
</x-layouts.landing>
