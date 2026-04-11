<?php

namespace App\Livewire\Kyc;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerProfiles extends Component
{
    use WithPagination;

    public string $search = '';

    public string $kycFilter = '';

    public string $branchFilter = '';

    // ── Detail slide-over ──────────────────────────────────────────────────
    public bool $showDetail = false;

    public ?string $detailCustomerId = null;

    // ── Approve ─────────────────────────────────────────────────────────
    public bool $showApproveModal = false;

    public ?string $approveCustomerId = null;

    public string $approveNotes = '';

    // ── Reject ──────────────────────────────────────────────────────────
    public bool $showRejectModal = false;

    public ?string $rejectCustomerId = null;

    public string $rejectReason = '';

    public string $rejectNotes = '';

    /** @var array<string,int> */
    public array $statCounts = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.view'), 403);
        $this->loadStats();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedKycFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBranchFilter(): void
    {
        $this->resetPage();
    }

    private function loadStats(): void
    {
        $this->statCounts = [
            'total' => Customer::count(),
            'verified' => Customer::query()->kycApproved()->count(),
            'pending' => Customer::whereDoesntHave('verifications', fn ($q) => $q->where('status', 'approved'))
                ->whereHas('verifications', fn ($q) => $q->where('status', 'pending'))->count(),
            'rejected' => Customer::whereDoesntHave('verifications', fn ($q) => $q->where('status', 'approved'))
                ->whereHas('verifications', fn ($q) => $q->where('status', 'rejected'))->count(),
        ];
    }

    // ── Detail ───────────────────────────────────────────────────────────
    public function openDetail(string $id): void
    {
        $this->detailCustomerId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailCustomerId = null;
    }

    // ── Approve ─────────────────────────────────────────────────────────
    public function openApproveModal(string $id): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->approveCustomerId = $id;
        $this->approveNotes = '';
        $this->showApproveModal = true;
    }

    public function approveKyc(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->validate(['approveNotes' => 'nullable|string|max:500']);

        $customer = Customer::findOrFail($this->approveCustomerId);

        Verification::updateOrCreate(
            ['customer_id' => $customer->id, 'type' => 'kyc'],
            [
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'notes' => $this->approveNotes ?: null,
            ]
        );

        $customer->update(['kyc_status' => 'approved']);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['notes' => $this->approveNotes])
            ->log('kyc_approved');

        $this->reset(['approveCustomerId', 'approveNotes', 'showApproveModal']);
        $this->loadStats();
        $this->dispatch('toast', message: "KYC approved for {$customer->full_name}.", type: 'success');
    }

    // ── Reject ─────────────────────────────────────────────────────────
    public function openRejectModal(string $id): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->rejectCustomerId = $id;
        $this->rejectReason = '';
        $this->rejectNotes = '';
        $this->showRejectModal = true;
    }

    public function rejectKyc(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->validate([
            'rejectReason' => 'required|string|max:255',
            'rejectNotes' => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($this->rejectCustomerId);

        Verification::updateOrCreate(
            ['customer_id' => $customer->id, 'type' => 'kyc'],
            [
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => $this->rejectReason,
                'notes' => $this->rejectNotes ?: null,
            ]
        );

        $customer->update(['kyc_status' => 'rejected']);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $this->rejectReason])
            ->log('kyc_rejected');

        $this->reset(['rejectCustomerId', 'rejectReason', 'rejectNotes', 'showRejectModal']);
        $this->loadStats();
        $this->dispatch('toast', message: "KYC rejected for {$customer->full_name}.", type: 'success');
    }

    public function releaseAsset(string $id): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);

        $customer = Customer::query()
            ->with(['inventoryUnit', 'latestVerification'])
            ->findOrFail($id);

        if (! $customer->hasApprovedKyc()) {
            $this->dispatch('toast', message: 'Approve the KYC application before releasing the asset.', type: 'error');

            return;
        }

        if (! $customer->hasSuccessfulDepositPayment()) {
            $this->dispatch('toast', message: 'Successful deposit payment is required before releasing the asset.', type: 'error');

            return;
        }

        if (! $customer->hasAcceptedAgreement()) {
            $this->dispatch('toast', message: 'The customer must accept the agreement before release.', type: 'error');

            return;
        }

        if (! $customer->hasCapturedSignatures()) {
            $this->dispatch('toast', message: 'Customer and FO signatures are required before release.', type: 'error');

            return;
        }

        if (! $customer->hasAssetHandoverRecord()) {
            $this->dispatch('toast', message: 'Upload the handover checklist before releasing the asset.', type: 'error');

            return;
        }

        if ($customer->isAssetReleased()) {
            $this->dispatch('toast', message: 'This asset was already released.', type: 'success');

            return;
        }

        if (! $customer->inventoryUnit) {
            $this->dispatch('toast', message: 'No linked stock unit was found for this application.', type: 'error');

            return;
        }

        $customer->update([
            'asset_release_status' => 'released',
            'asset_released_at' => now(),
            'asset_released_by' => auth()->id(),
        ]);

        if ($customer->inventoryUnit->status !== 'sold') {
            $customer->inventoryUnit->update(['status' => 'assigned']);
        }

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties([
                'inventory_unit_id' => $customer->inventory_unit_id,
                'asset_release_status' => 'released',
            ])
            ->log("Asset released for {$customer->full_name}");

        $this->dispatch('toast', message: "Asset released successfully for {$customer->full_name}.", type: 'success');
    }

    public function canReleaseCustomerAsset(Customer $customer): bool
    {
        return auth()->user()->canAccess('loans.create') && $customer->isReadyForAssetRelease();
    }

    public function render()
    {
        $customers = Customer::with(['latestVerification', 'branch', 'registeredBy', 'loans'])
            ->searchDirectory($this->search)
            ->when($this->kycFilter === 'verified', fn ($q) => $q->kycApproved())
            ->when($this->kycFilter === 'pending', fn ($q) => $q->whereDoesntHave('verifications', fn ($v) => $v->where('status', 'approved'))
                ->whereHas('verifications', fn ($v) => $v->where('status', 'pending')))
            ->when($this->kycFilter === 'rejected', fn ($q) => $q->whereHas('verifications', fn ($v) => $v->where('status', 'rejected'))
                ->whereDoesntHave('verifications', fn ($v) => $v->where('status', 'approved')))
            ->when($this->kycFilter === 'not_started', fn ($q) => $q->whereDoesntHave('verifications'))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->latest()
            ->paginate(20);

        $detailCustomer = $this->detailCustomerId
            ? Customer::with([
                'verifications.reviewedBy',
                'verifications.fo',
                'latestVerification.reviewedBy',
                'latestVerification.fo',
                'branch',
                'vendor',
                'agreementDocument',
                'assetReleasedBy',
                'inventoryUnit.phoneModel.brand',
                'registeredBy',
                'selcomPaymentRequests' => fn (Builder $query) => $query->latest('paid_at')->latest(),
                'loans' => fn ($q) => $q->latest()->take(5),
            ])->find($this->detailCustomerId)
            : null;

        $branches = Branch::orderBy('name')->get();

        return view('livewire.kyc.customer-profiles', compact(
            'customers', 'detailCustomer', 'branches'
        ))->layout('layouts.app', ['title' => 'Customer Profiles']);
    }
}
