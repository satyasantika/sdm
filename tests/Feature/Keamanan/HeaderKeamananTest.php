<?php

test('header keamanan hadir pada respons web dan api', function () {
    foreach (['/admin/login', '/api/health'] as $url) {
        $this->get($url)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeaderMissing('Strict-Transport-Security');
    }
});

test('csp berjalan sebagai report-only secara bawaan dan dapat dipaksa atau dimatikan', function () {
    $this->get('/admin/login')->assertHeader('Content-Security-Policy-Report-Only')->assertHeaderMissing('Content-Security-Policy');

    config(['keamanan.csp_mode' => 'enforce']);
    $this->get('/admin/login')->assertHeader('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');

    config(['keamanan.csp_mode' => 'off']);
    $this->get('/admin/login')->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');
});

test('hsts hanya aktif di production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('/api/health')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('konfigurasi sesi aman dan debug mati di production', function () {
    expect(config('session.http_only'))->toBeTrue()->and(config('session.same_site'))->toBe('lax');

    $konfig = (static fn () => require config_path('session.php'))();
    expect($konfig)->toHaveKey('secure');
    expect(file_get_contents(config_path('session.php')))->toContain("env('APP_ENV') === 'production'");
});

test('cors api tertutup secara bawaan', function () {
    $this->get('/api/health', ['Origin' => 'https://evil.example.com'])->assertHeaderMissing('Access-Control-Allow-Origin');
});
