<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Verification;
use App\Services\ApplicationAutoCheckService;
use App\Services\DeviceIdentifierScanService;
use App\Services\IMEITrackingService;
use App\Services\KycAccessoryOfferService;
use App\Services\KycDeviceCatalogService;
use App\Services\KycPhoneService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

    public function deviceModels(Request $request, KycDeviceCatalogService $catalog): JsonResponse
    {
        $request->validate([
            'brand_id' => ['nullable', 'exists:brands,id'],
        ]);

        $models = $catalog->modelsFor($request->user(), $request->string('brand_id')->toString() ?: null)
            ->map(fn ($model) => [
                'id' => $model->id,
                'brand_id' => $model->brand_id,
                'brand_name' => $model->brand?->name,
                'name' => $model->name,
                'retail_price' => $model->retail_price,
                'specifications' => $model->specifications,
                'device_specs' => $catalog->buildDeviceSpecs($model),
            ])
            ->values();

        return $this->successResponse($models, 'Device models retrieved.');
    }

    public function deviceInventory(Request $request, KycDeviceCatalogService $catalog): JsonResponse
    {
        $request->validate([
            'phone_model_id' => ['nullable', 'exists:phone_models,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

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
        ])->values();

        return $this->successResponse($units, 'Available inventory retrieved.');
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
        KycAccessoryOfferService $accessoryOffers
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

        $deviceSelection = $this->resolveDeviceSelection(
            $request,
            $validated,
            $catalog,
            $scanService,
            $imeiTracking
        );

        $draft = Customer::create([
            'registered_by' => auth()->id(),
            'phone_model_id' => $deviceSelection['phone_model_id'],
            'inventory_unit_id' => $deviceSelection['inventory_unit_id'],
            'device_specs' => $deviceSelection['device_specs'],
            'imei_number' => $deviceSelection['imei_number'],
            'imei_2' => $deviceSelection['imei_2'],
            'serial_number' => $deviceSelection['serial_number'],
            'cash_price' => $deviceSelection['cash_price'],
            'deposit_amount' => $validated['deposit_amount'],
            'preferred_repayment' => $validated['preferred_repayment'],
            'imei_photo_path' => $this->storeFile($request, 'imei_photo', 'imei'),
            'device_box_photo_path' => $this->storeFile($request, 'device_box_photo', 'device_box'),
            'device_photo_path' => $this->storeFile($request, 'device_photo', 'device'),
            'device_scan_metadata' => $deviceSelection['device_scan_metadata'],
            'device_accessories' => $normalizedAccessories !== [] ? $normalizedAccessories : null,
            'store_offer_notes' => $validated['store_offer_notes'] ?? null,
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
        ], 'Step 1 (Device) saved. Proceed to Step 2.');
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
            'id_front_photo' => ['required', 'image', 'max:5120'],
            'id_back_photo' => ['required', 'image', 'max:5120'],
            'headshot_photo' => ['required', 'image', 'max:5120'],
            'client_fo_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $customer->update([
            'first_name' => ucfirst(strtolower(trim($validated['first_name']))),
            'middle_name' => isset($validated['middle_name']) ? ucfirst(strtolower(trim($validated['middle_name']))) : null,
            'last_name' => ucfirst(strtolower(trim($validated['last_name']))),
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'nida_number' => strtoupper(trim($validated['nida_number'])),
            'id_type' => $validated['id_type'],
            'id_front_photo_path' => $this->storeFile($request, 'id_front_photo', 'id_front'),
            'id_back_photo_path' => $this->storeFile($request, 'id_back_photo', 'id_back'),
            'headshot_photo_path' => $this->storeFile($request, 'headshot_photo', 'headshot'),
            'client_fo_photo_path' => $this->storeFile($request, 'client_fo_photo', 'client_fo'),
        ]);

        return $this->successResponse(['customer_id' => $customer->id, 'step' => 2], 'Step 2 (Identity) saved.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 3 — Contact & Location
    // POST /api/v1/kyc/application/{customer_id}/step3
    // ──────────────────────────────────────────────────────────────
    public function step3Contact(Request $request, string $customerId, KycPhoneService $phoneService): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:20'],
            'phone_country' => ['required', 'string'],
            'alt_phone' => ['nullable', 'string', 'min:7', 'max:20'],
            'alt_phone_country' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'branch_id' => ['required', 'exists:branches,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $contactPhones = $this->normalizeContactPhones($customer, $validated, $phoneService);

        $customer->update([
            'phone' => $contactPhones['phone'],
            'alt_phone' => $contactPhones['alt_phone'],
            'phone_metadata' => $contactPhones['phone_metadata'],
            'email' => isset($validated['email']) ? strtolower(trim($validated['email'])) : null,
            'branch_id' => $validated['branch_id'],
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
            'duration_at_work' => ['nullable', 'string', 'max:60'],
            'business_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $customer->update([
            'occupation' => $validated['occupation'] ?? null,
            'employer' => $validated['employer'] ?? null,
            'work_location' => $validated['work_location'] ?? null,
            'monthly_income' => $validated['monthly_income'],
            'monthly_expenses' => $validated['monthly_expenses'] ?? null,
            'income_payment_cycle' => $validated['income_payment_cycle'] ?? null,
            'duration_at_work' => $validated['duration_at_work'] ?? null,
            'business_photo_path' => $this->storeFile($request, 'business_photo', 'business'),
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

    // ──────────────────────────────────────────────────────────────
    // STEP 7 — Submit
    // POST /api/v1/kyc/application/{customer_id}/step7
    // ──────────────────────────────────────────────────────────────
    public function step7Submit(
        Request $request,
        string $customerId,
        ApplicationAutoCheckService $checker
    ): JsonResponse {
        $customer = $this->findAgentCustomerOrFail($customerId);

        $validated = $request->validate([
            'fo_notes' => ['nullable', 'string', 'max:1000'],
            'application_source' => ['nullable', 'in:walk_in,referral,vendor,social_media,agent'],
        ]);

        // Guard: required fields must already be present
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

        if (! empty($missing)) {
            return $this->errorResponse('Complete earlier steps before submitting. Missing: '.implode(', ', $missing), 422);
        }

        $customer->update([
            'fo_notes' => $validated['fo_notes'] ?? null,
            'application_source' => $validated['application_source'] ?? null,
            'kyc_status' => 'pending',
            'kyc_stage' => 1,
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

        $result = $checker->run($customer, $verification);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['auto_check_status' => $result['status']])
            ->log("API KYC submitted for {$customer->full_name} — auto-check: {$result['status']}");

        return $this->successResponse([
            'customer_id' => $customer->id,
            'verification_id' => $verification->id,
            'auto_check_status' => $result['status'],
            'auto_check_results' => $result['checks'],
        ], 'Application submitted successfully.');
    }

    // ──────────────────────────────────────────────────────────────
    // GET application draft status
    // GET /api/v1/kyc/application/{customer_id}/status
    // ──────────────────────────────────────────────────────────────
    public function applicationStatus(string $customerId): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId, ['latestVerification']);

        return $this->successResponse([
            'customer_id' => $customer->id,
            'kyc_status' => $customer->kyc_status,
            'kyc_stage' => $customer->kyc_stage,
            'auto_check_status' => $customer->latestVerification?->auto_check_status,
            'auto_check_results' => $customer->latestVerification?->auto_check_results,
        ], 'Application status retrieved.');
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
        $drafts = (clone $base)->whereDoesntHave('verifications')->count();

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

        $query = Customer::with(['latestVerification', 'branch'])
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
                $query->whereDoesntHave('verifications');
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
            'kyc_status' => $c->latestVerification?->status ?? 'draft',
            'auto_check' => $c->latestVerification?->auto_check_status,
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
    public function customerDetail(string $customerId): JsonResponse
    {
        $customer = Customer::with([
            'latestVerification.fo',
            'latestVerification.reviewedBy',
            'verifications',
            'branch',
            'phoneModel.brand',
            'inventoryUnit',
        ])->where('registered_by', auth()->id())
            ->findOrFail($customerId);

        $v = $customer->latestVerification;

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
            'device' => [
                'brand_name' => $customer->phoneModel?->brand?->name,
                'phone_model_id' => $customer->phone_model_id,
                'inventory_unit_id' => $customer->inventory_unit_id,
                'specs' => $customer->device_specs,
                'imei_1' => $customer->imei_number,
                'imei_2' => $customer->imei_2,
                'serial_number' => $customer->serial_number,
                'cash_price' => $customer->cash_price,
                'deposit_amount' => $customer->deposit_amount,
                'preferred_repayment' => $customer->preferred_repayment,
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
            'photos' => [
                'imei' => $this->photoUrl($customer->imei_photo_path),
                'device_box' => $this->photoUrl($customer->device_box_photo_path),
                'device' => $this->photoUrl($customer->device_photo_path),
                'id_front' => $this->photoUrl($customer->id_front_photo_path),
                'id_back' => $this->photoUrl($customer->id_back_photo_path),
                'headshot' => $this->photoUrl($customer->headshot_photo_path),
                'client_fo' => $this->photoUrl($customer->client_fo_photo_path),
                'business' => $this->photoUrl($customer->business_photo_path),
            ],
            'fo_notes' => $customer->fo_notes,
            'application_source' => $customer->application_source,
            'kyc_status' => $customer->kyc_status,
            'registered_at' => $customer->created_at->toDateTimeString(),
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

    private function photoUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
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

        if ($phoneModel && $catalog->hasAvailableUnitsFor($user, $phoneModel->id) && ! $inventoryUnit) {
            throw ValidationException::withMessages([
                'inventory_unit_id' => 'Select an available stock unit for the chosen model.',
            ]);
        }

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
            'device_specs' => $phoneModel ? $catalog->buildDeviceSpecs($phoneModel) : trim((string) $validated['device_specs']),
            'imei_number' => $imeiNumber,
            'imei_2' => $imei2 ?: null,
            'serial_number' => $serialNumber ?: null,
            'cash_price' => $cashPrice,
            'device_scan_metadata' => $scanMetadata,
        ];
    }
}
