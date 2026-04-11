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
            <x-fluent-icon name="chat-bubble-left-right" size="lg" palette="teal" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">SMS Logs</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Automated and manual SMS dispatch records</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/40">
            <x-fluent-icon name="chat-bubble-left-right" size="xs" palette="teal" />
            <span class="text-sm font-bold text-orange-500 dark:text-blue-400">{{ number_format($stats['total']) }} total messages</span>
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-5 text-white relative overflow-hidden shadow-lg shadow-blue-900/20">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <x-fluent-icon name="chat-bubble-left-right" size="sm" palette="teal" />
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">All Time</span>
            </div>
            <p class="text-3xl font-black">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-white/60 mt-1">SMS records logged</p>
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
                <x-fluent-icon name="chart-bar" size="sm" palette="amber" />
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">This Month</span>
            </div>
            <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($stats['this_month']) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('M Y') }}</p>
        </div>
    </div>

    {{-- Type breakdown mini-row --}}
    @if($stats['bulk'] > 0 || $stats['automated'] > 0 || $stats['welcome'] > 0 || $stats['system'] > 0)
    <div class="flex gap-3">
        <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900/30">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span class="text-xs font-bold text-orange-500 dark:text-blue-400">{{ number_format($stats['bulk']) }} Bulk SMS</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100 dark:border-orange-900/30">
            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
            <span class="text-xs font-bold text-orange-600 dark:text-orange-400">{{ number_format($stats['automated']) }} Automated</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-900/30">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($stats['welcome']) }} Welcome</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-zinc-800 rounded-xl border border-gray-100 dark:border-zinc-700">
            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
            <span class="text-xs font-bold text-gray-500">{{ number_format($stats['system']) }} System</span>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        {{-- Type tabs --}}
        <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-zinc-800 p-1">
            @foreach(['' => 'All', 'bulk' => 'Bulk', 'automated' => 'Automated', 'welcome' => 'Welcome', 'system' => 'System'] as $val => $label)
            <button wire:click="$set('typeFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors
                        {{ $typeFilter === $val
                            ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-900 dark:hover:text-white' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        <div class="flex gap-2 flex-wrap">
            <div class="w-56">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search message…" icon="magnifying-glass" />
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
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Message</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider hidden md:table-cell">Recipient / Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Triggered By</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Sent</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800">
                    @forelse($logs as $log)
                    @php
                        $smsType    = \App\Livewire\Communications\SmsLogs::smsTypeFromDescription($log->description);
                        $typeMeta   = match($smsType) {
                            'bulk'      => ['label' => 'Bulk',      'color' => 'bg-blue-100 text-orange-600 dark:bg-blue-900/30 dark:text-blue-400'],
                            'automated' => ['label' => 'Automated', 'color' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'],
                            'welcome'   => ['label' => 'Welcome',   'color' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'],
                            default     => ['label' => 'System',    'color' => 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-400'],
                        };
                        $subjectLabel = class_basename($log->subject_type ?? '');
                    @endphp
                    <tr wire:key="sms-{{ $log->id }}"
                        class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer"
                        wire:click="openDetail('{{ $log->id }}')">

                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $typeMeta['color'] }}">
                                {{ $typeMeta['label'] }}
                            </span>
                        </td>

                        <td class="px-4 py-3 max-w-xs">
                            <p class="text-xs text-gray-800 dark:text-gray-200 line-clamp-2">{{ $log->description }}</p>
                        </td>

                        <td class="px-4 py-3 hidden md:table-cell">
                            @if($log->subject_type)
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $subjectLabel }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ $log->subject_id ? Str::limit($log->subject_id, 8, '…') : '—' }}</p>
                            @else
                            <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 hidden lg:table-cell">
                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ $log->causer?->name ?? 'System' }}</p>
                            @if($log->log_name && $log->log_name !== 'default')
                            <p class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ $log->log_name }}</p>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-[10px] text-gray-400">{{ $log->created_at->format('H:i') }} · {{ $log->created_at->diffForHumans() }}</p>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <button wire:click.stop="openDetail('{{ $log->id }}')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-orange-500 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center">
                            <flux:icon name="chat-bubble-left-right" class="size-12 mx-auto mb-3 text-gray-300 dark:text-zinc-600" />
                            <p class="font-semibold text-gray-500">No SMS logs found</p>
                            <p class="text-xs text-gray-400 mt-1">
                                @if($search || $typeFilter || $dateFrom || $dateTo) Try clearing your filters @else SMS logs will appear here when messages are dispatched @endif
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

    {{-- ══ SMS DETAIL SLIDE-OVER ══ --}}
    <div x-data="{ open: @entangle('showDetail') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex justify-end" style="display:none">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="relative w-full max-w-lg bg-white dark:bg-zinc-900 shadow-2xl overflow-y-auto flex flex-col">

            @if($this->detailLog)
            @php
                $dl      = $this->detailLog;
                $dlType  = \App\Livewire\Communications\SmsLogs::smsTypeFromDescription($dl->description);
                $dlMeta  = match($dlType) {
                    'bulk'      => ['label' => 'Bulk SMS',       'grad' => 'from-orange-500 to-orange-600'],
                    'automated' => ['label' => 'Automated SMS',  'grad' => 'from-orange-500 to-red-600'],
                    'welcome'   => ['label' => 'Welcome SMS',    'grad' => 'from-emerald-600 to-teal-700'],
                    default     => ['label' => 'System SMS',     'grad' => 'from-gray-600 to-gray-700'],
                };
                $dlSubjectLabel = class_basename($dl->subject_type ?? '');
                $dlProps = collect($dl->properties)->toArray();
            @endphp

            {{-- Header --}}
            <div class="px-6 py-5 bg-gradient-to-r {{ $dlMeta['grad'] }} text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold text-white/60 uppercase tracking-wider">{{ $dlMeta['label'] }}</p>
                        <p class="text-sm font-black mt-1">{{ $dl->created_at->format('l, d M Y · H:i:s') }}</p>
                        <p class="text-white/60 text-xs mt-1">{{ $dl->created_at->diffForHumans() }}</p>
                    </div>
                    <button wire:click="closeDetail" class="p-1.5 rounded-lg hover:bg-white/10 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 px-6 py-5 space-y-5">

                {{-- Full Message --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Message Content</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4">
                        <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap">{{ $dl->description }}</p>
                    </div>
                </div>

                {{-- Log Metadata --}}
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Log Metadata</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Log Channel</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5 font-mono">{{ $dl->log_name ?? 'default' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Event</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dl->event ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Triggered By</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $dl->causer?->name ?? 'System' }}</p>
                            @if($dl->causer?->email)
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $dl->causer->email }}</p>
                            @endif
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-3">
                            <p class="text-[10px] text-gray-400 uppercase font-bold">SMS Type</p>
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100 mt-0.5 capitalize">{{ $dlMeta['label'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Subject / Recipient --}}
                @if($dl->subject_type)
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Recipient / Subject</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $dlSubjectLabel }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5 font-mono">ID: {{ $dl->subject_id }}</p>
                            </div>
                            @if($dl->subject)
                            <div class="text-right">
                                @if(\App\Livewire\Communications\SmsLogs::subjectDisplayName($dl->subject))
                                <p class="text-xs font-semibold text-gray-800 dark:text-gray-100">{{ \App\Livewire\Communications\SmsLogs::subjectDisplayName($dl->subject) }}</p>
                                @endif
                                @if(\App\Livewire\Communications\SmsLogs::subjectDisplayPhone($dl->subject))
                                <p class="text-[10px] text-gray-400">📱 {{ \App\Livewire\Communications\SmsLogs::subjectDisplayPhone($dl->subject) }}</p>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Properties JSON --}}
                @if(!empty($dlProps))
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Additional Data</h3>
                    <div class="bg-gray-900 dark:bg-zinc-950 rounded-xl p-4 overflow-x-auto">
                        <pre class="text-[11px] text-emerald-400 font-mono whitespace-pre-wrap">{{ json_encode($dlProps, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
                @endif

                {{-- Timestamp --}}
                <div class="pt-3 border-t border-gray-100 dark:border-zinc-800">
                    <p class="text-[10px] text-gray-400 uppercase font-bold">Logged At</p>
                    <p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5">{{ $dl->created_at->format('l, d M Y · H:i:s') }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Entry ID: <span class="font-mono">{{ $dl->id }}</span></p>
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
