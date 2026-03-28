<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleApiController extends Controller
{
    use ApiResponse;

    /**
     * List all roles and their mapped permissions
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        return $this->successResponse($roles, "RBAC Roles retrieved.");
    }

    /**
     * Get 'Me' Permissions payload config for Frontends
     */
    public function currentAccess(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return $this->successResponse([
            'user_id' => $user->id,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], "Current session RBAC context payload.");
    }

    /**
     * Force sync an array of permission names to a particular Role
     */
    public function sync(Request $request, string $roleId): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $role = Role::findOrFail($roleId);
        
        // Sync operation via Spatie
        $role->syncPermissions($request->permissions);

        // Security Audit Logging
        activity('security')
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->event('permissions_synced')
            ->withProperties(['matrix' => $request->permissions])
            ->log("Admin updated RBAC permission matrix for role: {$role->name}");

        return $this->successResponse($role->load('permissions'), "Role permission matrix successfully synchronized.");
    }
}
