<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Stock Control
 *
 * Endpoints for dealer shop-floor terminals to live-search IMEIs.
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
     * Dealer stock list
     *
     * Returns inventory assigned to the authenticated user's dealer context.
     */
    public function vendorStock(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealerId = $user->dealer_id ?: $user->managedDealers()->value('id');

        if (! $dealerId) {
            return $this->errorResponse('No valid dealer mapping found.', 403);
        }

        $stock = InventoryUnit::with('phoneModel')
            ->where('dealer_id', $dealerId)
            ->where('status', 'vendor_stock')
            ->paginate(50);

        return $this->successResponse($stock, 'Dealer stock retrieved');
    }
}
