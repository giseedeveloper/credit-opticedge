<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use App\Models\Vendor;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Stock Control
 *
 * Endpoints for vendor shop floor terminals to live-search IMEIs.
 */
class StockApiController extends Controller
{
    use ApiResponse;

    /**
     * Search IMEI
     *
     * Provides real-time lookup.
     *
     * @queryParam imei required The 15-digit code.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'imei' => ['required', 'string', 'digits_between:14,20'],
        ]);

        $imei = $request->query('imei');

        $unit = InventoryUnit::with('phoneModel.brand')
            ->where('imei_1', $imei)
            ->orWhere('imei_2', $imei)
            ->first();

        if (! $unit) {
            return $this->errorResponse('Device not found', 404);
        }

        return $this->successResponse($unit, 'Device located');
    }

    /**
     * Vendor Stock List
     *
     * Returns all assigned inventory to a specific vendor ID.
     */
    public function vendorStock(Request $request): JsonResponse
    {
        $user = $request->user();
        $vendorId = $user->managedVendors()->value('id');

        if (! $vendorId && $user->branch_id) {
            $vendorId = Vendor::where('branch_id', $user->branch_id)->value('id');
        }

        if (! $vendorId) {
            return $this->errorResponse('No valid vendor hierarchy found.', 403);
        }

        $stock = InventoryUnit::with('phoneModel')
            ->where('vendor_id', $vendorId)
            ->where('status', 'vendor_stock')
            ->paginate(50);

        return $this->successResponse($stock, 'Vendor stock retrieved');
    }
}
