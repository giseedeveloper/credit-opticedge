{{-- KYC Wizard — 7-Step Front Office Application --}}
<div class="flex flex-col gap-6">

    {{-- Toast --}}
    <div x-data="{ show:false, msg:'', type:'success' }"
         x-on:toast.window="msg=$event.detail.message; type=$event.detail.type; show=true; setTimeout(()=>show=false,4000)"
         x-show="show" x-transition
         :class="type==='success' ? 'bg-teal-600' : (type==='error' ? 'bg-red-500' : 'bg-amber-500')"
         class="fixed bottom-5 right-5 z-[60] text-white text-sm font-medium px-5 py-3 rounded-xl shadow-xl flex items-center gap-2" style="display:none">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span x-text="msg"></span>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-start gap-4">
            <x-fluent-icon name="identification" size="lg" palette="teal" />
            <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Customer Acquisition Center</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">7-step agent KYC onboarding — device, identity, contact, income, NOK, consent &amp; submit</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('kyc.pending') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                <x-fluent-icon name="clock" size="xs" palette="amber" />
                Pending Queue
            </a>
            <a href="{{ route('kyc.customers') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                <x-fluent-icon name="users" size="xs" palette="sky" />
                All Profiles
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Wizard ══ --}}
        <div class="lg:col-span-3">

            @if($submitted)
            {{-- ══ Success State ══ --}}
            @php
                $acStatus  = $autoCheckResult['status'] ?? 'passed';
                $acChecks  = $autoCheckResult['checks'] ?? [];
                $acColor   = match($acStatus) {
                    'passed'           => 'emerald',
                    'needs_correction' => 'amber',
                    'manual_review'    => 'blue',
                    'auto_rejected'    => 'red',
                    default            => 'gray',
                };
                $acLabel   = match($acStatus) {
                    'passed'           => 'Auto-checks Passed — queued for review',
                    'needs_correction' => 'Needs Correction — some fields require attention',
                    'manual_review'    => 'Flagged for Manual Review',
                    'auto_rejected'    => 'Auto-Rejected — hard rule violation detected',
                    default            => 'Submitted',
                };
            @endphp
            <div class="space-y-4">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-8 text-center">
                    <div class="w-16 h-16 rounded-full bg-{{ $acColor }}-100 dark:bg-{{ $acColor }}-900/30 flex items-center justify-center mx-auto mb-4">
                        @if($acStatus === 'auto_rejected')
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @else
                        <svg class="w-8 h-8 text-{{ $acColor }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-white">Application Submitted!</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $submittedName }}</span> has been registered.
                    </p>
                    <span class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-{{ $acColor }}-100 text-{{ $acColor }}-700">
                        <span class="w-2 h-2 rounded-full bg-{{ $acColor }}-400"></span>
                        {{ $acLabel }}
                    </span>
                    <div class="flex justify-center gap-3 mt-5">
                        <button wire:click="startNew" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:opacity-90 transition-opacity">
                            <flux:icon name="plus" class="size-4" /> Register Another
                        </button>
                        <a href="{{ route('kyc.pending') }}" wire:navigate class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 transition-colors">
                            View Pending Queue
                        </a>
                    </div>
                </div>

                {{-- Auto-check breakdown --}}
                @if(count($acChecks))
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-5">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Auto-Check Results</p>
                    <div class="space-y-2">
                        @foreach($acChecks as $key => $check)
                        <div class="flex items-start gap-3 text-sm">
                            @if($check['pass'])
                            <svg class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            @else
                            <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                            <div>
                                <span class="font-semibold text-gray-700 dark:text-gray-200 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                                <span class="text-gray-500"> — {{ $check['message'] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @else

            {{-- Stepper --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">

                {{-- Step Header --}}
                <div class="px-6 pt-5 pb-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50/60 dark:bg-zinc-800/60">
                    @php
                    $steps = [
                        1 => ['label' => 'Device',   'desc' => 'IMEI & price'],
                        2 => ['label' => 'Identity', 'desc' => 'NIDA & docs'],
                        3 => ['label' => 'Contact',  'desc' => 'Phone & area'],
                        4 => ['label' => 'Income',   'desc' => 'Work & pay'],
                        5 => ['label' => 'NOK',      'desc' => 'Next of kin'],
                        6 => ['label' => 'Consent',  'desc' => 'Terms & data'],
                        7 => ['label' => 'Submit',   'desc' => 'FO notes'],
                    ];
                    @endphp
                    <div class="flex items-center gap-0">
                        @foreach($steps as $n => $s)
                        <div class="flex items-center {{ $n < 7 ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-colors
                                    {{ $step > $n ? 'bg-emerald-500 text-white' : ($step === $n ? 'bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-md' : 'bg-gray-200 dark:bg-zinc-700 text-gray-500 dark:text-gray-400') }}">
                                    @if($step > $n)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                    {{ $n }}
                                    @endif
                                </div>
                                <span class="text-[9px] mt-1 font-semibold {{ $step >= $n ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">{{ $s['label'] }}</span>
                            </div>
                            @if($n < 7)
                            <div class="flex-1 h-[2px] mx-1 mt-[-12px] rounded {{ $step > $n ? 'bg-emerald-400' : 'bg-gray-200 dark:bg-zinc-700' }}"></div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-2 text-[10px] text-gray-400">Step {{ $step }} of 7</div>
                </div>

                {{-- Form Body --}}
                <div class="p-6">
                    <form wire:submit.prevent="{{ $step === 7 ? 'processApplication' : 'nextStep' }}" enctype="multipart/form-data">

                        {{-- ═══ STEP 1: DEVICE ═══ --}}
                        @if($step === 1)
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Device Information</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Choose the exact device from stock, then confirm IMEI, serial, price &amp; proof photos</p>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <flux:field>
                                    <flux:label>Device Brand <span class="text-red-500">*</span></flux:label>
                                    <flux:select wire:model.live="brandId">
                                        <flux:select.option value="">— Select brand —</flux:select.option>
                                        @foreach($availableBrands as $brand)
                                        <flux:select.option :value="$brand->id">{{ $brand->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="brandId" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Phone Model <span class="text-red-500">*</span></flux:label>
                                    <flux:select wire:model.live="phoneModelId" :disabled="$brandId === ''">
                                        <flux:select.option value="">— Select model —</flux:select.option>
                                        @foreach($availableModels as $model)
                                        <flux:select.option :value="$model->id">{{ $model->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="phoneModelId" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Find Stock Unit</flux:label>
                                    <flux:input wire:model.live.debounce.300ms="inventorySearch" placeholder="Search IMEI, serial or model…" />
                                    <flux:description>Filter the stock list before picking a specific device.</flux:description>
                                </flux:field>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Available Stock Unit <span class="text-red-500">*</span></flux:label>
                                    <flux:select wire:model.live="inventoryUnitId" :disabled="$phoneModelId === ''">
                                        <flux:select.option value="">— Select stock unit —</flux:select.option>
                                        @foreach($availableUnits as $unit)
                                        <flux:select.option :value="$unit->id">
                                            {{ $unit->phoneModel?->brand?->name }} {{ $unit->phoneModel?->name }} · {{ $unit->imei_1 }}{{ $unit->serial_number ? ' · '.$unit->serial_number : '' }}
                                        </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="inventoryUnitId" />
                                    <flux:description>{{ $availableUnits->count() }} matching unit(s) available for this step.</flux:description>
                                </flux:field>
                                <flux:field>
                                    <flux:label>Device Summary <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="deviceSpecs" placeholder="Auto-filled from selected model" readonly />
                                    <flux:error name="deviceSpecs" />
                                    <flux:description>Model, storage, RAM and color are pulled from the catalog automatically.</flux:description>
                                </flux:field>
                            </div>

                            @if($selectedPhoneModel || $selectedInventoryUnit)
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                @if($selectedPhoneModel)
                                <div class="rounded-2xl border border-sky-100 bg-sky-50/80 p-4">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-sky-500">Chosen Model</p>
                                    <p class="mt-2 text-sm font-black text-slate-900">{{ $selectedPhoneModel->brand?->name }} {{ $selectedPhoneModel->name }}</p>
                                    <p class="mt-1 text-xs text-slate-600">
                                        @foreach(($selectedPhoneModel->specifications ?? []) as $label => $value)
                                            {{ ucfirst($label) }}: {{ $value }}@if(! $loop->last) · @endif
                                        @endforeach
                                    </p>
                                    <p class="mt-2 text-xs font-semibold text-sky-700">Recommended cash price: TZS {{ number_format((float) $selectedPhoneModel->retail_price) }}</p>
                                </div>
                                @endif
                                @if($selectedInventoryUnit)
                                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 p-4">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-500">Linked Inventory</p>
                                    <p class="mt-2 text-sm font-black text-slate-900">{{ $selectedInventoryUnit->imei_1 }}</p>
                                    <p class="mt-1 text-xs text-slate-600">
                                        {{ $selectedInventoryUnit->serial_number ?: 'No serial saved' }}
                                        @if($selectedInventoryUnit->vendor)
                                            · {{ $selectedInventoryUnit->vendor->name }}
                                        @elseif($selectedInventoryUnit->branch)
                                            · {{ $selectedInventoryUnit->branch->name }}
                                        @endif
                                    </p>
                                    <p class="mt-2 text-xs font-semibold text-emerald-700">This unit is now tied to the application draft for confirmation.</p>
                                </div>
                                @endif
                            </div>
                            @endif

                            <div class="rounded-2xl border border-gray-200 bg-white/80 p-4 shadow-sm">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <h4 class="text-sm font-black text-gray-900">Store Offers & Accessories</h4>
                                        <p class="mt-1 text-xs text-gray-500">Capture free gifts, discounted add-ons, or paid extras handed over with the phone.</p>
                                    </div>
                                    <button type="button" wire:click="addCustomAccessory"
                                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50">
                                        <flux:icon name="plus" class="size-4" />
                                        Add Custom Item
                                    </button>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($accessoryPresets as $preset)
                                    <button type="button" wire:click="addAccessoryPreset('{{ $preset['code'] }}')"
                                            class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-100">
                                        {{ $preset['name'] }}
                                    </button>
                                    @endforeach
                                </div>

                                @if($deviceAccessories !== [])
                                <div class="mt-4 space-y-3">
                                    @foreach($deviceAccessories as $index => $accessory)
                                    <div wire:key="accessory-{{ $index }}" class="rounded-2xl border border-gray-100 bg-gray-50/90 p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-gray-400">Accessory {{ $index + 1 }}</p>
                                            <button type="button" wire:click="removeAccessoryItem({{ $index }})"
                                                    class="inline-flex items-center gap-1 text-xs font-semibold text-red-500 hover:text-red-600">
                                                <flux:icon name="trash" class="size-4" />
                                                Remove
                                            </button>
                                        </div>
                                        <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-12">
                                            <div class="lg:col-span-4">
                                                <flux:field>
                                                    <flux:label>Accessory Name</flux:label>
                                                    <flux:input wire:model="deviceAccessories.{{ $index }}.name" placeholder="e.g. Screen Protector" />
                                                    @error("deviceAccessories.$index.name") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                                </flux:field>
                                            </div>
                                            <div class="lg:col-span-2">
                                                <flux:field>
                                                    <flux:label>Qty</flux:label>
                                                    <flux:input wire:model="deviceAccessories.{{ $index }}.quantity" type="number" min="1" max="10" />
                                                    @error("deviceAccessories.$index.quantity") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                                </flux:field>
                                            </div>
                                            <div class="lg:col-span-3">
                                                <flux:field>
                                                    <flux:label>Offer Type</flux:label>
                                                    <flux:select wire:model="deviceAccessories.{{ $index }}.offer_type">
                                                        <flux:select.option value="free">Free Gift</flux:select.option>
                                                        <flux:select.option value="discounted">Discounted</flux:select.option>
                                                        <flux:select.option value="charged">Charged</flux:select.option>
                                                    </flux:select>
                                                    @error("deviceAccessories.$index.offer_type") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                                </flux:field>
                                            </div>
                                            <div class="lg:col-span-3">
                                                <flux:field>
                                                    <flux:label>Unit Price (TZS)</flux:label>
                                                    <flux:input wire:model="deviceAccessories.{{ $index }}.unit_price" type="number" min="0" placeholder="0 for free" />
                                                    @error("deviceAccessories.$index.unit_price") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                                </flux:field>
                                            </div>
                                            <div class="lg:col-span-12">
                                                <flux:field>
                                                    <flux:label>Notes</flux:label>
                                                    <flux:input wire:model="deviceAccessories.{{ $index }}.notes" placeholder="Promo reason, brand of cover, colour, or handover note" />
                                                    @error("deviceAccessories.$index.notes") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                                </flux:field>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                                <div class="mt-4">
                                    <flux:field>
                                        <flux:label>Store Offer Notes <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                        <flux:textarea wire:model="storeOfferNotes" rows="2" placeholder="Example: Customer received a free cover and protector because of weekend promo." />
                                        <flux:error name="storeOfferNotes" />
                                    </flux:field>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>IMEI 1 <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="imeiNumber" placeholder="15 digits" class="font-mono" maxlength="15" />
                                    <flux:error name="imeiNumber" />
                                    <flux:description>Dial *#06# on device</flux:description>
                                </flux:field>
                                <flux:field>
                                    <flux:label>IMEI 2 <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                    <flux:input wire:model="imei2" placeholder="15 digits if dual SIM" class="font-mono" maxlength="15" />
                                    <flux:error name="imei2" />
                                </flux:field>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Serial Number <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                    <flux:input wire:model="serialNumber" placeholder="S/N from box or settings" class="font-mono" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Repayment Plan <span class="text-red-500">*</span></flux:label>
                                    <flux:select wire:model="preferredRepayment">
                                        <flux:select.option value="">— Select —</flux:select.option>
                                        <flux:select.option value="weekly">Weekly</flux:select.option>
                                        <flux:select.option value="biweekly">Bi-weekly</flux:select.option>
                                        <flux:select.option value="monthly">Monthly</flux:select.option>
                                    </flux:select>
                                    <flux:error name="preferredRepayment" />
                                </flux:field>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Cash Price (TZS) <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="cashPrice" type="number" min="1" placeholder="e.g. 450000" />
                                    <flux:error name="cashPrice" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Deposit / Down Payment (TZS) <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="depositAmount" type="number" min="0" placeholder="e.g. 50000" />
                                    <flux:error name="depositAmount" />
                                </flux:field>
                            </div>
                            <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-4 space-y-4">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Credit Offer Terms</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        These terms will be snapshotted onto the customer record and later used when the loan account is created.
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Interest Type <span class="text-red-500">*</span></flux:label>
                                        <flux:select wire:model="loanInterestType">
                                            <flux:select.option value="flat">Flat Rate</flux:select.option>
                                            <flux:select.option value="reducing_balance">Reducing Balance</flux:select.option>
                                        </flux:select>
                                        <flux:error name="loanInterestType" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Interest Rate (%) <span class="text-red-500">*</span></flux:label>
                                        <flux:input wire:model="loanInterestRate" type="number" min="0" step="0.01" placeholder="e.g. 3.5" />
                                        <flux:error name="loanInterestRate" />
                                    </flux:field>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Duration (Weeks) <span class="text-red-500">*</span></flux:label>
                                        <flux:input wire:model="loanDurationWeeks" type="number" min="1" max="260" placeholder="e.g. 52" />
                                        <flux:error name="loanDurationWeeks" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Grace Period (Days) <span class="text-red-500">*</span></flux:label>
                                        <flux:input wire:model="loanGracePeriodDays" type="number" min="0" max="60" placeholder="e.g. 3" />
                                        <flux:error name="loanGracePeriodDays" />
                                    </flux:field>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                @foreach([
                                    ['imeiPhoto','IMEI / Box Sticker Photo','optional', true],
                                    ['deviceBoxPhoto','Box Photo','optional', true],
                                    ['devicePhoto','Device Photo','optional', false],
                                ] as [$field,$label,$hint,$supportsScan])
                                <div @if($supportsScan) x-data="deviceIdentifierScanner($wire, '{{ $field }}')" @endif>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $label }} <span class="text-gray-400 font-normal">({{ $hint }})</span></label>
                                    <input wire:model="{{ $field }}" type="file" accept="image/*"
                                           @if($supportsScan) capture="environment" x-on:change="scan($event)" @endif
                                           class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 border border-gray-200 rounded-lg p-1" />
                                    @error($field) <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                                    <div wire:loading wire:target="{{ $field }}" class="mt-0.5 text-[10px] text-gray-400">Uploading…</div>
                                    @if($supportsScan)
                                    <div x-show="message" x-transition class="mt-1 text-[10px] text-sky-600" x-text="message"></div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            @if($scanFeedbackMessage)
                            <div @class([
                                'flex items-start gap-2 rounded-xl border p-3',
                                'border-emerald-100 bg-emerald-50 text-emerald-700' => $scanFeedbackTone === 'emerald',
                                'border-sky-100 bg-sky-50 text-sky-700' => $scanFeedbackTone === 'sky',
                                'border-red-100 bg-red-50 text-red-700' => $scanFeedbackTone === 'red',
                                'border-amber-100 bg-amber-50 text-amber-700' => $scanFeedbackTone === 'amber',
                                'border-slate-200 bg-slate-50 text-slate-700' => ! in_array($scanFeedbackTone, ['emerald', 'sky', 'red', 'amber'], true),
                            ])>
                                <flux:icon name="sparkles" class="mt-0.5 size-4 shrink-0" />
                                <div>
                                    <p class="text-xs font-semibold">Auto-scan feedback</p>
                                    <p class="mt-0.5 text-xs">{{ $scanFeedbackMessage }}</p>
                                    @if(($deviceScan['confidence'] ?? 0) > 0)
                                    <p class="mt-1 text-[10px] uppercase tracking-[0.2em] opacity-80">Confidence {{ number_format((float) $deviceScan['confidence'] * 100, 0) }}%</p>
                                    @endif
                                </div>
                            </div>
                            @endif
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-100 flex items-start gap-2">
                                <flux:icon name="information-circle" class="size-4 text-amber-600 mt-0.5 shrink-0" />
                                <p class="text-xs text-amber-700">IMEI and serial should come from the linked stock unit. Uploading the box or sticker photo will also try to auto-detect identifiers on supported browsers.</p>
                            </div>
                        </div>
                        @endif

                        {{-- ═══ STEP 2: CUSTOMER IDENTITY ═══ --}}
                        @if($step === 2)
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Customer Identity</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Legal names as on national ID + NIDA + ID photos</p>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <flux:field>
                                    <flux:label>First Name <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="firstName" placeholder="e.g. Amina" />
                                    <flux:error name="firstName" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Middle Name <span class="text-gray-400 font-normal text-xs">(opt)</span></flux:label>
                                    <flux:input wire:model="middleName" placeholder="e.g. Juma" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Last Name <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="lastName" placeholder="e.g. Mohamed" />
                                    <flux:error name="lastName" />
                                </flux:field>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Gender <span class="text-red-500">*</span></flux:label>
                                    <flux:select wire:model="gender">
                                        <flux:select.option value="">— Select —</flux:select.option>
                                        <flux:select.option value="male">Male</flux:select.option>
                                        <flux:select.option value="female">Female</flux:select.option>
                                        <flux:select.option value="other">Other</flux:select.option>
                                    </flux:select>
                                    <flux:error name="gender" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Date of Birth <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                    <flux:input wire:model="dob" type="date" />
                                </flux:field>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>NIDA Number <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="nidaNumber" placeholder="20-digit NIDA" class="font-mono" maxlength="20" />
                                    <flux:error name="nidaNumber" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>ID Type Used <span class="text-red-500">*</span></flux:label>
                                    <flux:select wire:model="idType">
                                        <flux:select.option value="">— Select —</flux:select.option>
                                        <flux:select.option value="nida">NIDA Card</flux:select.option>
                                        <flux:select.option value="passport">Passport</flux:select.option>
                                        <flux:select.option value="driving_license">Driving License</flux:select.option>
                                        <flux:select.option value="voter_card">Voter Card</flux:select.option>
                                    </flux:select>
                                    <flux:error name="idType" />
                                </flux:field>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach([
                                    ['idFrontPhoto','ID Front Photo','required'],
                                    ['idBackPhoto','ID Back Photo','required'],
                                    ['headshotPhoto','Customer Headshot','required'],
                                    ['clientFoPhoto','Client + FO Photo','optional'],
                                ] as [$field,$label,$hint])
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $label }}
                                        @if($hint === 'required') <span class="text-red-500">*</span> @else <span class="text-gray-400">(opt)</span> @endif
                                    </label>
                                    <input wire:model="{{ $field }}" type="file" accept="image/*"
                                           class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 border border-gray-200 rounded-lg p-1" />
                                    @error($field) <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                                    <div wire:loading wire:target="{{ $field }}" class="mt-0.5 text-[10px] text-gray-400">Uploading…</div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- ═══ STEP 3: CONTACT & LOCATION ═══ --}}
                        @if($step === 3)
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Contact & Location</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Phone numbers, branch, and residential address</p>
                            </div>
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4">
                                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[200px_minmax(0,1fr)]">
                                        <flux:field>
                                            <flux:label>Primary Country <span class="text-red-500">*</span></flux:label>
                                            <flux:select wire:model="phoneCountry">
                                                @foreach($phoneCountries as $country)
                                                <flux:select.option value="{{ $country['iso'] }}">{{ $country['flag'] }} {{ $country['name'] }} ({{ $country['dial_code'] }})</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:error name="phoneCountry" />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Primary Phone <span class="text-red-500">*</span></flux:label>
                                            <flux:input wire:model="phone" type="tel" placeholder="712 345 678" />
                                            <flux:error name="phone" />
                                            <flux:description>Enter local digits. The system will save it with the selected country code.</flux:description>
                                        </flux:field>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4">
                                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[200px_minmax(0,1fr)]">
                                        <flux:field>
                                            <flux:label>Alt Country</flux:label>
                                            <flux:select wire:model="altPhoneCountry">
                                                @foreach($phoneCountries as $country)
                                                <flux:select.option value="{{ $country['iso'] }}">{{ $country['flag'] }} {{ $country['name'] }} ({{ $country['dial_code'] }})</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:error name="altPhoneCountry" />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Alt Phone <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                            <flux:input wire:model="altPhone" type="tel" placeholder="744 000 111" />
                                            <flux:error name="altPhone" />
                                            <flux:description>Use a second reachable number if available.</flux:description>
                                        </flux:field>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Email <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                    <flux:input wire:model="email" type="email" placeholder="amina@example.com" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Branch <span class="text-red-500">*</span></flux:label>
                                    <flux:select wire:model="branchId">
                                        <flux:select.option value="">— Select branch —</flux:select.option>
                                        @foreach($branches as $b)
                                        <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="branchId" />
                                </flux:field>
                            </div>
                            <flux:field>
                                <flux:label>Physical Address <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                <flux:input wire:model="address" placeholder="Street, plot, ward…" />
                            </flux:field>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Region</flux:label>
                                    <flux:input wire:model="region" placeholder="e.g. Dar es Salaam" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>District</flux:label>
                                    <flux:input wire:model="district" placeholder="e.g. Kinondoni" />
                                </flux:field>
                            </div>
                            <flux:field>
                                <flux:label>Landmark <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                <flux:input wire:model="landmark" placeholder="e.g. Karibu na kanisa la ABC" />
                            </flux:field>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>GPS Latitude <span class="text-gray-400 font-normal text-xs">(optional, -90 to 90)</span></flux:label>
                                    <flux:input wire:model="latitude" type="number" step="any" placeholder="-6.7924" min="-90" max="90" />
                                    <flux:error name="latitude" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>GPS Longitude <span class="text-gray-400 font-normal text-xs">(optional, -180 to 180)</span></flux:label>
                                    <flux:input wire:model="longitude" type="number" step="any" placeholder="39.2083" min="-180" max="180" />
                                    <flux:error name="longitude" />
                                </flux:field>
                            </div>
                        </div>
                        @endif

                        {{-- ═══ STEP 4: INCOME & WORK ═══ --}}
                        @if($step === 4)
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Income & Employment</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Work details and monthly income for affordability assessment</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Occupation <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                    <flux:input wire:model="occupation" placeholder="e.g. Teacher, Trader" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Employer / Business <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                    <flux:input wire:model="employer" placeholder="e.g. Govt, Self-employed" />
                                </flux:field>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Work / Business Location <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                    <flux:input wire:model="workLocation" placeholder="e.g. Kariakoo, Dar es Salaam" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Duration at Work / Business <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                    <flux:input wire:model="durationAtWork" placeholder="e.g. 2 years, 6 months" />
                                </flux:field>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Monthly Income (TZS) <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="monthlyIncome" type="number" min="0" placeholder="e.g. 500000" />
                                    <flux:error name="monthlyIncome" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Monthly Expenses (TZS) <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                    <flux:input wire:model="monthlyExpenses" type="number" min="0" placeholder="e.g. 200000" />
                                </flux:field>
                            </div>
                            <flux:field>
                                <flux:label>Income Payment Cycle <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                <flux:select wire:model="incomePaymentCycle">
                                    <flux:select.option value="">— Select —</flux:select.option>
                                    <flux:select.option value="weekly">Weekly</flux:select.option>
                                    <flux:select.option value="biweekly">Bi-weekly</flux:select.option>
                                    <flux:select.option value="monthly">Monthly (salary)</flux:select.option>
                                    <flux:select.option value="irregular">Irregular / Daily</flux:select.option>
                                </flux:select>
                            </flux:field>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Business / Workplace Photo <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input wire:model="businessPhoto" type="file" accept="image/*"
                                       class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 border border-gray-200 rounded-lg p-1" />
                                @error('businessPhoto') <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                                <div wire:loading wire:target="businessPhoto" class="mt-0.5 text-[10px] text-gray-400">Uploading…</div>
                            </div>
                        </div>
                        @endif

                        {{-- ═══ STEP 5: NEXT OF KIN ═══ --}}
                        @if($step === 5)
                        <div class="space-y-5">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Next of Kin</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Emergency contacts for tracing and verification calls</p>
                            </div>
                            {{-- Primary NOK --}}
                            <div class="border border-gray-100 dark:border-zinc-700 rounded-xl p-4 space-y-4">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Primary NOK <span class="text-red-400">*</span></p>
                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <flux:field>
                                        <flux:label>Full Name <span class="text-red-500">*</span></flux:label>
                                        <flux:input wire:model="nokName" placeholder="e.g. John Mwangi" />
                                        <flux:error name="nokName" />
                                    </flux:field>
                                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[200px_minmax(0,1fr)]">
                                        <flux:field>
                                            <flux:label>Country <span class="text-red-500">*</span></flux:label>
                                            <flux:select wire:model="nokPhoneCountry">
                                                @foreach($phoneCountries as $country)
                                                <flux:select.option value="{{ $country['iso'] }}">{{ $country['flag'] }} {{ $country['name'] }} ({{ $country['dial_code'] }})</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:error name="nokPhoneCountry" />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Phone <span class="text-red-500">*</span></flux:label>
                                            <flux:input wire:model="nokPhone" type="tel" placeholder="713 222 444" />
                                            <flux:error name="nokPhone" />
                                        </flux:field>
                                    </div>
                                </div>
                                <flux:field>
                                    <flux:label>Relationship <span class="text-red-500">*</span></flux:label>
                                    <flux:select wire:model="nokRelationship">
                                        <flux:select.option value="">— Select —</flux:select.option>
                                        <flux:select.option value="spouse">Spouse</flux:select.option>
                                        <flux:select.option value="parent">Parent</flux:select.option>
                                        <flux:select.option value="sibling">Sibling</flux:select.option>
                                        <flux:select.option value="child">Child</flux:select.option>
                                        <flux:select.option value="friend">Friend</flux:select.option>
                                        <flux:select.option value="other">Other</flux:select.option>
                                    </flux:select>
                                    <flux:error name="nokRelationship" />
                                </flux:field>
                            </div>
                            {{-- Secondary NOK --}}
                            <div class="border border-gray-100 dark:border-zinc-700 rounded-xl p-4 space-y-4">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Secondary NOK <span class="text-gray-400">(optional)</span></p>
                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <flux:field>
                                        <flux:label>Full Name</flux:label>
                                        <flux:input wire:model="nok2Name" placeholder="e.g. Maria Juma" />
                                    </flux:field>
                                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[200px_minmax(0,1fr)]">
                                        <flux:field>
                                            <flux:label>Country</flux:label>
                                            <flux:select wire:model="nok2PhoneCountry">
                                                @foreach($phoneCountries as $country)
                                                <flux:select.option value="{{ $country['iso'] }}">{{ $country['flag'] }} {{ $country['name'] }} ({{ $country['dial_code'] }})</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            <flux:error name="nok2PhoneCountry" />
                                        </flux:field>
                                        <flux:field>
                                            <flux:label>Phone</flux:label>
                                            <flux:input wire:model="nok2Phone" type="tel" placeholder="754 987 654" />
                                            <flux:error name="nok2Phone" />
                                        </flux:field>
                                    </div>
                                </div>
                                <flux:field>
                                    <flux:label>Relationship</flux:label>
                                    <flux:select wire:model="nok2Relationship">
                                        <flux:select.option value="">— Select —</flux:select.option>
                                        <flux:select.option value="spouse">Spouse</flux:select.option>
                                        <flux:select.option value="parent">Parent</flux:select.option>
                                        <flux:select.option value="sibling">Sibling</flux:select.option>
                                        <flux:select.option value="child">Child</flux:select.option>
                                        <flux:select.option value="friend">Friend</flux:select.option>
                                        <flux:select.option value="other">Other</flux:select.option>
                                    </flux:select>
                                </flux:field>
                            </div>
                        </div>
                        @endif

                        {{-- ═══ STEP 6: CONSENT & DECLARATION ═══ --}}
                        @if($step === 6)
                        <div class="space-y-5">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Consent & Declaration</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Customer must verbally confirm all items below before you check them</p>
                            </div>
                            <div class="space-y-3">
                                <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 dark:border-zinc-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors {{ $termsAccepted ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/10' : '' }}">
                                    <input type="checkbox" wire:model="termsAccepted" class="mt-0.5 w-4 h-4 accent-emerald-600 flex-shrink-0" />
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Terms & Loan Agreement <span class="text-red-500">*</span></p>
                                        <p class="text-xs text-gray-500 mt-0.5">Customer confirms they have been shown: device price, deposit amount, installment amount, total payable, and all penalties/charges. They accept the loan terms.</p>
                                    </div>
                                </label>
                                @error('termsAccepted') <p class="text-xs text-red-500 -mt-1 ml-1">{{ $message }}</p> @enderror

                                <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 dark:border-zinc-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors {{ $dataConsentAccepted ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/10' : '' }}">
                                    <input type="checkbox" wire:model="dataConsentAccepted" class="mt-0.5 w-4 h-4 accent-emerald-600 flex-shrink-0" />
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Data Processing Consent <span class="text-red-500">*</span></p>
                                        <p class="text-xs text-gray-500 mt-0.5">Customer consents to their personal data (ID, photos, financial info) being collected and processed for KYC verification and credit assessment purposes, as per the Personal Data Protection Act.</p>
                                    </div>
                                </label>
                                @error('dataConsentAccepted') <p class="text-xs text-red-500 -mt-1 ml-1">{{ $message }}</p> @enderror

                                <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 dark:border-zinc-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors {{ $callConsentAccepted ? 'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/10' : '' }}">
                                    <input type="checkbox" wire:model="callConsentAccepted" class="mt-0.5 w-4 h-4 accent-emerald-600 flex-shrink-0" />
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Call Verification Consent <span class="text-red-500">*</span></p>
                                        <p class="text-xs text-gray-500 mt-0.5">Customer and their Next of Kin consent to being contacted by phone for identity verification, repayment reminders, and collections follow-up.</p>
                                    </div>
                                </label>
                                @error('callConsentAccepted') <p class="text-xs text-red-500 -mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="p-3 rounded-xl bg-blue-50 border border-blue-100 flex items-start gap-2">
                                <flux:icon name="information-circle" class="size-4 text-blue-600 mt-0.5 shrink-0" />
                                <p class="text-xs text-blue-700">By checking all boxes, you as the Front Officer confirm that the customer has verbally agreed to each statement above. This is recorded with a timestamp.</p>
                            </div>
                        </div>
                        @endif

                        {{-- ═══ STEP 7: REVIEW & SUBMIT ═══ --}}
                        @if($step === 7)
                        @php
                            $paymentRecord = $latestDraftPayment;
                            $paymentBadge = match($paymentRecord?->status) {
                                'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                'pending', 'order_created', 'initiated' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                            };
                            $paymentLabel = match($paymentRecord?->status) {
                                'completed' => 'Deposit paid successfully',
                                'failed' => 'Payment failed',
                                'pending', 'order_created', 'initiated' => 'Waiting for customer approval',
                                default => 'Payment not started',
                            };
                            $agreementUrl = $activeAgreementDocument
                                ? \Illuminate\Support\Facades\Storage::disk($activeAgreementDocument->disk)->url($activeAgreementDocument->path)
                                : null;
                        @endphp
                        <div class="space-y-5">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Review & Submit</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Collect the first deposit, present the agreement, capture signatures, then submit to verification.</p>
                            </div>

                            {{-- Payment --}}
                            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-gray-400">Step 7A · Deposit Payment</p>
                                        <h4 class="mt-1 text-sm font-black text-gray-900 dark:text-white">Send Selcom mobile money prompt</h4>
                                        <p class="mt-1 text-xs text-gray-500">Customer should approve the deposit using their phone before the agreement and signatures can continue.</p>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $paymentBadge }}">
                                        <span class="h-2 w-2 rounded-full {{ str_contains($paymentBadge, 'emerald') ? 'bg-emerald-500' : (str_contains($paymentBadge, 'red') ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                                        {{ $paymentLabel }}
                                    </span>
                                </div>
                                <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                                    <flux:field>
                                        <flux:label>Payment Phone</flux:label>
                                        <flux:input wire:model="paymentPhone" type="tel" placeholder="+255712345678" />
                                        <flux:error name="paymentPhone" />
                                        @error('depositAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </flux:field>
                                    <div class="flex flex-wrap items-end gap-2">
                                        <button type="button" wire:click="initiateDepositPayment"
                                                class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-2.5 text-xs font-semibold text-white transition-colors hover:bg-orange-600">
                                            <flux:icon name="device-phone-mobile" class="size-4" />
                                            Send Prompt
                                        </button>
                                        <button type="button" wire:click="refreshDepositPaymentStatus"
                                                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50 dark:border-zinc-700 dark:text-gray-300 dark:hover:bg-zinc-800">
                                            <flux:icon name="arrow-path" class="size-4" />
                                            Refresh Status
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-gray-500 lg:grid-cols-3">
                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-zinc-800">
                                        <span class="font-semibold text-gray-700 dark:text-gray-200">Deposit amount:</span>
                                        TZS {{ number_format((float) $depositAmount) }}
                                    </div>
                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-zinc-800">
                                        <span class="font-semibold text-gray-700 dark:text-gray-200">Reference:</span>
                                        {{ $paymentRecord?->selcom_reference ?? $paymentRecord?->transid ?? 'Not generated yet' }}
                                    </div>
                                    <div class="rounded-xl bg-gray-50 px-3 py-2 dark:bg-zinc-800">
                                        <span class="font-semibold text-gray-700 dark:text-gray-200">Last update:</span>
                                        {{ $paymentRecord?->updated_at?->diffForHumans() ?? '—' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Agreement & signatures --}}
                            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/60">
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-gray-400">Step 7B · Agreement, Signatures & Handover</p>
                                <h4 class="mt-1 text-sm font-black text-gray-900 dark:text-white">Present agreement after successful payment</h4>
                                <p class="mt-1 text-xs text-gray-500">FO should only continue when the deposit is successful and the customer has read or been shown the agreement PDF.</p>

                                @if(! $activeAgreementDocument)
                                <div class="mt-4 rounded-2xl border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/10 dark:text-amber-300">
                                    Admin or owner must upload the active agreement PDF in Settings → System Health before this application can be completed.
                                </div>
                                @elseif($paymentRecord?->status !== 'completed')
                                <div class="mt-4 rounded-2xl border border-dashed border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/10 dark:text-blue-300">
                                    Waiting for successful deposit payment. Once payment is confirmed, the agreement preview and signature pads below become the required next action.
                                </div>
                                @else
                                <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                                    <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-800/60">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ $activeAgreementDocument->metadata['original_name'] ?? 'Customer agreement' }}</p>
                                                <p class="mt-0.5 text-[11px] text-gray-500">Show or open the PDF with the customer before asking for a decision.</p>
                                            </div>
                                            <a href="{{ $agreementUrl }}" target="_blank"
                                               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50 dark:border-zinc-700 dark:text-gray-300 dark:hover:bg-zinc-700">
                                                <flux:icon name="document-text" class="size-4" />
                                                View PDF
                                            </a>
                                        </div>
                                        <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 dark:border-zinc-700">
                                            <iframe src="{{ $agreementUrl }}" class="h-72 w-full bg-white"></iframe>
                                        </div>
                                        <div class="mt-4">
                                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Customer accepts the agreement?</p>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach(['yes' => 'Yes, customer accepts', 'no' => 'No, customer declined'] as $value => $label)
                                                <button type="button" wire:click="$set('agreementDecision', '{{ $value }}')"
                                                        class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold transition-colors {{ $agreementDecision === $value ? ($value === 'yes' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300') : 'bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                                    {{ $label }}
                                                </button>
                                                @endforeach
                                            </div>
                                            @error('agreementDecision') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-800/60">
                                            <p class="text-xs font-semibold text-gray-900 dark:text-white">Customer Signature</p>
                                            <p class="mt-0.5 text-[11px] text-gray-500">Capture the customer's signature after they agree to the contract.</p>
                                            <div class="mt-3" x-data="signaturePadCapture($wire, 'customerSignatureData')">
                                                <canvas x-ref="canvas" class="h-40 w-full rounded-xl border border-dashed border-gray-300 bg-white"></canvas>
                                                <div class="mt-2 flex justify-end">
                                                    <button type="button" @click="clear()" class="text-xs font-semibold text-red-500 hover:text-red-600">Clear</button>
                                                </div>
                                            </div>
                                            @error('customerSignatureData') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>

                                        <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-800/60">
                                            <p class="text-xs font-semibold text-gray-900 dark:text-white">Front Officer Signature</p>
                                            <p class="mt-0.5 text-[11px] text-gray-500">FO confirms the agreement was explained and the device handover list matches what the customer received.</p>
                                            <div class="mt-3" x-data="signaturePadCapture($wire, 'foSignatureData')">
                                                <canvas x-ref="canvas" class="h-40 w-full rounded-xl border border-dashed border-gray-300 bg-white"></canvas>
                                                <div class="mt-2 flex justify-end">
                                                    <button type="button" @click="clear()" class="text-xs font-semibold text-red-500 hover:text-red-600">Clear</button>
                                                </div>
                                            </div>
                                            @error('foSignatureData') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>

                                        <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-800/60">
                                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Asset Handover List <span class="text-red-500">*</span></label>
                                            <input wire:model="assetHandoverList" type="file" accept=".pdf,image/*"
                                                   class="mt-2 block w-full rounded-lg border border-gray-200 p-1 text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-orange-50 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-orange-700 hover:file:bg-orange-100" />
                                            @error('assetHandoverList') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                            <div wire:loading wire:target="assetHandoverList" class="mt-1 text-[10px] text-gray-400">Uploading handover list…</div>
                                            <flux:field class="mt-3">
                                                <flux:label>Handover Notes</flux:label>
                                                <flux:textarea wire:model="assetHandoverNotes" rows="2" placeholder="Accessories, charger, receipt pack, or stock pack handed to the customer." />
                                                <flux:error name="assetHandoverNotes" />
                                            </flux:field>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            {{-- Application Summary --}}
                            <div class="border border-gray-100 dark:border-zinc-700 rounded-xl p-4 space-y-3 text-sm">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Application Summary</p>
                                <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
                                    <div><span class="text-gray-500">Device:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $deviceSpecs ?: '—' }}</span></div>
                                    <div><span class="text-gray-500">IMEI:</span> <span class="font-mono font-semibold text-gray-800 dark:text-gray-100">{{ $imeiNumber ?: '—' }}</span></div>
                                    <div><span class="text-gray-500">Cash Price:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">TZS {{ number_format($cashPrice) }}</span></div>
                                    <div><span class="text-gray-500">Deposit:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">TZS {{ number_format($depositAmount) }}</span></div>
                                    <div><span class="text-gray-500">Customer:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">{{ trim("$firstName $lastName") ?: '—' }}</span></div>
                                    <div><span class="text-gray-500">Phone:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $phone ?: '—' }}</span></div>
                                    <div><span class="text-gray-500">Accessories:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">{{ count($deviceAccessories) ? count($deviceAccessories).' item(s)' : 'None' }}</span></div>
                                    <div><span class="text-gray-500">NIDA:</span> <span class="font-mono font-semibold text-gray-800 dark:text-gray-100">{{ $nidaNumber ?: '—' }}</span></div>
                                    <div><span class="text-gray-500">Monthly Income:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">TZS {{ number_format($monthlyIncome) }}</span></div>
                                    <div><span class="text-gray-500">NOK:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $nokName ?: '—' }} ({{ $nokPhone ?: '—' }})</span></div>
                                    <div><span class="text-gray-500">Repayment:</span> <span class="font-semibold text-gray-800 dark:text-gray-100 capitalize">{{ $preferredRepayment ?: '—' }}</span></div>
                                    <div><span class="text-gray-500">Payment:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $paymentLabel }}</span></div>
                                    <div><span class="text-gray-500">Agreement:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $agreementDecision === 'yes' ? 'Accepted' : ($agreementDecision === 'no' ? 'Declined' : 'Pending') }}</span></div>
                                </div>
                                <div class="pt-2 border-t border-gray-100 dark:border-zinc-700 flex gap-4 text-xs">
                                    @foreach(['Terms' => $termsAccepted, 'Data Consent' => $dataConsentAccepted, 'Call Consent' => $callConsentAccepted] as $label => $val)
                                    <span class="inline-flex items-center gap-1 {{ $val ? 'text-emerald-600' : 'text-red-500' }}">
                                        @if($val)<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>@else<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>@endif
                                        {{ $label }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            {{-- FO Notes --}}
                            <flux:field>
                                <flux:label>FO Notes / Remarks <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                <flux:textarea wire:model="foNotes" rows="3" placeholder="Any observations, special circumstances, or risk flags noticed during this application…" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Application Source <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                <flux:select wire:model="applicationSource">
                                    <flux:select.option value="">— How did customer come in? —</flux:select.option>
                                    <flux:select.option value="walk_in">Walk-in</flux:select.option>
                                    <flux:select.option value="referral">Referral</flux:select.option>
                                    <flux:select.option value="vendor">Vendor Referral</flux:select.option>
                                    <flux:select.option value="social_media">Social Media</flux:select.option>
                                    <flux:select.option value="agent">Field Agent</flux:select.option>
                                </flux:select>
                            </flux:field>
                            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 flex items-start gap-2">
                                <flux:icon name="check-circle" class="size-4 text-emerald-600 mt-0.5 shrink-0" />
                                <p class="text-xs text-emerald-700">All information will be submitted for Back Office verification. Automated checks will run instantly upon submission.</p>
                            </div>
                        </div>
                        @endif

                        {{-- ═══ Navigation ═══ --}}
                        <div class="mt-6 pt-5 border-t border-gray-100 dark:border-zinc-800 flex items-center justify-between">
                            @if($step > 1)
                            <button type="button" wire:click="previousStep"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Back
                            </button>
                            @else
                            <div></div>
                            @endif

                            @if($step < 7)
                            <button type="submit" wire:loading.attr="disabled" wire:target="nextStep"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:opacity-90 disabled:opacity-60 transition-opacity">
                                <span wire:loading.remove wire:target="nextStep">Continue</span>
                                <span wire:loading wire:target="nextStep">Validating…</span>
                                <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            @else
                            <button type="submit" wire:loading.attr="disabled" wire:target="processApplication"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white transition-colors">
                                <flux:icon wire:loading wire:target="processApplication" name="arrow-path" class="size-4 animate-spin" />
                                <span wire:loading.remove wire:target="processApplication">Submit Application</span>
                                <span wire:loading wire:target="processApplication">Submitting…</span>
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- ══ RIGHT: Recent Profiles ══ --}}
        <div class="lg:col-span-2 flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-gray-900 dark:text-white text-sm">Recently Registered</h3>
                <a href="{{ route('kyc.customers') }}" wire:navigate class="text-xs text-orange-500 hover:text-blue-800 font-semibold">View all →</a>
            </div>

            <div class="flex flex-col gap-3">
                @forelse($recentProfiles as $profile)
                @php
                    $pv = $profile->latestVerification;
                    $pLabel = match($pv?->status) {
                        'approved' => 'Verified',
                        'pending'  => 'In Review',
                        'rejected' => 'Rejected',
                        default    => 'Not Started',
                    };
                    $pBadge = match($pv?->status) {
                        'approved' => 'bg-emerald-100 text-emerald-700',
                        'pending'  => 'bg-amber-100 text-amber-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        default    => 'bg-zinc-100 text-zinc-600',
                    };
                    $pDot = match($pv?->status) {
                        'approved' => 'bg-emerald-400',
                        'pending'  => 'bg-amber-400',
                        'rejected' => 'bg-red-400',
                        default    => 'bg-zinc-400',
                    };
                @endphp
                <div wire:key="vault-{{ $profile->id }}"
                     class="bg-white dark:bg-zinc-900 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm p-4 flex items-center gap-3 hover:border-blue-200 dark:hover:border-blue-700 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white text-xs font-black flex-shrink-0">
                        {{ strtoupper(substr($profile->first_name, 0, 1).substr($profile->last_name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $profile->full_name }}</p>
                        <p class="font-mono text-[10px] text-gray-400 mt-0.5">
                            {{ $profile->phone }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $pBadge }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $pDot }}"></span>
                            {{ $pLabel }}
                        </span>
                        <span class="text-[10px] text-gray-400">{{ $profile->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                @empty
                <div class="p-8 border border-dashed border-gray-200 dark:border-zinc-700 rounded-xl text-center text-gray-500">
                    <flux:icon name="users" class="size-8 mx-auto mb-2 text-gray-300" />
                    <p class="text-sm">No customers registered yet</p>
                </div>
                @endforelse
            </div>

            {{-- Quick Stats --}}
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-5 text-white mt-2">
                <p class="text-xs font-semibold text-white/70 uppercase tracking-wider mb-1">Today's Registrations</p>
                <p class="text-3xl font-black">{{ \App\Models\Customer::whereDate('created_at', today())->count() }}</p>
                <p class="text-xs text-white/60 mt-1">customers registered today</p>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('kyc.pending') }}" wire:navigate
                       class="flex-1 text-center py-2 text-xs font-bold rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                        Pending Queue
                    </a>
                    <a href="{{ route('kyc.customers') }}" wire:navigate
                       class="flex-1 text-center py-2 text-xs font-bold rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                        All Profiles
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
