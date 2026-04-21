<div class="flex flex-col gap-6">

    {{-- Toast --}}
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : 'bg-red-500'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2" style="display:none">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-start gap-4">
            <x-fluent-icon name="shield-check" size="lg" palette="slate" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Audit Trail</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Every system action — who did what, and when</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800">
            <x-fluent-icon name="shield-check" size="xs" palette="slate" />
            <span class="text-sm font-bold text-slate-600 dark:text-slate-400">{{ number_format($stats['total']) }} total entries</span>
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-slate-700 to-gray-800 rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-slate-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="shield-check" size="sm" palette="slate" />
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">All Time</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-white/60 mt-1">{{ number_format($stats['unique_users']) }} unique {{ Str::plural('user', $stats['unique_users']) }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="sun" size="sm" palette="amber" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Today</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['today']) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('d M Y') }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="calendar-days" size="sm" palette="blue" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">This Week</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['this_week']) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->startOfWeek()->format('d M') }} – {{ now()->endOfWeek()->format('d M') }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-5 border border-gray-100 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="users" size="sm" palette="sky" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Users</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['unique_users']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Users with audit entries</p>
        </div>
    </div>

    {{-- Event breakdown mini-row --}}
    <div class="flex gap-3 flex-wrap">
        <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-900/30">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ number_format($stats['created']) }} Created</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-oe-soft dark:bg-oe/10 rounded-xl border border-oe/20 dark:border-oe/20">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span class="text-xs font-bold text-oe-hover dark:text-oe">{{ number_format($stats['updated']) }} Updated</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-900/30">
            <span class="w-2 h-2 rounded-full bg-red-500"></span>
            <span class="text-xs font-bold text-red-700 dark:text-red-400">{{ number_format($stats['deleted']) }} Deleted</span>
        </div>
        @php $other = $stats['total'] - $stats['created'] - $stats['updated'] - $stats['deleted']; @endphp
        @if($other > 0)
        <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-zinc-800 rounded-xl border border-gray-100 dark:border-zinc-700">
            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
            <span class="text-xs font-bold text-gray-500">{{ number_format($other) }} Other</span>
        </div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between flex-wrap">
        {{-- Event tabs --}}
        <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-zinc-800 p-1">
            @foreach(['' => 'All', 'created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted'] as $val => $label)
            <button wire:click="$set('eventFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors
                        {{ $eventFilter === $val
                            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <div class="flex gap-2 flex-wrap">
            {{-- Log channel filter --}}
            <flux:select wire:model.live="logFilter" class="w-40">
                <flux:select.option value="">All Channels</flux:select.option>
                @foreach($logNames as $ln)
                <flux:select.option :value="$ln">{{ ucfirst($ln) }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="w-56">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Description, model…" icon="magnifying-glass" />
            </div>
            <flux:input wire:model.live="dateFrom" type="date" class="w-36" />
            <flux:input wire:model.live="dateTo"   type="date" class="w-36" />
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-zinc-800/80 border-b border-gray-100 dark:border-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider hidden md:table-cell">Model</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Performed By</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">When</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                    @forelse($logs as $log)
                    @php
                        $eventColor = match($log->event) {
                            'created' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                            'updated' => 'bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe',
                            'deleted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            default   => 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-400',
                        };
                        $channelColor = match($log->log_name) {
                            'loan'      => 'bg-oe-soft text-oe-hover dark:bg-oe/10 dark:text-oe',
                            'system'    => 'bg-slate-100 text-slate-700 dark:bg-slate-900/30 dark:text-slate-400',
                            'inventory' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            'security'  => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                            'payment'   => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                            default     => 'bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-zinc-400',
                        };
                        $hasChanges = $log->properties?->has('attributes') || $log->properties?->has('old');
                        $subjectName = class_basename($log->subject_type ?? '');
                    @endphp
                    <tr wire:key="audit-{{ $log->id }}"
                        class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer"
                        wire:click="openDetail('{{ $log->id }}')">

                        {{-- Channel --}}
                        <td class="px-4 py-3">
                            @if($log->log_name)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $channelColor }}">
                                {{ ucfirst($log->log_name) }}
                            </span>
                            @else
                            <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Event --}}
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $eventColor }}">
                                {{ ucfirst($log->event ?? 'log') }}
                            </span>
                        </td>

                        {{-- Description --}}
                        <td class="px-4 py-3 max-w-xs">
                            <p class="text-xs text-gray-800 dark:text-gray-200 truncate">{{ $log->description }}</p>
                            @if($hasChanges)
                            <p class="text-[10px] text-oe mt-0.5 font-semibold">Has changes →</p>
                            @endif
                        </td>

                        {{-- Model --}}
                        <td class="px-4 py-3 hidden md:table-cell">
                            @if($log->subject_type)
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $subjectName }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ $log->subject_id ? Str::limit($log->subject_id, 10, '…') : '' }}</p>
                            @else
                            <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Performed By --}}
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center text-white text-[9px] font-black flex-shrink-0">
                                    {{ strtoupper(substr($log->causer?->name ?? 'S', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $log->causer?->name ?? 'System' }}</p>
                                    @if($log->causer?->email)
                                    <p class="text-[10px] text-gray-400">{{ $log->causer->email }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- When --}}
                        <td class="px-4 py-3">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-[10px] text-gray-400">{{ $log->created_at->format('H:i') }} · {{ $log->created_at->diffForHumans() }}</p>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <button wire:click.stop="openDetail('{{ $log->id }}')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-oe dark:text-oe hover:bg-oe-soft dark:hover:bg-oe/10 transition-colors">
                                View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-16 text-center">
                            <flux:icon name="shield-check" class="size-12 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                            <p class="font-semibold text-gray-500">No audit entries found</p>
                            <p class="text-xs text-gray-400 mt-1">
                                @if($search || $eventFilter || $logFilter || $dateFrom || $dateTo) Try clearing your filters @else System actions will appear here as they occur @endif
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-800">{{ $logs->links() }}</div>
        @endif
    </div>

    {{-- ══ AUDIT DETAIL SLIDE-OVER ══ --}}
    <div x-data="{ open: @entangle('showDetail') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-xl bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">

            @if($this->detailLog)
            @php
                $dl        = $this->detailLog;
                $dlEvent   = $dl->event ?? 'log';
                $dlGrad    = match($dlEvent) {
                    'created' => 'from-emerald-600 to-teal-700',
                    'updated' => 'from-oe to-oe-hover',
                    'deleted' => 'from-red-600 to-rose-700',
                    default   => 'from-slate-600 to-gray-700',
                };
                $dlProps   = $dl->properties instanceof \Illuminate\Support\Collection
                    ? $dl->properties->toArray()
                    : (array) $dl->properties;
                $oldValues = $dlProps['old'] ?? [];
                $newValues = $dlProps['attributes'] ?? [];
                $allKeys   = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                $subjectName = class_basename($dl->subject_type ?? '');
            @endphp

            {{-- Header --}}
            <div class="px-6 py-5 bg-gradient-to-r {{ $dlGrad }} text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-white/60">{{ ucfirst($dl->log_name ?? 'audit') }}</span>
                            <span class="text-white/30">·</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-white/80">{{ ucfirst($dlEvent) }}</span>
                        </div>
                        <p class="text-base font-black leading-tight">{{ $dl->description }}</p>
                        <p class="text-white/60 text-xs mt-1">{{ $dl->created_at->format('l, d M Y · H:i:s') }}</p>
                    </div>
                    <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0 ml-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Who + When --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Actor & Timing</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-oe/90 to-oe flex items-center justify-center text-white text-xs font-black flex-shrink-0">
                                {{ strtoupper(substr($dl->causer?->name ?? 'S', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100">{{ $dl->causer?->name ?? 'System' }}</p>
                                @if($dl->causer?->email)
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $dl->causer->email }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Timestamp</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dl->created_at->format('d M Y') }}</p>
                            <p class="text-[10px] text-gray-400">{{ $dl->created_at->format('H:i:s') }} · {{ $dl->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>

                {{-- Subject / Model --}}
                @if($dl->subject_type)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Affected Record</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $subjectName }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $dl->subject_id }}</p>
                            </div>
                            @if($dl->subject)
                            <div class="text-right">
                                @if(isset($dl->subject->name))
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $dl->subject->name }}</p>
                                @elseif(isset($dl->subject->full_name))
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $dl->subject->full_name }}</p>
                                @elseif(isset($dl->subject->loan_number))
                                <p class="text-xs font-semibold text-oe font-mono">{{ $dl->subject->loan_number }}</p>
                                @endif
                                @if(isset($dl->subject->phone))
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $dl->subject->phone }}</p>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Changes Diff (old vs new) --}}
                @if(!empty($allKeys))
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">
                        Changes
                        <span class="ml-2 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-zinc-800 text-gray-500 text-[9px]">{{ count($allKeys) }} {{ Str::plural('field', count($allKeys)) }}</span>
                    </h3>
                    <div class="rounded-xl border border-gray-100 dark:border-zinc-800 overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-gray-400 uppercase w-1/3">Field</th>
                                    @if(!empty($oldValues))
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-red-400 uppercase">Before</th>
                                    @endif
                                    @if(!empty($newValues))
                                    <th class="px-3 py-2 text-left text-[10px] font-bold text-emerald-500 uppercase">After</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                                @foreach($allKeys as $key)
                                @php
                                    $oldVal = $oldValues[$key] ?? null;
                                    $newVal = $newValues[$key] ?? null;
                                    $changed = $oldVal !== $newVal;
                                @endphp
                                <tr class="{{ $changed ? 'bg-amber-50/40 dark:bg-amber-900/5' : '' }}">
                                    <td class="px-3 py-2 font-mono text-gray-500 dark:text-gray-400 font-bold">{{ $key }}</td>
                                    @if(!empty($oldValues))
                                    <td class="px-3 py-2 text-red-600 dark:text-red-400 max-w-[140px]">
                                        <span class="break-all">{{ is_null($oldVal) ? '(null)' : (is_array($oldVal) ? json_encode($oldVal) : Str::limit((string) $oldVal, 60)) }}</span>
                                    </td>
                                    @endif
                                    @if(!empty($newValues))
                                    <td class="px-3 py-2 text-emerald-600 dark:text-emerald-400 max-w-[140px]">
                                        <span class="break-all">{{ is_null($newVal) ? '(null)' : (is_array($newVal) ? json_encode($newVal) : Str::limit((string) $newVal, 60)) }}</span>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Raw properties fallback if no structured old/new --}}
                @elseif(!empty(array_filter($dlProps, fn($v) => !in_array(array_search($v, $dlProps), ['attributes', 'old']))))
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Additional Data</h3>
                    <div class="bg-gray-900 dark:bg-zinc-950 rounded-xl p-4">
                        <pre class="text-[11px] text-emerald-400 font-mono whitespace-pre-wrap overflow-x-auto">{{ json_encode($dlProps, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
                @endif

                {{-- Entry ID --}}
                <div class="pt-3 border-t border-gray-100 dark:border-zinc-800">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Entry ID</p>
                    <p class="text-xs font-mono text-gray-600 dark:text-gray-400 mt-0.5">{{ $dl->id }}</p>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800">
                <button wire:click="closeDetail"
                        class="w-full py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

</div>
