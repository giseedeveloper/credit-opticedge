<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\KycApprovalService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HQ KYC approval over REST — mirrors Livewire PendingVerifications for mobile/admin tools.
 */
class KycApprovalApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private KycApprovalService $kycApproval,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'stage' => ['nullable', 'integer', 'min:1', 'max:4'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $stage = (int) ($request->integer('stage') ?: 1);
        $perPage = (int) ($request->integer('per_page') ?: 15);

        $customers = Customer::query()
            ->with(['latestKycVerification', 'dealer', 'registeredBy'])
            ->whereHas('latestKycVerification', fn ($q) => $q
                ->where('stage', $stage)
                ->where('status', 'pending'))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('nida_number', 'like', $term)
                        ->orWhere('imei_number', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);

        return $this->successResponse([
            'stage' => $stage,
            'stage_counts' => $this->stageCounts(),
            'customers' => $customers->through(fn (Customer $customer) => $this->serializeQueueCustomer($customer)),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(string $customerId): JsonResponse
    {
        $customer = Customer::query()
            ->with([
                'latestKycVerification.faceMatchManualVerifiedBy',
                'dealer',
                'registeredBy',
                'loans' => fn ($q) => $q->latest()->take(3),
            ])
            ->findOrFail($customerId);

        return $this->successResponse($this->serializeDetailCustomer($customer));
    }

    public function approveStage(Request $request, string $customerId, int $stage): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $customer = Customer::findOrFail($customerId);
        $verification = $this->kycApproval->approveStage(
            $customer,
            $stage,
            $request->user(),
            $request->string('notes')->toString() ?: null,
        );

        return $this->successResponse([
            'customer' => $this->serializeDetailCustomer($customer->fresh(['latestKycVerification'])),
            'verification' => $verification,
        ], "Stage {$stage} approved.");
    }

    public function rejectStage(Request $request, string $customerId, int $stage): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $customer = Customer::findOrFail($customerId);
        $verification = $this->kycApproval->rejectStage(
            $customer,
            $stage,
            $request->user(),
            $request->string('reason')->toString(),
            $request->string('notes')->toString() ?: null,
        );

        return $this->successResponse([
            'customer' => $this->serializeDetailCustomer($customer->fresh(['latestKycVerification'])),
            'verification' => $verification,
        ], "Stage {$stage} rejected.");
    }

    public function recordConfirmationCall(Request $request, string $customerId): JsonResponse
    {
        $request->validate([
            'outcome' => ['required', 'in:confirmed,not_confirmed'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $customer = Customer::findOrFail($customerId);
        $verification = $this->kycApproval->recordConfirmationCall(
            $customer,
            $request->user(),
            $request->string('outcome')->toString(),
            $request->string('notes')->toString() ?: null,
        );

        return $this->successResponse([
            'customer' => $this->serializeDetailCustomer($customer->fresh(['latestKycVerification'])),
            'verification' => $verification,
        ], 'Confirmation call recorded.');
    }

    public function recordNokCall(Request $request, string $customerId): JsonResponse
    {
        $request->validate([
            'outcome' => ['required', 'in:confirmed,not_confirmed'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $customer = Customer::findOrFail($customerId);
        $verification = $this->kycApproval->recordNokCall(
            $customer,
            $request->user(),
            $request->string('outcome')->toString(),
            $request->string('notes')->toString() ?: null,
        );

        return $this->successResponse([
            'customer' => $this->serializeDetailCustomer($customer->fresh(['latestKycVerification'])),
            'verification' => $verification,
        ], 'NOK call recorded.');
    }

    public function manualVerifyFaceMatch(string $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);
        $verification = $this->kycApproval->manualVerifyFaceMatch($customer, request()->user());

        return $this->successResponse([
            'customer_id' => $customer->id,
            'face_match_status' => $verification->face_match_status,
        ], 'Face match marked as verified.');
    }

    /**
     * @return array<int, int>
     */
    private function stageCounts(): array
    {
        return [
            1 => Customer::whereHas('latestKycVerification', fn ($q) => $q->where('stage', 1)->where('status', 'pending'))->count(),
            2 => Customer::whereHas('latestKycVerification', fn ($q) => $q->where('stage', 2)->where('status', 'pending'))->count(),
            3 => Customer::whereHas('latestKycVerification', fn ($q) => $q->where('stage', 3)->where('status', 'pending'))->count(),
            4 => Customer::whereHas('latestKycVerification', fn ($q) => $q->where('stage', 4)->where('status', 'pending'))->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeQueueCustomer(Customer $customer): array
    {
        $verification = $customer->latestKycVerification;

        return [
            'id' => $customer->id,
            'full_name' => $customer->full_name,
            'phone' => $customer->phone,
            'kyc_status' => $customer->kyc_status,
            'kyc_stage' => $customer->kyc_stage,
            'verification' => $verification ? [
                'id' => $verification->id,
                'stage' => $verification->stage,
                'status' => $verification->status,
                'face_match_status' => $verification->face_match_status,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDetailCustomer(Customer $customer): array
    {
        $verification = $customer->latestKycVerification;

        return [
            'id' => $customer->id,
            'full_name' => $customer->full_name,
            'phone' => $customer->phone,
            'nida_number' => $customer->nida_number,
            'kyc_status' => $customer->kyc_status,
            'kyc_stage' => $customer->kyc_stage,
            'asset_release_status' => $customer->asset_release_status,
            'verification' => $verification,
            'dealer' => $customer->dealer,
            'registered_by' => $customer->registeredBy,
            'recent_loans' => $customer->loans,
        ];
    }
}
