<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Http\Requests\Api\Kyc\HandoverChecklistRequest;
use App\Models\Customer;
use App\Services\CustomerLoanProvisioningService;
use App\Services\KycStageFlowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ManagesKycAgentPortal
{
    use ApiResponse;

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

        $query = Customer::with(['latestKycVerification', 'latestVerification', 'dealer'])
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
            'face_match' => [
                'status' => $c->latestKycVerification?->face_match_status
                    ?? $c->latestVerification?->face_match_status,
                'score' => $c->latestKycVerification?->face_match_score
                    ?? $c->latestVerification?->face_match_score,
            ],
            'dealer' => $c->dealer?->name,
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
        $customer = $this->findAgentCustomerOrFail($customerId, [
            'latestKycVerification.fo',
            'latestKycVerification.reviewedBy',
            'latestKycVerification.faceMatchManualVerifiedBy',
            'latestVerification.fo',
            'latestVerification.reviewedBy',
            'latestVerification.faceMatchManualVerifiedBy',
            'verifications',
            'dealer',
            'phoneModel.brand',
            'inventoryUnit',
            'agreementDocument',
            'assetReleasedBy',
        ]);

        $this->syncCustomerKycStatusFromVerification($customer);

        $v = $customer->latestKycVerification;
        $activeAgreement = $this->activeAgreementDocument();
        $latestPayment = $this->latestDraftPaymentFor($customer);
        $isDraftApplication = $customer->verifications->isEmpty();
        $resumeStep = $this->determineResumeStep($customer);

        $dealerSummary = $customer->dealer ? ['id' => $customer->dealer->id, 'name' => $customer->dealer->name] : null;

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
            'dealer' => $dealerSummary,
            'vendor' => $dealerSummary,
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
                'face_match' => [
                    'status' => $v->face_match_status,
                    'score' => $v->face_match_score,
                    'reason' => $v->face_match_reason,
                    'ran_at' => $v->face_match_ran_at?->toDateTimeString(),
                    'manual_verified_by' => $v->faceMatchManualVerifiedBy?->name,
                    'manual_verified_at' => $v->face_match_manual_verified_at?->toDateTimeString(),
                    'alert' => in_array($v->face_match_status, ['review', 'failed'], true),
                ],
                'rejection_reason' => $v->rejection_reason,
                'notes' => $v->notes,
                'reviewed_by' => $v->reviewedBy?->name,
                'reviewed_at' => $v->reviewed_at?->toDateTimeString(),
                'fo' => $v->fo?->name,
                'submitted_at' => $v->created_at->toDateTimeString(),
            ] : null,
        ], 'Customer detail retrieved.');
    }

    /**
     * Upload or replace the signed handover checklist after the application was submitted
     * (fixes FO flows that reached approval without a checklist file on record).
     */
    public function uploadHandoverChecklist(HandoverChecklistRequest $request, string $customerId): JsonResponse
    {
        $customer = $this->findAgentCustomerOrFail($customerId, ['inventoryUnit', 'assetReleasedBy']);

        if ($customer->isAssetReleased()) {
            return $this->errorResponse('This asset was already released; the handover file cannot be changed.', 422);
        }

        $validated = $request->validated();

        $path = $this->storeFile($request, 'asset_handover_list', 'handover');

        if (! filled($path)) {
            return $this->errorResponse('Could not store the handover checklist file.', 422);
        }

        $customer->update([
            'asset_handover_list_path' => $path,
            'asset_handover_notes' => filled($validated['asset_handover_notes'] ?? null)
                ? (string) $validated['asset_handover_notes']
                : $customer->asset_handover_notes,
        ]);

        activity('kyc')
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties(['path' => $path])
            ->log('Handover checklist uploaded via FO app');

        return $this->successResponse([
            'release' => $this->serializeReleaseSummary($customer->fresh(['inventoryUnit', 'assetReleasedBy'])),
        ], 'Handover checklist uploaded.');
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

        if (! $this->hasPassedFaceMatch($customer)) {
            return $this->errorResponse(
                'Face verification must be passed or manually verified before release.',
                422
            );
        }

        if ($customer->isAssetReleased()) {
            $loan = $loanProvisioning->provisionForCustomerPortal($customer->fresh());

            return $this->successResponse([
                'customer_id' => $customer->id,
                'release' => $this->serializeReleaseSummary($customer),
                'loan' => $loan ? $this->serializeLoanSummary($loan) : null,
            ], 'This asset was already released.');
        }

        $loan = DB::transaction(function () use ($customer, $loanProvisioning) {
            $customer->update([
                'asset_release_status' => 'released',
                'asset_released_at' => now(),
                'asset_released_by' => auth()->id(),
            ]);

            if ($customer->inventoryUnit && $customer->inventoryUnit->status !== 'sold') {
                $customer->inventoryUnit->update(['status' => 'sold']);
            }

            return $loanProvisioning->provision(
                $customer->fresh(['inventoryUnit', 'assetReleasedBy']),
                auth()->user()
            );
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
}
