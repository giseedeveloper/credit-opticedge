<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactorAuthenticationIsConfigured
{
    /**
     * Keep admins who just passed password login inside the MFA setup flow.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! (bool) $request->session()->get('admin_mfa_setup_required', false)) {
            return $next($request);
        }

        if (! $user->mustConfigureTwoFactorAuthentication()) {
            $request->session()->forget('admin_mfa_setup_required');

            return $next($request);
        }

        if ($request->routeIs(
            'admin.mfa.setup',
            'security.edit',
            'password.confirm',
            'password.confirm.store',
            'logout',
            'two-factor.*',
            'verification.*',
        ) || $request->is('livewire*')) {
            return $next($request);
        }

        return redirect()->route('admin.mfa.setup')->with(
            'mfa_setup_required',
            'Admin accounts must set up multi-factor authentication before using the console.',
        );
    }
}
