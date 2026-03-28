<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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

    public function handle(\App\Services\VendorHierarchyService $service): void
    {
        $loan = \App\Models\Loan::findOrFail($this->loanId);
        $transaction = \App\Models\Transaction::findOrFail($this->transactionId);

        $service->postCommission($loan, $transaction);
    }

    public function failed(\Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('ProcessCommissionJob failed', [
            'loan_id'        => $this->loanId,
            'transaction_id' => $this->transactionId,
            'exception'      => $exception->getMessage(),
        ]);
    }
}
