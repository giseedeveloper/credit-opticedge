<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use App\Services\RefurbishmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefurbishmentApiController extends Controller
{
    use ApiResponse;

    /**
     * Circular economy trigger receiving replacement LCDs + Batteries OPEX data.
     */
    public function refurbishDevice(Request $request, string $unitId, RefurbishmentService $refurbService): JsonResponse
    {
        $request->validate([
            'part_cost' => 'required|numeric|min:0',
            'grading' => 'required|string|in:Brand New,Grade A,Grade B,Grade C,Faulty',
            'notes' => 'required|string|max:500'
        ]);

        $unit = InventoryUnit::findOrFail($unitId);

        $freshUnit = $refurbService->processRefurbishment(
            $unit,
            (float) $request->part_cost,
            $request->grading,
            $request->notes
        );

        return $this->successResponse($freshUnit, "Unit lifecycle transitioned to: {$freshUnit->grading}");
    }
}
