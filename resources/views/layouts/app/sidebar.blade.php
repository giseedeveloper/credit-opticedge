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

        </style>
    </head>
    <body class="min-h-screen bg-page">

        {{-- Sidebar: stashable mobile + custom Alpine icon-only collapse desktop --}}
        <flux:sidebar stashable sticky
            class="app-shell-sidebar border-e border-[#d9dee5] flex flex-col p-0 shadow-sm"
            x-data
        >
            {{-- Mobile close --}}
            <flux:sidebar.toggle class="lg:hidden absolute top-3 right-3 text-gray-400 hover:text-gray-700 z-10" icon="x-mark" />

            {{-- Brand --}}
            <div class="flex items-center gap-3 px-4 py-4 border-b border-[#d9dee5] shrink-0"
                 :class="$store.sidebar.open ? 'justify-start' : 'justify-center'">
                <x-fluent-icon name="credit-card" size="md" />
                <div x-show="$store.sidebar.open" x-transition.opacity class="leading-tight overflow-hidden">
                    <div class="text-sm font-bold text-gray-900 whitespace-nowrap">Opticedge</div>
                    <span class="inline-block text-[9px] font-black tracking-widest uppercase bg-orange-500 text-white px-1.5 py-0.5 rounded mt-0.5">Credit</span>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="sb-nav flex-1 overflow-y-auto px-2.5 py-3 space-y-2">
                @php
                $user = auth()->user();
                $navGroups = [
                    ['icon'=>'chart-bar-square','label'=>'Business Insights','active'=>request()->routeIs('dashboard'),'links'=>[
                        ['href'=>route('dashboard'),'label'=>'Dashboard','icon'=>'home','ability'=>'dashboard.view','active'=>request()->routeIs('dashboard')],
                    ]],
                    ['icon'=>'archive-box','label'=>'Inventory Vault','active'=>request()->routeIs('stock.*','inventory.grid'),'links'=>[
                        ['href'=>route('stock.index'),'label'=>'Stock Overview','icon'=>'clipboard-document-list','ability'=>'devices.view','active'=>request()->routeIs('stock.index')],
                        ['href'=>route('stock.brands'),'label'=>'Brands & Models','icon'=>'tag','ability'=>'products.view','active'=>request()->routeIs('stock.brands')],
                        ['href'=>route('stock.imei'),'label'=>'IMEI Search','icon'=>'magnifying-glass','ability'=>'devices.view','active'=>request()->routeIs('stock.imei')],
                        ['href'=>route('stock.transfers'),'label'=>'Stock Transfers','icon'=>'arrows-right-left','ability'=>'devices.view','active'=>request()->routeIs('stock.transfers')],
                        ['href'=>route('inventory.grid'),'label'=>'Master Stock (IMEI)','icon'=>'server-stack','ability'=>'devices.view','active'=>request()->routeIs('inventory.grid')],
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
                    ['icon'=>'building-storefront','label'=>'Partner Network','active'=>request()->routeIs('partners.*'),'links'=>[
                        ['href'=>route('partners.vendors'),'label'=>'Dealer Shops','icon'=>'building-storefront','ability'=>'vendors.view','active'=>request()->routeIs('partners.vendors')],
                        ['href'=>route('partners.commissions'),'label'=>'Commission Ledger','icon'=>'chart-bar','ability'=>'accounting.view','active'=>request()->routeIs('partners.commissions')],
                    ]],
                    ['icon'=>'banknotes','label'=>'Financial Hub','active'=>request()->routeIs('financials.*'),'links'=>[
                        ['href'=>route('financials.collections'),'label'=>'Collections','icon'=>'currency-dollar','ability'=>'accounting.view','active'=>request()->routeIs('financials.collections')],
                        ['href'=>route('financials.accounting'),'label'=>'Accounting Workspace','icon'=>'calculator','ability'=>'accounting.view','active'=>request()->routeIs('financials.accounting')],
                    ]],
                    ['icon'=>'cog-6-tooth','label'=>'System Control','active'=>request()->routeIs('comms.*','audits.*','access','settings.*','staff.*'),'links'=>[
                        ['href'=>route('comms.sms'),'label'=>'SMS Center','icon'=>'chat-bubble-left-right','ability'=>'sms_campaign.view','active'=>request()->routeIs('comms.sms')],
                        ['href'=>route('comms.audit'),'label'=>'Audit Trail','icon'=>'eye','ability'=>'reports.view','active'=>request()->routeIs('comms.audit')],
                        ['href'=>route('audits.logs'),'label'=>'Forensic Logs','icon'=>'shield-exclamation','ability'=>'reports.view','active'=>request()->routeIs('audits.logs')],
                        ['href'=>route('staff.index'),'label'=>'Staff Management','icon'=>'users','ability'=>'staff.view','active'=>request()->routeIs('staff.*')],
                        ['href'=>route('access'),'label'=>'Roles & Permissions','icon'=>'key','ability'=>'access.view','active'=>request()->routeIs('access')],
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
                     :class="$store.sidebar.open ? 'rounded-2xl border border-white/70 bg-white/45 p-1.5 shadow-[inset_0_1px_0_rgba(255,255,255,0.55)]' : ''">

                    {{-- EXPANDED: section label --}}
                    <div x-show="$store.sidebar.open" x-transition.opacity
                         class="flex items-center gap-2 px-2.5 pb-1.5 pt-1.5">
                        <x-fluent-icon :name="$group['icon']" size="xs" class="{{ $group['active'] ? 'opacity-100' : 'opacity-70' }}" />
                        <span class="text-[10px] font-bold tracking-widest uppercase truncate
                              {{ $group['active'] ? 'text-orange-500' : 'text-gray-500' }}">
                            {{ $group['label'] }}
                        </span>
                    </div>

                    {{-- EXPANDED: sub-items --}}
                    <div x-show="$store.sidebar.open"
                         class="space-y-1 px-1 pb-1">
                        @foreach($group['links'] as $link)
                        <a href="{{ $link['href'] }}" wire:navigate
                           class="flex items-center gap-2.5 px-3 py-2 text-[13px] rounded-lg transition-all duration-150
                               {{ $link['active']
                                   ? 'bg-white text-gray-950 font-semibold ring-1 ring-gray-200/80 shadow-sm'
                                   : 'text-gray-700 hover:bg-white/80 hover:text-gray-950' }}">
                            <x-fluent-icon :name="$link['icon']" size="xs" class="{{ $link['active'] ? 'scale-[1.02]' : 'opacity-80' }}" />
                            {{ $link['label'] }}
                        </a>
                        @endforeach
                    </div>

                    {{-- COLLAPSED: sub-item icons with teleported fixed-position tooltips --}}
                    <div x-show="!$store.sidebar.open" x-cloak class="space-y-1 px-1 py-0.5">
                        @foreach($group['links'] as $link)
                        <div x-data="{ hover: false, ty: 0 }"
                             @mouseenter="hover = true; ty = $el.getBoundingClientRect().top + $el.getBoundingClientRect().height / 2"
                             @mouseleave="hover = false">
                            <a href="{{ $link['href'] }}" wire:navigate
                               class="flex items-center justify-center w-full py-2.5 rounded-lg transition-all duration-150
                                   {{ $link['active']
                                       ? 'bg-white text-orange-600 ring-1 ring-gray-200/80 shadow-sm'
                                       : 'text-gray-600 hover:bg-white/75 hover:text-gray-900' }}">
                                <x-fluent-icon :name="$link['icon']" size="sm" class="{{ $link['active'] ? 'scale-105' : 'opacity-85' }}" />
                            </a>
                            <template x-teleport="body">
                                <div x-show="hover"
                                     x-transition.opacity.duration.150ms
                                     :style="`top: ${ty}px; left: 72px; transform: translateY(-50%)`"
                                     class="fixed z-[9999] pointer-events-none
                                            bg-white border border-gray-200
                                            text-[12px] text-gray-700 font-medium rounded-lg
                                            px-3 py-1.5 shadow-xl whitespace-nowrap">
                                    {{ $link['label'] }}
                                </div>
                            </template>
                        </div>
                        @endforeach
                        {{-- Group separator in collapsed mode --}}
                        <div class="mx-2 my-1 h-px bg-gray-200/80"></div>
                    </div>

                </div>
                @endforeach
            </nav>

        </flux:sidebar>

        {{-- Desktop Header --}}
        <flux:header class="hidden lg:flex bg-white border-b border-gray-200 h-14 items-center justify-between px-4">
            {{-- Hamburger toggle --}}
            <button
                x-data
                @click="$store.sidebar.toggle()"
                :title="$store.sidebar.open ? 'Collapse sidebar' : 'Expand sidebar'"
                class="flex flex-col items-center justify-center gap-[4.5px] w-9 h-9 rounded-lg bg-gray-50 border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700 active:scale-95 transition-all duration-150"
            >
                <span class="block h-[2px] w-[18px] bg-current rounded-full transition-all duration-200"></span>
                <span class="block h-[2px] bg-current rounded-full transition-all duration-200"
                      :class="$store.sidebar.open ? 'w-[13px]' : 'w-[18px]'"></span>
                <span class="block h-[2px] w-[18px] bg-current rounded-full transition-all duration-200"></span>
            </button>

            {{-- Search --}}
            <div class="flex-1 max-w-xl mx-4" x-data>
                <flux:input
                    x-ref="search"
                    @keydown.window.prevent.cmd.k="$refs.search.focus()"
                    @keydown.window.prevent.ctrl.k="$refs.search.focus()"
                    icon="magnifying-glass"
                    placeholder="Search (Cmd+K)..."
                    class="w-full bg-gray-50 border-gray-200"
                />
            </div>

            {{-- Right actions --}}
            <div class="flex items-center gap-3">
                @livewire('notifications.alert-bell')

                {{-- User avatar + dropdown --}}
                <flux:dropdown position="bottom" align="end">
                    <button type="button"
                        class="size-9 rounded-full bg-gradient-to-br from-orange-400 to-orange-600
                               flex items-center justify-center ring-2 ring-orange-300/40
                               hover:ring-orange-400/70 hover:shadow-lg hover:shadow-orange-400/20
                               transition-all duration-200 cursor-pointer">
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
        <flux:header class="lg:hidden bg-white border-b border-gray-200">
            <flux:sidebar.toggle class="text-gray-500" icon="bars-2" inset="left" />
            <span class="ml-2 text-sm font-bold text-gray-900">Opticedge Credit</span>
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
        @fluxScripts
    </body>
</html>
