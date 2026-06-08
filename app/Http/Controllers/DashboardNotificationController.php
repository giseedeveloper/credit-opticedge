<?php

namespace App\Http\Controllers;

use App\Services\DashboardNotificationService;
use Illuminate\Http\JsonResponse;

class DashboardNotificationController extends Controller
{
    public function __invoke(DashboardNotificationService $notifications): JsonResponse
    {
        abort_unless(auth()->check(), 401);

        return response()->json([
            'success' => true,
            'data' => $notifications->feed(auth()->user()),
        ]);
    }
}
