<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Verification;

class ApplicationAutoCheckService
{
    /**
     * Run all automated checks on a freshly submitted application.
     *
     * Returns status: passed | needs_correction | manual_review | auto_rejected
     *
     * @return array{status: string, checks: array<string, array{pass: bool, message: string}>}
     */
    public function run(Customer $customer, Verification $verification): array
    {
        $checks = [];

        $checks['nida_duplicate'] = $this->checkNidaDuplicate($customer);
        $checks['phone_duplicate'] = $this->checkPhoneDuplicate($customer);
        $checks['imei_duplicate'] = $this->checkImeiDuplicate($customer);
        $checks['age_minimum'] = $this->checkMinimumAge($customer);
        $checks['required_docs'] = $this->checkRequiredDocs($customer);
        $checks['income_ratio'] = $this->checkIncomeRatio($customer);
        $checks['nok_phone_reuse'] = $this->checkNokPhoneReuse($customer);
        $checks['active_loan'] = $this->checkActiveLoan($customer);
        $checks['fo_pattern'] = $this->checkFoPattern($customer);
        $checks['consent'] = $this->checkConsent($customer);

        $hardFails = array_filter($checks, fn ($c) => ! $c['pass'] && $c['severity'] === 'hard');
        $softFails = array_filter($checks, fn ($c) => ! $c['pass'] && $c['severity'] === 'soft');
        $warnOnly = array_filter($checks, fn ($c) => ! $c['pass'] && $c['severity'] === 'warn');

        $status = match (true) {
            count($hardFails) > 0 => 'auto_rejected',
            count($softFails) > 0 => 'needs_correction',
            count($warnOnly) > 0 => 'manual_review',
            default => 'passed',
        };

        $verification->update([
            'auto_check_status' => $status,
            'auto_check_results' => $checks,
            'auto_check_ran_at' => now(),
        ]);

        return ['status' => $status, 'checks' => $checks];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkNidaDuplicate(Customer $customer): array
    {
        $duplicate = Customer::where('nida_number', $customer->nida_number)
            ->where('id', '!=', $customer->id)
            ->whereNotNull('nida_number')
            ->exists();

        return [
            'pass' => ! $duplicate,
            'severity' => 'hard',
            'message' => $duplicate
                ? 'NIDA number already registered to another customer.'
                : 'NIDA is unique.',
        ];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkPhoneDuplicate(Customer $customer): array
    {
        $duplicate = Customer::where('phone', $customer->phone)
            ->where('id', '!=', $customer->id)
            ->exists();

        return [
            'pass' => ! $duplicate,
            'severity' => 'hard',
            'message' => $duplicate
                ? 'Phone number already registered to another customer.'
                : 'Phone is unique.',
        ];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkImeiDuplicate(Customer $customer): array
    {
        if (! $customer->imei_number) {
            return ['pass' => false, 'severity' => 'hard', 'message' => 'IMEI number is missing.'];
        }

        $duplicate = Customer::where('imei_number', $customer->imei_number)
            ->where('id', '!=', $customer->id)
            ->exists();

        return [
            'pass' => ! $duplicate,
            'severity' => 'hard',
            'message' => $duplicate
                ? 'IMEI already used in another application.'
                : 'IMEI is unique.',
        ];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkMinimumAge(Customer $customer): array
    {
        if (! $customer->date_of_birth) {
            return ['pass' => true, 'severity' => 'warn', 'message' => 'Date of birth not provided — age not verified.'];
        }

        $age = $customer->date_of_birth->age;

        return [
            'pass' => $age >= 18,
            'severity' => 'hard',
            'message' => $age >= 18
                ? "Customer age {$age} meets minimum requirement."
                : "Customer age {$age} is below minimum of 18 years.",
        ];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkRequiredDocs(Customer $customer): array
    {
        $missing = [];

        if (! $customer->id_front_photo_path) {
            $missing[] = 'ID Front';
        }
        if (! $customer->id_back_photo_path) {
            $missing[] = 'ID Back';
        }
        if (! $customer->headshot_photo_path) {
            $missing[] = 'Customer Headshot';
        }
        if (! $customer->imei_number) {
            $missing[] = 'IMEI';
        }
        if (! $customer->nida_number) {
            $missing[] = 'NIDA Number';
        }

        return [
            'pass' => empty($missing),
            'severity' => 'soft',
            'message' => empty($missing)
                ? 'All required documents present.'
                : 'Missing required docs: '.implode(', ', $missing).'.',
        ];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkIncomeRatio(Customer $customer): array
    {
        if (! $customer->monthly_income || ! $customer->cash_price) {
            return ['pass' => true, 'severity' => 'warn', 'message' => 'Income or device price missing — affordability not computed.'];
        }

        $income = (float) $customer->monthly_income;
        $devicePrice = (float) $customer->cash_price;
        $deposit = (float) ($customer->deposit_amount ?? 0);
        $financed = max(0, $devicePrice - $deposit);

        // Financed amount should not exceed 6x monthly income
        $pass = $financed <= ($income * 6);

        return [
            'pass' => $pass,
            'severity' => 'soft',
            'message' => $pass
                ? "Financed amount TZS {$financed} is within affordability range."
                : "Financed amount TZS {$financed} may exceed 6× monthly income of TZS {$income}.",
        ];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkNokPhoneReuse(Customer $customer): array
    {
        if (! $customer->nok_phone) {
            return ['pass' => false, 'severity' => 'soft', 'message' => 'NOK phone number is missing.'];
        }

        $count = Customer::where('nok_phone', $customer->nok_phone)
            ->where('id', '!=', $customer->id)
            ->count();

        $suspicious = $count >= 5;

        return [
            'pass' => ! $suspicious,
            'severity' => 'warn',
            'message' => $suspicious
                ? "NOK phone used in {$count} other applications — possible fraudulent pattern."
                : 'NOK phone appears normal.',
        ];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkActiveLoan(Customer $customer): array
    {
        $hasActive = $customer->activeLoans()->exists();

        return [
            'pass' => ! $hasActive,
            'severity' => 'hard',
            'message' => $hasActive
                ? 'Customer already has an active loan — new application blocked.'
                : 'No active loans found.',
        ];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkFoPattern(Customer $customer): array
    {
        $foId = $customer->registered_by;

        if (! $foId) {
            return ['pass' => true, 'severity' => 'warn', 'message' => 'FO ID not captured.'];
        }

        $todayCount = Customer::where('registered_by', $foId)
            ->whereDate('created_at', today())
            ->count();

        $suspicious = $todayCount > 20;

        return [
            'pass' => ! $suspicious,
            'severity' => 'warn',
            'message' => $suspicious
                ? "FO submitted {$todayCount} applications today — high volume flagged for review."
                : "FO volume today ({$todayCount}) is within normal range.",
        ];
    }

    /** @return array{pass: bool, severity: string, message: string} */
    private function checkConsent(Customer $customer): array
    {
        $allAccepted = $customer->terms_accepted
            && $customer->data_consent_accepted
            && $customer->call_consent_accepted;

        return [
            'pass' => $allAccepted,
            'severity' => 'hard',
            'message' => $allAccepted
                ? 'All consent declarations accepted.'
                : 'One or more consent declarations are missing.',
        ];
    }
}
