<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('api/*')) {
            $response->headers->set(
                'X-Content-Type-Options',
                'nosniff'
            );

            $response->headers->set(
                'X-Frame-Options',
                'DENY'
            );

            $response->headers->set(
                'Referrer-Policy',
                'no-referrer'
            );

            $response->headers->set(
                'Content-Security-Policy',
                "frame-ancestors 'none'"
            );
        }

        return $response;
    }
}
