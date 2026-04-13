<?php

namespace App\Livewire\Kyc;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Verification;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class PendingVerifications extends Component
{
    use WithPagination;

    public string $search = '';

    public string $branchFilter = '';

    public int $activeTab = 1;

    // ── Detail slide-over ───────────────────────────────────────────────
    public bool $showDetail = false;

    public ?string $detailCustomerId = null;

    // ── Stage 1 / Stage 2 Approve ──────────────────────────────────────
    public bool $showApproveModal = false;

    public ?string $actionCustomerId = null;

    public int $actionStage = 1;

    public string $approveNotes = '';

    // ── Stage 1 / Stage 2 Reject ───────────────────────────────────────
    public bool $showRejectModal = false;

    public string $rejectReason = '';

    public string $rejectNotes = '';

    // ── Stage 3: Confirmation Call ─────────────────────────────────────
    public bool $showCallModal = false;

    public string $callOutcome = '';

    public string $callNotes = '';

    // ── Stage 4: NOK Call ──────────────────────────────────────────────
    public bool $showNokModal = false;

    public string $nokOutcome = '';

    public string $nokNotes = '';

    /** @var array<int,int> */
    public array $stageCounts = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('loans.view'), 403);
        $this->loadStats();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    private function loadStats(): void
    {
        $this->stageCounts = [
            1 => Customer::whereHas('latestKycVerification', fn ($q) => $q->where('stage', 1)->where('status', 'pending'))->count(),
            2 => Customer::whereHas('latestKycVerification', fn ($q) => $q->where('stage', 2)->where('status', 'pending'))->count(),
            3 => Customer::whereHas('latestKycVerification', fn ($q) => $q->where('stage', 3)->where('status', 'pending'))->count(),
            4 => Customer::whereHas('latestKycVerification', fn ($q) => $q->where('stage', 4)->where('status', 'pending'))->count(),
        ];
    }

    // ── Detail ─────────────────────────────────────────────────────────
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

    // ── Stage 1 & 2 Approve ────────────────────────────────────────────
    public function openApproveModal(string $id, int $stage): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->actionCustomerId = $id;
        $this->actionStage = $stage;
        $this->approveNotes = '';
        $this->showApproveModal = true;
    }

    public function approveStage(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->validate(['approveNotes' => 'nullable|string|max:500']);

        $customer = Customer::findOrFail($this->actionCustomerId);
        $verification = $this->resolveKycVerification($customer);
        $stage = $this->actionStage;
        $nextStage = $stage + 1;

        $verification->update([
            "stage{$stage}_status" => 'approved',
            "stage{$stage}_reviewed_by" => auth()->id(),
            "stage{$stage}_reviewed_at" => now(),
            "stage{$stage}_notes" => $this->approveNotes ?: null,
            'stage' => $nextStage <= 4 ? $nextStage : $stage,
            'status' => $nextStage > 4 ? 'approved' : 'pending',
        ]);

        $customer->update([
            'kyc_stage' => $nextStage <= 4 ? $nextStage : $stage,
            'kyc_status' => $nextStage > 4 ? 'approved' : 'pending',
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->log("Stage {$stage} approved for {$customer->full_name}");

        $this->reset(['actionCustomerId', 'actionStage', 'approveNotes', 'showApproveModal']);
        $this->loadStats();
        $this->dispatch('toast', message: "Stage {$stage} approved — moved to Stage {$nextStage}.", type: 'success');
    }

    // ── Stage 1 & 2 Reject ─────────────────────────────────────────────
    public function openRejectModal(string $id, int $stage): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->actionCustomerId = $id;
        $this->actionStage = $stage;
        $this->rejectReason = '';
        $this->rejectNotes = '';
        $this->showRejectModal = true;
    }

    public function rejectStage(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->validate([
            'rejectReason' => 'required|string|max:255',
            'rejectNotes' => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($this->actionCustomerId);
        $verification = $this->resolveKycVerification($customer);
        $stage = $this->actionStage;

        $verification->update([
            "stage{$stage}_status" => 'rejected',
            "stage{$stage}_reviewed_by" => auth()->id(),
            "stage{$stage}_reviewed_at" => now(),
            "stage{$stage}_rejection_reason" => $this->rejectReason,
            "stage{$stage}_notes" => $this->rejectNotes ?: null,
            'status' => 'rejected',
            'rejection_reason' => $this->rejectReason,
        ]);

        $customer->update(['kyc_status' => 'rejected']);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $this->rejectReason])
            ->log("Stage {$stage} rejected for {$customer->full_name}");

        $this->reset(['actionCustomerId', 'actionStage', 'rejectReason', 'rejectNotes', 'showRejectModal']);
        $this->loadStats();
        $this->dispatch('toast', message: "Application rejected at Stage {$stage}.", type: 'error');
    }

    // ── Stage 3: Confirmation Call ─────────────────────────────────────
    public function openCallModal(string $id): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->actionCustomerId = $id;
        $this->callOutcome = '';
        $this->callNotes = '';
        $this->showCallModal = true;
    }

    public function recordConfirmationCall(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->validate([
            'callOutcome' => 'required|in:confirmed,not_confirmed',
            'callNotes' => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($this->actionCustomerId);
        $verification = $this->resolveKycVerification($customer);

        $isConfirmed = $this->callOutcome === 'confirmed';

        $verification->update([
            'stage3_status' => $isConfirmed ? 'approved' : 'rejected',
            'confirmation_call_outcome' => $this->callOutcome,
            'confirmation_call_notes' => $this->callNotes ?: null,
            'confirmation_called_at' => now(),
            'confirmation_called_by' => auth()->id(),
            'stage' => $isConfirmed ? 4 : 3,
            'status' => $isConfirmed ? 'pending' : 'rejected',
        ]);

        $customer->update([
            'kyc_stage' => $isConfirmed ? 4 : 3,
            'kyc_status' => $isConfirmed ? 'pending' : 'rejected',
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->log("Confirmation call {$this->callOutcome} for {$customer->full_name}");

        $this->reset(['actionCustomerId', 'callOutcome', 'callNotes', 'showCallModal']);
        $this->loadStats();
        $msg = $isConfirmed ? 'Confirmation call confirmed — moved to Stage 4.' : 'Confirmation call failed — application rejected.';
        $this->dispatch('toast', message: $msg, type: $isConfirmed ? 'success' : 'error');
    }

    // ── Stage 4: NOK Call + Final Approval ─────────────────────────────
    public function openNokModal(string $id): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->actionCustomerId = $id;
        $this->nokOutcome = '';
        $this->nokNotes = '';
        $this->showNokModal = true;
    }

    public function recordNokCall(): void
    {
        abort_unless(auth()->user()->canAccess('loans.create'), 403);
        $this->validate([
            'nokOutcome' => 'required|in:confirmed,not_confirmed',
            'nokNotes' => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($this->actionCustomerId);
        $verification = $this->resolveKycVerification($customer);

        $isConfirmed = $this->nokOutcome === 'confirmed';

        $verification->update([
            'stage4_status' => $isConfirmed ? 'approved' : 'rejected',
            'nok_call_outcome' => $this->nokOutcome,
            'nok_call_notes' => $this->nokNotes ?: null,
            'nok_called_at' => now(),
            'nok_called_by' => auth()->id(),
            'status' => $isConfirmed ? 'approved' : 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $customer->update([
            'kyc_stage' => 4,
            'kyc_status' => $isConfirmed ? 'approved' : 'rejected',
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->log($isConfirmed
                ? "KYC fully approved for {$customer->full_name}"
                : "NOK call failed — KYC rejected for {$customer->full_name}");

        $this->reset(['actionCustomerId', 'nokOutcome', 'nokNotes', 'showNokModal']);
        $this->loadStats();
        $msg = $isConfirmed ? "KYC fully approved for {$customer->full_name}." : 'NOK call failed — application rejected.';
        $this->dispatch('toast', message: $msg, type: $isConfirmed ? 'success' : 'error');
    }

    public function render()
    {
        $customers = Customer::with(['latestKycVerification', 'branch', 'registeredBy'])
            ->whereHas('latestKycVerification', fn ($q) => $q->where('stage', $this->activeTab)->where('status', 'pending'))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('nida_number', 'like', "%{$this->search}%")
                    ->orWhere('imei_number', 'like', "%{$this->search}%");
            }))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->latest()
            ->paginate(15);

        $detailCustomer = $this->detailCustomerId
            ? Customer::with([
                'latestKycVerification',
                'branch',
                'registeredBy',
                'loans' => fn ($q) => $q->latest()->take(3),
            ])->find($this->detailCustomerId)
            : null;

        $branches = Branch::orderBy('name')->get();

        return view('livewire.kyc.pending-verifications', compact(
            'customers', 'detailCustomer', 'branches'
        ))->layout('layouts.app', ['title' => 'KYC Verifications']);
    }

    private function resolveKycVerification(Customer $customer): Verification
    {
        $verification = $customer->latestKycVerification()->first();

        if (! $verification) {
            throw ValidationException::withMessages([
                'verification' => 'No KYC verification record found for this customer.',
            ]);
        }

        return $verification;
    }
}
