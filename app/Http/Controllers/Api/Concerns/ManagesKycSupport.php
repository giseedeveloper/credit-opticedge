<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\SelcomPaymentRequest;
use App\Models\SystemDocument;
use App\Models\Verification;
use App\Services\DeviceIdentifierScanService;
use App\Services\IMEITrackingService;
use App\Services\KycAccessoryOfferService;
use App\Services\KycDeviceCatalogService;
use App\Services\KycPhoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

trait ManagesKycSupport
{
    private function normalizeContactPhones(Customer $customer, array $validated, KycPhoneService $phoneService): array
    {
        $primaryPhone = $phoneService->normalizeForField(
            'phone',
            'phone_country',
            $validated['phone'] ?? null,
            $validated['phone_country'] ?? null,
        );
        $altPhone = $phoneService->normalizeForField(
            'alt_phone',
            'alt_phone_country',
            $validated['alt_phone'] ?? null,
            $validated['alt_phone_country'] ?? $validated['phone_country'] ?? null,
            false
        );

        $errors = [];

        if (Customer::query()
            ->where('phone', $primaryPhone['e164'])
            ->whereKeyNot($customer->id)
            ->exists()) {
            $errors['phone'] = 'This phone number is already registered.';
        }

        if ($altPhone && $altPhone['e164'] === $primaryPhone['e164']) {
            $errors['alt_phone'] = 'Alternative phone must be different from the primary phone.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $phoneMetadata = $customer->phone_metadata ?? [];
        $phoneMetadata['phone'] = $primaryPhone;

        if ($altPhone) {
            $phoneMetadata['alt_phone'] = $altPhone;
        } else {
            unset($phoneMetadata['alt_phone']);
        }

        return [
            'phone' => $primaryPhone['e164'],
            'alt_phone' => $altPhone['e164'] ?? null,
            'phone_metadata' => $phoneMetadata,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *     nok_phone: string,
     *     nok2_phone: ?string,
     *     phone_metadata: array<string, mixed>
     * }
     */
    private function normalizeNokPhones(Customer $customer, array $validated, KycPhoneService $phoneService): array
    {
        $primaryPhone = $customer->phone;
        $nokPhone = $phoneService->normalizeForField(
            'nok_phone',
            'nok_phone_country',
            $validated['nok_phone'] ?? null,
            $validated['nok_phone_country'] ?? null,
        );
        $nok2Phone = $phoneService->normalizeForField(
            'nok2_phone',
            'nok2_phone_country',
            $validated['nok2_phone'] ?? null,
            $validated['nok2_phone_country'] ?? $validated['nok_phone_country'] ?? null,
            false
        );

        $errors = [];

        if ($primaryPhone && $nokPhone['e164'] === $primaryPhone) {
            $errors['nok_phone'] = 'Next of kin phone must be different from the customer phone.';
        }

        if ($nok2Phone && $nok2Phone['e164'] === $nokPhone['e164']) {
            $errors['nok2_phone'] = 'Secondary next of kin phone must be different from the primary next of kin phone.';
        }

        if ($primaryPhone && $nok2Phone && $nok2Phone['e164'] === $primaryPhone) {
            $errors['nok2_phone'] = 'Secondary next of kin phone must be different from the customer phone.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $phoneMetadata = $customer->phone_metadata ?? [];
        $phoneMetadata['nok_phone'] = $nokPhone;

        if ($nok2Phone) {
            $phoneMetadata['nok2_phone'] = $nok2Phone;
        } else {
            unset($phoneMetadata['nok2_phone']);
        }

        return [
            'nok_phone' => $nokPhone['e164'],
            'nok2_phone' => $nok2Phone['e164'] ?? null,
            'phone_metadata' => $phoneMetadata,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAccessories(array $items, KycAccessoryOfferService $accessoryOffers): array
    {
        $normalizedItems = $accessoryOffers->normalize($items);
        $errors = [];

        foreach ($normalizedItems as $index => $item) {
            if (($item['name'] ?? '') === '') {
                $errors["accessories.{$index}.name"] = 'Enter the accessory name.';
            }

            if (($item['offer_type'] ?? 'free') !== 'free' && ! isset($item['unit_price'])) {
                $errors["accessories.{$index}.unit_price"] = 'Enter the selling or discounted price for this accessory.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalizedItems;
    }

    private function activeAgreementDocument(): ?SystemDocument
    {
        return SystemDocument::query()
            ->where('key', 'kyc_customer_agreement')
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    private function latestDraftPaymentFor(Customer $customer): ?SelcomPaymentRequest
    {
        return SelcomPaymentRequest::query()
            ->when($customer->application_draft_reference, function ($query) use ($customer): void {
                $query->where('draft_reference', $customer->application_draft_reference);
            }, function ($query) use ($customer): void {
                $query->where('customer_id', $customer->id);
            })
            ->latest('paid_at')
            ->latest()
            ->first();
    }

    private function handleSelcomException(InvalidArgumentException $exception, string $customerId): JsonResponse
    {
        $message = $exception->getMessage();
        $status = str_contains(strtolower($message), 'not configured') ? 503 : 422;

        Log::warning('Selcom checkout request failed.', [
            'customer_id' => $customerId,
            'message' => $message,
        ]);

        return $this->errorResponse($message, $status);
    }

    private function storeSignatureDataUrl(?string $dataUrl, string $directory): ?string
    {
        if (! is_string($dataUrl) || $dataUrl === '') {
            return null;
        }

        if (! preg_match('/^data:image\/png;base64,(.+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'signature' => 'Signature format is invalid. Please sign again.',
            ]);
        }

        $binary = base64_decode($matches[1], true);

        if ($binary === false) {
            throw ValidationException::withMessages([
                'signature' => 'Signature image could not be decoded. Please sign again.',
            ]);
        }

        $path = "kyc/{$directory}/".Str::uuid().'.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializePaymentSummary(?SelcomPaymentRequest $payment): ?array
    {
        if (! $payment) {
            return null;
        }

        return [
            'id' => $payment->id,
            'status' => $payment->status,
            'payment_status' => $payment->payment_status,
            'result' => $payment->result,
            'resultcode' => $payment->resultcode,
            'amount' => $payment->amount,
            'phone' => $payment->phone,
            'reference' => $payment->selcom_reference ?: $payment->transid,
            'order_id' => $payment->order_id,
            'transid' => $payment->transid,
            'payment_gateway_url' => $payment->payment_gateway_url,
            'paid_at' => $payment->paid_at?->toDateTimeString(),
            'updated_at' => $payment->updated_at?->toDateTimeString(),
            'is_completed' => $payment->isCompleted(),
            'is_failed' => $payment->status === 'failed',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAgreementSummary(?SystemDocument $document, Customer $customer): array
    {
        return [
            'active_document' => $document ? [
                'id' => $document->id,
                'title' => $document->title,
                'mime_type' => $document->mime_type,
                'url' => Storage::disk($document->disk)->url($document->path),
                'uploaded_at' => $document->created_at?->toDateTimeString(),
                'original_name' => $document->metadata['original_name'] ?? $document->title,
            ] : null,
            'accepted' => $customer->agreement_accepted,
            'presented_at' => $customer->agreement_presented_at?->toDateTimeString(),
            'decision_at' => $customer->agreement_decision_at?->toDateTimeString(),
            'customer_signature_url' => $this->photoUrl($customer->customer_signature_path),
            'fo_signature_url' => $this->photoUrl($customer->fo_signature_path),
            'handover_list_url' => $this->photoUrl($customer->asset_handover_list_path),
            'handover_notes' => $customer->asset_handover_notes,
        ];
    }

    /**
     * FO list/detail badge: use real `customers.kyc_status` once a verification exists;
     * before submission the mobile UI historically expected `draft`.
     */
    private function displayKycStatusForFo(Customer $customer, ?Verification $kycVerification): string
    {
        if ($customer->verifications->isEmpty()) {
            return 'draft';
        }

        $fromCustomer = (string) ($customer->kyc_status ?? '');
        if ($fromCustomer !== '') {
            return $fromCustomer;
        }

        return $kycVerification?->status ?? 'draft';
    }

    /**
     * Align `customers.kyc_status` with the latest type=kyc verification row when the
     * verification is already approved — fixes FO badges vs release eligibility drift.
     */
    private function syncCustomerKycStatusFromVerification(Customer $customer): void
    {
        $customer->loadMissing('latestKycVerification');
        $verification = $customer->latestKycVerification;

        if (! $verification) {
            return;
        }

        $vStatus = (string) $verification->status;

        if (in_array($vStatus, ['approved', 'verified'], true) && ! $customer->hasApprovedKyc()) {
            $customer->forceFill([
                'kyc_status' => $vStatus === 'verified' ? 'verified' : 'approved',
            ])->saveQuietly();
        }
    }

    /**
     * Human-readable reasons the FO cannot release yet (empty when ready or already released).
     *
     * @return list<string>
     */
    private function releaseEligibilityBlockers(Customer $customer): array
    {
        if ($customer->isAssetReleased()) {
            return [];
        }

        $blockers = [];

        if (! $customer->hasApprovedKyc()) {
            $blockers[] = 'KYC must be approved before release.';
        }

        if (! $customer->hasSuccessfulDepositPayment()) {
            $blockers[] = 'Deposit payment must be completed.';
        }

        if (! $customer->hasAcceptedAgreement()) {
            $blockers[] = 'Customer must accept the agreement.';
        }

        if (! $customer->hasCapturedSignatures()) {
            $blockers[] = 'Customer and FO signatures are required.';
        }

        if (! $customer->hasAssetHandoverRecord()) {
            $blockers[] = 'Handover checklist upload is required.';
        }

        if (! filled($customer->agreement_document_id)) {
            $blockers[] = 'Agreement document is not linked to this application.';
        }

        if (! filled($customer->inventory_unit_id)) {
            $blockers[] = 'No inventory unit is linked.';
        }

        return $blockers;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReleaseSummary(Customer $customer): array
    {
        return [
            'status' => $customer->asset_release_status,
            'released_at' => $customer->asset_released_at?->toDateTimeString(),
            'released_by' => $customer->assetReleasedBy?->name,
            'can_release_asset' => $customer->isReadyForAssetRelease(),
            'eligibility_blockers' => $this->releaseEligibilityBlockers($customer),
            'inventory_unit_id' => $customer->inventory_unit_id,
            'inventory_unit_status' => $customer->inventoryUnit?->status,
            'loan_terms' => $this->serializeLoanTerms($customer),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *     interest_rate: float,
     *     interest_type: string,
     *     duration_weeks: int,
     *     repayment_frequency: string,
     *     grace_period_days: int,
     *     source: string
     * }
     */
    private function resolvedLoanTermsSnapshot(array $validated): array
    {
        return [
            'interest_rate' => round((float) $validated['loan_interest_rate'], 2),
            'interest_type' => (string) $validated['loan_interest_type'],
            'duration_weeks' => max(1, (int) $validated['loan_duration_weeks']),
            'repayment_frequency' => (string) $validated['preferred_repayment'],
            'grace_period_days' => max(0, (int) $validated['loan_grace_period_days']),
            'source' => 'kyc_capture',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLoanTerms(Customer $customer): array
    {
        return [
            'interest_rate' => $customer->loan_interest_rate,
            'interest_type' => $customer->loan_interest_type,
            'duration_weeks' => $customer->loan_duration_weeks,
            'grace_period_days' => $customer->loan_grace_period_days,
            'repayment_frequency' => $customer->preferred_repayment,
            'source' => $this->loanTermsSource($customer),
        ];
    }

    private function loanTermsSource(Customer $customer): string
    {
        $metadata = is_array($customer->metadata) ? $customer->metadata : [];
        $storedTerms = is_array($metadata['loan_terms'] ?? null) ? $metadata['loan_terms'] : [];

        return (string) ($storedTerms['source'] ?? 'kyc_capture');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLoanSummary(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'status' => $loan->status,
            'principal_amount' => $loan->principal_amount,
            'deposit_paid' => $loan->deposit_paid,
            'interest_rate' => $loan->interest_rate,
            'interest_type' => $loan->interest_type,
            'total_debt' => $loan->total_debt,
            'total_payable' => $loan->total_payable,
            'remaining_balance' => $loan->remaining_balance,
            'repayment_frequency' => $loan->repayment_frequency,
            'duration_weeks' => $loan->duration_weeks,
            'disbursed_at' => $loan->disbursed_at?->toDateString(),
            'due_date' => $loan->due_date?->toDateString(),
        ];
    }

    private function photoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return URL::temporarySignedRoute(
            'api.kyc.public-media',
            now()->addMinutes(15),
            ['path' => $path]
        );
    }

    private function hasPassedFaceMatch(Customer $customer): bool
    {
        $customer->loadMissing(['latestKycVerification', 'latestVerification']);

        $status = $customer->latestKycVerification?->face_match_status
            ?? $customer->latestVerification?->face_match_status;

        return in_array((string) $status, ['passed', 'manual_verified'], true);
    }

    private function findAgentCustomerOrFail(string $customerId, array $with = []): Customer
    {
        $query = Customer::with($with);

        if (! auth()->user()?->isAdmin()) {
            $query->where('registered_by', auth()->id());
        }

        $customer = $query->findOrFail($customerId);

        if (
            ! auth()->user()?->isAdmin()
            && $customer->registered_by === auth()->id()
            && $customer->isAssetReleased()
        ) {
            abort(403, 'Customer details are locked after approval/release. View summary only.');
        }

        return $customer;
    }

    private function storeFile(Request $request, string $field, string $directory): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return $request->file($field)->store("kyc/{$directory}", 'public');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *     phone_model_id: ?string,
     *     inventory_unit_id: ?string,
     *     device_specs: string,
     *     imei_number: string,
     *     imei_2: ?string,
     *     serial_number: ?string,
     *     cash_price: string|float|int,
     *     device_scan_metadata: array<string, mixed>|null
     * }
     */
    private function resolveDeviceSelection(
        Request $request,
        array $validated,
        KycDeviceCatalogService $catalog,
        DeviceIdentifierScanService $scanService,
        IMEITrackingService $imeiTracking
    ): array {
        $user = $request->user();
        $phoneModelId = $validated['phone_model_id'] ?? null;
        $inventoryUnitId = $validated['inventory_unit_id'] ?? null;

        $phoneModel = $catalog->accessibleModel($user, $phoneModelId);
        $inventoryUnit = $catalog->accessibleUnit($user, $inventoryUnitId);
        $scanMetadata = isset($validated['device_scan'])
            ? $scanService->parseClientPayload($validated['device_scan'])
            : null;

        if ($phoneModel && isset($validated['brand_id']) && (string) $phoneModel->brand_id !== (string) $validated['brand_id']) {
            throw ValidationException::withMessages([
                'brand_id' => 'The selected model does not belong to the provided brand.',
            ]);
        }

        // In the 7-step flow, selecting an inventory/stock unit is optional.
        // If an implementation chooses to link stock later (e.g., at release),
        // we should not block Step 1 when stock exists.

        if ($inventoryUnit && $phoneModel && (string) $inventoryUnit->phone_model_id !== (string) $phoneModel->id) {
            throw ValidationException::withMessages([
                'inventory_unit_id' => 'The selected stock unit does not belong to the chosen model.',
            ]);
        }

        if (! $phoneModel && empty($validated['device_specs'])) {
            throw ValidationException::withMessages([
                'device_specs' => 'Provide the device description or choose a linked phone model.',
            ]);
        }

        $cashPrice = $validated['cash_price'] ?? $phoneModel?->retail_price;

        if ((float) $validated['deposit_amount'] > (float) $cashPrice) {
            throw ValidationException::withMessages([
                'deposit_amount' => 'Deposit cannot be greater than the device cash price.',
            ]);
        }

        $manualImei = strtoupper(trim((string) ($validated['imei_number'] ?? '')));
        $manualImei2 = isset($validated['imei_2']) ? strtoupper(trim($validated['imei_2'])) : null;
        $manualSerial = isset($validated['serial_number']) ? strtoupper(trim($validated['serial_number'])) : null;
        $scannedImei = $scanMetadata['selected_imei'] ?? null;
        $scannedSerial = $scanMetadata['selected_serial'] ?? null;

        if ($inventoryUnit) {
            $expectedImeis = collect([$inventoryUnit->imei_1, $inventoryUnit->imei_2])->filter();
            $inventorySerial = $inventoryUnit->serial_number ? strtoupper($inventoryUnit->serial_number) : null;

            if ($manualImei !== '' && ! $expectedImeis->contains($manualImei)) {
                throw ValidationException::withMessages([
                    'imei_number' => 'Typed IMEI does not match the selected stock unit.',
                ]);
            }

            if ($scannedImei && ! $expectedImeis->contains($scannedImei)) {
                throw ValidationException::withMessages([
                    'device_scan' => 'Scanned IMEI does not match the selected stock unit.',
                ]);
            }

            if ($manualSerial && $inventorySerial && $manualSerial !== $inventorySerial) {
                throw ValidationException::withMessages([
                    'serial_number' => 'Typed serial number does not match the selected stock unit.',
                ]);
            }

            if ($scannedSerial && $inventorySerial && strtoupper($scannedSerial) !== $inventorySerial) {
                throw ValidationException::withMessages([
                    'device_scan' => 'Scanned serial number does not match the selected stock unit.',
                ]);
            }
        }

        if (! $inventoryUnit && $manualImei !== '' && $scannedImei && $manualImei !== $scannedImei) {
            throw ValidationException::withMessages([
                'device_scan' => 'Scanned IMEI does not match the typed IMEI.',
            ]);
        }

        if (! $inventoryUnit && $manualSerial && $scannedSerial && strtoupper($scannedSerial) !== $manualSerial) {
            throw ValidationException::withMessages([
                'device_scan' => 'Scanned serial number does not match the typed serial number.',
            ]);
        }

        $imeiNumber = $inventoryUnit?->imei_1 ?? ($manualImei !== '' ? $manualImei : $scannedImei);
        $imei2 = $inventoryUnit?->imei_2 ?? ($manualImei2 ?: null);
        $serialNumber = $inventoryUnit?->serial_number ?? ($manualSerial ?: $scannedSerial);

        if (! $inventoryUnit && ! $imeiNumber) {
            throw ValidationException::withMessages([
                'imei_number' => 'Provide the IMEI manually or upload a scan that captures it clearly.',
            ]);
        }

        if (! $inventoryUnit) {
            try {
                $imeiTracking->assertImeiUnique($imeiNumber, $imei2);
            } catch (ValidationException $exception) {
                $mappedErrors = collect($exception->errors())
                    ->mapWithKeys(function (array $messages, string $key): array {
                        return [match ($key) {
                            'imei_1' => 'imei_number',
                            'imei_2' => 'imei_2',
                            default => $key,
                        } => $messages];
                    })
                    ->all();

                throw ValidationException::withMessages($mappedErrors);
            }
        }

        return [
            'phone_model_id' => $phoneModel?->id,
            'inventory_unit_id' => $inventoryUnit?->id,
            'dealer_id' => $inventoryUnit?->dealer_id,
            'device_specs' => $phoneModel ? $catalog->buildDeviceSpecs($phoneModel) : trim((string) $validated['device_specs']),
            'imei_number' => $imeiNumber,
            'imei_2' => $imei2 ?: null,
            'serial_number' => $serialNumber ?: null,
            'cash_price' => $cashPrice,
            'device_scan_metadata' => $scanMetadata,
        ];
    }

    private function determineResumeStep(Customer $customer): int
    {
        if (
            $customer->agreement_accepted
            || filled($customer->customer_signature_path)
            || filled($customer->fo_signature_path)
            || filled($customer->asset_handover_list_path)
            || filled($customer->fo_notes)
            || filled($customer->application_source)
        ) {
            return 7;
        }

        if ($customer->terms_accepted && $customer->data_consent_accepted && $customer->call_consent_accepted) {
            return 7;
        }

        if (filled($customer->nok_name) && filled($customer->nok_phone) && filled($customer->nok_relationship)) {
            return 6;
        }

        if (! is_null($customer->monthly_income)) {
            return 5;
        }

        if (
            filled($customer->phone)
            && ! str_starts_with($customer->phone, '_draft_')
            && filled($customer->dealer_id)
        ) {
            return 4;
        }

        if (
            filled($customer->nida_number)
            && $customer->first_name !== '_draft_'
            && $customer->last_name !== '_draft_'
        ) {
            return 3;
        }

        return 2;
    }
}
