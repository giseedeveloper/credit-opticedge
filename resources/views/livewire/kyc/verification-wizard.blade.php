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
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Customer Acquisition Center</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">7-step agent KYC onboarding — device, identity, contact, income, NOK, consent &amp; submit</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('kyc.pending') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="clock" class="size-4" />
                Pending Queue
            </a>
            <a href="{{ route('kyc.customers') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                <flux:icon name="users" class="size-4" />
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
                                <p class="text-sm text-gray-500 mt-0.5">Capture the device being applied for — IMEI, price &amp; photos</p>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <flux:field>
                                    <flux:label>Device Brand / Model <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="deviceSpecs" placeholder="e.g. Tecno Camon 30 – 8GB/256GB" />
                                    <flux:error name="deviceSpecs" />
                                </flux:field>
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
                            <div class="grid grid-cols-3 gap-3">
                                @foreach([
                                    ['imeiPhoto','IMEI / Box Sticker Photo','optional'],
                                    ['deviceBoxPhoto','Box Photo','optional'],
                                    ['devicePhoto','Device Photo','optional'],
                                ] as [$field,$label,$hint])
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $label }} <span class="text-gray-400 font-normal">({{ $hint }})</span></label>
                                    <input wire:model="{{ $field }}" type="file" accept="image/*"
                                           class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 border border-gray-200 rounded-lg p-1" />
                                    @error($field) <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                                    <div wire:loading wire:target="{{ $field }}" class="mt-0.5 text-[10px] text-gray-400">Uploading…</div>
                                </div>
                                @endforeach
                            </div>
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-100 flex items-start gap-2">
                                <flux:icon name="information-circle" class="size-4 text-amber-600 mt-0.5 shrink-0" />
                                <p class="text-xs text-amber-700">IMEI must be exactly 15 digits. Back Office will validate device authenticity before approval.</p>
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
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Primary Phone <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="phone" type="tel" placeholder="+255 7XX XXX XXX" />
                                    <flux:error name="phone" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Alt Phone <span class="text-gray-400 font-normal text-xs">(optional)</span></flux:label>
                                    <flux:input wire:model="altPhone" type="tel" placeholder="+255 7XX XXX XXX" />
                                </flux:field>
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
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Full Name <span class="text-red-500">*</span></flux:label>
                                        <flux:input wire:model="nokName" placeholder="e.g. John Mwangi" />
                                        <flux:error name="nokName" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Phone <span class="text-red-500">*</span></flux:label>
                                        <flux:input wire:model="nokPhone" type="tel" placeholder="+255 7XX XXX XXX" />
                                        <flux:error name="nokPhone" />
                                    </flux:field>
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
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Full Name</flux:label>
                                        <flux:input wire:model="nok2Name" placeholder="e.g. Maria Juma" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Phone</flux:label>
                                        <flux:input wire:model="nok2Phone" type="tel" placeholder="+255 7XX XXX XXX" />
                                    </flux:field>
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
                        <div class="space-y-5">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Review & Submit</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Final review — add FO notes then submit to verification queue</p>
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
                                    <div><span class="text-gray-500">NIDA:</span> <span class="font-mono font-semibold text-gray-800 dark:text-gray-100">{{ $nidaNumber ?: '—' }}</span></div>
                                    <div><span class="text-gray-500">Monthly Income:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">TZS {{ number_format($monthlyIncome) }}</span></div>
                                    <div><span class="text-gray-500">NOK:</span> <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $nokName ?: '—' }} ({{ $nokPhone ?: '—' }})</span></div>
                                    <div><span class="text-gray-500">Repayment:</span> <span class="font-semibold text-gray-800 dark:text-gray-100 capitalize">{{ $preferredRepayment ?: '—' }}</span></div>
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
