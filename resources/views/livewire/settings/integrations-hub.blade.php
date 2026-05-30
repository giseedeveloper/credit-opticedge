<div>
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,4000)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : 'bg-red-500'"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2">
        <flux:icon name="check-circle" class="w-4 h-4 shrink-0" />
        <span x-text="msg"></span>
    </div>

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <div class="p-3 rounded-2xl bg-gradient-to-br from-oe to-teal-500 text-white shadow-lg shadow-oe/25">
                <flux:icon name="puzzle-piece" class="w-7 h-7" />
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Integration Hub</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-xl">
                    SMS, device lock (MDM), and face-match — configure vendors via <code class="text-xs bg-gray-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">.env</code>.
                    Use <strong>HTTP</strong> drivers for any provider until a named adapter is added.
                </p>
            </div>
        </div>
        <button wire:click="refresh" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold bg-oe hover:bg-oe-hover text-white rounded-xl shadow-sm transition-colors disabled:opacity-60">
            <flux:icon name="arrow-path" class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refresh" />
            Refresh status
        </button>
    </div>

    @php
        $sms = $snapshot['sms'] ?? [];
        $mdm = $snapshot['mdm'] ?? [];
        $face = $snapshot['face_match'] ?? [];
        $cards = [
            [
                'key' => 'sms',
                'title' => 'SMS Gateway',
                'icon' => 'chat-bubble-left-right',
                'gradient' => 'from-sky-500 to-blue-600',
                'data' => $sms,
                'env' => 'SMS_DRIVER, SMS_HTTP_* or BEEM_*',
            ],
            [
                'key' => 'mdm',
                'title' => 'MDM / Device Lock',
                'icon' => 'lock-closed',
                'gradient' => 'from-violet-500 to-purple-600',
                'data' => $mdm,
                'env' => 'MDM_DRIVER, MDM_HTTP_*',
            ],
            [
                'key' => 'face',
                'title' => 'Face Match (KYC)',
                'icon' => 'face-smile',
                'gradient' => 'from-amber-500 to-orange-600',
                'data' => [
                    'ready' => $face['ready'] ?? false,
                    'mode' => ($face['url_configured'] ?? false) ? 'live' : 'review_fallback',
                    'configured_driver' => $face['required'] ? 'required' : 'optional',
                    'active_driver' => $face['url_configured'] ? 'http' : 'none',
                    'label' => $face['label'] ?? 'Unknown',
                    'hint' => ($face['required'] ?? false) && ! ($face['url_configured'] ?? false)
                        ? 'Production requires FACE_MATCH_URL — KYC will fail closed.'
                        : 'Set FACE_MATCH_URL for automated face verification.',
                ],
                'env' => 'FACE_MATCH_URL, FACE_MATCH_REQUIRED',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
        @foreach($cards as $card)
            @php $d = $card['data']; $ready = $d['ready'] ?? false; $mode = $d['mode'] ?? 'simulation'; @endphp
            <div class="group relative overflow-hidden rounded-2xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-shadow">
                <div class="h-1.5 bg-gradient-to-r {{ $card['gradient'] }}"></div>
                <div class="p-6">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 text-gray-700 dark:text-gray-200">
                                <flux:icon :name="$card['icon']" class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ $card['title'] }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $d['label'] ?? '' }}</p>
                            </div>
                        </div>
                        <span class="shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide
                            {{ $ready ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                            <span class="w-2 h-2 rounded-full {{ $ready ? 'bg-teal-500' : 'bg-amber-400' }}"></span>
                            {{ $ready ? 'Ready' : 'Setup' }}
                        </span>
                    </div>

                    <dl class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400">Configured</dt>
                            <dd class="font-mono text-xs text-gray-700 dark:text-gray-200">{{ $d['configured_driver'] ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400">Active</dt>
                            <dd class="font-mono text-xs text-gray-700 dark:text-gray-200">{{ $d['active_driver'] ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400">Mode</dt>
                            <dd>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md
                                    {{ $mode === 'live' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200' : 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-gray-300' }}">
                                    {{ $mode === 'live' ? 'Live' : 'Simulation' }}
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed border-t border-gray-100 dark:border-zinc-800 pt-3">
                        {{ $d['hint'] ?? '' }}
                    </p>
                    <p class="mt-2 text-[10px] font-mono text-gray-400">{{ $card['env'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="rounded-2xl border border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex items-center gap-2">
                <flux:icon name="paper-airplane" class="w-5 h-5 text-oe" />
                <h3 class="font-bold text-gray-900 dark:text-white">Test SMS dispatch</h3>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-500">Queues a real <code class="text-xs bg-gray-100 dark:bg-zinc-800 px-1 rounded">SendSmsJob</code> using the active driver above.</p>
                <flux:input wire:model="testPhone" label="Phone" placeholder="2557XXXXXXXX" />
                <flux:textarea wire:model="testMessage" label="Message" rows="3" />
                <flux:button wire:click="sendTestSms" variant="primary" icon="paper-airplane">
                    Send test SMS
                </flux:button>
            </div>
        </div>

        <div class="rounded-2xl border border-dashed border-gray-200 dark:border-zinc-700 bg-gradient-to-br from-gray-50 to-white dark:from-zinc-900 dark:to-zinc-950 p-6">
            <h3 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <flux:icon name="document-text" class="w-5 h-5 text-oe" />
                Vendor setup cheat sheet
            </h3>
            <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <li class="flex gap-2">
                    <span class="font-bold text-oe shrink-0">1.</span>
                    <span><strong>Any SMS API:</strong> <code class="text-xs">SMS_DRIVER=http</code> + <code class="text-xs">SMS_HTTP_URL</code> (POST JSON: phone, message, sender_id).</span>
                </li>
                <li class="flex gap-2">
                    <span class="font-bold text-oe shrink-0">2.</span>
                    <span><strong>BEEM:</strong> <code class="text-xs">SMS_DRIVER=beem</code> + BEEM credentials (Tanzania).</span>
                </li>
                <li class="flex gap-2">
                    <span class="font-bold text-oe shrink-0">3.</span>
                    <span><strong>MDM vendor:</strong> <code class="text-xs">MDM_DRIVER=http</code> + lock/unlock webhook URLs.</span>
                </li>
                <li class="flex gap-2">
                    <span class="font-bold text-oe shrink-0">4.</span>
                    <span>Send your vendor’s API doc to engineering — we add a named driver without changing jobs.</span>
                </li>
            </ul>
            <a href="{{ route('comms.sms') }}" wire:navigate
               class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-oe hover:underline">
                View SMS activity logs
                <flux:icon name="arrow-right" class="w-4 h-4" />
            </a>
        </div>
    </div>
</div>
