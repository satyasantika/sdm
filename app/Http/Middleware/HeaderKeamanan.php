<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Header keamanan dasar untuk seluruh respons web dan API (K-15). */
class HeaderKeamanan
{
    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);
        $h = $respons->headers;

        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction()) {
            $h->set('Strict-Transport-Security', 'max-age='.config('keamanan.hsts_detik').'; includeSubDomains');
        }

        match (config('keamanan.csp_mode')) {
            'enforce' => $h->set('Content-Security-Policy', (string) config('keamanan.csp')),
            'report-only' => $h->set('Content-Security-Policy-Report-Only', (string) config('keamanan.csp')),
            default => null,
        };

        return $respons;
    }
}
