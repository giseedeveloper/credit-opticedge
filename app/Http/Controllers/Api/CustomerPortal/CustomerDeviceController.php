<?php

namespace App\Http\Controllers\Api\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Customer Portal — Device
 *
 * View device information linked to the customer's loan.
 */
class CustomerDeviceController extends Controller
{
    use ApiResponse;

    /**
     * Get device details.
     *
     * Returns the customer's device info, including brand, model, IMEI,
     * and loan contract summary.
     */
    public function show(Request $request): JsonResponse
    {
        $customer = $this->resolveCustomer($request)->load([
            'phoneModel.brand',
            'inventoryUnit',
            'agreementDocument',
        ]);

        $phoneModel = $customer->phoneModel;
        $brand = $phoneModel?->brand;
        $unit = $customer->inventoryUnit;

        return $this->successResponse([
            'brand' => $brand ? [
                'id' => $brand->id,
                'name' => $brand->name,
            ] : null,
            'model' => $phoneModel ? [
                'id' => $phoneModel->id,
                'name' => $phoneModel->name,
                'storage' => $phoneModel->storage ?? null,
                'ram' => $phoneModel->ram ?? null,
            ] : null,
            'imei' => $customer->imei_number,
            'imei_2' => $customer->imei_2,
            'serial_number' => $customer->serial_number,
            'device_specs' => $customer->device_specs,
            'device_photo_url' => $customer->device_photo_path
                ? $this->mediaUrl($customer->device_photo_path)
                : null,
            'cash_price' => $customer->cash_price,
            'deposit_amount' => $customer->deposit_amount,
            'preferred_repayment' => $customer->preferred_repayment,
            'asset_release_status' => $customer->asset_release_status,
            'asset_released_at' => $customer->asset_released_at?->toDateString(),
            'agreement' => $customer->agreementDocument ? [
                'title' => $customer->agreementDocument->title ?? 'Customer Agreement',
                'file_url' => $customer->agreementDocument->file_path
                    ? $this->mediaUrl($customer->agreementDocument->file_path)
                    : null,
            ] : null,
        ], 'Device details retrieved.');
    }

    private function resolveCustomer(Request $request): Customer
    {
        $tokenable = $request->user('sanctum');

        abort_unless($tokenable instanceof Customer, 401, 'Unauthorized.');

        return $tokenable;
    }

    private function mediaUrl(?string $path): ?string
    {
        return $path ? route('api.kyc.public-media', ['path' => $path]) : null;
    }
}
