<?php

use Illuminate\Http\Middleware\TrustProxies;

afterEach(fn () => TrustProxies::flushState());

/** Landing page, panduan statis, dan dukungan sub-path https://supportfkip.unsil.ac.id/sdm. */
function panggilDiSdm(string $uri)
{
    // Meniru nginx/reverse proxy: awalan dipotong dari URI dan dikirim sebagai X-Forwarded-Prefix.
    TrustProxies::at('*');

    return test()->call('GET', $uri, [], [], [], [
        'HTTP_X_FORWARDED_PREFIX' => '/sdm',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'supportfkip.unsil.ac.id',
        'HTTP_X_FORWARDED_PORT' => '443',
    ]);
}

test('landing page tampil untuk tamu dan menaut ke login serta panduan', function () {
    $this->get('/')->assertOk()->assertSee('Masuk Dosen')->assertSee('Masuk Admin')->assertSee('panduan/index.html', false);
});

test('url dibangun dengan awalan /sdm dan https saat dilayani di sub-path', function () {
    $html = panggilDiSdm('/admin/login')->assertOk()->getContent();

    expect($html)->toContain('https://supportfkip.unsil.ac.id/sdm/admin/password-reset/request');
});

test('landing di sub-path menaut ke /sdm', function () {
    $html = panggilDiSdm('/')->assertOk()->getContent();

    expect($html)->toContain('https://supportfkip.unsil.ac.id/sdm/saya')->toContain('https://supportfkip.unsil.ac.id/sdm/panduan/index.html');
});

test('halaman login panel memuat aset dan endpoint di bawah /sdm', function () {
    $html = panggilDiSdm('/admin/login')->assertOk()->getContent();

    expect($html)->toContain('https://supportfkip.unsil.ac.id/sdm/css/filament')
        ->and($html)->toContain('https://supportfkip.unsil.ac.id/sdm/livewire')
        ->and($html)->not->toContain('https://supportfkip.unsil.ac.id/css/filament');
});

test('panduan statis lengkap: setiap gambar dan tautan internal ada', function () {
    $dir = public_path('panduan');
    $halaman = glob($dir.'/*.html');

    expect(array_map('basename', $halaman))->toContain('index.html', 'admin-kepegawaian.html', 'admin-prodi.html', 'pimpinan.html', 'dosen-tendik.html', 'super-admin.html');

    foreach ($halaman as $berkas) {
        $isi = file_get_contents($berkas);
        preg_match_all('/(?:src|href)="([^"#]+)"/', $isi, $cocok);

        foreach ($cocok[1] as $rujukan) {
            if (str_starts_with($rujukan, 'http') || str_starts_with($rujukan, '../')) {
                continue;
            }
            expect(file_exists($dir.'/'.$rujukan))->toBeTrue(basename($berkas).' → '.$rujukan);
        }
    }
});

test('panduan tidak memuat data sensitif atau kata sandi demo', function () {
    foreach (glob(public_path('panduan').'/*.html') as $berkas) {
        expect(file_get_contents($berkas))->not->toContain('Demo-Sandi')->not->toContain('JBSWY3DPEHPK3PXP');
    }
});

test('landing memuat bagian fitur, peran, dan privasi', function () {
    $this->get('/')->assertOk()->assertSee('Fitur')->assertSee('Pengingat tenggat')->assertSee('Privasi &amp; keamanan', false)->assertSee('panduan/dosen-tendik.html', false);
});

test('halaman login kedua panel memuat panel merek yang senada', function () {
    $this->get('/admin/login')->assertOk()->assertSee('sdm-samping', false)->assertSee('Panel Admin &amp; Pimpinan', false);
    $this->get('/saya/login')->assertOk()->assertSee('sdm-samping', false)->assertSee('Data Saya');
});
