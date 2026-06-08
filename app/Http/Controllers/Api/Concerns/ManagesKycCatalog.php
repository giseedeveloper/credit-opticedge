<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Services\CustomerLoanProvisioningService;
use App\Services\KycDeviceCatalogService;
use App\Services\KycPhoneService;
use App\Services\KycStageFlowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ManagesKycCatalog
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
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function phoneCountries(KycPhoneService $phoneService): JsonResponse
    {
        return $this->successResponse($phoneService->supportedCountries(), 'Phone country options retrieved.');
    }

    public function deviceBrands(Request $request, KycDeviceCatalogService $catalog): JsonResponse
    {
        $brands = $catalog->catalogBrands()
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

        $models = $catalog->catalogModels($request->string('brand_id')->toString() ?: null)
            ->map(fn ($model) => [
                'id' => $model->id,
                'brand_id' => $model->brand_id,
                'brand_name' => $model->brand?->name,
                'name' => $model->name,
                'retail_price' => $model->retail_price,
                'recommended_deposit' => $catalog->recommendedDepositForRetailPrice($model->retail_price),
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
            'recommended_deposit' => $catalog->recommendedDepositForRetailPrice($unit->phoneModel?->retail_price),
            'imei_1' => $unit->imei_1,
            'imei_2' => $unit->imei_2,
            'serial_number' => $unit->serial_number,
            'status' => $unit->status,
            'dealer_id' => $unit->dealer_id,
            'recommended_terms' => $recommendedTerms,
        ])->values();

        return $this->successResponse($units, 'Available inventory retrieved.');
    }

    public function stageFlow(KycStageFlowService $stageFlow): JsonResponse
    {
        return $this->successResponse($stageFlow->contract(), 'KYC stage flow retrieved.');
    }

    /**
     * @deprecated Branches removed; empty list for older mobile clients.
     */
    public function branches(): JsonResponse
    {
        return $this->successResponse([
            'items' => [],
            'deprecated' => true,
            'replacement' => 'dealer',
            'message' => 'Branch tenancy was removed. Use dealer context from the authenticated agent instead.',
        ], 'Branches are no longer used.');
    }
}
