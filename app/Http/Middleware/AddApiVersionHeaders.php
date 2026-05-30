<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddApiVersionHeaders
{
    public function handle(Request $request, Closure $next, string $version = '1'): Response
    {
        $response = $next($request);

        $response->headers->set('X-Api-Version', $version);

        if ($version === '1') {
            $response->headers->set('Deprecation', 'true');
            $response->headers->set('Link', '</api/v2/meta>; rel="successor-version"');
        }

        return $response;
    }
}
