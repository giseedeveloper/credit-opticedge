<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\SelcomPaymentRequest;
use App\Models\SystemDocument;
use App\Models\Verification;
use App\Services\ApplicationAutoCheckService;
use App\Services\CustomerLoanProvisioningService;
use App\Services\DeviceIdentifierScanService;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group KYC — Agent Mobile App
 *
 * Step-by-step endpoints for the field agent mobile application.
 * Each step validates and persists its own slice of data.
 * A draft customer is created at Step 1 and enriched through Steps 2-7.
 */
class KycApiController extends Controller
{
    use ApiResponse;

    public function publicMedia(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = trim((string) $validated['path'], '/');

        abort_if(
            $path === ''
            || str_contains($path, '..')
            || str_starts_with($path, '/'),
            404
        );

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Authorization',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function phoneCountries(KycPhoneService $phoneService): JsonResponse
    {
        return $this->successResponse($phoneService->supportedCountries(), 'Phone country options retrieved.');
    }

    public function deviceBrands(Request $request, KycDeviceCatalogService $catalog): JsonResponse
    {
        $brands = $catalog->brandsFor($request->user())
            ->map(fn ($brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
            ])
            ->values();

        return $this->successResponse($brands, 'Device brands retrieved.');
    }

    public function deviceModels(
        Request $request,
        KycDeviceCatalogService $catalog,
        CustomerLoanProvisioningService $loanProvisioning
    ): JsonResponse {
        $request->validate([
            'brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
            'preferred_repayment' => ['nullable', 'in:weekly,biweekly,monthly'],
        ]);

        $recommendedTerms = $loanProvisioning->defaultTerms(
            $request->string('preferred_repayment')->toString() ?: null
        );

        $models = $catalog->modelsFor($request->user(), $request->string('brand_id')->toString() ?: null)
            ->map(fn ($model) => [
                'id' => $model->id,
                'brand_id' => $model->brand_id,
                'brand_name' => $model->brand?->name,
                'name' => $model->name,
                'retail_price' => $model->retail_price,
                'specifications' => $model->specifications,
                'device_specs' => $catalog->buildDeviceSpecs($model),
                'recommended_terms' => $recommendedTerms,
            ])
            ->values();

        return $this->successResponse($models, 'Device models retrieved.');
    }

    public function deviceInventory(
        Request $request,
        KycDeviceCatalogService $catalog,
        CustomerLoanProvisioningService $loanProvisioning
    ): JsonResponse {
        $request->validate([
            'phone_model_id' => ['nullable', 'exists:phone_models,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'preferred_repayment' => ['nullable', 'in:weekly,biweekly,monthly'],
        ]);

        $recommendedTerms = $loanProvisioning->defaultTerms(
            $request->string('preferred_repayment')->toString() ?: null
        );

        $units = $catalog->unitsFor(
            $request->user(),
            $request->string('phone_model_id')->toString() ?: null,
            trim($request->string('search')->toString())
        )->map(fn ($unit) => [
            'id' => $unit->id,
            'phone_model_id' => $unit->phone_model_id,
            'brand_name' => $unit->phoneModel?->brand?->name,
            'model_name' => $unit->phoneModel?->name,
            'device_specs' => $unit->phoneModel ? $catalog->buildDeviceSpecs($unit->phoneModel) : null,
            'recommended_cash_price' => $unit->phoneModel?->retail_price,
            'imei_1' => $unit->imei_1,
            'imei_2' => $unit->imei_2,
            'serial_number' => $unit->serial_number,
            'status' => $unit->status,
            'branch_id' => $unit->branch_id,
            'vendor_id' => $unit->vendor_id,
            'recommended_terms' => $recommendedTerms,
        ])->values();

        return $this->successResponse($units, 'Available inventory retrieved.');
    }

    public function stageFlow(KycStageFlowService $stageFlow): JsonResponse
    {
        return $this->successResponse($stageFlow->contract(), 'KYC stage flow retrieved.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 1 — Device
    // POST /api/v1/kyc/application/step1
    // ──────────────────────────────────────────────────────────────
    public function step1Device(
        Request $request,
        KycDeviceCatalogService $catalog,
        DeviceIdentifierScanService $scanService,
        IMEITrackingService $imeiTracking,
        KycAccessoryOfferService $accessoryOffers,
        CustomerLoanProvisioningService $loanProvisioning,
        KycStageFlowService $stageFlow
    ): JsonResponse {
        $validated = $request->validate([
            'brand_id' => ['nullable', 'exists:brands,id'],
            'phone_model_id' => ['nullable', 'exists:phone_models,id'],
            'inventory_unit_id' => ['nullable', 'exists:inventory_units,id'],
            'device_specs' => ['required_without:phone_model_id', 'nullable', 'string', 'min:3', 'max:200'],
            'imei_number' => ['nullable', 'string', 'digits:15'],
            'imei_2' => ['nullable', 'string', 'digits:15'],
            'serial_number' => ['nullable', 'string', 'max:60'],
            'cash_price' => ['required_without:phone_model_id', 'nullable', 'numeric', 'min:1'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'preferred_repayment' => ['required', 'in:weekly,biweekly,monthly'],
            'loan_interest_rate' => ['nullable', 'numeric', 'min:0'],
            'loan_interest_type' => ['nullable', 'in:flat,reducing_balance'],
            'loan_duration_weeks' => ['nullable', 'integer', 'min:1', 'max:260'],
            'loan_grace_period_days' => ['nullable', 'integer', 'min:0', 'max:60'],
            'imei_photo' => ['nullable', 'image', 'max:5120'],
            'device_box_photo' => ['nullable', 'image', 'max:5120'],
            'device_photo' => ['nullable', 'image', 'max:5120'],
            'device_scan' => ['nullable', 'array'],
            'accessories' => ['nullable', 'array', 'max:8'],
            'accessories.*.code' => ['nullable', 'string', 'max:60'],
            'accessories.*.name' => ['nullable', 'string', 'max:60'],
            'accessories.*.quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'accessories.*.offer_type' => ['nullable', 'in:free,charged,discounted'],
            'accessories.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'accessories.*.notes' => ['nullable', 'string', 'max:160'],
            'store_offer_notes' => ['nullable', 'string', 'max:500'],
        ]);

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

        $draft = Customer::create([
            'registered_by' => auth()->id(),
            'application_draft_reference' => (string) Str::uuid(),
            'branch_id' => $deviceSelection['branch_id'] ?? $scopeContext['branch_id'],
            'vendor_id' => $deviceSelection['vendor_id'] ?? $scopeContext['vendor_id'],
            'phone_model_id' => $deviceSelection['phone_model_id'],
            'inventory_unit_id' => $deviceSelection['inventory_unit_id'],
            'device_specs' => $deviceSelection['device_specs'],
            'imei_number' => $deviceSelection['imei_number'],
            'imei_2' => $deviceSelection['imei_2'],
            'serial_number' => $deviceSelection['serial_number'],
            'cash_price' => $deviceSelection['cash_price'],
            'deposit_amount' => $validated['deposit_amount'],
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
    public function step2Identity(Request $request, string $customerId): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

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

        return $this->successResponse(['customer_id' => $customer->id, 'step' => 2], 'Step 2 (Identity) saved.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 3 — Contact & Location
    // POST /api/v1/kyc/application/{customer_id}/step3
    // ──────────────────────────────────────────────────────────────
    public function step3Contact(Request $request, string $customerId, KycPhoneService $phoneService): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId, ['vendor']);

        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:20'],
            'phone_country' => ['required', 'string'],
            'alt_phone' => ['nullable', 'string', 'min:7', 'max:20'],
            'alt_phone_country' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $contactPhones = $this->normalizeContactPhones($customer, $validated, $phoneService);
        $lockedBranchId = $customer->vendor?->branch_id ?: $customer->branch_id ?: $request->user()->branch_id;
        $requestedBranchId = $validated['branch_id'] ?? null;

        if ($customer->vendor?->branch_id && $requestedBranchId && $requestedBranchId !== $customer->vendor->branch_id) {
            throw ValidationException::withMessages([
                'branch_id' => 'This application is already tied to the selected vendor store branch and cannot be moved to a different branch.',
            ]);
        }

        $resolvedBranchId = $requestedBranchId ?: $lockedBranchId;

        if (! $resolvedBranchId) {
            throw ValidationException::withMessages([
                'branch_id' => 'Select the branch that will serve this customer.',
            ]);
        }

        $customer->update([
            'phone' => $contactPhones['phone'],
            'alt_phone' => $contactPhones['alt_phone'],
            'phone_metadata' => $contactPhones['phone_metadata'],
            'email' => isset($validated['email']) ? strtolower(trim($validated['email'])) : null,
            'branch_id' => $resolvedBranchId,
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
    public function step4Income(Request $request, string $customerId): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validate([
            'occupation' => ['nullable', 'string', 'max:100'],
            'employer' => ['nullable', 'string', 'max:100'],
            'work_location' => ['nullable', 'string', 'max:200'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'monthly_expenses' => ['nullable', 'numeric', 'min:0'],
            'income_payment_cycle' => ['nullable', 'in:weekly,biweekly,monthly,irregular'],
            'is_pep' => ['nullable', 'boolean'],
            'duration_at_work' => ['nullable', 'string', 'max:60'],
            'business_photo' => ['nullable', 'image', 'max:5120'],
        ]);

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
    public function step5Nok(Request $request, string $customerId, KycPhoneService $phoneService): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validate([
            'nok_name' => ['required', 'string', 'min:2', 'max:100'],
            'nok_phone' => ['required', 'string', 'min:7', 'max:20'],
            'nok_phone_country' => ['required', 'string'],
            'nok_relationship' => ['required', 'string', 'max:60'],
            'nok2_name' => ['nullable', 'string', 'max:100'],
            'nok2_phone' => ['nullable', 'string', 'min:7', 'max:20'],
            'nok2_phone_country' => ['nullable', 'string'],
            'nok2_relationship' => ['nullable', 'string', 'max:60'],
        ]);

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
    public function step6Consent(Request $request, string $customerId): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $request->validate([
            'terms_accepted' => ['required', 'accepted'],
            'data_consent_accepted' => ['required', 'accepted'],
            'call_consent_accepted' => ['required', 'accepted'],
        ]);

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
        $customer = $this->findAgentCustomerOrFail($customerId, ['vendor']);

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
            'branch_id' => ['nullable', 'exists:branches,id'],
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
        $lockedBranchId = $customer->vendor?->branch_id ?: $customer->branch_id ?: $request->user()->branch_id;
        $requestedBranchId = $validated['branch_id'] ?? null;

        if ($customer->vendor?->branch_id && $requestedBranchId && $requestedBranchId !== $customer->vendor->branch_id) {
            throw ValidationException::withMessages([
                'branch_id' => 'This application is already tied to the selected vendor store branch and cannot be moved to a different branch.',
            ]);
        }

        $resolvedBranchId = $requestedBranchId ?: $lockedBranchId;

        if (! $resolvedBranchId) {
            throw ValidationException::withMessages([
                'branch_id' => 'Select the branch that will serve this customer.',
            ]);
        }

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
            $resolvedBranchId,
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
                'branch_id' => $resolvedBranchId,
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
        Request $request,
        string $customerId,
        KycPhoneService $phoneService,
        SelcomCheckoutService $selcom,
        KycStageFlowService $stageFlow
    ): JsonResponse {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validate([
            'payment_phone' => ['required', 'string', 'min:7', 'max:20'],
            'payment_phone_country' => ['required', 'string', 'size:2'],
        ]);

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
        Request $request,
        string $customerId,
        ApplicationAutoCheckService $checker,
        SelcomCheckoutService $selcom,
        KycStageFlowService $stageFlow
    ): JsonResponse {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validate([
            'fo_notes' => ['nullable', 'string', 'max:1000'],
            'application_source' => ['nullable', 'in:walk_in,referral,vendor,social_media,agent'],
            'agreement_decision' => ['required', 'in:yes,no'],
            'payment_phone' => ['nullable', 'string', 'max:20'],
            'loan_term_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'downpayment_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_signature' => ['nullable', 'string'],
            'fo_signature' => ['nullable', 'string'],
            'etr_receipt_photo' => ['nullable', 'image', 'max:5120'],
            'asset_handover_list' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'asset_handover_notes' => ['nullable', 'string', 'max:500'],
        ]);

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
        if (! $customer->branch_id) {
            $missing[] = 'branch_id';
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

    // ──────────────────────────────────────────────────────────────
    // FO DASHBOARD STATS
    // GET /api/v1/kyc/dashboard
    // ──────────────────────────────────────────────────────────────
    public function dashboard(): JsonResponse
    {
        $foId = auth()->id();

        $base = Customer::where('registered_by', $foId);

        $total = (clone $base)->count();
        $pending = (clone $base)->whereHas('latestVerification', fn ($q) => $q->where('status', 'pending'))->count();
        $verified = (clone $base)->whereHas('latestVerification', fn ($q) => $q->where('status', 'approved'))->count();
        $declined = (clone $base)->whereHas('latestVerification', fn ($q) => $q->where('status', 'rejected'))->count();
        $drafts = (clone $base)
            ->whereDoesntHave('verifications')
            ->whereNotNull('kyc_fo_saved_as_draft_at')
            ->count();

        return $this->successResponse([
            'total_registered' => $total,
            'pending' => $pending,
            'verified' => $verified,
            'declined' => $declined,
            'drafts' => $drafts,
        ], 'Dashboard stats retrieved.');
    }

    // ──────────────────────────────────────────────────────────────
    // MY CUSTOMER LIST (paginated)
    // GET /api/v1/kyc/customers?status=pending&search=John&per_page=20
    // ──────────────────────────────────────────────────────────────
    public function myCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,verified,rejected,draft'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = Customer::with(['latestKycVerification', 'latestVerification', 'branch'])
            ->withCount('verifications')
            ->where('registered_by', auth()->id())
            ->whereNot('first_name', '_draft_');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('nida_number', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'draft') {
                $query->whereDoesntHave('verifications')
                    ->whereNotNull('kyc_fo_saved_as_draft_at');
            } elseif ($status === 'approved' || $status === 'verified') {
                $query->kycApproved();
            } else {
                $query->whereHas('latestVerification', fn ($q) => $q->where('status', $status));
            }
        }

        $customers = $query->latest()->paginate($request->integer('per_page', 20));

        $items = $customers->getCollection()->map(fn (Customer $c) => [
            'id' => $c->id,
            'full_name' => $c->full_name,
            'phone' => $c->phone,
            'gender' => $c->gender,
            'kyc_status' => $c->verifications_count === 0
                ? 'draft'
                : ($c->kyc_status ?: ($c->latestKycVerification?->status ?? 'draft')),
            'auto_check' => $c->latestKycVerification?->auto_check_status
                ?? $c->latestVerification?->auto_check_status,
            'branch' => $c->branch?->name,
            'headshot_url' => $this->photoUrl($c->headshot_photo_path),
            'registered_at' => $c->created_at->toDateTimeString(),
        ]);

        return $this->successResponse([
            'data' => $items,
            'current_page' => $customers->currentPage(),
            'last_page' => $customers->lastPage(),
            'total' => $customers->total(),
        ], 'Customers retrieved.');
    }

    // ──────────────────────────────────────────────────────────────
    // CUSTOMER FULL DETAIL
    // GET /api/v1/kyc/customers/{id}
    // ──────────────────────────────────────────────────────────────
    public function customerDetail(string $customerId, KycStageFlowService $stageFlow): JsonResponse
    {
        $customer = Customer::with([
            'latestKycVerification.fo',
            'latestKycVerification.reviewedBy',
            'latestVerification.fo',
            'latestVerification.reviewedBy',
            'verifications',
            'branch',
            'vendor',
            'phoneModel.brand',
            'inventoryUnit',
            'agreementDocument',
            'assetReleasedBy',
        ])->where('registered_by', auth()->id())
            ->findOrFail($customerId);

        $this->syncCustomerKycStatusFromVerification($customer);

        $v = $customer->latestKycVerification;
        $activeAgreement = $this->activeAgreementDocument();
        $latestPayment = $this->latestDraftPaymentFor($customer);
        $isDraftApplication = $customer->verifications->isEmpty();
        $resumeStep = $this->determineResumeStep($customer);

        return $this->successResponse([
            'id' => $customer->id,
            'full_name' => $customer->full_name,
            'first_name' => $customer->first_name,
            'middle_name' => $customer->middle_name,
            'last_name' => $customer->last_name,
            'gender' => $customer->gender,
            'date_of_birth' => $customer->date_of_birth?->toDateString(),
            'nida_number' => $customer->nida_number,
            'id_type' => $customer->id_type,
            'phone' => $customer->phone,
            'phone_display' => $customer->formattedPhone('phone'),
            'alt_phone' => $customer->alt_phone,
            'alt_phone_display' => $customer->formattedPhone('alt_phone'),
            'email' => $customer->email,
            'address' => $customer->address,
            'landmark' => $customer->landmark,
            'region' => $customer->region,
            'district' => $customer->district,
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,
            'branch' => $customer->branch ? ['id' => $customer->branch->id, 'name' => $customer->branch->name] : null,
            'vendor' => $customer->vendor ? ['id' => $customer->vendor->id, 'name' => $customer->vendor->name] : null,
            'device' => [
                'brand_id' => $customer->phoneModel?->brand?->id,
                'brand_name' => $customer->phoneModel?->brand?->name,
                'model_name' => $customer->phoneModel?->name,
                'phone_model_id' => $customer->phone_model_id,
                'inventory_unit_id' => $customer->inventory_unit_id,
                'specs' => $customer->device_specs,
                'imei_1' => $customer->imei_number,
                'imei_2' => $customer->imei_2,
                'serial_number' => $customer->serial_number,
                'cash_price' => $customer->cash_price,
                'deposit_amount' => $customer->deposit_amount,
                'preferred_repayment' => $customer->preferred_repayment,
                'loan_interest_rate' => $customer->loan_interest_rate,
                'loan_interest_type' => $customer->loan_interest_type,
                'loan_duration_weeks' => $customer->loan_duration_weeks,
                'loan_grace_period_days' => $customer->loan_grace_period_days,
                'loan_terms_source' => $this->loanTermsSource($customer),
                'scan_metadata' => $customer->device_scan_metadata,
                'accessories' => $customer->device_accessories,
                'store_offer_notes' => $customer->store_offer_notes,
            ],
            'income' => [
                'occupation' => $customer->occupation,
                'employer' => $customer->employer,
                'work_location' => $customer->work_location,
                'monthly_income' => $customer->monthly_income,
                'monthly_expenses' => $customer->monthly_expenses,
                'income_payment_cycle' => $customer->income_payment_cycle,
                'duration_at_work' => $customer->duration_at_work,
            ],
            'nok' => [
                'nok_name' => $customer->nok_name,
                'nok_phone' => $customer->nok_phone,
                'nok_phone_display' => $customer->formattedPhone('nok_phone'),
                'nok_relationship' => $customer->nok_relationship,
                'nok2_name' => $customer->nok2_name,
                'nok2_phone' => $customer->nok2_phone,
                'nok2_phone_display' => $customer->formattedPhone('nok2_phone'),
                'nok2_relationship' => $customer->nok2_relationship,
            ],
            'phone_metadata' => $customer->phone_metadata,
            'consent' => [
                'terms_accepted' => $customer->terms_accepted,
                'data_consent_accepted' => $customer->data_consent_accepted,
                'call_consent_accepted' => $customer->call_consent_accepted,
                'consent_timestamp' => $customer->consent_timestamp?->toDateTimeString(),
            ],
            'payment' => $this->serializePaymentSummary($latestPayment),
            'agreement' => $this->serializeAgreementSummary($activeAgreement, $customer),
            'release' => $this->serializeReleaseSummary($customer),
            'flow' => $stageFlow->summary($customer, $latestPayment, $activeAgreement),
            'photos' => [
                'imei' => $this->photoUrl($customer->imei_photo_path),
                'device_box' => $this->photoUrl($customer->device_box_photo_path),
                'device' => $this->photoUrl($customer->device_photo_path),
                'id_front' => $this->photoUrl($customer->id_front_photo_path),
                'id_back' => $this->photoUrl($customer->id_back_photo_path),
                'headshot' => $this->photoUrl($customer->headshot_photo_path),
                'client_fo' => $this->photoUrl($customer->client_fo_photo_path),
                'business' => $this->photoUrl($customer->business_photo_path),
                'customer_signature' => $this->photoUrl($customer->customer_signature_path),
                'fo_signature' => $this->photoUrl($customer->fo_signature_path),
                'asset_handover_list' => $this->photoUrl($customer->asset_handover_list_path),
            ],
            'fo_notes' => $customer->fo_notes,
            'application_source' => $customer->application_source,
            'kyc_status' => $this->displayKycStatusForFo($customer, $v),
            'registered_at' => $customer->created_at->toDateTimeString(),
            'can_release_asset' => $customer->isReadyForAssetRelease(),
            'can_resume_draft' => $isDraftApplication,
            'fo_saved_as_draft_at' => $customer->kyc_fo_saved_as_draft_at?->toDateTimeString(),
            'resume_step' => $resumeStep,
            'resume_stage' => $stageFlow->determineResumeStage($customer),
            'verification' => $v ? [
                'id' => $v->id,
                'status' => $v->status,
                'stage' => $v->stage,
                'stage_label' => $v->currentStageLabel(),
                'auto_check_status' => $v->auto_check_status,
                'auto_check_results' => $v->auto_check_results,
                'auto_check_ran_at' => $v->auto_check_ran_at?->toDateTimeString(),
                'rejection_reason' => $v->rejection_reason,
                'notes' => $v->notes,
                'reviewed_by' => $v->reviewedBy?->name,
                'reviewed_at' => $v->reviewed_at?->toDateTimeString(),
                'fo' => $v->fo?->name,
                'submitted_at' => $v->created_at->toDateTimeString(),
            ] : null,
        ], 'Customer detail retrieved.');
    }

    public function releaseAsset(string $customerId, CustomerLoanProvisioningService $loanProvisioning): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId, ['inventoryUnit', 'assetReleasedBy', 'agreementDocument']);

        if (! $customer->hasApprovedKyc()) {
            return $this->errorResponse('Approve the KYC application before releasing the asset.', 422);
        }

        if (! $customer->hasSuccessfulDepositPayment()) {
            return $this->errorResponse('Successful deposit payment is required before releasing the asset.', 422);
        }

        if (! $customer->hasAcceptedAgreement()) {
            return $this->errorResponse('Agreement acceptance is required before release.', 422);
        }

        if (! $customer->hasCapturedSignatures()) {
            return $this->errorResponse('Customer and FO signatures must be captured before release.', 422);
        }

        if (! $customer->hasAssetHandoverRecord()) {
            return $this->errorResponse('Asset handover checklist is required before release.', 422);
        }

        if (! filled($customer->agreement_document_id)) {
            return $this->errorResponse('Agreement document must be on file before release.', 422);
        }

        if ($customer->isAssetReleased()) {
            $loan = $loanProvisioning->provisionForCustomerPortal($customer->fresh());

            return $this->successResponse([
                'customer_id' => $customer->id,
                'release' => $this->serializeReleaseSummary($customer),
                'loan' => $loan ? $this->serializeLoanSummary($loan) : null,
            ], 'This asset was already released.');
        }

        if (! $customer->inventoryUnit) {
            return $this->errorResponse('No linked stock unit was found for this application.', 422);
        }

        $loan = DB::transaction(function () use ($customer, $loanProvisioning) {
            $customer->update([
                'asset_release_status' => 'released',
                'asset_released_at' => now(),
                'asset_released_by' => auth()->id(),
            ]);

            if ($customer->inventoryUnit->status !== 'sold') {
                $customer->inventoryUnit->update(['status' => 'assigned']);
            }

            return $loanProvisioning->provision($customer->fresh(['inventoryUnit', 'assetReleasedBy']), auth()->user());
        });

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties([
                'inventory_unit_id' => $customer->inventory_unit_id,
                'asset_release_status' => 'released',
            ])
            ->log("API asset released for {$customer->full_name}");

        return $this->successResponse([
            'customer_id' => $customer->id,
            'release' => $this->serializeReleaseSummary($customer->fresh()),
            'loan' => $this->serializeLoanSummary($loan),
        ], 'Asset released successfully.');
    }

    // ──────────────────────────────────────────────────────────────
    // BRANCHES LIST (for step 3 dropdown)
    // GET /api/v1/kyc/branches
    // ──────────────────────────────────────────────────────────────
    public function branches(): JsonResponse
    {
        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return $this->successResponse($branches, 'Branches retrieved.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *     phone: string,
     *     alt_phone: ?string,
     *     phone_metadata: array<string, mixed>
     * }
     */
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
        return $path ? route('api.kyc.public-media', ['path' => $path]) : null;
    }

    private function findAgentCustomerOrFail(string $customerId, array $with = []): Customer
    {
        $query = Customer::with($with);

        if (! auth()->user()?->isAdmin()) {
            $query->where('registered_by', auth()->id());
        }

        return $query->findOrFail($customerId);
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
            'branch_id' => $inventoryUnit?->branch_id,
            'vendor_id' => $inventoryUnit?->vendor_id,
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
            && filled($customer->branch_id)
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
