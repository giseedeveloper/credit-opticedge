<?php

namespace App\Http\Controllers\Api\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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

        $phones = $this->phoneVariants($request->string('phone')->toString());

        $customer = Customer::query()
            ->whereIn('phone', $phones)
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'phone' => ['Namba hii haipatikani kwenye mfumo wetu.'],
            ]);
        }

        if ($customer->isAssetReleased()) {
            return $this->successResponse([
                'eligibility' => 'portal_active',
                'has_pin' => $customer->pin !== null,
                'customer_name' => $customer->first_name,
                'kyc_status' => $customer->kyc_status,
                'asset_release_status' => $customer->asset_release_status,
            ], 'Customer found.');
        }

        if ($this->hasTrackableKyc($customer)) {
            return $this->successResponse([
                'eligibility' => 'kyc_in_progress',
                'has_pin' => false,
                'customer_name' => $customer->first_name,
                'kyc_status' => $customer->kyc_status,
                'kyc_stage' => $customer->kyc_stage,
                'asset_release_status' => $customer->asset_release_status,
                'portal_message' => $this->kycProgressMessage($customer),
            ], 'KYC in progress.');
        }

        throw ValidationException::withMessages([
            'phone' => ['Namba hii haipatikani kwenye mfumo wetu.'],
        ]);
    }

    /**
     * Read-only KYC progress for customers waiting on HQ (no portal login yet).
     */
    public function kycStatus(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:20'],
        ]);

        $phones = $this->phoneVariants($request->string('phone')->toString());

        $customer = Customer::query()
            ->with('latestKycVerification')
            ->whereIn('phone', $phones)
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'phone' => ['Namba hii haipatikani kwenye mfumo wetu.'],
            ]);
        }

        if ($customer->isAssetReleased()) {
            return $this->successResponse([
                'eligibility' => 'portal_active',
                'kyc_status' => $customer->kyc_status,
                'kyc_stage' => $customer->kyc_stage,
                'asset_release_status' => $customer->asset_release_status,
                'portal_message' => 'Kifaa chako kimetolewa. Unaweza kuingia kwenye programu ya mteja.',
            ]);
        }

        if (! $this->hasTrackableKyc($customer)) {
            throw ValidationException::withMessages([
                'phone' => ['Hakuna maombi ya KYC yanayoendelea kwa namba hii.'],
            ]);
        }

        $verification = $customer->latestKycVerification;

        return $this->successResponse([
            'eligibility' => 'kyc_in_progress',
            'customer_name' => $customer->first_name,
            'kyc_status' => $customer->kyc_status,
            'kyc_stage' => $customer->kyc_stage,
            'asset_release_status' => $customer->asset_release_status,
            'verification' => $verification ? [
                'stage' => $verification->stage,
                'status' => $verification->status,
                'face_match_status' => $verification->face_match_status,
                'stage1_status' => $verification->stage1_status,
                'stage2_status' => $verification->stage2_status,
                'stage3_status' => $verification->stage3_status,
                'stage4_status' => $verification->stage4_status,
            ] : null,
            'portal_message' => $this->kycProgressMessage($customer),
        ]);
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

        $phones = $this->phoneVariants($request->string('phone')->toString());

        $customer = Customer::whereIn('phone', $phones)
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
            'customer' => $this->serializeProfile($customer->load('dealer')),
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

        $phones = $this->phoneVariants($request->string('phone')->toString());

        $customer = Customer::whereIn('phone', $phones)
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
            'customer' => $this->serializeProfile($customer->load('dealer')),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * Get authenticated customer profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $customer = $this->resolveCustomer($request)->load('dealer', 'phoneModel.brand');

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

    /**
     * Return all plausible phone formats so the query matches
     * regardless of how the number was stored in the database.
     *
     * @return string[]
     */
    private function phoneVariants(string $raw): array
    {
        $digits = preg_replace('/[^0-9]/', '', $raw);

        // Derive the 9-digit core (without country code or leading zero)
        if (str_starts_with($digits, '255') && strlen($digits) >= 12) {
            $core = substr($digits, 3);          // 255xxxxxxxxx → xxxxxxxxx
        } elseif (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            $core = substr($digits, 1);           // 0xxxxxxxxx → xxxxxxxxx
        } elseif (strlen($digits) === 9) {
            $core = $digits;                      // xxxxxxxxx
        } else {
            return [$digits];                     // fallback, return as-is
        }

        return [
            '0'.$core,       // 07xxxxxxxx
            '255'.$core,     // 2557xxxxxxxx
            '+255'.$core,    // +2557xxxxxxxx
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProfile(Customer $customer): array
    {
        $dealerPayload = $customer->dealer ? [
            'id' => $customer->dealer->id,
            'name' => $customer->dealer->name,
            'phone' => $customer->dealer->phone,
            'address' => $customer->dealer->address,
        ] : null;

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
            'branch' => null,
            'dealer' => $dealerPayload,
            'vendor' => $dealerPayload,
        ];
    }

    private function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return URL::temporarySignedRoute(
            'api.kyc.public-media',
            now()->addMinutes(15),
            ['path' => $path]
        );
    }

    private function hasTrackableKyc(Customer $customer): bool
    {
        if ($customer->latestKycVerification()->exists()) {
            return true;
        }

        return filled($customer->kyc_status)
            && ! in_array($customer->kyc_status, ['draft'], true);
    }

    private function kycProgressMessage(Customer $customer): string
    {
        return match ($customer->kyc_status) {
            'rejected' => 'Maombi yako yamekataliwa. Wasiliana na duka lako kwa msaada.',
            'approved' => 'KYC imeidhinishwa. Tunasubiri hatua ya kutoa kifaa.',
            'pending' => 'Maombi yako yako chini ya ukaguzi (hatua '.($customer->kyc_stage ?? 1).').',
            default => 'Maombi yako yako katika mfumo. Tutakujulisha baada ya ukaguzi.',
        };
    }
}
