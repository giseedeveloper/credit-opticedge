<!DOCTYPE html>
<html lang="en" class="h-full bg-[#E5E4E2]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Opticedge Credit') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Guard against localStorage quota errors (breaks Flux UI / Livewire) --}}
    <script>
    (function(){var o=Storage.prototype.setItem;Storage.prototype.setItem=function(k,v){try{o.call(this,k,v)}catch(e){if(e instanceof DOMException&&(e.code===22||e.name==='QuotaExceededError')){this.clear();try{o.call(this,k,v)}catch(_){}}else{throw e}}}})();
    </script>

    @livewireStyles
    <!-- ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>if(localStorage.getItem('sb-collapsed')==='true')document.documentElement.classList.add('sidebar-collapsed');</script>
    <style>
        [data-flux-sidebar] { transition: width 0.25s ease; width: 256px; }
        html.sidebar-collapsed [data-flux-sidebar] { width: 64px; }
        .nav-active-glow {
            background-color: #1e3a8a !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.4);
            border-left: 3px solid #c084fc;
        }
    </style>
</head>
<body class="h-full antialiased font-sans text-gray-900 selection:bg-orange-500 selection:text-white flex bg-[#E5E4E2]">

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen">
        <flux:header class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md border-b border-gray-200 dark:border-zinc-800 h-16 flex items-center justify-between px-4 lg:px-8 shadow-sm print:hidden">
            <div class="flex items-center gap-1">
                {{-- Mobile: stash sidebar --}}
                <flux:sidebar.toggle class="lg:hidden text-gray-500 hover:text-gray-900" icon="bars-3" />
                <span class="font-bold text-orange-500 dark:text-white tracking-tight lg:hidden ml-1">Opticedge Credit</span>

                {{-- Desktop: animated hamburger toggle --}}
                <button
                    x-data
                    @click="$store.sidebar.toggle()"
                    :title="$store.sidebar.open ? 'Collapse sidebar' : 'Expand sidebar'"
                    class="hidden lg:flex flex-col items-center justify-center gap-[4.5px] w-9 h-9 rounded-xl bg-white border border-blue-200/80 text-orange-500 hover:bg-orange-500 hover:text-white hover:border-transparent hover:shadow-lg hover:shadow-blue-400/25 active:scale-95 transition-all duration-200"
                >
                    <span class="block h-[2px] w-[18px] bg-current rounded-full transition-all duration-200"></span>
                    <span class="block h-[2px] bg-current rounded-full transition-all duration-200" :class="$store.sidebar.open ? 'w-[13px]' : 'w-[18px]'"></span>
                    <span class="block h-[2px] w-[18px] bg-current rounded-full transition-all duration-200"></span>
                </button>
            </div>
            
            <div class="flex-1 max-w-xl mx-auto hidden md:block relative" x-data>
                <flux:input 
                    x-ref="search"
                    @keydown.window.prevent.cmd.k="$refs.search.focus()"
                    @keydown.window.prevent.ctrl.k="$refs.search.focus()"
                    icon="magnifying-glass" 
                    placeholder="Global Search (Press Cmd+K or Ctrl+K)..." 
                    class="w-full bg-gray-50 text-gray-900 border-gray-300 focus:border-[#2563eb] transition-colors" 
                />
            </div>
            
            <div class="flex items-center gap-2">
                <button class="md:hidden text-gray-500 hover:text-orange-500 p-1">
                    <flux:icon name="magnifying-glass" class="w-5 h-5" />
                </button>
                @livewire('notifications.alert-bell')
            </div>
        </flux:header>

        <!-- Main Payload Container with Light Gray Setup -->
        <flux:main class="flex-1 p-4 md:p-8 w-full max-w-full lg:max-w-7xl mx-auto bg-[#E5E4E2]">
            {{ $slot }}
        </flux:main>
    </div>

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
    </script>
    @fluxScripts
    @livewireScripts
</body>
</html>
