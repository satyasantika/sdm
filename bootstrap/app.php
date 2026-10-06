<?php

use App\Http\Middleware\HeaderKeamanan;
use App\Http\Middleware\TambahVersiApi;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
        // Di belakang reverse proxy kampus (TLS dihentikan di proxy): isi TRUSTED_PROXIES dengan IP/CIDR proxy, dipisah koma.
        $middleware->trustProxies(
            at: in_array(env('TRUSTED_PROXIES'), ['*', '**'], true) ? env('TRUSTED_PROXIES') : (env('TRUSTED_PROXIES') ? array_map('trim', explode(',', (string) env('TRUSTED_PROXIES'))) : null),
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PREFIX,
        );
        $middleware->append(HeaderKeamanan::class);
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'api.versi' => TambahVersiApi::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
