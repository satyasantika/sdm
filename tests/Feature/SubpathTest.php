<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;

afterEach(fn () => URL::forceRootUrl(null));

function terapkanUrlSubpath(): void
{
    $provider = app()->getProvider(AppServiceProvider::class);
    (new ReflectionMethod($provider, 'paksaUrlSubpath'))->invoke($provider);
}

test('app.url berawalan memaksa route dan asset memakai awalan /sdm', function () {
    config(['app.url' => 'https://supportfkip.unsil.ac.id/sdm']);

    terapkanUrlSubpath();

    expect(route('filament.admin.auth.login'))->toStartWith('https://supportfkip.unsil.ac.id/sdm/')
        ->and(asset('css/app.css'))->toStartWith('https://supportfkip.unsil.ac.id/sdm/');
});

test('app.url tanpa path tidak memaksa awalan', function () {
    config(['app.url' => 'http://localhost']);

    terapkanUrlSubpath();

    expect(route('filament.admin.auth.login'))->not->toContain('/sdm');
});

test('contoh env menetapkan cookie dan path sesi per aplikasi', function () {
    $env = file_get_contents(base_path('.env.example'));

    expect($env)->toContain('SESSION_PATH=/sdm')->toContain('SESSION_COOKIE=sdm_session');
});

test('build produksi memberi ASSET_URL subpath ke Vite agar url() CSS berawalan /sdm', function () {
    $dockerfile = file_get_contents(base_path('docker/Dockerfile'));
    $compose = file_get_contents(base_path('compose.production.yaml'));

    expect($dockerfile)->toContain('ARG ASSET_URL=https://supportfkip.unsil.ac.id/sdm')
        ->and($dockerfile)->toMatch('/ASSET_URL="\$ASSET_URL" npm run build/')
        ->and(substr_count($compose, 'ASSET_URL: ${ASSET_URL:-https://supportfkip.unsil.ac.id/sdm}'))->toBe(2);
});
