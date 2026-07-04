<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\EmailOtpService;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;

class EmailOtpChallengeController extends Controller
{
    public function send(TwoFactorLoginRequest $request, EmailOtpService $emailOtp)
    {
        if (! $request->hasChallengedUser()) {
            throw new HttpResponseException(redirect()->route('login'));
        }

        $user = $request->challengedUser();

        $emailOtp->send($user, $request);

        activity('security')
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Email OTP sent for MFA challenge.');

        return redirect()
            ->route('two-factor.login')
            ->with('mfa_challenge_method', 'email')
            ->with('email_otp_sent', __('We sent a verification code to your email.'));
    }

    public function store(
        TwoFactorLoginRequest $request,
        StatefulGuard $guard,
        EmailOtpService $emailOtp,
    ) {
        if (! $request->hasChallengedUser()) {
            throw new HttpResponseException(redirect()->route('login'));
        }

        $validated = $request->validate([
            'email_code' => ['required', 'digits:6'],
        ]);

        $user = $request->challengedUser();

        if (! $user->canUseEmailOtpForTwoFactorChallenge() || ! $emailOtp->verify($user, $request, $validated['email_code'])) {
            activity('security')
                ->performedOn($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('Email OTP MFA challenge failed.');

            return redirect()
                ->route('two-factor.login')
                ->with('mfa_challenge_method', 'email')
                ->withErrors(['email_code' => __('The email verification code is invalid or has expired.')]);
        }

        activity('security')
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Email OTP MFA challenge passed.');

        $request->session()->forget(['login.id', 'mfa_challenge_method']);

        $guard->login($user, $request->remember());

        $request->session()->regenerate();

        return app(TwoFactorLoginResponse::class);
    }
}
