<?php

namespace App\Livewire\Financials;

use App\Models\Loan;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;

class DailyCollections extends Component
{
    use WithPagination;

    public string $date = '';

    public string $channelFilter = '';

    // ── Record Payment ─────────────────────────────────────────────────────
    public bool $showPaymentModal = false;

    public string $paymentLoanSearch = '';

    public string $paymentLoanId = '';

    public string $paymentAmount = '';

    public string $paymentChannel = 'mpesa';

    public string $paymentExternalRef = '';

    public string $paymentNote = '';

    public string $search = '';

    public bool $showDetail = false;

    public ?string $detailTxnId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('accounting.view'), 403);
        $this->date = today()->toDateString();
    }

    public function openPaymentModal(): void
    {
        $this->reset(['paymentLoanSearch', 'paymentLoanId', 'paymentAmount', 'paymentExternalRef', 'paymentNote']);
        $this->paymentChannel = 'mpesa';
        $this->showPaymentModal = true;
    }

    public function updatedDate(): void
    {
        $this->resetPage();
    }

    public function updatedChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentLoanSearch(): void
    {
        $this->paymentLoanId = '';
    }

    public function openDetail(string $id): void
    {
        $this->detailTxnId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailTxnId = null;
    }

    public function getDetailTxnProperty(): ?Transaction
    {
        if (! $this->detailTxnId) {
            return null;
        }

        return Transaction::with([
            'loan.customer',
            'loan.branch',
            'loan.inventoryUnit.phoneModel.brand',
            'recordedBy',
            'repaymentSchedule',
        ])->find($this->detailTxnId);
    }

    public function recordPayment(): void
    {
        $this->validate([
            'paymentLoanId' => 'required|exists:loans,id',
            'paymentAmount' => 'required|numeric|min:1',
            'paymentChannel' => 'required|in:mpesa,cash,tigopesa,halopesa,bank',
        ]);

        $loan = Loan::findOrFail($this->paymentLoanId);
        $amount = (float) $this->paymentAmount;

        Transaction::create([
            'loan_id' => $loan->id,
            'customer_id' => $loan->customer_id,
            'recorded_by' => auth()->id(),
            'type' => 'repayment',
            'entry_type' => 'credit',
            'amount' => $amount,
            'channel' => $this->paymentChannel,
            'reference' => 'TXN-'.strtoupper(substr(uniqid(), -8)),
            'external_reference' => $this->paymentExternalRef ?: null,
            'description' => $this->paymentNote ?: "Repayment via {$this->paymentChannel}",
            'transacted_at' => now(),
        ]);

        $newPaid = (float) $loan->amount_paid + $amount;
        $newRemaining = max(0, (float) $loan->remaining_balance - $amount);

        $loan->update([
            'amount_paid' => $newPaid,
            'remaining_balance' => $newRemaining,
            'outstanding_balance' => $newRemaining,
            'status' => $newRemaining <= 0 ? 'completed' : $loan->status,
            'completed_at' => $newRemaining <= 0 ? now() : null,
        ]);

        activity('loan')
            ->performedOn($loan)
            ->causedBy(auth()->user())
            ->log('Payment TZS '.number_format($amount)." via {$this->paymentChannel} recorded on {$loan->loan_number}");

        $this->showPaymentModal = false;
        $this->dispatch('toast', message: 'TZS '.number_format($amount).' recorded on '.$loan->loan_number.'!', type: 'success');
    }

    public function render()
    {
        $transactions = Transaction::with(['loan.customer', 'recordedBy'])
            ->where('entry_type', 'credit')
            ->whereDate('transacted_at', $this->date)
            ->when($this->channelFilter, fn ($q) => $q->where('channel', $this->channelFilter))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->whereInsensitiveLike('reference', "%{$this->search}%")
                    ->orWhereInsensitiveLike('external_reference', "%{$this->search}%")
                    ->orWhereHas('loan', fn ($q) => $q->whereInsensitiveLike('loan_number', "%{$this->search}%"))
                    ->orWhereHas('loan.customer', fn ($q) => $q
                        ->whereInsensitiveLike('first_name', "%{$this->search}%")
                        ->orWhereInsensitiveLike('last_name', "%{$this->search}%")
                        ->orWhereInsensitiveLike('phone', "%{$this->search}%"));
            }))
            ->latest('transacted_at')
            ->paginate(25);

        $base = fn () => Transaction::where('entry_type', 'credit')->whereDate('transacted_at', $this->date);

        $summary = [
            'total' => (clone $base())->sum('amount'),
            'count' => (clone $base())->count(),
            'mpesa' => (clone $base())->where('channel', 'mpesa')->sum('amount'),
            'cash' => (clone $base())->where('channel', 'cash')->sum('amount'),
            'tigopesa' => (clone $base())->where('channel', 'tigopesa')->sum('amount'),
            'halopesa' => (clone $base())->where('channel', 'halopesa')->sum('amount'),
            'bank' => (clone $base())->where('channel', 'bank')->sum('amount'),
            'month_total' => Transaction::where('entry_type', 'credit')
                ->whereMonth('transacted_at', now()->month)
                ->whereYear('transacted_at', now()->year)
                ->sum('amount'),
        ];

        $searchLoans = Loan::with('customer')
            ->when($this->paymentLoanSearch, function ($q) {
                $q->whereInsensitiveLike('loan_number', "%{$this->paymentLoanSearch}%")
                    ->orWhereHas('customer', fn ($c) => $c->whereInsensitiveLike('first_name', "%{$this->paymentLoanSearch}%")
                        ->orWhereInsensitiveLike('last_name', "%{$this->paymentLoanSearch}%")
                        ->orWhereInsensitiveLike('phone', "%{$this->paymentLoanSearch}%")
                    );
            })
            ->whereIn('status', ['active', 'overdue', 'defaulted'])
            ->orderBy('loan_number')
            ->limit(30)
            ->get();

        return view('livewire.financials.daily-collections', compact('transactions', 'summary', 'searchLoans'))
            ->layout('layouts.app', ['title' => 'Daily Collections']);
    }
}
