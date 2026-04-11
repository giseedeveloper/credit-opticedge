<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\PhoneModel;
use App\Models\RepaymentSchedule;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 1. BRANCHES ──────────────────────────────────────────────────
        $hq = Branch::firstOrCreate(
            ['code' => 'HQ-001'],
            [
                'name' => 'OpticEdge HQ - Dar es Salaam',
                'region' => 'Dar es Salaam',
                'address' => 'Samora Avenue, Posta, Ilala',
                'phone' => '+255 22 211 0000',
                'is_headquarter' => true,
                'is_active' => true,
            ]
        );

        $branches = collect([
            ['code' => 'BR-002', 'name' => 'Kariakoo Branch', 'region' => 'Dar es Salaam', 'address' => 'Msimbazi Street, Kariakoo'],
            ['code' => 'BR-003', 'name' => 'Arusha Branch', 'region' => 'Arusha', 'address' => 'Sokoine Road, Arusha CBD'],
            ['code' => 'BR-004', 'name' => 'Mwanza Branch', 'region' => 'Mwanza', 'address' => 'Station Road, Mwanza'],
            ['code' => 'BR-005', 'name' => 'Dodoma Branch', 'region' => 'Dodoma', 'address' => 'Jakaya Kikwete Road, Dodoma'],
            ['code' => 'BR-006', 'name' => 'Moshi Branch', 'region' => 'Kilimanjaro', 'address' => 'Rindi Lane, Moshi Town'],
        ])->map(fn ($b) => Branch::firstOrCreate(['code' => $b['code']], array_merge($b, [
            'phone' => '+255 75'.fake()->numerify('#######'),
            'is_headquarter' => false,
            'is_active' => true,
        ])));

        $allBranches = collect([$hq])->merge($branches);

        // ── 2. BRANDS & MODELS ───────────────────────────────────────────
        $brandData = [
            'Samsung' => [
                ['name' => 'Galaxy A06',    'retail' => 290_000,  'cost' => 240_000, 'specs' => ['ram' => '4GB', 'storage' => '64GB',  'color' => 'Black']],
                ['name' => 'Galaxy A15',    'retail' => 480_000,  'cost' => 400_000, 'specs' => ['ram' => '4GB', 'storage' => '128GB', 'color' => 'Blue']],
                ['name' => 'Galaxy A35',    'retail' => 780_000,  'cost' => 650_000, 'specs' => ['ram' => '6GB', 'storage' => '128GB', 'color' => 'Awesome Iceblue']],
                ['name' => 'Galaxy S23 FE', 'retail' => 1_350_000, 'cost' => 1_100_000, 'specs' => ['ram' => '8GB', 'storage' => '256GB', 'color' => 'Graphite']],
            ],
            'Tecno' => [
                ['name' => 'Spark 20',    'retail' => 250_000, 'cost' => 200_000, 'specs' => ['ram' => '8GB',  'storage' => '128GB', 'color' => 'Cyber White']],
                ['name' => 'Camon 20',    'retail' => 430_000, 'cost' => 360_000, 'specs' => ['ram' => '8GB',  'storage' => '256GB', 'color' => 'Dark Welkin']],
                ['name' => 'Pova 5',      'retail' => 360_000, 'cost' => 295_000, 'specs' => ['ram' => '8GB',  'storage' => '128GB', 'color' => 'Sky Mirror']],
                ['name' => 'Pop 8',       'retail' => 175_000, 'cost' => 140_000, 'specs' => ['ram' => '2GB',  'storage' => '32GB',  'color' => 'Iceland Snow']],
            ],
            'Infinix' => [
                ['name' => 'Hot 40i',   'retail' => 220_000, 'cost' => 175_000, 'specs' => ['ram' => '4GB', 'storage' => '128GB', 'color' => 'Horizon Gold']],
                ['name' => 'Note 30',   'retail' => 500_000, 'cost' => 420_000, 'specs' => ['ram' => '8GB', 'storage' => '256GB', 'color' => 'Rome Green']],
                ['name' => 'Smart 8',   'retail' => 160_000, 'cost' => 130_000, 'specs' => ['ram' => '3GB', 'storage' => '64GB',  'color' => 'Crystal Green']],
                ['name' => 'Zero 30',   'retail' => 820_000, 'cost' => 690_000, 'specs' => ['ram' => '8GB', 'storage' => '256GB', 'color' => 'Golden Hour']],
            ],
            'Itel' => [
                ['name' => 'A70',   'retail' => 130_000, 'cost' => 100_000, 'specs' => ['ram' => '2GB', 'storage' => '32GB', 'color' => 'Black']],
                ['name' => 'P55',   'retail' => 210_000, 'cost' => 170_000, 'specs' => ['ram' => '4GB', 'storage' => '64GB', 'color' => 'Dark Blue']],
                ['name' => 'S24',   'retail' => 280_000, 'cost' => 225_000, 'specs' => ['ram' => '4GB', 'storage' => '128GB', 'color' => 'Magic Sky']],
            ],
            'Xiaomi' => [
                ['name' => 'Redmi 13C',     'retail' => 310_000,  'cost' => 260_000,  'specs' => ['ram' => '4GB',  'storage' => '128GB', 'color' => 'Clover Green']],
                ['name' => 'Redmi Note 13', 'retail' => 560_000,  'cost' => 465_000,  'specs' => ['ram' => '8GB',  'storage' => '256GB', 'color' => 'Arctic White']],
                ['name' => 'Poco X6',       'retail' => 870_000,  'cost' => 730_000,  'specs' => ['ram' => '12GB', 'storage' => '256GB', 'color' => 'Black']],
            ],
            'Apple' => [
                ['name' => 'iPhone 13',       'retail' => 1_980_000, 'cost' => 1_650_000, 'specs' => ['ram' => '4GB', 'storage' => '128GB', 'color' => 'Midnight']],
                ['name' => 'iPhone 14',       'retail' => 2_550_000, 'cost' => 2_150_000, 'specs' => ['ram' => '6GB', 'storage' => '128GB', 'color' => 'Blue']],
                ['name' => 'iPhone 13 mini',  'retail' => 1_700_000, 'cost' => 1_400_000, 'specs' => ['ram' => '4GB', 'storage' => '128GB', 'color' => 'Pink']],
            ],
            'Nokia' => [
                ['name' => 'G42',   'retail' => 340_000, 'cost' => 280_000, 'specs' => ['ram' => '6GB', 'storage' => '128GB', 'color' => 'So Purple']],
                ['name' => 'C32',   'retail' => 220_000, 'cost' => 175_000, 'specs' => ['ram' => '4GB', 'storage' => '64GB',  'color' => 'Charcoal']],
                ['name' => '105 4G', 'retail' => 65_000,  'cost' => 50_000,  'specs' => ['ram' => '-',   'storage' => '-',     'color' => 'Blue']],
            ],
        ];

        $phoneModels = collect();

        foreach ($brandData as $brandName => $models) {
            $brand = Brand::firstOrCreate(
                ['name' => $brandName],
                ['slug' => Str::slug($brandName), 'is_active' => true]
            );

            foreach ($models as $m) {
                $pm = PhoneModel::firstOrCreate(
                    ['name' => $m['name'], 'brand_id' => $brand->id],
                    [
                        'slug' => Str::slug($brandName.'-'.$m['name']),
                        'retail_price' => $m['retail'],
                        'cost_price' => $m['cost'],
                        'specifications' => $m['specs'],
                        'is_active' => true,
                    ]
                );
                $phoneModels->push($pm);
            }
        }

        // ── 3. VENDORS ────────────────────────────────────────────────────
        $vendorData = [
            ['name' => 'TechHub Kariakoo',     'code' => 'VND-001', 'branch' => 'BR-002', 'commission' => 4.5],
            ['name' => 'MobiDeals Arusha',     'code' => 'VND-002', 'branch' => 'BR-003', 'commission' => 3.5],
            ['name' => 'SmartPhone Palace',    'code' => 'VND-003', 'branch' => 'BR-002', 'commission' => 5.0],
            ['name' => 'Lake Zone Gadgets',    'code' => 'VND-004', 'branch' => 'BR-004', 'commission' => 4.0],
            ['name' => 'Kili Mobile Shop',     'code' => 'VND-005', 'branch' => 'BR-006', 'commission' => 3.0],
            ['name' => 'Capital City Electronics', 'code' => 'VND-006', 'branch' => 'BR-005', 'commission' => 4.5],
        ];

        $vendors = collect();
        foreach ($vendorData as $v) {
            $branch = $allBranches->firstWhere('code', $v['branch']) ?? $hq;
            $vendor = Vendor::firstOrCreate(
                ['code' => $v['code']],
                [
                    'branch_id' => $branch->id,
                    'name' => $v['name'],
                    'phone' => '+255 76'.fake()->numerify('#######'),
                    'email' => Str::slug($v['name']).'@vendor.co.tz',
                    'address' => $branch->address,
                    'tin_number' => fake()->numerify('###-###-###'),
                    'commission_rate' => $v['commission'],
                    'status' => 'active',
                ]
            );
            $vendors->push($vendor);
        }

        // ── 4. STAFF USERS ────────────────────────────────────────────────
        $staffData = [
            ['name' => 'Amina Khalid',   'email' => 'amina.khalid@opticedge.co.tz',   'role' => 'manager',       'branch' => 'BR-002', 'phone' => '+255 754 100 001'],
            ['name' => 'Juma Omari',     'email' => 'juma.omari@opticedge.co.tz',     'role' => 'front-officer', 'branch' => 'BR-002', 'phone' => '+255 754 100 002'],
            ['name' => 'Grace Ndumbo',   'email' => 'grace.ndumbo@opticedge.co.tz',   'role' => 'front-officer', 'branch' => 'BR-003', 'phone' => '+255 754 100 003'],
            ['name' => 'Hassan Mwanga',  'email' => 'hassan.mwanga@opticedge.co.tz',  'role' => 'accountant',    'branch' => 'HQ-001', 'phone' => '+255 754 100 004'],
            ['name' => 'Fatuma Ally',    'email' => 'fatuma.ally@opticedge.co.tz',    'role' => 'back-officer',  'branch' => 'HQ-001', 'phone' => '+255 754 100 005'],
            ['name' => 'Peter Masanja',  'email' => 'peter.masanja@opticedge.co.tz',  'role' => 'supervisor',    'branch' => 'BR-003', 'phone' => '+255 754 100 006'],
            ['name' => 'Neema Mramba',   'email' => 'neema.mramba@opticedge.co.tz',   'role' => 'front-officer', 'branch' => 'BR-004', 'phone' => '+255 754 100 007'],
            ['name' => 'David Lyimo',    'email' => 'david.lyimo@opticedge.co.tz',    'role' => 'manager',       'branch' => 'BR-004', 'phone' => '+255 754 100 008'],
            ['name' => 'Zainab Suleiman', 'email' => 'zainab.suleiman@opticedge.co.tz', 'role' => 'front-officer', 'branch' => 'BR-005', 'phone' => '+255 754 100 009'],
            ['name' => 'Emmanuel Tarimo', 'email' => 'emmanuel.tarimo@opticedge.co.tz', 'role' => 'accountant',    'branch' => 'BR-006', 'phone' => '+255 754 100 010'],
        ];

        $staffUsers = collect();
        foreach ($staffData as $s) {
            $branch = $allBranches->firstWhere('code', $s['branch']) ?? $hq;
            $user = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'phone' => $s['phone'],
                    'role' => $s['role'],
                    'employee_code' => strtoupper('EMP-'.fake()->unique()->bothify('####')),
                    'branch_id' => $branch->id,
                    'joined_at' => fake()->dateTimeBetween('-2 years', '-2 weeks')->format('Y-m-d'),
                    'is_active' => true,
                ]
            );

            $role = Role::firstOrCreate(['name' => $s['role'], 'guard_name' => 'web']);
            $user->syncRoles([$s['role']]);
            $user->forceFill([
                'role' => $s['role'],
                'branch_id' => $branch->id,
                'joined_at' => $user->joined_at ?? fake()->dateTimeBetween('-2 years', '-2 weeks')->format('Y-m-d'),
            ])->save();
            $staffUsers->push($user);
        }

        // ── 5. CUSTOMERS ─────────────────────────────────────────────────
        $customerNames = [
            ['first' => 'Mariam',   'last' => 'Juma'],
            ['first' => 'Rashid',   'last' => 'Bakari'],
            ['first' => 'Upendo',   'last' => 'Mwelwa'],
            ['first' => 'Salim',    'last' => 'Aboud'],
            ['first' => 'Joyce',    'last' => 'Kileo'],
            ['first' => 'Hamisi',   'last' => 'Mwangi'],
            ['first' => 'Rehema',   'last' => 'Nkosi'],
            ['first' => 'Baraka',   'last' => 'Msuya'],
            ['first' => 'Zawadi',   'last' => 'Tarimo'],
            ['first' => 'Ibrahim',  'last' => 'Said'],
            ['first' => 'Veronica', 'last' => 'Mmari'],
            ['first' => 'Omary',    'last' => 'Khamis'],
            ['first' => 'Stella',   'last' => 'Mwakipesile'],
            ['first' => 'Yusuph',   'last' => 'Abdallah'],
            ['first' => 'Beatrice', 'last' => 'Minja'],
            ['first' => 'Ally',     'last' => 'Nyambu'],
            ['first' => 'Josephine', 'last' => 'Kitundu'],
            ['first' => 'Nassoro',  'last' => 'Ramadhani'],
            ['first' => 'Angela',   'last' => 'Luoga'],
            ['first' => 'Fadhili',  'last' => 'Mwero'],
            ['first' => 'Lilian',   'last' => 'Ndunguru'],
            ['first' => 'Yunus',    'last' => 'Masoud'],
            ['first' => 'Theresia', 'last' => 'Mwamba'],
            ['first' => 'Khalfan',  'last' => 'Hamad'],
            ['first' => 'Dorothy',  'last' => 'Mlay'],
        ];

        $customers = collect();
        foreach ($customerNames as $i => $cn) {
            $branch = $allBranches->get($i % $allBranches->count());
            $vendor = $vendors->get($i % $vendors->count());
            $staff = $staffUsers->get($i % $staffUsers->count());

            $customer = Customer::firstOrCreate(
                ['phone' => '07'.str_pad($i + 60000000, 8, '0', STR_PAD_LEFT)],
                [
                    'branch_id' => $branch->id,
                    'vendor_id' => $vendor->id,
                    'registered_by' => $staff->id,
                    'first_name' => $cn['first'],
                    'last_name' => $cn['last'],
                    'email' => Str::slug($cn['first'].'.'.$cn['last']).'@gmail.com',
                    'nida_number' => str_pad($i + 1, 20, '0', STR_PAD_LEFT),
                    'date_of_birth' => Carbon::now()->subYears(rand(22, 50))->subDays(rand(0, 364))->format('Y-m-d'),
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'occupation' => fake()->randomElement(['Teacher', 'Trader', 'Driver', 'Farmer', 'Nurse', 'Engineer', 'Accountant', 'Tailor', 'Electrician']),
                    'employer' => fake()->company(),
                    'monthly_income' => fake()->randomElement([450_000, 600_000, 800_000, 1_000_000, 1_200_000, 1_500_000]),
                    'address' => $branch->address,
                    'region' => $branch->region,
                    'district' => fake()->city(),
                    'kyc_status' => $i < 20 ? 'approved' : 'pending',
                    'credit_status' => 'eligible',
                ]
            );
            $customers->push($customer);
        }

        // ── 6. INVENTORY UNITS (DEVICES) ──────────────────────────────────
        $inventoryUnits = collect();
        $unitIndex = 0;

        foreach ($allBranches as $branch) {
            // 8 devices per branch = 48 total
            foreach ($phoneModels->random(8) as $model) {
                $imei1 = '35'.str_pad($unitIndex + 1000000, 13, '0', STR_PAD_LEFT);
                $status = match (true) {
                    $unitIndex < 25 => 'sold',
                    $unitIndex < 35 => 'available',
                    default => 'available',
                };

                $vendor = $vendors->get($unitIndex % $vendors->count());
                $unit = InventoryUnit::firstOrCreate(
                    ['imei_1' => $imei1],
                    [
                        'phone_model_id' => $model->id,
                        'branch_id' => $branch->id,
                        'vendor_id' => $vendor->id,
                        'imei_2' => null,
                        'serial_number' => 'SN-'.strtoupper(Str::random(5)).'-'.str_pad($unitIndex + 1, 4, '0', STR_PAD_LEFT),
                        'status' => $status,
                        'purchase_price' => $model->cost_price,
                        'received_at' => Carbon::now()->subDays(rand(30, 180))->format('Y-m-d'),
                    ]
                );
                $inventoryUnits->push($unit);
                $unitIndex++;
            }
        }

        // ── 7. LOANS (ACTIVE / COMPLETED / OVERDUE / PENDING) ─────────────
        $soldUnits = $inventoryUnits->where('status', 'sold')->values();
        $approvedCustomers = $customers->where('kyc_status', 'approved')->values();

        $loanScenarios = [
            // [principal, rate, weeks, depositPct, status, daysAgo, paymentsMade]
            [800_000,  20, 24, 0.10, 'active',    90, 3],
            [450_000,  22, 12, 0.05, 'active',    45, 2],
            [1_200_000, 18, 24, 0.15, 'active',    60, 4],
            [600_000,  20, 16, 0.10, 'active',    30, 1],
            [350_000,  25, 8,  0.05, 'completed', 120, 8],
            [500_000,  20, 12, 0.10, 'completed', 180, 12],
            [750_000,  18, 16, 0.10, 'completed', 200, 16],
            [400_000,  22, 8,  0.05, 'overdue',   70, 1],
            [900_000,  20, 24, 0.10, 'overdue',   150, 2],
            [300_000,  25, 8,  0.05, 'defaulted', 200, 0],
            [1_500_000, 18, 24, 0.10, 'active',    20, 1],
            [650_000,  20, 16, 0.10, 'active',    50, 2],
            [280_000,  25, 8,  0.05, 'active',    10, 0],
            [1_100_000, 18, 24, 0.10, 'active',    35, 1],
            [480_000,  22, 12, 0.05, 'active',    25, 1],
            [700_000,  20, 20, 0.10, 'active',    80, 4],
            [950_000,  18, 24, 0.15, 'overdue',   100, 2],
            [550_000,  22, 12, 0.10, 'completed', 160, 12],
            [380_000,  25, 8,  0.05, 'active',    15, 0],
            [820_000,  20, 16, 0.10, 'active',    40, 2],
        ];

        $adminUser = User::where('email', 'admin@opticedge.co.tz')->first()
            ?? $staffUsers->first();

        $transactions = collect();

        foreach ($loanScenarios as $idx => $scene) {
            [$principal, $rate, $weeks, $depositPct, $status, $daysAgo, $paymentsMade] = $scene;

            $customer = $approvedCustomers->get($idx % $approvedCustomers->count());
            $unit = $soldUnits->get($idx % $soldUnits->count());
            $staff = $staffUsers->get($idx % $staffUsers->count());
            $vendor = $vendors->get($idx % $vendors->count());
            $branch = $allBranches->get($idx % $allBranches->count());

            $deposit = round($principal * $depositPct);
            $months = (int) ceil($weeks / 4);
            $totalInterest = round($principal * ($rate / 100) * $months, 2);
            $totalDebt = $principal + $totalInterest;
            $remaining = $totalDebt - $deposit;
            $disbursedAt = Carbon::now()->subDays($daysAgo);
            $dueDate = $disbursedAt->copy()->addWeeks($weeks);
            $loanNumber = 'LN-'.str_pad($idx + 1, 6, '0', STR_PAD_LEFT);

            if (Loan::where('loan_number', $loanNumber)->exists()) {
                continue;
            }

            $installmentAmount = $weeks > 0 ? round($remaining / $weeks, 2) : $remaining;
            $amountPaid = round($installmentAmount * $paymentsMade + $deposit, 2);
            $outstandingBal = max(0, round($remaining - ($amountPaid - $deposit), 2));

            if ($status === 'completed') {
                $amountPaid = $totalDebt;
                $outstandingBal = 0;
            }

            $loan = Loan::create([
                'customer_id' => $customer->id,
                'inventory_unit_id' => $unit->id,
                'vendor_id' => $vendor->id,
                'branch_id' => $branch->id,
                'disbursed_by' => $staff->id,
                'approved_by' => $adminUser->id,
                'loan_number' => $loanNumber,
                'principal_amount' => $principal,
                'deposit_paid' => $deposit,
                'interest_rate' => $rate,
                'interest_type' => 'flat',
                'total_debt' => $totalDebt,
                'total_payable' => $remaining,
                'amount_paid' => $amountPaid,
                'remaining_balance' => $outstandingBal,
                'outstanding_balance' => $outstandingBal,
                'penalty_amount' => $status === 'overdue' ? round($remaining * 0.02, 2) : 0,
                'duration_weeks' => $weeks,
                'grace_period_days' => 3,
                'repayment_frequency' => 'weekly',
                'status' => $status,
                'disbursed_at' => $disbursedAt->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'completed_at' => $status === 'completed' ? $dueDate->toDateString() : null,
                'notes' => 'Demo loan — '.$customer->first_name.' '.$customer->last_name,
            ]);

            // Repayment schedules
            for ($w = 1; $w <= $weeks; $w++) {
                $scheduledDate = $disbursedAt->copy()->addWeeks($w);
                $isPaid = $w <= $paymentsMade;
                $paidAt = $isPaid ? $disbursedAt->copy()->addWeeks($w)->subDays(rand(0, 2)) : null;
                $scheduleStatus = match (true) {
                    $status === 'completed' => 'paid',
                    $isPaid => 'paid',
                    $scheduledDate->isPast() && ! $isPaid => 'overdue',
                    default => 'pending',
                };

                RepaymentSchedule::create([
                    'loan_id' => $loan->id,
                    'installment_number' => $w,
                    'amount_due' => $installmentAmount,
                    'principal_component' => round($installmentAmount * 0.82, 2),
                    'interest_component' => round($installmentAmount * 0.18, 2),
                    'penalty_component' => 0,
                    'amount_paid' => ($scheduleStatus === 'paid' || $status === 'completed') ? $installmentAmount : 0,
                    'balance_remaining' => ($scheduleStatus === 'paid' || $status === 'completed') ? 0 : $installmentAmount,
                    'due_date' => $scheduledDate->toDateString(),
                    'paid_at' => $paidAt?->toDateString(),
                    'status' => $scheduleStatus,
                    'days_overdue' => $scheduleStatus === 'overdue' ? (int) $scheduledDate->diffInDays(now()) : 0,
                ]);
            }

            // Deposit transaction
            if ($deposit > 0) {
                Transaction::create([
                    'loan_id' => $loan->id,
                    'customer_id' => $customer->id,
                    'recorded_by' => $staff->id,
                    'reference' => 'TXN-DEP-'.str_pad($idx + 1, 6, '0', STR_PAD_LEFT),
                    'type' => 'deposit',
                    'entry_type' => 'credit',
                    'amount' => $deposit,
                    'channel' => fake()->randomElement(['cash', 'mobile_money']),
                    'description' => 'Initial deposit for '.$loanNumber,
                    'transacted_at' => $disbursedAt->toDateTimeString(),
                ]);
            }

            // Repayment transactions
            for ($p = 1; $p <= $paymentsMade; $p++) {
                $txnDate = $disbursedAt->copy()->addWeeks($p)->subDays(rand(0, 1));
                Transaction::create([
                    'loan_id' => $loan->id,
                    'customer_id' => $customer->id,
                    'recorded_by' => $staff->id,
                    'reference' => 'TXN-'.strtoupper(Str::random(10)),
                    'type' => 'repayment',
                    'entry_type' => 'credit',
                    'amount' => $installmentAmount,
                    'channel' => fake()->randomElement(['cash', 'mobile_money', 'bank']),
                    'description' => "Week {$p} repayment — {$loanNumber}",
                    'transacted_at' => $txnDate->toDateTimeString(),
                ]);
            }
        }

        $this->command->info('✓ Branches:        '.$allBranches->count());
        $this->command->info('✓ Brands:          '.Brand::count());
        $this->command->info('✓ Phone Models:    '.PhoneModel::count());
        $this->command->info('✓ Vendors:         '.Vendor::count());
        $this->command->info('✓ Staff Users:     '.($staffUsers->count() + 1));
        $this->command->info('✓ Customers:       '.Customer::count());
        $this->command->info('✓ Inventory Units: '.InventoryUnit::count());
        $this->command->info('✓ Loans:           '.Loan::count());
        $this->command->info('✓ Schedules:       '.RepaymentSchedule::count());
        $this->command->info('✓ Transactions:    '.Transaction::count());
    }
}
