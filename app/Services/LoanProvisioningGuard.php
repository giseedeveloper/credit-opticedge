<?php

namespace App\Services;

use App\Models\Customer;
use InvalidArgumentException;

/**
 * Single gate for creating or activating customer loans across release, portal, and web flows.
 */
class LoanProvisioningGuard
{
    public const CHANNEL_RELEASE = 'release';

    public const CHANNEL_PORTAL = 'portal';

    public const CHANNEL_MANUAL_WEB = 'manual_web';

    /**
     * @return list<string>
     */
    public function blockingReasons(Customer $customer, string $channel = self::CHANNEL_RELEASE): array
    {
        $reasons = [];

        if ($customer->activeLoans()->exists()) {
            $reasons[] = 'Customer already has an active loan.';
        }

        if (! $customer->hasApprovedKyc()) {
            $reasons[] = 'KYC must be fully approved before a loan can be created.';
        }

        if (! $customer->inventory_unit_id && ! filled($customer->imei_number)) {
            $reasons[] = 'Customer must have an assigned inventory unit or a captured IMEI.';
        }

        if (! $customer->hasSuccessfulDepositPayment()) {
            $reasons[] = 'Deposit payment must be completed.';
        }

        if ($channel === self::CHANNEL_RELEASE || $channel === self::CHANNEL_PORTAL) {
            if (! $customer->isAssetReleased()) {
                $reasons[] = 'Asset must be released before loan provisioning.';
            }

            if (! filled($customer->cash_price) || (float) $customer->cash_price <= 0) {
                $reasons[] = 'Cash price must be set on the customer record.';
            }

            if (! filled($customer->preferred_repayment)) {
                $reasons[] = 'Preferred repayment frequency must be set.';
            }
        }

        if ($channel === self::CHANNEL_MANUAL_WEB) {
            if (! config('credit.allow_manual_disbursement')) {
                $reasons[] = 'Manual loan disbursement is disabled. Use the KYC release workflow.';
            }

            if (! $customer->isAssetReleased()) {
                $reasons[] = 'Manual disbursement requires asset release through the standard KYC flow.';
            }
        }

        return $reasons;
    }

    public function canProvision(Customer $customer, string $channel = self::CHANNEL_RELEASE): bool
    {
        return $this->blockingReasons($customer, $channel) === [];
    }

    public function assertCanProvision(Customer $customer, string $channel = self::CHANNEL_RELEASE): void
    {
        $reasons = $this->blockingReasons($customer, $channel);

        if ($reasons !== []) {
            throw new InvalidArgumentException($reasons[0]);
        }
    }
}
