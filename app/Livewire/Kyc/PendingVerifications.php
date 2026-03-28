<?php

namespace App\Livewire\Kyc;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Verification;
use Livewire\Component;
use Livewire\WithPagination;

class PendingVerifications extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $statusFilter  = '';
    public string $branchFilter  = '';

    // ── Detail slide-over ──────────────────────────────────────────────────
    public bool    $showDetail       = false;
    public ?string $detailCustomerId = null;

    // ── Approve ─────────────────────────────────────────────────────────
    public bool    $showApproveModal   = false;
    public ?string $approveCustomerId  = null;
    public string  $approveNotes       = '';

    // ── Reject ──────────────────────────────────────────────────────────
    public bool    $showRejectModal    = false;
    public ?string $rejectCustomerId   = null;
    public string  $rejectReason       = '';
    public string  $rejectNotes        = '';

    /** @var array<string,int> */
    public array $statCounts = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.view'), 403);
        $this->loadStats();
    }

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedBranchFilter(): void { $this->resetPage(); }

    private function loadStats(): void
    {
        $base = Customer::whereDoesntHave('verifications', fn ($q) => $q->where('status', 'approved'));

        $this->statCounts = [
            'total'       => (clone $base)->count(),
            'not_started' => (clone $base)->whereDoesntHave('verifications')->count(),
            'pending'     => (clone $base)->whereHas('verifications', fn ($q) => $q->where('status', 'pending'))->count(),
            'rejected'    => (clone $base)->whereHas('verifications', fn ($q) => $q->where('status', 'rejected'))->count(),
        ];
    }

    // ── Detail ───────────────────────────────────────────────────────────
    public function openDetail(string $id): void
    {
        $this->detailCustomerId = $id;
        $this->showDetail       = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail       = false;
        $this->detailCustomerId = null;
    }

    // ── Approve ─────────────────────────────────────────────────────────
    public function openApproveModal(string $id): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->approveCustomerId = $id;
        $this->approveNotes      = '';
        $this->showApproveModal  = true;
    }

    public function approveKyc(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->validate(['approveNotes' => 'nullable|string|max:500']);

        $customer = Customer::findOrFail($this->approveCustomerId);

        Verification::updateOrCreate(
            ['customer_id' => $customer->id, 'type' => 'kyc'],
            [
                'status'      => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'notes'       => $this->approveNotes ?: null,
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
        $this->rejectReason     = '';
        $this->rejectNotes      = '';
        $this->showRejectModal  = true;
    }

    public function rejectKyc(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->validate([
            'rejectReason' => 'required|string|max:255',
            'rejectNotes'  => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($this->rejectCustomerId);

        Verification::updateOrCreate(
            ['customer_id' => $customer->id, 'type' => 'kyc'],
            [
                'status'           => 'rejected',
                'reviewed_by'      => auth()->id(),
                'reviewed_at'      => now(),
                'rejection_reason' => $this->rejectReason,
                'notes'            => $this->rejectNotes ?: null,
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

    public function render()
    {
        $customers = Customer::with(['verifications', 'latestVerification', 'branch', 'vendor', 'registeredBy'])
            ->whereDoesntHave('verifications', fn ($q) => $q->where('status', 'approved'))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('first_name', 'ilike', "%{$this->search}%")
                    ->orWhere('last_name', 'ilike', "%{$this->search}%")
                    ->orWhere('middle_name', 'ilike', "%{$this->search}%")
                    ->orWhere('phone', 'ilike', "%{$this->search}%")
                    ->orWhere('nida_number', 'ilike', "%{$this->search}%");
            }))
            ->when($this->statusFilter === 'not_started', fn ($q) => $q->whereDoesntHave('verifications'))
            ->when($this->statusFilter === 'pending', fn ($q) => $q->whereHas('verifications', fn ($v) => $v->where('status', 'pending')))
            ->when($this->statusFilter === 'rejected', fn ($q) => $q->whereHas('verifications', fn ($v) => $v->where('status', 'rejected')))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->latest()
            ->paginate(15);

        $detailCustomer = $this->detailCustomerId
            ? Customer::with([
                'verifications.reviewedBy',
                'latestVerification.reviewedBy',
                'branch',
                'vendor',
                'registeredBy',
                'loans' => fn ($q) => $q->latest()->take(3),
            ])->find($this->detailCustomerId)
            : null;

        $branches = Branch::orderBy('name')->get();

        return view('livewire.kyc.pending-verifications', compact(
            'customers', 'detailCustomer', 'branches'
        ))->layout('layouts.app', ['title' => 'Pending KYC Verifications']);
    }
}
