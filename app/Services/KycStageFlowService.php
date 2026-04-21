<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SelcomPaymentRequest;
use App\Models\SystemDocument;

class KycStageFlowService
{
    /**
     * Current public contract for the simplified FO/app onboarding flow.
     */
    public const VERSION = 'kyc_3_stage_v1';

    /**
     * @return array{
     *     version: string,
     *     total_stages: int,
     *     stages: list<array<string, mixed>>
     * }
     */
    public function contract(): array
    {
        return [
            'version' => self::VERSION,
            'total_stages' => 3,
            'stages' => $this->definitions(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            [
                'stage' => 1,
                'key' => 'device_offer',
                'label' => 'Device & Offer',
                'short_label' => 'Device',
                'description' => 'Select the exact handset, confirm identifiers, capture evidence, and lock the credit offer.',
                'legacy_steps' => [1],
                'primary_endpoint' => '/api/v1/kyc/application/stage1',
                'legacy_endpoint' => '/api/v1/kyc/application/step1',
            ],
            [
                'stage' => 2,
                'key' => 'customer_verification',
                'label' => 'Customer & Verification',
                'short_label' => 'Customer',
                'description' => 'Capture identity, contact, income, next of kin, and consent in one guided review.',
                'legacy_steps' => [2, 3, 4, 5, 6],
                'primary_endpoint' => '/api/v1/kyc/application/{customer_id}/stage2',
                'legacy_endpoint' => '/api/v1/kyc/application/{customer_id}/step2-6',
            ],
            [
                'stage' => 3,
                'key' => 'payment_agreement_handover',
                'label' => 'Payment, Agreement & Handover',
                'short_label' => 'Finalize',
                'description' => 'Confirm deposit payment, present the agreement, collect signatures, and upload handover proof.',
                'legacy_steps' => [7],
                'primary_endpoint' => '/api/v1/kyc/application/{customer_id}/stage3',
                'legacy_endpoint' => '/api/v1/kyc/application/{customer_id}/step7',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(
        Customer $customer,
        ?SelcomPaymentRequest $payment = null,
        ?SystemDocument $activeAgreement = null
    ): array {
        $resumeStage = $this->determineResumeStage($customer);
        $stages = [];

        foreach ($this->definitions() as $definition) {
            $checklist = $this->checklistFor($definition['stage'], $customer, $payment, $activeAgreement);
            $completedItems = count(array_filter($checklist, fn (array $item): bool => $item['complete'] === true));
            $totalItems = count($checklist);
            $blockers = array_values(array_map(
                fn (array $item): string => $item['label'],
                array_filter($checklist, fn (array $item): bool => $item['blocking'] === true && $item['complete'] === false)
            ));

            $stages[] = array_merge($definition, [
                'status' => $this->stageStatus((int) $definition['stage'], $resumeStage, $totalItems, $completedItems),
                'completed_items' => $completedItems,
                'total_items' => $totalItems,
                'blockers' => $blockers,
                'checklist' => $checklist,
            ]);
        }

        return [
            'version' => self::VERSION,
            'total_stages' => 3,
            'current_stage' => $resumeStage,
            'resume_stage' => $resumeStage,
            'progress_percent' => $this->progressPercent($stages),
            'stages' => $stages,
        ];
    }

    public function determineResumeStage(Customer $customer): int
    {
        if ($this->stageThreeComplete($customer)) {
            return 3;
        }

        if ($this->stageTwoComplete($customer)) {
            return 3;
        }

        if ($this->stageOneComplete($customer)) {
            return 2;
        }

        return 1;
    }

    /**
     * @return list<array{key: string, label: string, complete: bool, blocking: bool}>
     */
    private function checklistFor(
        int $stage,
        Customer $customer,
        ?SelcomPaymentRequest $payment,
        ?SystemDocument $activeAgreement
    ): array {
        return match ($stage) {
            1 => [
                $this->checklistItem('device_selected', 'Device model or specs selected', filled($customer->phone_model_id) || filled($customer->device_specs)),
                $this->checklistItem('identifier_confirmed', 'IMEI confirmed', filled($customer->imei_number)),
                $this->checklistItem('price_confirmed', 'Cash price captured', ! is_null($customer->cash_price)),
                $this->checklistItem('deposit_confirmed', 'Deposit amount captured', ! is_null($customer->deposit_amount)),
                $this->checklistItem('loan_terms_locked', 'Loan terms locked', $this->loanTermsCaptured($customer)),
            ],
            2 => [
                $this->checklistItem('identity', 'Identity details captured', $this->identityCaptured($customer)),
                $this->checklistItem('identity_photos', 'ID and headshot photos captured', $this->identityPhotosCaptured($customer)),
                $this->checklistItem('contact_branch', 'Phone and serving branch captured', $this->contactCaptured($customer)),
                $this->checklistItem('income', 'Income profile captured', ! is_null($customer->monthly_income)),
                $this->checklistItem('next_of_kin', 'Next of kin captured', $this->nextOfKinCaptured($customer)),
                $this->checklistItem('consent', 'Customer consent accepted', $this->consentCaptured($customer)),
            ],
            3 => [
                $this->checklistItem('deposit_payment', 'Deposit payment completed', $this->depositCompleted($customer, $payment)),
                $this->checklistItem('agreement_available', 'Agreement document available', filled($customer->agreement_document_id) || $activeAgreement !== null),
                $this->checklistItem('agreement_accepted', 'Agreement accepted', $customer->agreement_accepted === true),
                $this->checklistItem('signatures', 'Customer and FO signatures captured', $customer->hasCapturedSignatures()),
                $this->checklistItem('handover', 'Handover checklist uploaded', $customer->hasAssetHandoverRecord()),
            ],
            default => [],
        };
    }

    /**
     * @return array{key: string, label: string, complete: bool, blocking: bool}
     */
    private function checklistItem(string $key, string $label, bool $complete, bool $blocking = true): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'complete' => $complete,
            'blocking' => $blocking,
        ];
    }

    private function stageStatus(int $stage, int $resumeStage, int $totalItems, int $completedItems): string
    {
        if ($totalItems > 0 && $completedItems === $totalItems) {
            return 'completed';
        }

        if ($stage === $resumeStage) {
            return 'in_progress';
        }

        if ($stage < $resumeStage) {
            return 'completed';
        }

        return 'locked';
    }

    /**
     * @param  list<array<string, mixed>>  $stages
     */
    private function progressPercent(array $stages): int
    {
        $completed = 0;
        $total = 0;

        foreach ($stages as $stage) {
            $completed += (int) $stage['completed_items'];
            $total += (int) $stage['total_items'];
        }

        if ($total === 0) {
            return 0;
        }

        return (int) round(($completed / $total) * 100);
    }

    private function stageOneComplete(Customer $customer): bool
    {
        return (filled($customer->phone_model_id) || filled($customer->device_specs))
            && filled($customer->imei_number)
            && ! is_null($customer->cash_price)
            && ! is_null($customer->deposit_amount)
            && $this->loanTermsCaptured($customer);
    }

    private function stageTwoComplete(Customer $customer): bool
    {
        return $this->identityCaptured($customer)
            && $this->identityPhotosCaptured($customer)
            && $this->contactCaptured($customer)
            && ! is_null($customer->monthly_income)
            && $this->nextOfKinCaptured($customer)
            && $this->consentCaptured($customer);
    }

    private function stageThreeComplete(Customer $customer): bool
    {
        return $customer->hasSuccessfulDepositPayment()
            && $customer->hasAcceptedAgreement()
            && $customer->hasCapturedSignatures()
            && $customer->hasAssetHandoverRecord();
    }

    private function loanTermsCaptured(Customer $customer): bool
    {
        return filled($customer->preferred_repayment)
            && ! is_null($customer->loan_interest_rate)
            && filled($customer->loan_interest_type)
            && ! is_null($customer->loan_duration_weeks)
            && ! is_null($customer->loan_grace_period_days);
    }

    private function identityCaptured(Customer $customer): bool
    {
        return filled($customer->first_name)
            && $customer->first_name !== '_draft_'
            && filled($customer->last_name)
            && $customer->last_name !== '_draft_'
            && filled($customer->gender)
            && filled($customer->nida_number)
            && filled($customer->id_type);
    }

    private function identityPhotosCaptured(Customer $customer): bool
    {
        return filled($customer->id_front_photo_path)
            && filled($customer->id_back_photo_path)
            && filled($customer->headshot_photo_path);
    }

    private function contactCaptured(Customer $customer): bool
    {
        return filled($customer->phone)
            && ! str_starts_with((string) $customer->phone, '_draft_')
            && filled($customer->branch_id);
    }

    private function nextOfKinCaptured(Customer $customer): bool
    {
        return filled($customer->nok_name)
            && filled($customer->nok_phone)
            && filled($customer->nok_relationship);
    }

    private function consentCaptured(Customer $customer): bool
    {
        return $customer->terms_accepted === true
            && $customer->data_consent_accepted === true
            && $customer->call_consent_accepted === true;
    }

    private function depositCompleted(Customer $customer, ?SelcomPaymentRequest $payment): bool
    {
        return $customer->hasSuccessfulDepositPayment()
            || $payment?->isCompleted() === true;
    }
}
