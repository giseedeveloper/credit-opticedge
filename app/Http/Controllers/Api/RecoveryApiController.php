<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecoveryTicket;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecoveryApiController extends Controller
{
    use ApiResponse;

    /**
     * Fetch tickets for field recovery officers
     */
    public function fieldTickets(Request $request): JsonResponse
    {
        $user = $request->user();

        // Enforce role logic, currently returning all tickets assigned to the logged-in agent
        $tickets = RecoveryTicket::with(['loan.customer', 'loan.inventoryUnit'])
            ->where('assigned_agent_id', $user->id)
            ->whereIn('status', ['open', 'assigned'])
            ->paginate(20);

        return $this->successResponse($tickets, "Field recovery assignments pulled.");
    }
}
