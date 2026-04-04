<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * APIs for managing user and vendor tokens.
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Authenticate and Issue Token
     *
     * Logs the user in and hands back a Sanctum API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login_identifier' => 'required|string',
            'password' => 'required',
        ]);

        $ip = $request->ip();
        $rateLimitKey = 'login_attempts:'.$ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            activity('security')
                ->withProperties(['ip' => $ip, 'identifier' => $request->login_identifier])
                ->log("Brute force protection triggered. Locked out for {$seconds} seconds.");

            return $this->errorResponse("Too many login attempts. Please try again in {$seconds} seconds.", 429);
        }

        $loginId = $request->login_identifier;
        $field = filter_var($loginId, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if ($field === 'phone') {
            $phone = preg_replace('/[^0-9]/', '', $loginId);
            if (str_starts_with($phone, '0')) {
                $phone = '255'.substr($phone, 1);
            } elseif (strlen($phone) == 9) {
                $phone = '255'.$phone;
            }
            $loginId = $phone;
        }

        $user = User::where($field, $loginId)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($rateLimitKey, 60);

            activity('security')
                ->withProperties(['ip' => $ip, 'identifier' => $request->login_identifier])
                ->log('Failed API Login Attempt.');

            throw ValidationException::withMessages([
                'login_identifier' => ['Invalid credentials provided.'],
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        $tokenName = $request->header('User-Agent', 'mobile-app');
        $token = $user->createToken($tokenName)->plainTextToken;

        activity('security')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['ip' => $ip])
            ->log('Successful API Login via API Token Generation.');

        return $this->successResponse([
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Get Authenticated FO Profile
     *
     * Returns the current user's profile, role, branch, and key permissions
     * so the mobile app can gate features at login time.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('branch');

        $canRegister = $user->canAccess('loans.create');

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'roles' => $user->getRoleNames(),
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'name' => $user->branch->name,
            ] : null,
            'avatar_url' => $user->avatar_url,
            'is_active' => $user->is_active,
            'permissions' => [
                'can_register_customers' => $canRegister,
                'is_admin' => $user->isAdmin(),
            ],
        ], 'Profile retrieved.');
    }

    /**
     * Revoke Token
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse([], 'Logged out successfully');
    }
}
