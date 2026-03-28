<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\Activity;
use Symfony\Component\HttpFoundation\Response;

class AuditTrailMiddleware
{
    /**
     * Sensitive route name prefixes that must always be logged.
     *
     * @var array<int, string>
     */
    private array $sensitivePatterns = [
        'loans.',
        'repayments.',
        'customers.',
        'verifications.',
        'inventory.',
        'vendors.',
        'transactions.',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Auth::check()) {
            return $response;
        }

        $routeName = $request->route()?->getName() ?? '';

        if (! $this->isSensitiveRoute($routeName)) {
            return $response;
        }

        $this->logActivity($request, $routeName, $response->getStatusCode());

        return $response;
    }

    private function isSensitiveRoute(string $routeName): bool
    {
        foreach ($this->sensitivePatterns as $pattern) {
            if (str_starts_with($routeName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function logActivity(Request $request, string $routeName, int $statusCode): void
    {
        $user = Auth::user();

        activity('audit_trail')
            ->causedBy($user)
            ->withProperties([
                'route'       => $routeName,
                'method'      => $request->method(),
                'url'         => $request->fullUrl(),
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'status_code' => $statusCode,
                'payload'     => $this->sanitizePayload($request->except(['password', 'password_confirmation', '_token'])),
            ])
            ->log("[{$request->method()}] {$routeName} by {$user->name} (#{$user->id})");
    }

    /**
     * Truncate large payloads to prevent log bloat.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        return collect($payload)
            ->map(fn ($value) => is_string($value) && strlen($value) > 500
                ? substr($value, 0, 500) . '...'
                : $value)
            ->toArray();
    }
}
