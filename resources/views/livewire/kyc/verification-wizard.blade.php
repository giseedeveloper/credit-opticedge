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
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Customer Acquisition Center</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">4-step agent KYC onboarding — personal, contact, financial &amp; identity</p>
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
            {{-- Success State --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm p-10 text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="text-xl font-black text-gray-900 dark:text-white">Application Submitted!</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $submittedName }}</span> has been registered and queued for KYC review.
                </p>
                <div class="flex justify-center gap-3 mt-6">
                    <button wire:click="startNew"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 text-white hover:opacity-90 transition-opacity">
                        <flux:icon name="plus" class="size-4" />
                        Register Another
                    </button>
                    <a href="{{ route('kyc.pending') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 transition-colors">
                        View Pending Queue
                    </a>
                </div>
            </div>
            @else

            {{-- Stepper --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-100 dark:border-zinc-800 shadow-sm overflow-hidden">

                {{-- Step Header --}}
                <div class="px-6 pt-5 pb-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50/60 dark:bg-zinc-800/60">
                    @php
                    $steps = [
                        1 => ['label' => 'Personal',  'desc' => 'Name, gender, DOB'],
                        2 => ['label' => 'Contact',   'desc' => 'Phone, location'],
                        3 => ['label' => 'Financial', 'desc' => 'NIDA, income'],
                        4 => ['label' => 'Review',    'desc' => 'Confirm & submit'],
                    ];
                    @endphp
                    <div class="flex items-center gap-0">
                        @foreach($steps as $n => $s)
                        <div class="flex items-center {{ $n < 4 ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black transition-colors
                                    {{ $step > $n ? 'bg-emerald-500 text-white' : ($step === $n ? 'bg-gradient-to-br from-indigo-600 to-purple-700 text-white shadow-md shadow-indigo-200' : 'bg-gray-200 dark:bg-zinc-700 text-gray-500 dark:text-gray-400') }}">
                                    @if($step > $n)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                    {{ $n }}
                                    @endif
                                </div>
                                <span class="text-[10px] mt-1.5 font-semibold {{ $step >= $n ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">{{ $s['label'] }}</span>
                                <span class="text-[9px] text-gray-400 hidden sm:block">{{ $s['desc'] }}</span>
                            </div>
                            @if($n < 4)
                            <div class="flex-1 h-[2px] mx-2 mt-[-14px] rounded {{ $step > $n ? 'bg-emerald-400' : 'bg-gray-200 dark:bg-zinc-700' }}"></div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Form Body --}}
                <div class="p-6">
                    <form wire:submit.prevent="{{ $step === 4 ? 'processApplication' : 'nextStep' }}">

                        {{-- STEP 1: Personal Info --}}
                        @if($step === 1)
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">Step 1 of 4</p>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Personal Information</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Legal names as they appear on national ID</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>First Name <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="firstName" placeholder="e.g. Amina" />
                                    <flux:error name="firstName" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Middle Name <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                    <flux:input wire:model="middleName" placeholder="e.g. Juma" />
                                </flux:field>
                            </div>
                            <flux:field>
                                <flux:label>Last Name / Surname <span class="text-red-500">*</span></flux:label>
                                <flux:input wire:model="lastName" placeholder="e.g. Mohamed" />
                                <flux:error name="lastName" />
                            </flux:field>
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
                                    <flux:label>Date of Birth <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                    <flux:input wire:model="dob" type="date" />
                                </flux:field>
                            </div>
                            <flux:field>
                                <flux:label>Email Address <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                <flux:input wire:model="email" type="email" placeholder="amina@example.com" />
                            </flux:field>
                        </div>
                        @endif

                        {{-- STEP 2: Contact & Location --}}
                        @if($step === 2)
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">Step 2 of 4</p>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Contact & Location</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Phone number and residential details</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Primary Phone <span class="text-red-500">*</span></flux:label>
                                    <flux:input wire:model="phone" type="tel" placeholder="+255 7XX XXX XXX" />
                                    <flux:error name="phone" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Alt Phone <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                    <flux:input wire:model="altPhone" type="tel" placeholder="+255 7XX XXX XXX" />
                                </flux:field>
                            </div>
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
                            <flux:field>
                                <flux:label>Physical Address <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                <flux:input wire:model="address" placeholder="Street, plot, ward…" />
                            </flux:field>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Region <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                    <flux:input wire:model="region" placeholder="e.g. Dar es Salaam" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>District <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                    <flux:input wire:model="district" placeholder="e.g. Kinondoni" />
                                </flux:field>
                            </div>
                        </div>
                        @endif

                        {{-- STEP 3: Financial & Identity --}}
                        @if($step === 3)
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">Step 3 of 4</p>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Financial & Identity</h3>
                                <p class="text-sm text-gray-500 mt-0.5">National ID and income details for credit assessment</p>
                            </div>
                            <flux:field>
                                <flux:label>NIDA Number <span class="text-red-500">*</span></flux:label>
                                <flux:input wire:model="nidaNumber" placeholder="19XXXXXXXXXXXXXXXXXX (20 digits)" class="font-mono" maxlength="20" />
                                <flux:error name="nidaNumber" />
                            </flux:field>
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Occupation <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                    <flux:input wire:model="occupation" placeholder="e.g. Teacher, Trader" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Employer <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                    <flux:input wire:model="employer" placeholder="e.g. Govt, Self-employed" />
                                </flux:field>
                            </div>
                            <flux:field>
                                <flux:label>Monthly Income (TZS) <span class="text-gray-400 font-normal">(optional)</span></flux:label>
                                <flux:input wire:model="monthlyIncome" type="number" min="0" placeholder="e.g. 500000" />
                            </flux:field>
                            <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/40">
                                <div class="flex items-start gap-2">
                                    <flux:icon name="information-circle" class="size-4 text-amber-600 mt-0.5 flex-shrink-0" />
                                    <p class="text-xs text-amber-700 dark:text-amber-300">NIDA number must be exactly 20 characters and must not already exist in the system. Double-check before proceeding.</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- STEP 4: Review & Submit --}}
                        @if($step === 4)
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Step 4 of 4</p>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">Review & Submit</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Confirm all details before registering the customer</p>
                            </div>

                            {{-- Personal --}}
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Personal</p>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                                    <div><span class="text-gray-400 text-xs">Full Name</span><p class="font-semibold text-gray-800 dark:text-gray-100">{{ trim("$firstName $middleName $lastName") }}</p></div>
                                    <div><span class="text-gray-400 text-xs">Gender</span><p class="font-semibold text-gray-800 dark:text-gray-100">{{ ucfirst($gender) }}</p></div>
                                    @if($dob)<div><span class="text-gray-400 text-xs">Date of Birth</span><p class="font-semibold text-gray-800 dark:text-gray-100">{{ \Carbon\Carbon::parse($dob)->format('d M Y') }}</p></div>@endif
                                    @if($email)<div><span class="text-gray-400 text-xs">Email</span><p class="font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $email }}</p></div>@endif
                                </div>
                            </div>

                            {{-- Contact --}}
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Contact & Location</p>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                                    <div><span class="text-gray-400 text-xs">Phone</span><p class="font-semibold text-gray-800 dark:text-gray-100">{{ $phone }}</p></div>
                                    @if($altPhone)<div><span class="text-gray-400 text-xs">Alt Phone</span><p class="font-semibold text-gray-800 dark:text-gray-100">{{ $altPhone }}</p></div>@endif
                                    @if($address)<div class="col-span-2"><span class="text-gray-400 text-xs">Address</span><p class="font-semibold text-gray-800 dark:text-gray-100">{{ implode(', ', array_filter([$address, $district, $region])) }}</p></div>@endif
                                </div>
                            </div>

                            {{-- Financial & Identity --}}
                            <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Financial & Identity</p>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                                    <div class="col-span-2"><span class="text-gray-400 text-xs">NIDA Number</span><p class="font-mono font-bold text-gray-800 dark:text-gray-100">{{ $nidaNumber }}</p></div>
                                    @if($occupation)<div><span class="text-gray-400 text-xs">Occupation</span><p class="font-semibold text-gray-800 dark:text-gray-100">{{ $occupation }}</p></div>@endif
                                    @if($monthlyIncome)<div><span class="text-gray-400 text-xs">Monthly Income</span><p class="font-semibold text-teal-600 dark:text-teal-400">TZS {{ number_format((float)$monthlyIncome) }}</p></div>@endif
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Navigation --}}
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

                            @if($step < 4)
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 text-white hover:opacity-90 transition-opacity">
                                Continue
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            @else
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                                <flux:icon wire:loading wire:target="processApplication" name="arrow-path" class="size-4 animate-spin" />
                                <span wire:loading.remove wire:target="processApplication">Authorize &amp; Register</span>
                                <span wire:loading wire:target="processApplication">Saving…</span>
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
                <a href="{{ route('kyc.customers') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">View all →</a>
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
                     class="bg-white dark:bg-zinc-900 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm p-4 flex items-center gap-3 hover:border-indigo-200 dark:hover:border-indigo-700 transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-black flex-shrink-0">
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
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-5 text-white mt-2">
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
