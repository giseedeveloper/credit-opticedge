<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Http\Requests\Api\Kyc\KycPaymentRequest;
use App\Http\Requests\Api\Kyc\Step1DeviceRequest;
use App\Http\Requests\Api\Kyc\Step2IdentityRequest;
use App\Http\Requests\Api\Kyc\Step3ContactRequest;
use App\Http\Requests\Api\Kyc\Step4IncomeRequest;
use App\Http\Requests\Api\Kyc\Step5NokRequest;
use App\Http\Requests\Api\Kyc\Step6ConsentRequest;
use App\Http\Requests\Api\Kyc\Step7SubmitRequest;
use App\Models\Customer;
use App\Models\Verification;
use App\Services\ApplicationAutoCheckService;
use App\Services\CustomerLoanProvisioningService;
use App\Services\DeviceIdentifierScanService;
use App\Services\FaceMatchCoordinator;
use App\Services\IMEITrackingService;
use App\Services\KycAccessoryOfferService;
use App\Services\KycDeviceCatalogService;
use App\Services\KycPhoneService;
use App\Services\KycStageFlowService;
use App\Services\SelcomCheckoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

trait ManagesKycWizard
{
    use ApiResponse;

    public function step1Device(
        Step1DeviceRequest $request,
        KycDeviceCatalogService $catalog,
        DeviceIdentifierScanService $scanService,
        IMEITrackingService $imeiTracking,
        KycAccessoryOfferService $accessoryOffers,
        CustomerLoanProvisioningService $loanProvisioning,
        KycStageFlowService $stageFlow
    ): JsonResponse {
        $validated = $request->validated();

        $normalizedAccessories = $this->normalizeAccessories($validated['accessories'] ?? [], $accessoryOffers);
        $scopeContext = $catalog->scopeContextFor($request->user());
        $recommendedTerms = $loanProvisioning->defaultTerms(
            $request->string('preferred_repayment')->toString() ?: null
        );

        $validated['loan_interest_rate'] = $validated['loan_interest_rate'] ?? ($recommendedTerms['interest_rate'] ?? 0);
        $validated['loan_interest_type'] = $validated['loan_interest_type'] ?? ($recommendedTerms['interest_type'] ?? 'flat');
        $validated['loan_duration_weeks'] = $validated['loan_duration_weeks'] ?? ($recommendedTerms['duration_weeks'] ?? 52);
        $validated['loan_grace_period_days'] = $validated['loan_grace_period_days'] ?? ($recommendedTerms['grace_period_days'] ?? 0);

        $loanTerms = $this->resolvedLoanTermsSnapshot($validated);

        $deviceSelection = $this->resolveDeviceSelection(
            $request,
            $validated,
            $catalog,
            $scanService,
            $imeiTracking
        );

        $existingCustomerId = $validated['customer_id'] ?? null;

        if ($existingCustomerId) {
            $draft = $this->findAgentCustomerOrFail((string) $existingCustomerId);

            if ($draft->isAssetReleased()) {
                throw ValidationException::withMessages([
                    'customer_id' => 'This application cannot be edited after the asset has been released.',
                ]);
            }

            if ($draft->hasApprovedKyc()) {
                throw ValidationException::withMessages([
                    'customer_id' => 'This application is already approved; device details cannot be changed here.',
                ]);
            }

            $imeiPhotoPath = $this->storeFile($request, 'imei_photo', 'imei');
            $deviceBoxPhotoPath = $this->storeFile($request, 'device_box_photo', 'device_box');
            $devicePhotoPath = $this->storeFile($request, 'device_photo', 'device');

            $metadata = is_array($draft->metadata) ? $draft->metadata : [];
            $metadata['loan_terms'] = $loanTerms;

            $draft->update([
                'dealer_id' => $deviceSelection['dealer_id'] ?? $draft->dealer_id ?? $scopeContext['dealer_id'],
                'phone_model_id' => $deviceSelection['phone_model_id'],
                'inventory_unit_id' => $deviceSelection['inventory_unit_id'],
                'device_specs' => $deviceSelection['device_specs'],
                'imei_number' => $deviceSelection['imei_number'],
                'imei_2' => $deviceSelection['imei_2'],
                'serial_number' => $deviceSelection['serial_number'],
                'cash_price' => $deviceSelection['cash_price'],
                'deposit_amount' => $deviceSelection['deposit_amount'],
                'preferred_repayment' => $validated['preferred_repayment'],
                'loan_interest_rate' => $loanTerms['interest_rate'],
                'loan_interest_type' => $loanTerms['interest_type'],
                'loan_duration_weeks' => $loanTerms['duration_weeks'],
                'loan_grace_period_days' => $loanTerms['grace_period_days'],
                'imei_photo_path' => $imeiPhotoPath ?? $draft->imei_photo_path,
                'device_box_photo_path' => $deviceBoxPhotoPath ?? $draft->device_box_photo_path,
                'device_photo_path' => $devicePhotoPath,
                'device_scan_metadata' => $deviceSelection['device_scan_metadata'],
                'device_accessories' => $normalizedAccessories !== [] ? $normalizedAccessories : null,
                'store_offer_notes' => $validated['store_offer_notes'] ?? null,
                'metadata' => $metadata,
            ]);

            $draft = $draft->fresh();

            return $this->successResponse([
                'customer_id' => $draft->id,
                'step' => 1,
                'stage' => 1,
                'next_stage' => 2,
                'flow' => $stageFlow->summary($draft),
            ], 'Stage 1 (Device & Offer) updated. Proceed to Customer & Verification.');
        }

        $draft = Customer::create([
            'registered_by' => auth()->id(),
            'application_draft_reference' => (string) Str::uuid(),
            'dealer_id' => $deviceSelection['dealer_id'] ?? $scopeContext['dealer_id'],
            'phone_model_id' => $deviceSelection['phone_model_id'],
            'inventory_unit_id' => $deviceSelection['inventory_unit_id'],
            'device_specs' => $deviceSelection['device_specs'],
            'imei_number' => $deviceSelection['imei_number'],
            'imei_2' => $deviceSelection['imei_2'],
            'serial_number' => $deviceSelection['serial_number'],
            'cash_price' => $deviceSelection['cash_price'],
            'deposit_amount' => $deviceSelection['deposit_amount'],
            'preferred_repayment' => $validated['preferred_repayment'],
            'loan_interest_rate' => $loanTerms['interest_rate'],
            'loan_interest_type' => $loanTerms['interest_type'],
            'loan_duration_weeks' => $loanTerms['duration_weeks'],
            'loan_grace_period_days' => $loanTerms['grace_period_days'],
            'imei_photo_path' => $this->storeFile($request, 'imei_photo', 'imei'),
            'device_box_photo_path' => $this->storeFile($request, 'device_box_photo', 'device_box'),
            'device_photo_path' => $this->storeFile($request, 'device_photo', 'device'),
            'device_scan_metadata' => $deviceSelection['device_scan_metadata'],
            'device_accessories' => $normalizedAccessories !== [] ? $normalizedAccessories : null,
            'store_offer_notes' => $validated['store_offer_notes'] ?? null,
            'metadata' => ['loan_terms' => $loanTerms],
            // Required placeholders so model is saveable
            'first_name' => '_draft_',
            'last_name' => '_draft_',
            'phone' => '_draft_'.uniqid(),
            'kyc_status' => 'pending',
            'kyc_stage' => 1,
        ]);

        return $this->successResponse([
            'customer_id' => $draft->id,
            'step' => 1,
            'stage' => 1,
            'next_stage' => 2,
            'flow' => $stageFlow->summary($draft),
        ], 'Stage 1 (Device & Offer) saved. Proceed to Customer & Verification.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 2 — Customer Identity
    // POST /api/v1/kyc/application/{customer_id}/step2
    // ──────────────────────────────────────────────────────────────
    public function step2Identity(Step2IdentityRequest $request, string $customerId): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validated();

        $missingPhotoErrors = [];

        if (! $request->hasFile('id_front_photo') && ! $customer->id_front_photo_path) {
            $missingPhotoErrors['id_front_photo'] = 'ID front photo is required.';
        }

        if (! $request->hasFile('id_back_photo') && ! $customer->id_back_photo_path) {
            $missingPhotoErrors['id_back_photo'] = 'ID back photo is required.';
        }

        if (! $request->hasFile('headshot_photo') && ! $customer->headshot_photo_path) {
            $missingPhotoErrors['headshot_photo'] = 'Headshot photo is required.';
        }

        if ($missingPhotoErrors !== []) {
            throw ValidationException::withMessages($missingPhotoErrors);
        }

        $customer->update([
            'first_name' => ucfirst(strtolower(trim($validated['first_name']))),
            'middle_name' => isset($validated['middle_name']) ? ucfirst(strtolower(trim($validated['middle_name']))) : null,
            'last_name' => ucfirst(strtolower(trim($validated['last_name']))),
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'nida_number' => strtoupper(trim($validated['nida_number'])),
            'id_type' => $validated['id_type'],
            'id_front_photo_path' => $this->storeFile($request, 'id_front_photo', 'id_front') ?? $customer->id_front_photo_path,
            'id_back_photo_path' => $this->storeFile($request, 'id_back_photo', 'id_back') ?? $customer->id_back_photo_path,
            'headshot_photo_path' => $this->storeFile($request, 'headshot_photo', 'headshot') ?? $customer->headshot_photo_path,
            'client_fo_photo_path' => $this->storeFile($request, 'client_fo_photo', 'client_fo') ?? $customer->client_fo_photo_path,
        ]);

        $verification = Verification::firstOrCreate(
            ['customer_id' => $customer->id, 'type' => 'kyc'],
            ['status' => 'pending', 'stage' => 2],
        );

        if ($verification->face_match_status !== 'manual_verified') {
            $verification->update([
                'face_match_status' => 'pending',
                'face_match_reason' => null,
            ]);

            app(FaceMatchCoordinator::class)->queueAsyncIfNeeded($verification);
        }

        return $this->successResponse(['customer_id' => $customer->id, 'step' => 2], 'Step 2 (Identity) saved.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 3 — Contact & Location
    // POST /api/v1/kyc/application/{customer_id}/step3
    // ──────────────────────────────────────────────────────────────
    public function step3Contact(Step3ContactRequest $request, string $customerId, KycPhoneService $phoneService): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId, ['dealer']);

        $validated = $request->validated();

        $contactPhones = $this->normalizeContactPhones($customer, $validated, $phoneService);

        $customer->update([
            'phone' => $contactPhones['phone'],
            'alt_phone' => $contactPhones['alt_phone'],
            'phone_metadata' => $contactPhones['phone_metadata'],
            'email' => isset($validated['email']) ? strtolower(trim($validated['email'])) : null,
            'address' => $validated['address'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'region' => $validated['region'] ?? null,
            'district' => $validated['district'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return $this->successResponse(['customer_id' => $customer->id, 'step' => 3], 'Step 3 (Contact) saved.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 4 — Income & Work
    // POST /api/v1/kyc/application/{customer_id}/step4
    // ──────────────────────────────────────────────────────────────
    public function step4Income(Step4IncomeRequest $request, string $customerId): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validated();

        $metadata = is_array($customer->metadata) ? $customer->metadata : [];
        $metadata['is_pep'] = (bool) ($validated['is_pep'] ?? false);

        $customer->update([
            'occupation' => $validated['occupation'] ?? null,
            'employer' => $validated['employer'] ?? null,
            'work_location' => $validated['work_location'] ?? null,
            'monthly_income' => $validated['monthly_income'],
            'monthly_expenses' => $validated['monthly_expenses'] ?? null,
            'income_payment_cycle' => $validated['income_payment_cycle'] ?? null,
            'duration_at_work' => $validated['duration_at_work'] ?? null,
            'business_photo_path' => $this->storeFile($request, 'business_photo', 'business'),
            'metadata' => $metadata,
        ]);

        return $this->successResponse(['customer_id' => $customer->id, 'step' => 4], 'Step 4 (Income) saved.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 5 — Next of Kin
    // POST /api/v1/kyc/application/{customer_id}/step5
    // ──────────────────────────────────────────────────────────────
    public function step5Nok(Step5NokRequest $request, string $customerId, KycPhoneService $phoneService): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validated();

        $nokPhones = $this->normalizeNokPhones($customer, $validated, $phoneService);

        $customer->update([
            'nok_name' => trim($validated['nok_name']),
            'nok_phone' => $nokPhones['nok_phone'],
            'nok_relationship' => $validated['nok_relationship'],
            'nok2_name' => isset($validated['nok2_name']) ? trim($validated['nok2_name']) : null,
            'nok2_phone' => $nokPhones['nok2_phone'],
            'nok2_relationship' => $validated['nok2_relationship'] ?? null,
            'phone_metadata' => $nokPhones['phone_metadata'],
        ]);

        return $this->successResponse(['customer_id' => $customer->id, 'step' => 5], 'Step 5 (NOK) saved.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 6 — Consent
    // POST /api/v1/kyc/application/{customer_id}/step6
    // ──────────────────────────────────────────────────────────────
    public function step6Consent(Step6ConsentRequest $request, string $customerId): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $request->validated();

        $customer->update([
            'terms_accepted' => true,
            'data_consent_accepted' => true,
            'call_consent_accepted' => true,
            'consent_timestamp' => now(),
        ]);

        return $this->successResponse(['customer_id' => $customer->id, 'step' => 6], 'Step 6 (Consent) recorded.');
    }

    public function stage2CustomerVerification(
        Request $request,
        string $customerId,
        KycPhoneService $phoneService,
        KycStageFlowService $stageFlow
    ): JsonResponse {
        $customer = $this->findAgentCustomerOrFail($customerId, ['dealer']);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:60'],
            'middle_name' => ['nullable', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'min:2', 'max:60'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nida_number' => ['required', 'string', 'size:20', 'unique:customers,nida_number,'.$customer->id],
            'id_type' => ['required', 'in:nida,passport,driving_license,voter_card'],
            'id_front_photo' => ['nullable', 'image', 'max:5120'],
            'id_back_photo' => ['nullable', 'image', 'max:5120'],
            'headshot_photo' => ['nullable', 'image', 'max:5120'],
            'client_fo_photo' => ['nullable', 'image', 'max:5120'],
            'phone' => ['required', 'string', 'min:7', 'max:20'],
            'phone_country' => ['required', 'string'],
            'alt_phone' => ['nullable', 'string', 'min:7', 'max:20'],
            'alt_phone_country' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'employer' => ['nullable', 'string', 'max:100'],
            'work_location' => ['nullable', 'string', 'max:200'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'monthly_expenses' => ['nullable', 'numeric', 'min:0'],
            'income_payment_cycle' => ['nullable', 'in:weekly,biweekly,monthly,irregular'],
            'duration_at_work' => ['nullable', 'string', 'max:60'],
            'business_photo' => ['nullable', 'image', 'max:5120'],
            'nok_name' => ['required', 'string', 'min:2', 'max:100'],
            'nok_phone' => ['required', 'string', 'min:7', 'max:20'],
            'nok_phone_country' => ['required', 'string'],
            'nok_relationship' => ['required', 'string', 'max:60'],
            'nok2_name' => ['nullable', 'string', 'max:100'],
            'nok2_phone' => ['nullable', 'string', 'min:7', 'max:20'],
            'nok2_phone_country' => ['nullable', 'string'],
            'nok2_relationship' => ['nullable', 'string', 'max:60'],
            'terms_accepted' => ['required', 'accepted'],
            'data_consent_accepted' => ['required', 'accepted'],
            'call_consent_accepted' => ['required', 'accepted'],
        ]);

        $missingPhotoErrors = [];

        if (! $request->hasFile('id_front_photo') && ! $customer->id_front_photo_path) {
            $missingPhotoErrors['id_front_photo'] = 'ID front photo is required.';
        }

        if (! $request->hasFile('id_back_photo') && ! $customer->id_back_photo_path) {
            $missingPhotoErrors['id_back_photo'] = 'ID back photo is required.';
        }

        if (! $request->hasFile('headshot_photo') && ! $customer->headshot_photo_path) {
            $missingPhotoErrors['headshot_photo'] = 'Headshot photo is required.';
        }

        if ($missingPhotoErrors !== []) {
            throw ValidationException::withMessages($missingPhotoErrors);
        }

        $contactPhones = $this->normalizeContactPhones($customer, $validated, $phoneService);

        $customer->forceFill([
            'phone' => $contactPhones['phone'],
            'phone_metadata' => $contactPhones['phone_metadata'],
        ]);

        $nokPhones = $this->normalizeNokPhones($customer, $validated, $phoneService);

        $idFrontPhotoPath = $this->storeFile($request, 'id_front_photo', 'id_front') ?? $customer->id_front_photo_path;
        $idBackPhotoPath = $this->storeFile($request, 'id_back_photo', 'id_back') ?? $customer->id_back_photo_path;
        $headshotPhotoPath = $this->storeFile($request, 'headshot_photo', 'headshot') ?? $customer->headshot_photo_path;
        $clientFoPhotoPath = $this->storeFile($request, 'client_fo_photo', 'client_fo') ?? $customer->client_fo_photo_path;
        $businessPhotoPath = $this->storeFile($request, 'business_photo', 'business') ?? $customer->business_photo_path;

        DB::transaction(function () use (
            $customer,
            $validated,
            $contactPhones,
            $nokPhones,
            $idFrontPhotoPath,
            $idBackPhotoPath,
            $headshotPhotoPath,
            $clientFoPhotoPath,
            $businessPhotoPath
        ): void {
            $customer->update([
                'first_name' => ucfirst(strtolower(trim($validated['first_name']))),
                'middle_name' => isset($validated['middle_name']) ? ucfirst(strtolower(trim($validated['middle_name']))) : null,
                'last_name' => ucfirst(strtolower(trim($validated['last_name']))),
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'nida_number' => strtoupper(trim($validated['nida_number'])),
                'id_type' => $validated['id_type'],
                'id_front_photo_path' => $idFrontPhotoPath,
                'id_back_photo_path' => $idBackPhotoPath,
                'headshot_photo_path' => $headshotPhotoPath,
                'client_fo_photo_path' => $clientFoPhotoPath,
                'phone' => $contactPhones['phone'],
                'alt_phone' => $contactPhones['alt_phone'],
                'phone_metadata' => $nokPhones['phone_metadata'],
                'email' => isset($validated['email']) ? strtolower(trim($validated['email'])) : null,
                'address' => $validated['address'] ?? null,
                'landmark' => $validated['landmark'] ?? null,
                'region' => $validated['region'] ?? null,
                'district' => $validated['district'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'occupation' => $validated['occupation'] ?? null,
                'employer' => $validated['employer'] ?? null,
                'work_location' => $validated['work_location'] ?? null,
                'monthly_income' => $validated['monthly_income'],
                'monthly_expenses' => $validated['monthly_expenses'] ?? null,
                'income_payment_cycle' => $validated['income_payment_cycle'] ?? null,
                'duration_at_work' => $validated['duration_at_work'] ?? null,
                'business_photo_path' => $businessPhotoPath,
                'nok_name' => trim($validated['nok_name']),
                'nok_phone' => $nokPhones['nok_phone'],
                'nok_relationship' => $validated['nok_relationship'],
                'nok2_name' => isset($validated['nok2_name']) ? trim($validated['nok2_name']) : null,
                'nok2_phone' => $nokPhones['nok2_phone'],
                'nok2_relationship' => $validated['nok2_relationship'] ?? null,
                'terms_accepted' => true,
                'data_consent_accepted' => true,
                'call_consent_accepted' => true,
                'consent_timestamp' => now(),
                'kyc_stage' => 2,
            ]);
        });

        $customer = $customer->fresh();

        $verification = Verification::firstOrCreate(
            ['customer_id' => $customer->id, 'type' => 'kyc'],
            ['status' => 'pending', 'stage' => 2],
        );

        if ($verification->face_match_status !== 'manual_verified') {
            $verification->update([
                'face_match_status' => 'pending',
                'face_match_reason' => null,
            ]);

            app(FaceMatchCoordinator::class)->queueAsyncIfNeeded($verification);
        }

        return $this->successResponse([
            'customer_id' => $customer->id,
            'stage' => 2,
            'next_stage' => 3,
            'legacy_steps_completed' => [2, 3, 4, 5, 6],
            'flow' => $stageFlow->summary(
                $customer,
                $this->latestDraftPaymentFor($customer),
                $this->activeAgreementDocument()
            ),
        ], 'Stage 2 (Customer & Verification) saved. Proceed to Payment, Agreement & Handover.');
    }

    public function paymentRequest(
        KycPaymentRequest $request,
        string $customerId,
        KycPhoneService $phoneService,
        SelcomCheckoutService $selcom,
        KycStageFlowService $stageFlow
    ): JsonResponse {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validated();

        if ((float) ($customer->deposit_amount ?? 0) <= 0) {
            return $this->errorResponse('A valid deposit amount is required before sending a payment prompt.', 422);
        }

        if (! $customer->application_draft_reference) {
            $customer->update([
                'application_draft_reference' => (string) Str::uuid(),
            ]);
            $customer->refresh();
        }

        $paymentPhone = $phoneService->normalizeForField(
            'payment_phone',
            'payment_phone_country',
            $validated['payment_phone'],
            $validated['payment_phone_country']
        );

        $latestCompletedPayment = $selcom->latestCompletedDraftPayment($customer->application_draft_reference);

        if ($latestCompletedPayment) {
            $selcom->attachToCustomer($latestCompletedPayment, $customer);

            return $this->successResponse([
                'payment' => $this->serializePaymentSummary($latestCompletedPayment->fresh()),
                'agreement' => $this->serializeAgreementSummary($this->activeAgreementDocument(), $customer->fresh()),
                'release' => $this->serializeReleaseSummary($customer->fresh()),
                'flow' => $stageFlow->summary($customer->fresh(), $latestCompletedPayment->fresh(), $this->activeAgreementDocument()),
            ], 'This application already has a successful deposit payment.');
        }

        try {
            $payment = $selcom->createDraftPayment(
                $customer->application_draft_reference,
                $paymentPhone['e164'],
                (float) $customer->deposit_amount,
                auth()->id()
            );

            $payment = $selcom->initiateWalletPush($payment, [
                'name' => $customer->full_name !== '' ? $customer->full_name : 'OpticEdge Customer',
                'phone' => $paymentPhone['e164'],
                'email' => $customer->email ?: null,
            ], route('api.payments.selcom.webhook'));

            $payment = $selcom->attachToCustomer($payment, $customer->fresh());
        } catch (InvalidArgumentException $exception) {
            return $this->handleSelcomException($exception, $customer->id);
        }

        return $this->successResponse([
            'payment' => $this->serializePaymentSummary($payment),
            'agreement' => $this->serializeAgreementSummary($this->activeAgreementDocument(), $customer->fresh()),
            'release' => $this->serializeReleaseSummary($customer->fresh()),
            'flow' => $stageFlow->summary($customer->fresh(), $payment, $this->activeAgreementDocument()),
        ], 'Payment prompt sent successfully.');
    }

    public function paymentStatus(
        string $customerId,
        SelcomCheckoutService $selcom,
        KycStageFlowService $stageFlow
    ): JsonResponse {
        $customer = $this->findAgentCustomerOrFail($customerId);
        $payment = $this->latestDraftPaymentFor($customer);

        if (! $payment) {
            return $this->errorResponse('No payment prompt has been started for this application yet.', 404);
        }

        try {
            $payment = $selcom->syncPaymentStatusWithShortPoll($payment);
            $payment = $selcom->attachToCustomer($payment, $customer->fresh());
        } catch (InvalidArgumentException $exception) {
            return $this->handleSelcomException($exception, $customer->id);
        }

        return $this->successResponse([
            'payment' => $this->serializePaymentSummary($payment),
            'agreement' => $this->serializeAgreementSummary($this->activeAgreementDocument(), $customer->fresh()),
            'release' => $this->serializeReleaseSummary($customer->fresh()),
            'flow' => $stageFlow->summary($customer->fresh(), $payment, $this->activeAgreementDocument()),
        ], $payment->isCompleted()
            ? 'Deposit payment confirmed successfully.'
            : 'Payment status refreshed.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 7 — Submit
    // POST /api/v1/kyc/application/{customer_id}/step7
    // ──────────────────────────────────────────────────────────────
    public function step7Submit(
        Step7SubmitRequest $request,
        string $customerId,
        ApplicationAutoCheckService $checker,
        SelcomCheckoutService $selcom,
        KycStageFlowService $stageFlow
    ): JsonResponse {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validated();

        $finalStepErrors = [];

        if (! filled($validated['customer_signature'] ?? null) && ! $customer->customer_signature_path) {
            $finalStepErrors['customer_signature'] = 'Customer signature is required.';
        }

        if (! filled($validated['fo_signature'] ?? null) && ! $customer->fo_signature_path) {
            $finalStepErrors['fo_signature'] = 'FO signature is required.';
        }

        if (! $request->hasFile('etr_receipt_photo') && ! filled($customer->etr_receipt_path)) {
            $finalStepErrors['etr_receipt_photo'] = 'ETR receipt photo is required.';
        }

        if ($finalStepErrors !== []) {
            throw ValidationException::withMessages($finalStepErrors);
        }

        $missing = [];
        if (! $customer->nida_number) {
            $missing[] = 'nida_number';
        }
        if (! $customer->phone || str_starts_with($customer->phone, '_draft_')) {
            $missing[] = 'phone';
        }
        if (! $customer->nok_name) {
            $missing[] = 'nok_name';
        }
        if (! $customer->terms_accepted) {
            $missing[] = 'consent';
        }
        if (! $customer->dealer_id) {
            $missing[] = 'dealer_id';
        }
        if (! $customer->monthly_income) {
            $missing[] = 'monthly_income';
        }

        if (! empty($missing)) {
            return $this->errorResponse('Complete earlier steps before submitting. Missing: '.implode(', ', $missing), 422);
        }

        $successfulPayment = $customer->application_draft_reference
            ? $selcom->latestCompletedDraftPayment($customer->application_draft_reference)
            : null;

        if (! $successfulPayment) {
            return $this->errorResponse('A successful deposit payment is required before final submission.', 422);
        }

        if (! $this->hasPassedFaceMatch($customer)) {
            return $this->errorResponse(
                'Face verification must be passed or manually verified before final submission.',
                422
            );
        }

        if ($validated['agreement_decision'] !== 'yes') {
            return $this->errorResponse('Customer must accept the agreement before final submission.', 422);
        }

        $activeAgreement = $this->activeAgreementDocument();

        if (! $activeAgreement) {
            return $this->errorResponse('No active agreement PDF is available yet. Please contact admin.', 422);
        }

        $metadata = is_array($customer->metadata) ? $customer->metadata : [];
        if (isset($validated['loan_term_months'])) {
            $metadata['loan_term_months'] = (int) $validated['loan_term_months'];
        }
        if (isset($validated['downpayment_amount'])) {
            $metadata['downpayment_amount'] = (float) $validated['downpayment_amount'];
        }
        if (isset($validated['payment_phone'])) {
            $metadata['payment_phone'] = trim((string) $validated['payment_phone']);
        }

        $customer->update([
            'fo_notes' => $validated['fo_notes'] ?? null,
            'application_source' => $validated['application_source'] ?? null,
            'agreement_document_id' => $activeAgreement->id,
            'agreement_accepted' => true,
            'agreement_presented_at' => now(),
            'agreement_decision_at' => now(),
            'customer_signature_path' => $this->storeSignatureDataUrl($validated['customer_signature'] ?? null, 'customer-signatures') ?? $customer->customer_signature_path,
            'fo_signature_path' => $this->storeSignatureDataUrl($validated['fo_signature'] ?? null, 'fo-signatures') ?? $customer->fo_signature_path,
            'asset_handover_list_path' => $this->storeFile($request, 'asset_handover_list', 'handover') ?? $customer->asset_handover_list_path,
            'asset_handover_notes' => $validated['asset_handover_notes'] ?? null,
            'etr_receipt_path' => $this->storeFile($request, 'etr_receipt_photo', 'etr') ?? $customer->etr_receipt_path,
            'deposit_payment_status' => 'completed',
            'deposit_payment_amount' => $successfulPayment->amount,
            'deposit_payment_reference' => $successfulPayment->selcom_reference ?: $successfulPayment->transid,
            'deposit_paid_at' => $successfulPayment->paid_at,
            'asset_release_status' => 'pending',
            'kyc_status' => 'pending',
            'kyc_stage' => 3,
            'metadata' => $metadata,
            'kyc_fo_saved_as_draft_at' => null,
        ]);

        $verification = Verification::firstOrCreate(
            ['customer_id' => $customer->id, 'type' => 'kyc'],
            [
                'fo_id' => auth()->id(),
                'status' => 'pending',
                'stage' => 1,
                'stage1_status' => 'pending',
            ]
        );

        $selcom->attachToCustomer($successfulPayment, $customer->fresh());

        $result = $checker->run($customer, $verification);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['auto_check_status' => $result['status']])
            ->log("API KYC submitted for {$customer->full_name} — auto-check: {$result['status']}");

        return $this->successResponse([
            'customer_id' => $customer->id,
            'stage' => 3,
            'verification_id' => $verification->id,
            'auto_check_status' => $result['status'],
            'auto_check_results' => $result['checks'],
            'payment' => $this->serializePaymentSummary($successfulPayment->fresh()),
            'agreement' => $this->serializeAgreementSummary($activeAgreement, $customer->fresh()),
            'release' => $this->serializeReleaseSummary($customer->fresh()),
            'flow' => $stageFlow->summary($customer->fresh(), $successfulPayment->fresh(), $activeAgreement),
        ], 'Application submitted successfully.');
    }

    // ──────────────────────────────────────────────────────────────
    // GET application draft status
    // GET /api/v1/kyc/application/{customer_id}/status
    // ──────────────────────────────────────────────────────────────
    public function applicationStatus(string $customerId, KycStageFlowService $stageFlow): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId, ['latestVerification', 'agreementDocument', 'assetReleasedBy']);
        $activeAgreement = $this->activeAgreementDocument();
        $latestPayment = $this->latestDraftPaymentFor($customer);

        return $this->successResponse([
            'customer_id' => $customer->id,
            'kyc_status' => $customer->kyc_status,
            'kyc_stage' => $customer->kyc_stage,
            'auto_check_status' => $customer->latestVerification?->auto_check_status,
            'auto_check_results' => $customer->latestVerification?->auto_check_results,
            'payment' => $this->serializePaymentSummary($latestPayment),
            'agreement' => $this->serializeAgreementSummary($activeAgreement, $customer),
            'release' => $this->serializeReleaseSummary($customer),
            'flow' => $stageFlow->summary($customer, $latestPayment, $activeAgreement),
            'can_submit' => $latestPayment?->isCompleted() === true
                && $activeAgreement !== null,
        ], 'Application status retrieved.');
    }

    // ──────────────────────────────────────────────────────────────
    // FO explicit "save as draft" (Step 7 — lists under Drafts tab / dashboard)
    // POST /api/v1/kyc/application/{customer_id}/save-draft
    // ──────────────────────────────────────────────────────────────
    public function markFoSavedAsDraft(string $customerId): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

        if ($customer->verifications()->exists()) {
            return $this->errorResponse('Application already submitted.', 422);
        }

        if ($customer->first_name === '_draft_' || str_starts_with((string) $customer->phone, '_draft_')) {
            return $this->errorResponse('Complete identity steps before saving this draft.', 422);
        }

        $customer->forceFill([
            'kyc_fo_saved_as_draft_at' => now(),
        ])->save();

        return $this->successResponse([
            'customer_id' => $customer->id,
            'fo_saved_as_draft_at' => $customer->kyc_fo_saved_as_draft_at?->toDateTimeString(),
        ], 'Draft saved. You can resume from Customers.');
    }
}
