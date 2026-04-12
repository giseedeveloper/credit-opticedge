<?php

namespace App\Http\Controllers\Api\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * @group Customer Portal — Authentication
 *
 * Phone + PIN authentication for customer self-service app.
 */
class CustomerAuthController extends Controller
{
    use ApiResponse;

    /**
     * Step 1 – Check if a phone number belongs to an eligible customer.
     *
     * Returns `has_pin` so the app knows whether to show "Set PIN" or "Enter PIN".
     */
    public function checkPhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:20'],
        ]);

        $phone = $this->normalizePhone($request->string('phone')->toString());

        $customer = Customer::where('phone', $phone)
            ->where('asset_release_status', 'released')
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'phone' => ['Namba hii haipatikani kwenye mfumo wetu.'],
            ]);
        }

        return $this->successResponse([
            'has_pin' => $customer->pin !== null,
            'customer_name' => $customer->first_name,
        ], 'Customer found.');
    }

    /**
     * Step 2a – First-time PIN setup (customer has no PIN yet).
     */
    public function setPin(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:20'],
            'new_pin' => ['required', 'string', 'min:4', 'max:6', 'confirmed'],
        ]);

        $phone = $this->normalizePhone($request->string('phone')->toString());

        $customer = Customer::where('phone', $phone)
            ->where('asset_release_status', 'released')
            ->whereNull('pin')
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'phone' => ['Mteja hayupo au tayari ana PIN.'],
            ]);
        }

        $customer->update(['pin' => $request->new_pin]);

        // Auto-login after PIN setup
        $customer->tokens()->where('name', 'customer-app')->delete();
        $token = $customer->createToken('customer-app', ['customer-portal'])->plainTextToken;

        return $this->successResponse([
            'customer' => $this->serializeProfile($customer->load('branch', 'vendor')),
            'token' => $token,
        ], 'PIN imewekwa. Karibu!');
    }

    /**
     * Step 2b – Login with phone + PIN.
     *
     * Authenticates the customer and returns a Sanctum token.
     * Only customers with a released asset may log in.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:20'],
            'pin' => ['required', 'string', 'min:4', 'max:6'],
        ]);

        $ip = $request->ip();
        $rateLimitKey = 'customer_login:'.$ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return $this->errorResponse("Too many login attempts. Try again in {$seconds} seconds.", 429);
        }

        $phone = $this->normalizePhone($request->string('phone')->toString());

        $customer = Customer::where('phone', $phone)
            ->where('asset_release_status', 'released')
            ->whereNotNull('pin')
            ->first();

        if (! $customer || ! Hash::check($request->pin, $customer->pin)) {
            RateLimiter::hit($rateLimitKey, 60);

            throw ValidationException::withMessages([
                'phone' => ['Invalid phone number or PIN.'],
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        $customer->tokens()->where('name', 'customer-app')->delete();
        $token = $customer->createToken('customer-app', ['customer-portal'])->plainTextToken;

        return $this->successResponse([
            'customer' => $this->serializeProfile($customer->load('branch', 'vendor')),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * Get authenticated customer profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $customer = $this->resolveCustomer($request)->load('branch', 'vendor', 'phoneModel.brand');

        return $this->successResponse(
            $this->serializeProfile($customer),
            'Profile retrieved.'
        );
    }

    /**
     * Change PIN.
     */
    public function changePin(Request $request): JsonResponse
    {
        $request->validate([
            'current_pin' => ['required', 'string'],
            'new_pin' => ['required', 'string', 'min:4', 'max:6', 'confirmed'],
        ]);

        $customer = $this->resolveCustomer($request);

        if (! Hash::check($request->current_pin, $customer->pin)) {
            throw ValidationException::withMessages([
                'current_pin' => ['Current PIN is incorrect.'],
            ]);
        }

        $customer->update(['pin' => $request->new_pin]);

        return $this->successResponse([], 'PIN changed successfully.');
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')->currentAccessToken()->delete();

        return $this->successResponse([], 'Logged out successfully.');
    }

    /**
     * Resolve the authenticated Customer model from the Sanctum token.
     */
    private function resolveCustomer(Request $request): Customer
    {
        $tokenable = $request->user('sanctum');

        abort_unless($tokenable instanceof Customer, 401, 'Unauthorized.');

        return $tokenable;
    }

    private function normalizePhone(string $raw): string
    {
        $phone = preg_replace('/[^0-9]/', '', $raw);

        if (str_starts_with($phone, '0') && strlen($phone) >= 10) {
            $phone = '255'.substr($phone, 1);
        } elseif (strlen($phone) === 9) {
            $phone = '255'.$phone;
        }

        return $phone;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProfile(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'full_name' => $customer->full_name,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'phone' => $customer->phone,
            'phone_display' => $customer->formattedPhone('phone'),
            'email' => $customer->email,
            'gender' => $customer->gender,
            'nida_number' => $customer->nida_number,
            'headshot_url' => $customer->headshot_photo_path
                ? $this->mediaUrl($customer->headshot_photo_path)
                : null,
            'branch' => $customer->branch ? [
                'id' => $customer->branch->id,
                'name' => $customer->branch->name,
                'phone' => $customer->branch->phone,
                'region' => $customer->branch->region,
                'address' => $customer->branch->address,
            ] : null,
            'vendor' => $customer->vendor ? [
                'id' => $customer->vendor->id,
                'name' => $customer->vendor->name,
                'phone' => $customer->vendor->phone,
                'address' => $customer->vendor->address,
            ] : null,
        ];
    }

    private function mediaUrl(?string $path): ?string
    {
        return $path ? route('api.kyc.public-media', ['path' => $path]) : null;
    }
}
