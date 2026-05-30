<x-layouts.landing>
    <div class="relative overflow-x-hidden">
        {{-- Soft background orbs --}}
        <div class="pointer-events-none absolute -left-32 top-20 h-96 w-96 rounded-full bg-white/70 blur-3xl"></div>
        <div class="pointer-events-none absolute right-0 top-1/3 h-[28rem] w-[28rem] rounded-full bg-white/80 blur-3xl"></div>

        {{-- Navigation --}}
        <header class="relative z-20 mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-10">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange/40">
                <img src="{{ asset('opticedgecredity.jpeg') }}" alt="" class="h-9 w-9 rounded-lg object-cover shadow-sm" width="36" height="36">
                <span class="text-lg font-bold tracking-tight">
                    <span class="text-brand-charcoal">opticedge</span><span class="text-brand-orange"> credit</span>
                </span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-semibold tracking-wide text-zinc-600 md:flex" aria-label="Primary">
                <a href="#home" class="transition hover:text-black">HOME</a>
                <a href="#apps" class="transition hover:text-black">APPS</a>
                <a href="#features" class="transition hover:text-black">FEATURES</a>
                <a href="#about" class="transition hover:text-black">ABOUT</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full border border-zinc-300 bg-white px-5 py-2 text-sm font-bold tracking-wide transition hover:border-black">
                        DASHBOARD
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-full border border-zinc-300 bg-white px-5 py-2 text-sm font-bold tracking-wide transition hover:border-black hover:bg-black hover:text-white">
                        LOGIN
                    </a>
                @endauth
            </div>
        </header>

        {{-- Hero --}}
        <section id="home" class="relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-6 pb-20 pt-4 lg:grid-cols-2 lg:gap-8 lg:px-10 lg:pb-28 lg:pt-8">
            <div class="max-w-xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-zinc-500 shadow-sm ring-1 ring-black/5">
                    <span class="h-2 w-2 rounded-full bg-brand-orange"></span>
                    Tanzania · Device credit platform
                </p>

                <h1 class="text-4xl font-extrabold leading-[1.08] tracking-tight text-black sm:text-5xl lg:text-[3.35rem]">
                    Mobile Financing<br>Made Easy
                </h1>

                <p class="mt-5 max-w-md text-lg font-medium leading-relaxed text-zinc-600">
                    All-in-one financing apps with an easy-to-use dashboard for field officers, HQ teams, and customers.
                </p>

                <form action="{{ route('login') }}" method="get" class="mt-8 flex max-w-md flex-col gap-3 sm:flex-row sm:items-stretch">
                    <label class="sr-only" for="lead-email">Email</label>
                    <input
                        id="lead-email"
                        type="email"
                        name="email"
                        placeholder="Your email"
                        class="h-12 flex-1 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-medium text-zinc-800 shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-black focus:ring-2 focus:ring-black/10"
                    >
                    <button type="submit" class="h-12 shrink-0 rounded-xl bg-black px-8 text-sm font-bold tracking-wide text-white shadow-lg shadow-black/20 transition hover:bg-zinc-800">
                        GET STARTED
                    </button>
                </form>

                <div id="apps" class="mt-8 flex flex-wrap items-center gap-4">
                    <a href="#" class="inline-flex items-center gap-2 rounded-xl bg-zinc-900 px-4 py-2.5 text-white shadow-md transition hover:bg-black" aria-label="Download on the App Store">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                        <span class="text-left leading-tight">
                            <span class="block text-[10px] font-medium opacity-80">Download on the</span>
                            <span class="block text-sm font-bold">App Store</span>
                        </span>
                    </a>
                    <a href="#" class="inline-flex items-center gap-2 rounded-xl bg-zinc-900 px-4 py-2.5 text-white shadow-md transition hover:bg-black" aria-label="Get it on Google Play">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.609 1.814L13.792 12 3.61 22.186a1.004 1.004 0 0 1-.61-.92V2.734a1.004 1.004 0 0 1 .609-.92zm10.89 10.893 2.302 2.302-10.937 6.333a1.004 1.004 0 0 1-.445-.106l8.08-8.529zm3.185-3.186 2.853 1.65a1.004 1.004 0 0 1 0 1.738l-2.853 1.65-2.515-2.515 2.515-2.523zM5.864 2.658l8.08 8.529-2.302 2.302L3.61 2.764a1.004 1.004 0 0 1 2.254-.106z"/></svg>
                        <span class="text-left leading-tight">
                            <span class="block text-[10px] font-medium opacity-80">Get it on</span>
                            <span class="block text-sm font-bold">Google Play</span>
                        </span>
                    </a>
                </div>
            </div>

            <x-landing.hero-phones />
        </section>

        {{-- Features --}}
        <section id="features" class="relative z-10 border-t border-white/60 bg-white/50 py-20 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-6 lg:px-10">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-extrabold tracking-tight text-black sm:text-4xl">Built for the full credit lifecycle</h2>
                    <p class="mt-3 text-base font-medium text-zinc-600">From field KYC to HQ approval, collections, and customer self-service — one platform.</p>
                </div>

                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['title' => 'Field Officer app', 'body' => '7-step KYC wizard, offline queue, face match, and Selcom deposit prompts.', 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
                        ['title' => 'Customer portal', 'body' => 'Phone + PIN login, loan schedule, payments, and agreement access on mobile.', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['title' => 'HQ dashboard', 'body' => 'Executive analytics, lending panel, compliance exports, and role-based access.', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ] as $feature)
                        <article class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-zinc-200/80 transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-oe-soft text-brand-orange">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-zinc-900">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-600">{{ $feature['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- About / CTA --}}
        <section id="about" class="relative z-10 mx-auto max-w-7xl px-6 py-20 lg:px-10">
            <div class="overflow-hidden rounded-3xl bg-brand-charcoal px-8 py-12 text-white shadow-2xl lg:flex lg:items-center lg:justify-between lg:gap-12 lg:px-14">
                <div class="max-w-xl">
                    <h2 class="text-3xl font-extrabold tracking-tight">Ready to run device credit at scale?</h2>
                    <p class="mt-3 text-base font-medium text-zinc-300">Sign in to the HQ console or talk to our team about deploying Opticedge Credit for your dealer network.</p>
                </div>
                <div class="mt-8 flex shrink-0 flex-wrap gap-4 lg:mt-0">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-orange px-8 py-3.5 text-sm font-bold tracking-wide text-white transition hover:bg-brand-orange-hover">
                        HQ LOGIN
                    </a>
                    <a href="mailto:support@opticedgeafrica.net" class="inline-flex items-center justify-center rounded-xl border border-white/30 px-8 py-3.5 text-sm font-bold tracking-wide transition hover:bg-white/10">
                        CONTACT US
                    </a>
                </div>
            </div>
        </section>

        <footer class="relative z-10 border-t border-zinc-200/80 bg-white/60 px-6 py-8 text-center text-sm font-medium text-zinc-500 lg:px-10">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Inspired by modern fintech landing patterns.</p>
        </footer>
    </div>
</x-layouts.landing>
