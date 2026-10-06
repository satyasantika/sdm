<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Menambahkan header X-Api-Version pada setiap respons API v1. */
class TambahVersiApi
{
    public function handle(Request $request, Closure $next, string $versi = '1'): Response
    {
        $respons = $next($request);
        $respons->headers->set('X-Api-Version', $versi);

        return $respons;
    }
}
