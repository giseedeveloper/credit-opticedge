<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Verification;
use App\Services\ApplicationAutoCheckService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    // ──────────────────────────────────────────────────────────────
    // STEP 1 — Device
    // POST /api/v1/kyc/application/step1
    // ──────────────────────────────────────────────────────────────
    public function step1Device(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_specs' => ['required', 'string', 'min:3', 'max:200'],
            'imei_number' => ['required', 'string', 'digits:15'],
            'imei_2' => ['nullable', 'string', 'digits:15'],
            'serial_number' => ['nullable', 'string', 'max:60'],
            'cash_price' => ['required', 'numeric', 'min:1'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'preferred_repayment' => ['required', 'in:weekly,biweekly,monthly'],
            'imei_photo' => ['nullable', 'image', 'max:5120'],
            'device_box_photo' => ['nullable', 'image', 'max:5120'],
            'device_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $draft = Customer::create([
            'registered_by' => auth()->id(),
            'device_specs' => trim($validated['device_specs']),
            'imei_number' => strtoupper(trim($validated['imei_number'])),
            'imei_2' => isset($validated['imei_2']) ? strtoupper(trim($validated['imei_2'])) : null,
            'serial_number' => isset($validated['serial_number']) ? strtoupper(trim($validated['serial_number'])) : null,
            'cash_price' => $validated['cash_price'],
            'deposit_amount' => $validated['deposit_amount'],
            'preferred_repayment' => $validated['preferred_repayment'],
            'imei_photo_path' => $this->storeFile($request, 'imei_photo', 'imei'),
            'device_box_photo_path' => $this->storeFile($request, 'device_box_photo', 'device_box'),
            'device_photo_path' => $this->storeFile($request, 'device_photo', 'device'),
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
        $customer = Customer::findOrFail($customerId);

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
    public function step3Contact(Request $request, string $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);

        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:9', 'unique:customers,phone,'.$customer->id],
            'alt_phone' => ['nullable', 'string', 'min:9'],
            'email' => ['nullable', 'email'],
            'branch_id' => ['required', 'exists:branches,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $customer->update([
            'phone' => trim($validated['phone']),
            'alt_phone' => isset($validated['alt_phone']) ? trim($validated['alt_phone']) : null,
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
        $customer = Customer::findOrFail($customerId);

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
    public function step5Nok(Request $request, string $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);

        $validated = $request->validate([
            'nok_name' => ['required', 'string', 'min:2', 'max:100'],
            'nok_phone' => ['required', 'string', 'min:9'],
            'nok_relationship' => ['required', 'string', 'max:60'],
            'nok2_name' => ['nullable', 'string', 'max:100'],
            'nok2_phone' => ['nullable', 'string', 'min:9'],
            'nok2_relationship' => ['nullable', 'string', 'max:60'],
        ]);

        $customer->update([
            'nok_name' => trim($validated['nok_name']),
            'nok_phone' => trim($validated['nok_phone']),
            'nok_relationship' => $validated['nok_relationship'],
            'nok2_name' => isset($validated['nok2_name']) ? trim($validated['nok2_name']) : null,
            'nok2_phone' => isset($validated['nok2_phone']) ? trim($validated['nok2_phone']) : null,
            'nok2_relationship' => $validated['nok2_relationship'] ?? null,
        ]);

        return $this->successResponse(['customer_id' => $customer->id, 'step' => 5], 'Step 5 (NOK) saved.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 6 — Consent
    // POST /api/v1/kyc/application/{customer_id}/step6
    // ──────────────────────────────────────────────────────────────
    public function step6Consent(Request $request, string $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);

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
        $customer = Customer::findOrFail($customerId);

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
        $customer = Customer::with('latestVerification')->findOrFail($customerId);

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
            'status' => ['nullable', 'in:pending,approved,rejected,draft'],
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
            'alt_phone' => $customer->alt_phone,
            'email' => $customer->email,
            'address' => $customer->address,
            'landmark' => $customer->landmark,
            'region' => $customer->region,
            'district' => $customer->district,
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,
            'branch' => $customer->branch ? ['id' => $customer->branch->id, 'name' => $customer->branch->name] : null,
            'device' => [
                'specs' => $customer->device_specs,
                'imei_1' => $customer->imei_number,
                'imei_2' => $customer->imei_2,
                'serial_number' => $customer->serial_number,
                'cash_price' => $customer->cash_price,
                'deposit_amount' => $customer->deposit_amount,
                'preferred_repayment' => $customer->preferred_repayment,
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
                'nok_relationship' => $customer->nok_relationship,
                'nok2_name' => $customer->nok2_name,
                'nok2_phone' => $customer->nok2_phone,
                'nok2_relationship' => $customer->nok2_relationship,
            ],
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

    private function photoUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    private function storeFile(Request $request, string $field, string $directory): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return $request->file($field)->store("kyc/{$directory}", 'public');
    }
}
