<div>

    {{-- ── Toast ──────────────────────────────────────────────────── --}}
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,3500)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : type==='danger' ? 'bg-red-500' : 'bg-orange-500'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    {{-- ── Journal Entry Detail Slide-Over ───────────────────────── --}}
    @if($showJournalDetail && $selectedEntry)
    <div class="fixed inset-0 z-50 flex justify-end" x-data x-init="$nextTick(() => $el.focus())" tabindex="-1" @keydown.escape.window="$wire.closeJournalDetail()">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="closeJournalDetail"></div>
        <div class="relative w-full max-w-2xl h-full bg-white dark:bg-zinc-900 shadow-2xl flex flex-col"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

            {{-- Header --}}
            <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 bg-[#1a2035] flex items-start justify-between shrink-0">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-mono text-lg font-black text-white">{{ $selectedEntry->reference }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $selectedEntry->source === 'manual' ? 'bg-blue-500/30 text-blue-200' : 'bg-teal-500/30 text-teal-200' }}">
                            {{ ucfirst(str_replace('_', ' ', $selectedEntry->source)) }}
                        </span>
                        @if($selectedEntry->isBalanced())
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-500/30 text-teal-200">Balanced</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500/30 text-red-200">Unbalanced</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-300">{{ $selectedEntry->description }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $selectedEntry->date->format('l, d F Y') }}</p>
                </div>
                <button wire:click="closeJournalDetail" class="text-gray-400 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Meta info --}}
            <div class="px-6 py-3 bg-gray-50 dark:bg-zinc-800/40 border-b border-gray-100 dark:border-zinc-800 flex items-center gap-6 text-xs shrink-0">
                <div>
                    <span class="text-gray-400 font-semibold uppercase tracking-wider">Posted By</span>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 mt-0.5">{{ $selectedEntry->creator?->name ?? 'System' }}</p>
                </div>
                <div>
                    <span class="text-gray-400 font-semibold uppercase tracking-wider">Created At</span>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 mt-0.5">{{ $selectedEntry->created_at->format('d M Y H:i') }}</p>
                </div>
                <div>
                    <span class="text-gray-400 font-semibold uppercase tracking-wider">Total Lines</span>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 mt-0.5">{{ $selectedEntry->lines->count() }}</p>
                </div>
            </div>

            {{-- Lines table --}}
            <div class="flex-1 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10">
                        <tr class="bg-gray-100 dark:bg-zinc-800 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <th class="px-5 py-3 text-left">Account</th>
                            <th class="px-4 py-3 text-center">Type</th>
                            <th class="px-4 py-3 text-right">Debit (TZS)</th>
                            <th class="px-4 py-3 text-right">Credit (TZS)</th>
                            <th class="px-4 py-3 text-left">Narration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @foreach($selectedEntry->lines as $line)
                        @php
                        $typeBg = ['Asset'=>'bg-blue-100 text-orange-600','Liability'=>'bg-rose-100 text-rose-700','Equity'=>'bg-blue-100 text-orange-600','Revenue'=>'bg-teal-100 text-teal-700','Expense'=>'bg-amber-100 text-amber-700'];
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/40 transition-colors">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $line->account?->name ?? '—' }}</p>
                                <p class="font-mono text-[10px] text-gray-400">{{ $line->account?->code }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $typeBg[$line->account?->type ?? ''] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $line->account?->type ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold {{ (float)$line->debit > 0 ? 'text-orange-600 dark:text-blue-300' : 'text-gray-300' }}">
                                {{ (float)$line->debit > 0 ? number_format((float)$line->debit, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold {{ (float)$line->credit > 0 ? 'text-teal-700 dark:text-teal-300' : 'text-gray-300' }}">
                                {{ (float)$line->credit > 0 ? number_format((float)$line->credit, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $line->narration ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#1a2035] text-white">
                            <td colspan="2" class="px-5 py-3 text-xs font-bold uppercase tracking-wider">Totals</td>
                            <td class="px-4 py-3 text-right font-mono font-black text-blue-300">
                                {{ number_format((float)$selectedEntry->lines->sum('debit'), 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-black text-teal-300">
                                {{ number_format((float)$selectedEntry->lines->sum('credit'), 2) }}
                            </td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/40 flex justify-between items-center shrink-0">
                <p class="text-xs text-gray-400">Entry ID: <span class="font-mono">{{ $selectedEntry->id }}</span></p>
                <button wire:click="closeJournalDetail" class="px-5 py-2 text-sm font-semibold bg-[#1a2035] hover:bg-orange-600 text-white rounded-xl transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Create / Edit Account Modal ────────────────────────────── --}}
    @if($showAccountModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showAccountModal',false)">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-700 w-full max-w-md mx-4">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $editAccountId ? 'Edit Account' : 'New Account' }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Chart of Accounts entry</p>
                </div>
                <button wire:click="$set('showAccountModal',false)" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">Account Code</label>
                        <input wire:model="acCode" type="text" placeholder="e.g. 1000"
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono" />
                        @error('acCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">Type</label>
                        <select wire:model="acType"
                                class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Select type</option>
                            @foreach(['Asset','Liability','Equity','Revenue','Expense'] as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                        @error('acType') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">Account Name</label>
                    <input wire:model="acName" type="text" placeholder="e.g. Cash on Hand"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                    @error('acName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">Description <span class="normal-case font-normal text-gray-400">(optional)</span></label>
                    <input wire:model="acDescription" type="text" placeholder="Brief description"
                           class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/60 rounded-b-2xl flex justify-end gap-3">
                <button wire:click="$set('showAccountModal',false)" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">Cancel</button>
                <button wire:click="saveAccount" class="px-5 py-2 text-sm font-semibold bg-orange-500 hover:bg-orange-600 text-white rounded-xl transition-colors shadow-sm">Save Account</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Manual Journal Entry Modal ──────────────────────────────── --}}
    @if($showJournalModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showJournalModal',false)">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-700 w-full max-w-2xl mx-4 max-h-[90vh] flex flex-col">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Manual Journal Entry</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Double-entry — Total Debits must equal Total Credits</p>
                </div>
                <button wire:click="$set('showJournalModal',false)" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-4 overflow-y-auto flex-1">
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">Reference</label>
                        <input wire:model="jeReference" type="text"
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 font-mono" />
                        @error('jeReference') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">Date</label>
                        <input wire:model="jeDate" type="date"
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                        @error('jeDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">Description</label>
                        <input wire:model="jeDescription" type="text" placeholder="e.g. Rent payment"
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                        @error('jeDescription') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Lines --}}
                <div class="rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden mb-3">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-[#1a2035] text-white">
                                <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider">Account</th>
                                <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider w-28">Debit (TZS)</th>
                                <th class="px-3 py-2.5 text-right font-semibold uppercase tracking-wider w-28">Credit (TZS)</th>
                                <th class="px-3 py-2.5 text-left font-semibold uppercase tracking-wider">Narration</th>
                                <th class="px-2 py-2.5 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach($jeLines as $i => $line)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/40">
                                <td class="px-2 py-1.5">
                                    <select wire:model="jeLines.{{ $i }}.account_id"
                                            class="w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-orange-500">
                                        <option value="">Select account…</option>
                                        @foreach($allAccounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} – {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input wire:model="jeLines.{{ $i }}.debit" type="number" step="0.01" min="0" placeholder="0.00"
                                           class="w-full px-2 py-1.5 text-xs text-right border border-gray-200 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-orange-500 font-mono" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input wire:model="jeLines.{{ $i }}.credit" type="number" step="0.01" min="0" placeholder="0.00"
                                           class="w-full px-2 py-1.5 text-xs text-right border border-gray-200 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-orange-500 font-mono" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input wire:model="jeLines.{{ $i }}.narration" type="text" placeholder="optional…"
                                           class="w-full px-2 py-1.5 text-xs border border-gray-200 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-orange-500" />
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    @if(count($jeLines) > 2)
                                    <button wire:click="removeLine({{ $i }})" class="text-red-400 hover:text-red-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-zinc-800/40">
                                <td class="px-3 py-2 text-xs font-bold text-gray-500">TOTALS</td>
                                <td class="px-3 py-2 text-xs font-black text-right font-mono text-orange-600 dark:text-blue-300">
                                    {{ number_format(array_sum(array_column($jeLines, 'debit')), 2) }}
                                </td>
                                <td class="px-3 py-2 text-xs font-black text-right font-mono text-teal-700 dark:text-teal-300">
                                    {{ number_format(array_sum(array_column($jeLines, 'credit')), 2) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @error('jeLines') <p class="text-xs text-red-500 mb-2 flex items-center gap-1"><svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>{{ $message }}</p> @enderror

                <button wire:click="addLine" class="text-xs text-orange-500 font-semibold hover:text-blue-800 flex items-center gap-1 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Add Line
                </button>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/60 rounded-b-2xl flex justify-end gap-3 shrink-0">
                <button wire:click="$set('showJournalModal',false)" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">Cancel</button>
                <button wire:click="saveJournalEntry" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-semibold bg-orange-500 hover:bg-orange-600 disabled:opacity-60 text-white rounded-xl transition-colors shadow-sm flex items-center gap-2">
                    <span wire:loading.remove wire:target="saveJournalEntry">Post Entry</span>
                    <span wire:loading wire:target="saveJournalEntry" class="flex items-center gap-1.5">
                        <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                        Posting…
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Page Header ──────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between mb-6">
        <div class="flex items-start gap-4">
            <x-fluent-icon name="banknotes" size="lg" palette="emerald" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Accounting Workspace</h1>
            <p class="text-sm text-gray-400 mt-0.5 max-w-xl">Balance sheets, journals, and reconciliation insights presented with the same polish as your main dashboard.</p>
            </div>
        </div>
        @if($activeTab === 'journal')
        @can('accounting.create')
        <button wire:click="openJournalModal"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-bold bg-orange-500 hover:bg-orange-600 text-white rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Manual Entry
        </button>
        @endcan
        @elseif($activeTab === 'chart')
        @can('accounting.create')
        <button wire:click="openAccountModal()"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-bold bg-orange-500 hover:bg-orange-600 text-white rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            New Account
        </button>
        @endcan
        @endif
    </div>

    {{-- ── Tab Navigation ───────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">

        <div class="border-b border-gray-100 dark:border-zinc-800 px-4 overflow-x-auto" style="scrollbar-width:none">
            <div class="flex items-center gap-0.5 min-w-max">
                @php
                $tabs = [
                    ['key'=>'overview',        'label'=>'Overview',        'icon'=>'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                    ['key'=>'chart',           'label'=>'Chart',           'icon'=>'M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z'],
                    ['key'=>'journal',         'label'=>'Journal',         'icon'=>'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
                    ['key'=>'ledger',          'label'=>'Ledger',          'icon'=>'M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 19.5m9.75-12.375c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125h15.75c.621 0 1.125-.504 1.125-1.125v-1.5z'],
                    ['key'=>'pl',              'label'=>'P&L',             'icon'=>'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941'],
                    ['key'=>'selcom',          'label'=>'Selcom Import',   'icon'=>'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5'],
                    ['key'=>'reconciliation',  'label'=>'Reconciliation',  'icon'=>'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
                ];
                @endphp
                @foreach($tabs as $tab)
                <button wire:click="setTab('{{ $tab['key'] }}')"
                        class="flex items-center gap-1.5 px-4 py-3.5 text-xs font-bold transition-all border-b-2 whitespace-nowrap
                               {{ $activeTab === $tab['key']
                                    ? 'border-orange-500 text-orange-500 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-gray-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/></svg>
                    {{ $tab['label'] }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
             TAB: OVERVIEW
        ════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'overview')
        <div class="p-6">
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
                @php
                $statCards = [
                    ['label'=>'Total Assets',      'value'=>$overview['Asset'],      'color'=>'indigo',  'icon'=>'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z'],
                    ['label'=>'Total Liabilities', 'value'=>$overview['Liability'],  'color'=>'rose',    'icon'=>'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
                    ['label'=>'Net Equity',        'value'=>$overview['netEquity'],  'color'=>'violet',  'icon'=>'M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.97zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.97z'],
                    ['label'=>'Revenue (Period)',   'value'=>$overview['Revenue'],    'color'=>'teal',    'icon'=>'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941'],
                    ['label'=>'Expenses (Period)',  'value'=>$overview['Expense'],    'color'=>'amber',   'icon'=>'M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 2.43l3.658 3.158m0 0l3 2.654M7.5 15.75L3 12.375'],
                ];
                $colorMap = [
                    'indigo' => ['bg'=>'bg-blue-100 dark:bg-blue-900/30','text'=>'text-orange-500 dark:text-blue-400','val'=>'text-orange-600 dark:text-blue-300'],
                    'rose'   => ['bg'=>'bg-rose-100 dark:bg-rose-900/30',    'text'=>'text-rose-600 dark:text-rose-400',    'val'=>'text-rose-700 dark:text-rose-300'],
                    'violet' => ['bg'=>'bg-blue-100 dark:bg-blue-900/30','text'=>'text-orange-500 dark:text-blue-400','val'=>'text-orange-600 dark:text-blue-300'],
                    'teal'   => ['bg'=>'bg-teal-100 dark:bg-teal-900/30',    'text'=>'text-teal-600 dark:text-teal-400',    'val'=>'text-teal-700 dark:text-teal-300'],
                    'amber'  => ['bg'=>'bg-amber-100 dark:bg-amber-900/30',  'text'=>'text-amber-600 dark:text-amber-400',  'val'=>'text-amber-700 dark:text-amber-300'],
                ];
                @endphp
                @foreach($statCards as $card)
                @php $c = $colorMap[$card['color']]; @endphp
                <div class="bg-white dark:bg-zinc-900 rounded-xl border border-gray-100 dark:border-zinc-800 p-4 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="p-1.5 rounded-lg {{ $c['bg'] }} {{ $c['text'] }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
                        </div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $card['label'] }}</span>
                    </div>
                    <p class="text-lg font-black {{ $c['val'] }} font-mono">TZS {{ number_format((float)$card['value'], 2) }}</p>
                </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Recent Journal Entries</h3>
                    <div class="bg-gray-50 dark:bg-zinc-800/40 rounded-xl overflow-hidden">
                        @forelse($overview['recentEntries'] as $entry)
                        <div wire:click="viewJournalEntry('{{ $entry->id }}')" class="flex items-center gap-4 px-4 py-3 border-b border-gray-100 dark:border-zinc-800 last:border-0 hover:bg-white dark:hover:bg-zinc-800 transition-colors cursor-pointer group">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-orange-500 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $entry->description }}</p>
                                <p class="text-xs text-gray-400">{{ $entry->reference }} &middot; {{ $entry->date->format('d M Y') }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-bold font-mono text-orange-600 dark:text-blue-300">TZS {{ number_format((float)$entry->totalDebits(), 2) }}</p>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-bold {{ $entry->isBalanced() ? 'bg-teal-100 text-teal-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $entry->isBalanced() ? 'Balanced' : 'Unbalanced' }}
                                </span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-blue-500 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </div>
                        @empty
                        <div class="py-12 text-center">
                            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            <p class="text-sm text-gray-400 font-medium">No journal entries yet</p>
                            <p class="text-xs text-gray-300 mt-1">Post your first entry from the Journal tab</p>
                        </div>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Account Type Breakdown</h3>
                    @foreach(['Asset','Liability','Equity','Revenue','Expense'] as $t)
                    @php
                        $colors = ['Asset'=>'indigo','Liability'=>'rose','Equity'=>'violet','Revenue'=>'teal','Expense'=>'amber'];
                        $cn = $colors[$t];
                    @endphp
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-100 dark:border-zinc-800 last:border-0">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-{{ $cn }}-500"></span>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $t }}</span>
                        </div>
                        <span class="text-xs font-black font-mono text-gray-900 dark:text-gray-100">
                            TZS {{ number_format((float)$overview[$t], 2) }}
                        </span>
                    </div>
                    @endforeach
                    <div class="mt-3 p-3 rounded-xl bg-gray-50 dark:bg-zinc-800/40 flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500">Total Entries</span>
                        <span class="text-sm font-black text-orange-500">{{ number_format($overview['totalEntries']) }}</span>
                    </div>
                </div>
            </div>

            {{-- ApexCharts: Monthly Revenue vs Expenses --}}
            <div class="mt-6 bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 p-5 shadow-sm">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">6-Month Revenue vs Expenses</h3>
                <div id="overview-chart" style="min-height:200px"></div>
            </div>
        </div>

        <script>
        (function(){
            var categories = @json($chartData['categories']);
            var revenue    = @json($chartData['revenue']);
            var expenses   = @json($chartData['expenses']);
            if(typeof ApexCharts === 'undefined' || !categories.length) return;
            var el = document.getElementById('overview-chart');
            if(!el) return;
            new ApexCharts(el, {
                chart: { type:'bar', height:200, toolbar:{show:false}, fontFamily:'inherit' },
                series: [
                    { name:'Revenue', data: revenue },
                    { name:'Expenses', data: expenses }
                ],
                xaxis: { categories: categories, labels:{ style:{ fontSize:'11px' } } },
                colors: ['#14b8a6','#f43f5e'],
                plotOptions: { bar:{ columnWidth:'55%', borderRadius:4 } },
                dataLabels: { enabled:false },
                legend: { position:'top', fontSize:'12px' },
                grid: { borderColor:'#f1f5f9', strokeDashArray:3 },
                yaxis: { labels:{ formatter: v => 'TZS '+Number(v).toLocaleString() } },
                tooltip: { y:{ formatter: v => 'TZS '+Number(v).toLocaleString() } }
            }).render();
        })();
        </script>
        @endif

        {{-- ════════════════════════════════════════════════════════
             TAB: CHART OF ACCOUNTS
        ════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'chart')
        <div>
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-800 flex items-center gap-3 flex-wrap">
                <input wire:model.live.debounce.300="accountSearch" type="text" placeholder="Search code or name…"
                       class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 w-64" />
                <select wire:model.live="accountTypeFilter"
                        class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">All Types</option>
                    @foreach(['Asset','Liability','Equity','Revenue','Expense'] as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#1a2035] text-white text-[11px] font-semibold uppercase tracking-widest">
                            <th class="px-6 py-3.5 text-left">Code</th>
                            <th class="px-4 py-3.5 text-left">Account Name</th>
                            <th class="px-4 py-3.5 text-center">Type</th>
                            <th class="px-4 py-3.5 text-right">Computed Balance (TZS)</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @forelse($accounts as $acc)
                        @php
                        $typeBg = ['Asset'=>'bg-blue-100 text-orange-600','Liability'=>'bg-rose-100 text-rose-700','Equity'=>'bg-blue-100 text-orange-600','Revenue'=>'bg-teal-100 text-teal-700','Expense'=>'bg-amber-100 text-amber-700'];
                        $computed = $acc->computedBalance();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/40 transition-colors">
                            <td class="px-6 py-3 font-mono font-bold text-gray-700 dark:text-gray-300 text-xs">{{ $acc->code }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $acc->name }}</p>
                                @if($acc->description)
                                <p class="text-xs text-gray-400 truncate max-w-xs">{{ $acc->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $typeBg[$acc->type] ?? 'bg-gray-100 text-gray-600' }}">{{ $acc->type }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold {{ (float)$computed >= 0 ? 'text-teal-700 dark:text-teal-400' : 'text-red-600' }}">
                                {{ number_format((float)$computed, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $acc->is_active ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $acc->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <div class="flex items-center gap-3 justify-end">
                                    <button wire:click="openAccountModal('{{ $acc->id }}')"
                                            class="text-xs text-orange-500 hover:text-blue-800 font-semibold transition-colors">Edit</button>
                                    <button wire:click="toggleAccountStatus('{{ $acc->id }}')" wire:confirm="{{ $acc->is_active ? 'Deactivate this account?' : 'Activate this account?' }}"
                                            class="text-xs font-semibold transition-colors {{ $acc->is_active ? 'text-rose-500 hover:text-rose-700' : 'text-teal-600 hover:text-teal-800' }}">
                                        {{ $acc->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="py-16 text-center text-gray-400 text-sm">No accounts found. Create your first account.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($accounts->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-zinc-800">{{ $accounts->links() }}</div>
            @endif
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════
             TAB: JOURNAL
        ════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'journal')
        <div>
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-800 flex items-center gap-3 flex-wrap justify-between">
                <div class="flex items-center gap-3 flex-wrap">
                    <input wire:model.live.debounce.300="journalSearch" type="text" placeholder="Search reference or description…"
                           class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 w-64" />
                    <input wire:model.live="journalDateFrom" type="date"
                           class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                    <span class="text-xs text-gray-400">to</span>
                    <input wire:model.live="journalDateTo" type="date"
                           class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                </div>
                @can('accounting.export')
                <button wire:click="exportJournalExcel" wire:loading.attr="disabled"
                        class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold border border-gray-200 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 rounded-xl transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Export Excel
                </button>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#1a2035] text-white text-[11px] font-semibold uppercase tracking-widest">
                            <th class="px-6 py-3.5 text-left">Reference</th>
                            <th class="px-4 py-3.5 text-left">Date</th>
                            <th class="px-4 py-3.5 text-left">Description</th>
                            <th class="px-4 py-3.5 text-center">Lines</th>
                            <th class="px-4 py-3.5 text-right">Total Debit (TZS)</th>
                            <th class="px-4 py-3.5 text-center">Source</th>
                            <th class="px-4 py-3.5 text-center">Balanced</th>
                            <th class="px-4 py-3.5 text-left">Posted By</th>
                        <th class="px-4 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @forelse($journalEntries as $entry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/40 transition-colors cursor-pointer group" wire:click="viewJournalEntry('{{ $entry->id }}')">
                            <td class="px-6 py-3 font-mono font-bold text-orange-600 dark:text-blue-300 text-xs">{{ $entry->reference }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $entry->date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate max-w-xs">{{ $entry->description }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-bold text-gray-500">{{ $entry->lines->count() }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-gray-900 dark:text-gray-100 text-xs">
                                {{ number_format((float)$entry->totalDebits(), 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $entry->source === 'manual' ? 'bg-blue-100 text-orange-600' : 'bg-teal-100 text-teal-700' }}">
                                    {{ ucfirst(str_replace('_', ' ', $entry->source)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($entry->isBalanced())
                                <svg class="w-4 h-4 text-teal-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                @else
                                <svg class="w-4 h-4 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $entry->creator?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-blue-500 transition-colors mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="py-16 text-center">
                            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            <p class="text-sm text-gray-400 font-medium">No journal entries found</p>
                            <p class="text-xs text-gray-300 mt-1">Adjust your filters or post a new manual entry</p>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($journalEntries->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-zinc-800">{{ $journalEntries->links() }}</div>
            @endif
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════
             TAB: LEDGER
        ════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'ledger')
        <div>
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-800 flex items-center gap-3 flex-wrap justify-between">
                <div class="flex items-center gap-3 flex-wrap">
                    <select wire:model.live="ledgerAccountId"
                            class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 min-w-[260px]">
                        <option value="">Select account to view ledger…</option>
                        @foreach($allAccounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->code }} – {{ $acc->name }}</option>
                        @endforeach
                    </select>
                    @if($ledgerAccount)
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold
                        {{ ['Asset'=>'bg-blue-100 text-orange-600','Liability'=>'bg-rose-100 text-rose-700','Equity'=>'bg-blue-100 text-orange-600','Revenue'=>'bg-teal-100 text-teal-700','Expense'=>'bg-amber-100 text-amber-700'][$ledgerAccount->type] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $ledgerAccount->type }}
                    </span>
                    @endif
                </div>
                @if($ledgerAccountId)
                <input wire:model.live="ledgerDateFrom" type="date" title="From"
                       class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                <span class="text-xs text-gray-400">to</span>
                <input wire:model.live="ledgerDateTo" type="date" title="To"
                       class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                @can('accounting.export')
                <button wire:click="exportLedgerExcel"
                        class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold border border-gray-200 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 rounded-xl transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Export Excel
                </button>
                @endcan
                @endif
            </div>
            @if($ledgerAccountId && $ledgerLines->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#1a2035] text-white text-[11px] font-semibold uppercase tracking-widest">
                            <th class="px-6 py-3.5 text-left">Date</th>
                            <th class="px-4 py-3.5 text-left">Reference</th>
                            <th class="px-4 py-3.5 text-left">Description</th>
                            <th class="px-4 py-3.5 text-left">Narration</th>
                            <th class="px-4 py-3.5 text-right">Debit (TZS)</th>
                            <th class="px-4 py-3.5 text-right">Credit (TZS)</th>
                            <th class="px-4 py-3.5 text-right">Balance (TZS)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @foreach($ledgerLines as $line)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/40 transition-colors">
                            <td class="px-6 py-2.5 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $line->date?->format('d M Y') }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs font-bold text-orange-600 dark:text-blue-300">{{ $line->reference }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-700 dark:text-gray-300 truncate max-w-xs">{{ $line->description }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-400">{{ $line->narration ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs font-semibold {{ (float)$line->debit > 0 ? 'text-orange-600 dark:text-blue-300' : 'text-gray-300' }}">
                                {{ (float)$line->debit > 0 ? number_format((float)$line->debit, 2) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs font-semibold {{ (float)$line->credit > 0 ? 'text-teal-700 dark:text-teal-300' : 'text-gray-300' }}">
                                {{ (float)$line->credit > 0 ? number_format((float)$line->credit, 2) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs font-black {{ (float)$line->balance >= 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600' }}">
                                {{ number_format((float)$line->balance, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @elseif($ledgerAccountId)
            <div class="py-16 text-center text-gray-400 text-sm">No transactions recorded for this account.</div>
            @else
            <div class="py-16 text-center text-gray-400 text-sm">Select an account from the dropdown above to view its ledger.</div>
            @endif
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════
             TAB: P&L
        ════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'pl')
        <div class="p-6">
            <div class="flex items-center gap-3 mb-6 flex-wrap">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">From</label>
                    <input wire:model.live="plDateFrom" type="date"
                           class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">To</label>
                    <input wire:model.live="plDateTo" type="date"
                           class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500" />
                </div>
                @can('accounting.export')
                <button wire:click="exportPnlPdf" wire:loading.attr="disabled"
                        class="ml-auto flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold border border-gray-200 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 rounded-xl transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Export PDF
                </button>
                @endcan
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-teal-50 dark:bg-teal-900/10 rounded-2xl border border-teal-100 dark:border-teal-900/30 overflow-hidden">
                    <div class="px-5 py-3 bg-teal-600 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-white">Revenue</h3>
                        <span class="text-xs font-black text-white font-mono">TZS {{ number_format((float)$pnlData['totalRevenue'], 2) }}</span>
                    </div>
                    <div class="divide-y divide-teal-100 dark:divide-teal-900/30">
                        @forelse($pnlData['revenue'] as $r)
                        <div class="flex items-center justify-between px-5 py-2.5 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-colors">
                            <div>
                                <span class="font-mono text-xs text-teal-600 font-bold mr-2">{{ $r['code'] }}</span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $r['name'] }}</span>
                            </div>
                            <span class="font-mono text-sm font-bold text-teal-700 dark:text-teal-300">{{ number_format((float)$r['balance'], 2) }}</span>
                        </div>
                        @empty
                        <div class="py-8 text-center text-sm text-gray-400">No revenue accounts</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-rose-50 dark:bg-rose-900/10 rounded-2xl border border-rose-100 dark:border-rose-900/30 overflow-hidden">
                    <div class="px-5 py-3 bg-rose-600 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-white">Expenses</h3>
                        <span class="text-xs font-black text-white font-mono">TZS {{ number_format((float)$pnlData['totalExpense'], 2) }}</span>
                    </div>
                    <div class="divide-y divide-rose-100 dark:divide-rose-900/30">
                        @forelse($pnlData['expenses'] as $e)
                        <div class="flex items-center justify-between px-5 py-2.5 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors">
                            <div>
                                <span class="font-mono text-xs text-rose-600 font-bold mr-2">{{ $e['code'] }}</span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $e['name'] }}</span>
                            </div>
                            <span class="font-mono text-sm font-bold text-rose-700 dark:text-rose-300">{{ number_format((float)$e['balance'], 2) }}</span>
                        </div>
                        @empty
                        <div class="py-8 text-center text-sm text-gray-400">No expense accounts</div>
                        @endforelse
                    </div>
                </div>
            </div>

            @php $netProfit = (float)$pnlData['netProfit']; @endphp
            <div class="mt-5 p-5 rounded-2xl border-2 {{ $netProfit >= 0 ? 'bg-teal-50 border-teal-200 dark:bg-teal-900/10 dark:border-teal-900/30' : 'bg-red-50 border-red-200 dark:bg-red-900/10 dark:border-red-900/30' }} flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold {{ $netProfit >= 0 ? 'text-teal-600' : 'text-red-600' }} uppercase tracking-wider">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $plDateFrom }} – {{ $plDateTo }}</p>
                </div>
                <p class="text-2xl font-black font-mono {{ $netProfit >= 0 ? 'text-teal-700 dark:text-teal-300' : 'text-red-600' }}">
                    {{ $netProfit >= 0 ? '' : '(' }}TZS {{ number_format(abs($netProfit), 2) }}{{ $netProfit >= 0 ? '' : ')' }}
                </p>
            </div>
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════
             TAB: SELCOM IMPORT
        ════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'selcom')
        <div class="p-6">
            <div class="max-w-xl mb-6">
                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">Upload Selcom Statement</h3>
                <p class="text-xs text-gray-400 mb-4">Upload a CSV file exported from your Selcom portal. Expected columns: Date, Description, Amount (and optionally Account Code, Balance).</p>

                <label class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-gray-300 dark:border-zinc-600 rounded-2xl cursor-pointer bg-gray-50 dark:bg-zinc-800/40 hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg class="w-8 h-8 text-gray-300 dark:text-zinc-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    <span class="text-sm font-semibold text-gray-400">{{ $selcomFile ? $selcomFile->getClientOriginalName() : 'Click to upload CSV' }}</span>
                    <span class="text-xs text-gray-400 mt-1">CSV / TXT up to 5 MB</span>
                    <input wire:model="selcomFile" type="file" accept=".csv,.txt" class="hidden" />
                </label>

                @error('selcomFile') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror

                <div class="flex gap-3 mt-4">
                    <button wire:click="parseSelcomStatement" wire:loading.attr="disabled"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-bold bg-orange-500 hover:bg-orange-600 disabled:opacity-60 text-white rounded-xl transition-colors shadow-sm">
                        <span wire:loading.remove wire:target="parseSelcomStatement">Parse File</span>
                        <span wire:loading wire:target="parseSelcomStatement" class="flex items-center gap-1.5">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            Parsing…
                        </span>
                    </button>

                    @if(!empty($parsedRows))
                    <button wire:click="postSelcomToLedger" wire:loading.attr="disabled"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-bold bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white rounded-xl transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        Post {{ count($parsedRows) }} Rows to Ledger
                    </button>
                    @endif
                </div>

                @if($parseMessage)
                <div class="mt-3 p-3 rounded-xl text-xs font-semibold {{ $parseError ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-teal-50 text-teal-700 border border-teal-200' }}">
                    {{ $parseMessage }}
                </div>
                @endif
            </div>

            @if(!empty($parsedRows))
            <div class="rounded-xl border border-gray-200 dark:border-zinc-700 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-[#1a2035] text-white">
                            @foreach(array_keys($parsedRows[0] ?? []) as $col)
                            <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wider">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @foreach(array_slice($parsedRows, 0, 20) as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/40">
                            @foreach($row as $cell)
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $cell }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($parsedRows) > 20)
                <div class="px-4 py-2 text-xs text-gray-400 border-t border-gray-100 dark:border-zinc-800">
                    Showing 20 of {{ count($parsedRows) }} rows. All rows will be posted.
                </div>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- ════════════════════════════════════════════════════════
             TAB: RECONCILIATION
        ════════════════════════════════════════════════════════ --}}
        @if($activeTab === 'reconciliation')
        <div class="p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-orange-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200">Ledger vs Selcom Import Reconciliation</h3>
                    <p class="text-xs text-gray-400">Upload a Selcom CSV in the Import tab first, then return here to see variance.</p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-zinc-700 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#1a2035] text-white text-[11px] font-semibold uppercase tracking-widest">
                            <th class="px-6 py-3.5 text-left">Code</th>
                            <th class="px-4 py-3.5 text-left">Account Name</th>
                            <th class="px-4 py-3.5 text-center">Type</th>
                            <th class="px-4 py-3.5 text-right">Internal Balance (TZS)</th>
                            <th class="px-4 py-3.5 text-right">Selcom Balance (TZS)</th>
                            <th class="px-4 py-3.5 text-right">Variance (TZS)</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @forelse($recoRows as $row)
                        @php
                        $typeBg = ['Asset'=>'bg-blue-100 text-orange-600','Liability'=>'bg-rose-100 text-rose-700','Equity'=>'bg-blue-100 text-orange-600','Revenue'=>'bg-teal-100 text-teal-700','Expense'=>'bg-amber-100 text-amber-700'];
                        $hasImport = $row['imported'] !== null;
                        $variance  = $row['variance'];
                        $matched   = $variance !== null && bccomp($variance, '0', 4) === 0;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/40 transition-colors {{ $hasImport && !$matched ? 'bg-rose-50/40 dark:bg-rose-900/5' : '' }}">
                            <td class="px-6 py-3 font-mono font-bold text-gray-700 dark:text-gray-300 text-xs">{{ $row['code'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $typeBg[$row['type']] ?? 'bg-gray-100 text-gray-600' }}">{{ $row['type'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-gray-900 dark:text-gray-100 text-xs">
                                {{ number_format((float)$row['internal'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs {{ $hasImport ? 'font-bold text-orange-600 dark:text-blue-300' : 'text-gray-300' }}">
                                {{ $hasImport ? number_format((float)$row['imported'], 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-xs {{ !$hasImport ? 'text-gray-300' : ($matched ? 'text-teal-600 font-bold' : 'text-red-600 font-bold') }}">
                                @if($hasImport)
                                    {{ $matched ? '0.00' : number_format((float)$variance, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(!$hasImport)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500">No Import</span>
                                @elseif($matched)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-700">Matched</span>
                                @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">Variance</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="py-16 text-center text-gray-400 text-sm">No accounts in Chart of Accounts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

</div>
