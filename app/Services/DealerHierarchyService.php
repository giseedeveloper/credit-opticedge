<?php

namespace App\Services;

use App\Models\CommissionLedger;
use App\Models\Dealer;
use App\Models\DealerWallet;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DealerHierarchyService
{
    /**
     * Post commission to the dealer wallet after a repayment transaction.
     */
    public function postCommission(Loan $loan, Transaction $transaction): ?CommissionLedger
    {
        $dealer = $loan->dealer;

        if (! $dealer || $dealer->commission_rate <= 0) {
            return null;
        }

        $commissionAmount = round(
            (float) $transaction->amount * ((float) $dealer->commission_rate / 100),
            2
        );

        return DB::transaction(function () use ($dealer, $loan, $transaction, $commissionAmount) {
            $ledger = CommissionLedger::create([
                'dealer_id' => $dealer->id,
                'loan_id' => $loan->id,
                'transaction_id' => $transaction->id,
                'commission_rate' => $dealer->commission_rate,
                'commission_amount' => $commissionAmount,
                'status' => 'posted',
                'description' => "Commission on repayment {$transaction->reference}",
                'posted_at' => now(),
            ]);

            $this->creditWallet($dealer, $commissionAmount);

            return $ledger;
        });
    }

    public function creditWallet(Dealer $dealer, float $amount): DealerWallet
    {
        $wallet = DealerWallet::firstOrCreate(
            ['dealer_id' => $dealer->id],
            ['balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0]
        );

        $wallet->increment('balance', $amount);
        $wallet->increment('total_earned', $amount);
        $wallet->update(['last_transaction_at' => now()]);

        return $wallet->fresh();
    }

    /**
     * @throws \RuntimeException
     */
    public function debitWallet(Dealer $dealer, float $amount, string $description = ''): DealerWallet
    {
        $wallet = $dealer->wallet;

        if (! $wallet || $wallet->balance < $amount) {
            throw new \RuntimeException('Insufficient wallet balance for dealer '.$dealer->code);
        }

        $wallet->decrement('balance', $amount);
        $wallet->increment('total_withdrawn', $amount);
        $wallet->update(['last_transaction_at' => now()]);

        return $wallet->fresh();
    }

    /**
     * @return array{dealer_id: string, total_earned: float, total_withdrawn: float, balance: float}
     */
    public function walletStatement(Dealer $dealer): array
    {
        $wallet = $dealer->wallet ?? new DealerWallet([
            'balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
        ]);

        return [
            'dealer_id' => $dealer->id,
            'total_earned' => (float) $wallet->total_earned,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
            'balance' => (float) $wallet->balance,
        ];
    }

    /**
     * Staff users assigned to this dealer counter.
     */
    public function getDealerStaff(Dealer $dealer): Collection
    {
        return $dealer->staff()->where('role', 'staff')->get();
    }
}
