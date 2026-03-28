<?php

namespace App\Services;

use App\Models\CommissionLedger;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Models\VendorWallet;
use Illuminate\Support\Facades\DB;

class VendorHierarchyService
{
    /**
     * Post commission to the vendor wallet after a repayment transaction.
     */
    public function postCommission(Loan $loan, Transaction $transaction): ?CommissionLedger
    {
        $vendor = $loan->vendor;

        if (! $vendor || $vendor->commission_rate <= 0) {
            return null;
        }

        $commissionAmount = round(
            (float) $transaction->amount * ((float) $vendor->commission_rate / 100),
            2
        );

        return DB::transaction(function () use ($vendor, $loan, $transaction, $commissionAmount) {
            $ledger = CommissionLedger::create([
                'vendor_id'         => $vendor->id,
                'loan_id'           => $loan->id,
                'transaction_id'    => $transaction->id,
                'commission_rate'   => $vendor->commission_rate,
                'commission_amount' => $commissionAmount,
                'status'            => 'posted',
                'description'       => "Commission on repayment {$transaction->reference}",
                'posted_at'         => now(),
            ]);

            $this->creditWallet($vendor, $commissionAmount);

            return $ledger;
        });
    }

    /**
     * Credit the vendor's internal wallet.
     */
    public function creditWallet(Vendor $vendor, float $amount): VendorWallet
    {
        $wallet = VendorWallet::firstOrCreate(
            ['vendor_id' => $vendor->id],
            ['balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0]
        );

        $wallet->increment('balance', $amount);
        $wallet->increment('total_earned', $amount);
        $wallet->update(['last_transaction_at' => now()]);

        return $wallet->fresh();
    }

    /**
     * Debit the vendor's wallet (withdrawal or reversal).
     *
     * @throws \RuntimeException
     */
    public function debitWallet(Vendor $vendor, float $amount, string $description = ''): VendorWallet
    {
        $wallet = $vendor->wallet;

        if (! $wallet || $wallet->balance < $amount) {
            throw new \RuntimeException('Insufficient wallet balance for vendor ' . $vendor->code);
        }

        $wallet->decrement('balance', $amount);
        $wallet->increment('total_withdrawn', $amount);
        $wallet->update(['last_transaction_at' => now()]);

        return $wallet->fresh();
    }

    /**
     * Return the commission balance statement for a vendor.
     *
     * @return array{vendor_id: int, total_earned: float, total_withdrawn: float, balance: float}
     */
    public function walletStatement(Vendor $vendor): array
    {
        $wallet = $vendor->wallet ?? new VendorWallet([
            'balance'          => 0,
            'total_earned'     => 0,
            'total_withdrawn'  => 0,
        ]);

        return [
            'vendor_id'       => $vendor->id,
            'total_earned'    => (float) $wallet->total_earned,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
            'balance'         => (float) $wallet->balance,
        ];
    }

    /**
     * Get all staff (users) assigned to a vendor's branch.
     */
    public function getVendorStaff(Vendor $vendor): \Illuminate\Database\Eloquent\Collection
    {
        return $vendor->branch?->users()->where('role', 'staff')->get()
            ?? collect();
    }
}
