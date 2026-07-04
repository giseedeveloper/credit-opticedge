<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\Auth\EmailOtpCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailOtpService
{
    private const CODE_TTL_SECONDS = 300;

    private const RESEND_COOLDOWN_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    public function send(User $user, Request $request): void
    {
        if (! $user->canUseEmailOtpForTwoFactorChallenge()) {
            throw ValidationException::withMessages([
                'email_code' => __('Email OTP is not enabled for this account.'),
            ]);
        }

        if (Cache::has($this->cooldownKey($user, $request))) {
            throw ValidationException::withMessages([
                'email_code' => __('Please wait a minute before requesting another email code.'),
            ]);
        }

        $code = (string) random_int(100000, 999999);

        Cache::put($this->challengeKey($user, $request), [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addSeconds(self::CODE_TTL_SECONDS));

        Cache::put($this->cooldownKey($user, $request), true, now()->addSeconds(self::RESEND_COOLDOWN_SECONDS));

        $user->notify(new EmailOtpCodeNotification($code, (int) ceil(self::CODE_TTL_SECONDS / 60)));
    }

    public function verify(User $user, Request $request, string $code): bool
    {
        $challenge = Cache::get($this->challengeKey($user, $request));

        if (! is_array($challenge) || ! isset($challenge['hash'])) {
            return false;
        }

        if (($challenge['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            $this->clear($user, $request);

            return false;
        }

        if (! Hash::check($code, $challenge['hash'])) {
            $challenge['attempts'] = ($challenge['attempts'] ?? 0) + 1;

            Cache::put($this->challengeKey($user, $request), $challenge, now()->addSeconds(self::CODE_TTL_SECONDS));

            return false;
        }

        $this->clear($user, $request);

        return true;
    }

    public function clear(User $user, Request $request): void
    {
        Cache::forget($this->challengeKey($user, $request));
        Cache::forget($this->cooldownKey($user, $request));
    }

    private function challengeKey(User $user, Request $request): string
    {
        return 'auth:email-otp:challenge:'.$user->getKey();
    }

    private function cooldownKey(User $user, Request $request): string
    {
        return 'auth:email-otp:cooldown:'.$user->getKey();
    }
}
