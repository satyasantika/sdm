<?php

use Illuminate\Support\Facades\Route;

/** K-03: hanya rute publik yang diizinkan boleh tanpa autentikasi. */
test('semua rute selain daftar publik memiliki middleware autentikasi', function () {
    $publik = [
        '/', 'up', 'api/health', 'sanctum/csrf-cookie', '_boost/browser-logs',
        'admin/login', 'saya/login', 'admin/password-reset/request', 'admin/password-reset/reset',
        'saya/password-reset/request', 'saya/password-reset/reset',
    ];
    $terbuka = [];

    foreach (Route::getRoutes() as $rute) {
        $uri = $rute->uri();
        $middleware = implode(',', array_map(fn ($m) => is_string($m) ? $m : 'closure', $rute->gatherMiddleware()));

        if (in_array($uri, $publik, true) || str_starts_with($uri, 'livewire-') || str_starts_with($uri, 'livewire/') || str_starts_with($uri, 'storage/')) {
            continue;
        }
        if (preg_match('#^(admin|saya)/(logout)$#', $uri) || str_starts_with($uri, 'filament/')) {
            continue; // logout dan unduhan Filament memeriksa pemilik di dalam controller (diuji terpisah)
        }

        if (! preg_match('/Authenticate|(^|,)auth(:|,|$)|(^|,)auth$/', $middleware)) {
            $terbuka[] = implode('|', $rute->methods()).' '.$uri;
        }
    }

    expect($terbuka)->toBe([]);
});

test('rute livewire dan storage tidak membuka berkas atau data tanpa login', function () {
    expect($this->get('/storage/apa-saja.txt')->status())->toBeIn([403, 404]);
    expect($this->get('/filament/exports/'.fake()->uuid().'/download')->status())->toBeIn([401, 403, 404, 302]);
    $this->get('/horizon')->assertForbidden();
    $this->get('/admin')->assertRedirect();
    $this->get('/saya')->assertRedirect();
    $this->getJson('/api/user')->assertUnauthorized();
});
