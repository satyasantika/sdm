<?php

use Symfony\Component\Yaml\Yaml;

/**
 * Produksi Profil Ringan (STANDAR-TEKNIS §1b dan §7): MariaDB NATIVE di host, tanpa container database,
 * Redis, atau Horizon/queue; setiap container berbatas memori dan lognya dirotasi.
 */
function komposProduksi(): array
{
    return Yaml::parseFile(base_path('compose.production.yaml'));
}

it('hanya memuat layanan app, web, dan scheduler', function () {
    expect(array_keys(komposProduksi()['services']))->toEqualCanonicalizing(['php', 'web', 'scheduler']);
});

it('tidak memuat container database, redis, atau queue', function () {
    $kompos = komposProduksi();

    foreach (['queue', 'redis', 'horizon', 'db', 'mysql', 'mariadb'] as $layanan) {
        expect($kompos['services'])->not->toHaveKey($layanan);
    }

    foreach (['redis', 'mysql', 'mariadb', 'db-data'] as $volume) {
        expect($kompos['volumes'] ?? [])->not->toHaveKey($volume);
    }

    // hasil parse (tanpa komentar): tidak ada image/perintah Redis, Horizon, atau MySQL
    expect(json_encode($kompos))->not->toMatch('/horizon|redis|mysql/i');
});

it('menghubungkan container ke basis data host lewat host-gateway', function () {
    foreach (['php', 'scheduler'] as $nama) {
        expect(komposProduksi()['services'][$nama]['extra_hosts'])->toContain('host.docker.internal:host-gateway')
            ->and(komposProduksi()['services'][$nama]['environment']['DB_CONNECTION'])->toBe('mariadb')
            ->and(komposProduksi()['services'][$nama]['environment']['DB_HOST'])->toBe('host.docker.internal');
    }
});

it('memakai driver database untuk cache, sesi, dan antrean di container', function () {
    $env = komposProduksi()['services']['php']['environment'];

    expect($env['CACHE_STORE'])->toBe('database')
        ->and($env['SESSION_DRIVER'])->toBe('database')
        ->and($env['QUEUE_CONNECTION'])->toBe('database');
});

it('menjalankan penjadwal sebagai pemicu worker setelah app sehat', function () {
    $penjadwal = komposProduksi()['services']['scheduler'];

    expect($penjadwal['command'])->toBe(['php', 'artisan', 'schedule:work'])
        ->and($penjadwal['depends_on']['php']['condition'])->toBe('service_healthy')
        ->and($penjadwal['stop_grace_period'])->toBe('60s');
});

it('membatasi memori dan merotasi log setiap container', function () {
    foreach (komposProduksi()['services'] as $layanan) {
        expect($layanan)->toHaveKey('mem_limit')
            ->and($layanan['logging']['driver'])->toBe('json-file')
            ->and($layanan['logging']['options'])->toBe(['max-size' => '10m', 'max-file' => '3']);
    }
});

it('menjaga Dockerfile tanpa Redis dan berbasis PHP 8.5 dengan klien mariadb', function () {
    $dockerfile = file_get_contents(base_path('docker/Dockerfile'));

    expect($dockerfile)->not->toMatch('/redis|horizon|mysql-client/i')
        ->and($dockerfile)->toContain('FROM php:8.5-fpm-alpine')
        ->and($dockerfile)->toContain('mariadb-client')
        ->and($dockerfile)->toContain('pdo_mysql');
});

it('menunggu database MariaDB di entrypoint, bukan hanya driver mysql', function () {
    $skrip = file_get_contents(base_path('docker/entrypoint.sh'));

    expect($skrip)->toContain('mariadb | mysql)')
        ->and($skrip)->not->toContain('= "mysql" ]');
});

it('menyetel PHP-FPM ondemand dengan empat worker', function () {
    $pool = file_get_contents(base_path('docker/php/www.conf'));

    expect($pool)->toMatch('/^pm = ondemand$/m')
        ->and($pool)->toMatch('/^pm\.max_children = 4$/m')
        ->and($pool)->toMatch('/^pm\.process_idle_timeout = 10s$/m');
});

it('tidak mengarahkan nginx ke Horizon', function () {
    expect(file_get_contents(base_path('docker/nginx/default.conf')))->not->toMatch('/horizon/i');
});
