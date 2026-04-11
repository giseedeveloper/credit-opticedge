<?php

namespace App\Livewire\Partnership;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\InventoryUnit;
use App\Models\Loan;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class VendorDirectory extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $branchFilter = '';

    public bool $showCreateModal = false;

    public string $newName = '';

    public string $newCode = '';

    public string $newPhone = '';

    public string $newEmail = '';

    public string $newBranchId = '';

    public string $newOwnerUserId = '';

    public string $newTinNumber = '';

    public string $newCommissionRate = '0';

    public string $newStatus = 'active';

    public string $newAddress = '';

    public bool $showDetail = false;

    public ?string $detailVendorId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('vendors.view'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatedNewBranchId(): void
    {
        $this->resetValidation(['newBranchId', 'newOwnerUserId']);

        if ($this->newOwnerUserId === '') {
            return;
        }

        $ownerStillSelectable = $this->createOwnerOptions
            ->contains(fn (User $user) => $user->id === $this->newOwnerUserId);

        if (! $ownerStillSelectable) {
            $this->newOwnerUserId = '';
        }
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()->canAccess('vendors.create'), 403);

        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }

    public function createVendor(): void
    {
        abort_unless(auth()->user()->canAccess('vendors.create'), 403);

        $validated = $this->validate($this->createRules());

        $vendor = Vendor::create([
            'branch_id' => $validated['newBranchId'],
            'owner_user_id' => $validated['newOwnerUserId'] ?: null,
            'name' => $validated['newName'],
            'code' => $validated['newCode'] !== '' ? Str::upper($validated['newCode']) : $this->generateVendorCode($validated['newBranchId']),
            'phone' => $validated['newPhone'] ?: null,
            'email' => $validated['newEmail'] ?: null,
            'address' => $validated['newAddress'] ?: null,
            'tin_number' => $validated['newTinNumber'] ?: null,
            'commission_rate' => $validated['newCommissionRate'],
            'status' => $validated['newStatus'],
        ]);

        VendorWallet::firstOrCreate(
            ['vendor_id' => $vendor->id],
            ['balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0]
        );

        activity('vendors')
            ->performedOn($vendor)
            ->causedBy(auth()->user())
            ->withProperties([
                'branch_id' => $vendor->branch_id,
                'owner_user_id' => $vendor->owner_user_id,
                'status' => $vendor->status,
            ])
            ->log("Vendor [{$vendor->name}] was created");

        $this->closeCreateModal();
        $this->dispatch('toast', message: "{$vendor->name} added to the vendor directory.", type: 'success');
    }

    public function openDetail(string $id): void
    {
        $this->detailVendorId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailVendorId = null;
    }

    public function getDetailVendorProperty(): ?Vendor
    {
        if (! $this->detailVendorId) {
            return null;
        }

        return Vendor::with([
            'branch',
            'ownerUser.roles',
            'wallet',
            'commissionLedgers' => fn ($q) => $q->with('loan.customer')->latest('posted_at')->take(10),
            'loans' => fn ($q) => $q->with(['customer', 'inventoryUnit.phoneModel.brand'])->latest()->take(8),
            'customers' => fn ($q) => $q->withCount('loans')->latest()->take(6),
        ])
            ->withCount([
                'inventoryUnits',
                'loans',
                'customers',
                'customers as approved_customers_count' => fn (Builder $query) => $query->whereIn('kyc_status', Customer::approvedKycStatuses()),
                'loans as active_loans_count' => fn (Builder $query) => $query->where('status', 'active'),
                'loans as overdue_loans_count' => fn (Builder $query) => $query->where('status', 'overdue'),
                'loans as completed_loans_count' => fn (Builder $query) => $query->where('status', 'completed'),
            ])
            ->withSum('loans', 'principal_amount')
            ->withSum(['loans as loans_amount_paid_sum' => fn (Builder $query) => $query], 'amount_paid')
            ->withSum(['loans as loans_total_payable_sum' => fn (Builder $query) => $query], 'total_payable')
            ->withSum(['loans as loans_outstanding_balance_sum' => fn (Builder $query) => $query], 'outstanding_balance')
            ->withSum(['commissionLedgers as pending_commissions_sum' => fn (Builder $query) => $query->whereIn('status', ['pending', 'posted'])], 'commission_amount')
            ->withSum(['commissionLedgers as paid_commissions_sum' => fn (Builder $query) => $query->where('status', 'paid')], 'commission_amount')
            ->withSum(['commissionLedgers as recorded_commissions_sum' => fn (Builder $query) => $query], 'commission_amount')
            ->find($this->detailVendorId);
    }

    /**
     * @return Collection<int, User>
     */
    public function getCreateOwnerOptionsProperty(): Collection
    {
        if ($this->newBranchId === '') {
            return new Collection;
        }

        return User::query()
            ->with(['roles', 'branch'])
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->where('branch_id', $this->newBranchId)
                    ->orWhere(function (Builder $builder): void {
                        $builder->whereNull('branch_id')
                            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', ['admin', 'owner']));
                    });
            })
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        $vendors = Vendor::withCount(['inventoryUnits', 'loans'])
            ->withSum('loans', 'principal_amount')
            ->with(['branch', 'wallet'])
            ->when($this->search, function (Builder $query): void {
                $pattern = "%{$this->search}%";

                $query->where(function (Builder $builder) use ($pattern): void {
                    $builder->whereInsensitiveLike('name', $pattern)
                        ->orWhereInsensitiveLike('phone', $pattern)
                        ->orWhereInsensitiveLike('email', $pattern)
                        ->orWhereInsensitiveLike('code', $pattern);
                });
            })
            ->when($this->statusFilter, fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->branchFilter, fn (Builder $query) => $query->where('branch_id', $this->branchFilter))
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => Vendor::count(),
            'active' => Vendor::where('status', 'active')->count(),
            'total_stock' => InventoryUnit::count(),
            'customers_served' => Customer::whereNotNull('vendor_id')->count(),
            'total_paid_out' => VendorWallet::sum('total_withdrawn'),
            'loan_portfolio' => Loan::whereIn('status', ['active', 'overdue'])->sum('principal_amount'),
        ];

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_headquarter')
            ->orderBy('name')
            ->get();

        $createOwnerOptions = $this->createOwnerOptions;

        return view('livewire.partnership.vendor-directory', compact('vendors', 'stats', 'branches', 'createOwnerOptions'))
            ->layout('layouts.app', ['title' => 'Vendor Directory']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function createRules(): array
    {
        return [
            'newName' => ['required', 'string', 'min:3', 'max:120'],
            'newCode' => ['nullable', 'string', 'max:20', Rule::unique(Vendor::class, 'code')],
            'newPhone' => ['nullable', 'string', 'max:20'],
            'newEmail' => ['nullable', 'email', 'max:255'],
            'newBranchId' => ['required', Rule::exists(Branch::class, 'id')->where('is_active', true)],
            'newOwnerUserId' => ['nullable', Rule::exists(User::class, 'id')],
            'newTinNumber' => ['nullable', 'string', 'max:20'],
            'newCommissionRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'newStatus' => ['required', Rule::in(['active', 'suspended', 'closed'])],
            'newAddress' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function resetCreateForm(): void
    {
        $this->resetValidation();

        $this->newName = '';
        $this->newCode = '';
        $this->newPhone = '';
        $this->newEmail = '';
        $this->newBranchId = '';
        $this->newOwnerUserId = '';
        $this->newTinNumber = '';
        $this->newCommissionRate = '0';
        $this->newStatus = 'active';
        $this->newAddress = '';
    }

    protected function generateVendorCode(string $branchId): string
    {
        $branchCode = Branch::query()->whereKey($branchId)->value('code') ?? 'GEN';
        $cleanBranchCode = Str::upper((string) Str::of($branchCode)->replaceMatches('/[^A-Za-z0-9]/', '')->substr(0, 4));
        $prefix = $cleanBranchCode !== '' ? $cleanBranchCode : 'GEN';

        do {
            $candidate = sprintf('VND-%s-%04d', $prefix, random_int(1, 9999));
        } while (Vendor::where('code', $candidate)->exists());

        return $candidate;
    }
}
