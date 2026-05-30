<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script>if(localStorage.getItem('sb-collapsed')==='true')document.documentElement.classList.add('sidebar-collapsed');</script>
        <style>
            [x-cloak] { display: none !important; }

            [data-flux-sidebar] {
                transition: width 0.25s cubic-bezier(.4,0,.2,1);
                width: 256px;
            }
            html.sidebar-collapsed [data-flux-sidebar] { width: 64px; }

            /* Hide scrollbar everywhere but keep scroll */
            .sb-nav {
                scrollbar-width: none;        /* Firefox */
                -ms-overflow-style: none;     /* IE / Edge */
                overflow-x: hidden;
            }
            .sb-nav::-webkit-scrollbar { display: none; }  /* Chrome / Safari */

            /* wire:navigate — loading dim only on main column (see layouts/app.blade.php #app-shell-main) */
            #app-shell-main.is-livewire-navigating {
                position: relative;
                transition: opacity 0.15s ease;
            }
            #app-shell-main.is-livewire-navigating::before {
                content: '';
                position: absolute;
                inset: 0;
                z-index: 20;
                pointer-events: none;
                background: color-mix(in srgb, var(--color-page, #eef0f4) 22%, transparent);
            }
            .dark #app-shell-main.is-livewire-navigating::before {
                background: color-mix(in srgb, #09090b 18%, transparent);
            }
        </style>
    </head>
    <body class="min-h-screen bg-page font-sans text-brand-charcoal antialiased selection:bg-brand-orange/20 lg:flex lg:h-screen lg:overflow-hidden">

        {{-- Sidebar: stashable mobile + custom Alpine icon-only collapse desktop --}}
        <flux:sidebar stashable sticky
            class="app-shell-sidebar flex flex-col border-e border-zinc-200/90 bg-[var(--color-sidebar)] p-0 shadow-sm ring-1 ring-black/5"
            x-data
        >
            {{-- Mobile close --}}
            <flux:sidebar.toggle class="absolute top-3 right-3 z-10 cursor-pointer text-zinc-400 hover:text-brand-charcoal lg:hidden" icon="x-mark" />

            {{-- Brand: full wordmark expanded; compact monogram when collapsed (64px rail) --}}
            <div class="shrink-0 border-b border-zinc-200/90 bg-white/80 backdrop-blur-sm"
                 :class="$store.sidebar.open ? 'px-3 py-4' : 'px-0 py-3'">
                <div x-show="$store.sidebar.open" x-transition.opacity class="px-1">
                    <x-brand.wordmark class="text-lg sm:text-xl" />
                </div>
                <div x-show="!$store.sidebar.open" x-cloak class="flex w-full justify-center px-0">
                    <a href="{{ route('dashboard') }}" wire:navigate
                       title="{{ __('Opticedge Credit') }}"
                       class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl bg-brand-charcoal shadow-sm ring-1 ring-black/10 transition-colors hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange/50 focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--color-sidebar)]">
                        <span class="sr-only">{{ __('Opticedge Credit') }}</span>
                        <span class="text-[15px] font-black leading-none text-brand-orange" aria-hidden="true">O</span>
                    </a>
                </div>
            </div>

            {{-- Navigation --}}
            <nav id="app-sidebar-nav" class="sb-nav flex-1 overflow-y-auto px-2.5 py-3 space-y-2">
                @php
                $user = auth()->user();
                $navGroups = [
                    ['icon'=>'chart-bar-square','label'=>'Business Insights','active'=>request()->routeIs('dashboard'),'links'=>[
                        ['href'=>route('dashboard'),'label'=>'Dashboard','icon'=>'home','ability'=>'dashboard.view','active'=>request()->routeIs('dashboard')],
                    ]],
                    ['icon'=>'device-phone-mobile','label'=>'Device Catalog','active'=>request()->routeIs('stock.brands','stock.imei'),'links'=>[
                        ['href'=>route('stock.brands'),'label'=>'Brands & Models','icon'=>'tag','ability'=>'products.view','active'=>request()->routeIs('stock.brands')],
                        ['href'=>route('stock.imei'),'label'=>'IMEI Search','icon'=>'magnifying-glass','ability'=>'devices.view','active'=>request()->routeIs('stock.imei')],
                    ]],
                    ['icon'=>'shield-check','label'=>'KYC & Trust','active'=>request()->routeIs('kyc.*'),'links'=>[
                        ['href'=>route('kyc.pending'),'label'=>'Pending Verifications','icon'=>'clock','ability'=>'loans.view','active'=>request()->routeIs('kyc.pending')],
                        ['href'=>route('kyc.customers'),'label'=>'Verified Customers','icon'=>'user-group','ability'=>'loans.view','active'=>request()->routeIs('kyc.customers')],
                        ['href'=>route('kyc.wizard'),'label'=>'New KYC Wizard','icon'=>'user-plus','ability'=>'loans.create','active'=>request()->routeIs('kyc.wizard')],
                    ]],
                    ['icon'=>'credit-card','label'=>'Lending Engine','active'=>request()->routeIs('credit.*'),'links'=>[
                        ['href'=>route('credit.panel'),'label'=>'Active Loans','icon'=>'banknotes','ability'=>'loans.view','active'=>request()->routeIs('credit.panel')],
                        ['href'=>route('credit.defaulters'),'label'=>'Arrears & Defaults','icon'=>'exclamation-triangle','ability'=>'loans.view','active'=>request()->routeIs('credit.defaulters')],
                        ['href'=>route('credit.schedules'),'label'=>'Repayment Schedules','icon'=>'calendar-days','ability'=>'loans.view','active'=>request()->routeIs('credit.schedules')],
                        ['href'=>route('credit.calculator'),'label'=>'Loan Calculator','icon'=>'calculator','ability'=>'calculator.view','active'=>request()->routeIs('credit.calculator')],
                    ]],

                    ['icon'=>'banknotes','label'=>'Financial Hub','active'=>request()->routeIs('financials.*'),'links'=>[
                        ['href'=>route('financials.collections'),'label'=>'Collections','icon'=>'currency-dollar','ability'=>'accounting.view','active'=>request()->routeIs('financials.collections')],
                        ['href'=>route('financials.accounting'),'label'=>'Accounting Workspace','icon'=>'calculator','ability'=>'accounting.view','active'=>request()->routeIs('financials.accounting')],
                    ]],
                    ['icon'=>'cog-6-tooth','label'=>'System Control','active'=>request()->routeIs('comms.*','audits.*','access','settings.*','staff.*','dealers.*'),'links'=>[
                        ['href'=>route('comms.sms'),'label'=>'SMS Center','icon'=>'chat-bubble-left-right','ability'=>'sms_campaign.view','active'=>request()->routeIs('comms.sms')],
                        ['href'=>route('comms.audit'),'label'=>'Audit Trail','icon'=>'eye','ability'=>'reports.view','active'=>request()->routeIs('comms.audit')],
                        ['href'=>route('audits.logs'),'label'=>'Forensic Logs','icon'=>'shield-exclamation','ability'=>'reports.view','active'=>request()->routeIs('audits.logs')],
                        ['href'=>route('dealers.index'),'label'=>'Dealers','icon'=>'building-office-2','ability'=>'dealers.view','active'=>request()->routeIs('dealers.*')],
                        ['href'=>route('staff.index'),'label'=>'Staff Management','icon'=>'users','ability'=>'staff.view','active'=>request()->routeIs('staff.*')],
                        ['href'=>route('access'),'label'=>'Roles & Permissions','icon'=>'key','ability'=>'access.view','active'=>request()->routeIs('access')],
                        ['href'=>route('settings.integrations'),'label'=>'Integrations','icon'=>'puzzle-piece','ability'=>'settings.view','active'=>request()->routeIs('settings.integrations')],
                        ['href'=>route('settings.health'),'label'=>'System Health','icon'=>'heart','ability'=>'settings.view','active'=>request()->routeIs('settings.health')],
                    ]],
                ];

                $navGroups = collect($navGroups)
                    ->map(function ($group) use ($user) {
                        $group['links'] = collect($group['links'])
                            ->filter(fn ($link) => $user?->canAccess($link['ability']))
                            ->values()
                            ->all();
                        $group['active'] = collect($group['links'])->contains(fn ($link) => $link['active']);

                        return $group;
                    })
                    ->filter(fn ($group) => count($group['links']) > 0)
                    ->values()
                    ->all();
                @endphp

                @foreach($navGroups as $group)
                <div class="relative"
                     :class="$store.sidebar.open ? 'rounded-xl border border-zinc-200/80 bg-white/90 p-1.5 shadow-sm ring-1 ring-black/5 backdrop-blur-sm' : ''">

                    {{-- EXPANDED: section label --}}
                    <div x-show="$store.sidebar.open" x-transition.opacity
                         class="flex items-center gap-2 px-2.5 pb-1.5 pt-1.5">
                        <flux:icon :name="$group['icon']" class="size-4 shrink-0 {{ $group['active'] ? 'text-brand-orange' : 'text-zinc-400' }}" />
                        <span class="text-[10px] font-bold tracking-widest uppercase truncate
                              {{ $group['active'] ? 'text-brand-orange' : 'text-zinc-500' }}">
                            {{ $group['label'] }}
                        </span>
                    </div>

                    {{-- EXPANDED: sub-items --}}
                    <div x-show="$store.sidebar.open"
                         class="space-y-0.5 px-1 pb-1">
                        @foreach($group['links'] as $link)
                        <a href="{{ $link['href'] }}" wire:navigate
                           class="group flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] cursor-pointer motion-safe:transition-colors motion-safe:duration-200
                               {{ $link['active'] ? 'oe-nav-link-active' : 'oe-nav-link' }}">
                            <flux:icon :name="$link['icon']" class="size-[1.125rem] shrink-0 {{ $link['active'] ? 'text-brand-orange' : 'text-zinc-400 group-hover:text-zinc-600' }}" />
                            {{ $link['label'] }}
                        </a>
                        @endforeach
                    </div>

                    {{-- COLLAPSED: icon rail + tooltips --}}
                    <div x-show="!$store.sidebar.open" x-cloak class="space-y-1 px-1 py-0.5">
                        @foreach($group['links'] as $link)
                        <div x-data="{ hover: false, ty: 0 }"
                             @mouseenter="hover = true; ty = $el.getBoundingClientRect().top + $el.getBoundingClientRect().height / 2"
                             @mouseleave="hover = false">
                            <a href="{{ $link['href'] }}" wire:navigate
                               class="flex w-full cursor-pointer items-center justify-center rounded-lg py-2.5 motion-safe:transition-colors motion-safe:duration-200
                                   {{ $link['active']
                                       ? 'bg-brand-orange/15 text-brand-orange ring-1 ring-brand-orange/25 shadow-sm'
                                       : 'text-zinc-500 hover:bg-zinc-100 hover:text-brand-charcoal' }}">
                                <flux:icon :name="$link['icon']" class="size-5 shrink-0 {{ $link['active'] ? 'text-brand-orange' : 'text-zinc-500' }}" />
                            </a>
                            <template x-teleport="body">
                                <div x-show="hover"
                                     x-transition.opacity.duration.150ms
                                     :style="`top: ${ty}px; left: 72px; transform: translateY(-50%)`"
                                     class="fixed z-[9999] pointer-events-none
                                            rounded-lg border border-zinc-200 bg-white
                                            px-3 py-1.5 text-[12px] font-medium text-brand-charcoal shadow-lg ring-1 ring-black/5 whitespace-nowrap">
                                    {{ $link['label'] }}
                                </div>
                            </template>
                        </div>
                        @endforeach
                        <div class="mx-2 my-1.5 h-px bg-zinc-200/90"></div>
                    </div>

                </div>
                @endforeach
            </nav>

        </flux:sidebar>

        {{-- Desktop Header --}}
        <flux:header class="hidden h-14 items-center justify-between border-b border-zinc-200/90 bg-white/90 px-4 shadow-sm ring-1 ring-black/5 backdrop-blur-md supports-[backdrop-filter]:bg-white/80 lg:flex">
            {{-- Hamburger toggle --}}
            <button
                x-data
                @click="$store.sidebar.toggle()"
                type="button"
                :title="$store.sidebar.open ? 'Collapse sidebar' : 'Expand sidebar'"
                class="flex h-9 w-9 cursor-pointer flex-col items-center justify-center gap-[4.5px] rounded-lg border border-zinc-200 bg-zinc-50 text-zinc-600 motion-safe:transition-colors motion-safe:duration-200 hover:border-brand-orange/30 hover:bg-brand-orange/8 hover:text-brand-charcoal active:scale-95"
            >
                <span class="block h-[2px] w-[18px] bg-current rounded-full transition-all duration-200"></span>
                <span class="block h-[2px] bg-current rounded-full transition-all duration-200"
                      :class="$store.sidebar.open ? 'w-[13px]' : 'w-[18px]'"></span>
                <span class="block h-[2px] w-[18px] bg-current rounded-full transition-all duration-200"></span>
            </button>

            {{-- Search --}}
            <div class="mx-4 max-w-xl flex-1" x-data>
                <flux:input
                    x-ref="search"
                    @keydown.window.prevent.cmd.k="$refs.search.focus()"
                    @keydown.window.prevent.ctrl.k="$refs.search.focus()"
                    icon="magnifying-glass"
                    placeholder="Search (Cmd+K)..."
                    class="w-full border-zinc-200 bg-zinc-50/90 focus-within:border-black"
                />
            </div>

            {{-- Right actions --}}
            <div class="flex items-center gap-3">
                @livewire('notifications.alert-bell')

                {{-- User avatar + dropdown --}}
                <flux:dropdown position="bottom" align="end">
                    <button type="button"
                        class="flex size-9 cursor-pointer items-center justify-center rounded-full bg-gradient-to-br from-brand-orange to-brand-orange-hover ring-2 ring-brand-orange/30 motion-safe:transition-all motion-safe:duration-200 hover:shadow-md hover:shadow-brand-orange/20 hover:ring-brand-orange/50">
                        <span class="text-xs font-bold text-white uppercase select-none">
                            {{ implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', auth()->user()->name), 0, 2))) }}
                        </span>
                    </button>
                    <flux:menu class="w-56">
                        <div class="px-3 py-2.5 border-b border-border">
                            <div class="text-sm font-bold text-text">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-muted mt-0.5">{{ auth()->user()->email }}</div>
                        </div>
                        @if(Route::has('profile.edit'))
                        <flux:menu.item icon="user" wire:navigate :href="route('profile.edit')">My Profile</flux:menu.item>
                        @endif
                        <flux:menu.item icon="cog-6-tooth" wire:navigate :href="route('appearance.edit')">Appearance</flux:menu.item>
                        @if(auth()->user()->canAccess('access.view'))
                        <flux:menu.item icon="shield-check" wire:navigate :href="route('access')">Roles & Access</flux:menu.item>
                        @endif
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">Log Out</flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:header>

        {{-- Mobile Header --}}
        <flux:header class="border-b border-zinc-200/90 bg-white/95 backdrop-blur-md lg:hidden">
            <flux:sidebar.toggle class="cursor-pointer text-zinc-500 hover:text-brand-orange" icon="bars-2" inset="left" />
            <div class="ml-2 min-w-0 truncate">
                <x-brand.wordmark class="text-sm" />
            </div>
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" class="text-gray-700" />
                <flux:menu>
                    <flux:menu.separator />
                    @if(Route::has('profile.edit'))
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Settings</flux:menu.item>
                    @endif
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">Log out</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('sidebar', {
                    open: localStorage.getItem('sb-collapsed') !== 'true',
                    toggle() {
                        this.open = !this.open;
                        localStorage.setItem('sb-collapsed', String(!this.open));
                        document.documentElement.classList.toggle('sidebar-collapsed', !this.open);
                    }
                });
            });
            // Re-apply collapsed CSS class after every wire:navigate swap
            document.addEventListener('livewire:navigated', () => {
                const collapsed = localStorage.getItem('sb-collapsed') === 'true';
                document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
            });
        </script>
        <script data-navigate-once>
            (function () {
                const SCROLL_KEY = 'oe_sidebar_nav_scroll';
                const NAV_ID = 'app-sidebar-nav';
                const MAIN_ID = 'app-shell-main';

                function sidebarEl() {
                    return document.getElementById(NAV_ID);
                }

                function mainEl() {
                    return document.getElementById(MAIN_ID);
                }

                function saveSidebarScroll() {
                    const el = sidebarEl();
                    if (el) {
                        sessionStorage.setItem(SCROLL_KEY, String(el.scrollTop));
                    }
                }

                function restoreSidebarScroll() {
                    const y = parseInt(sessionStorage.getItem(SCROLL_KEY) || '0', 10);
                    const el = sidebarEl();
                    if (! el) {
                        return;
                    }
                    const apply = () => {
                        el.scrollTop = y;
                    };
                    requestAnimationFrame(apply);
                    requestAnimationFrame(() => requestAnimationFrame(apply));
                }

                function setMainNavigating(on) {
                    const main = mainEl();
                    if (main) {
                        main.classList.toggle('is-livewire-navigating', on);
                    }
                }

                document.addEventListener('livewire:navigating', () => {
                    saveSidebarScroll();
                    setMainNavigating(true);
                });
                document.addEventListener('livewire:navigated', () => {
                    setMainNavigating(false);
                    restoreSidebarScroll();
                });
            })();
        </script>
        @fluxScripts
        @livewireScripts
    </body>
</html>
