<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Models\Transaction;
use App\Services\DealerHierarchyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessCommissionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $loanId,
        public readonly int $transactionId
    ) {
        $this->onQueue('commissions');
    }

    public function handle(DealerHierarchyService $service): void
    {
        $loan = Loan::findOrFail($this->loanId);
        $transaction = Transaction::findOrFail($this->transactionId);

        $service->postCommission($loan, $transaction);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessCommissionJob failed', [
            'loan_id' => $this->loanId,
            'transaction_id' => $this->transactionId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
