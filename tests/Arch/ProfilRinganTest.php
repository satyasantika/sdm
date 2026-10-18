<?php

/**
 * Profil Ringan (STANDAR-TEKNIS §1b): contoh env tidak boleh kembali ke Redis/SQLite, dan Horizon tidak terpasang.
 */
dataset('contoh env', ['.env.example', '.env.production.example']);

it('memakai MariaDB dan driver database pada contoh env', function (string $berkas) {
    $isi = file_get_contents(base_path($berkas));

    expect($isi)
        ->toContain("\nDB_CONNECTION=mariadb\n")
        ->toContain("\nDB_HOST=host.docker.internal\n")
        ->toContain("\nDB_DATABASE=db_sdm\n")
        ->toContain("\nSESSION_DRIVER=database\n")
        ->toContain("\nQUEUE_CONNECTION=database\n")
        ->toContain("\nCACHE_STORE=database\n")
        ->toContain("\nAPP_KODE=sdm\n")
        ->not->toMatch('/^REDIS_/m')
        ->not->toMatch('/^HORIZON_/m')
        ->not->toContain('=redis')
        ->not->toContain('DB_CONNECTION=sqlite');
})->with('contoh env');

it('menguji terhadap MariaDB db_sdm_testing, bukan SQLite', function () {
    $isi = file_get_contents(base_path('phpunit.xml'));

    expect($isi)
        ->toContain('name="DB_CONNECTION" value="mariadb"')
        ->toContain('name="DB_DATABASE" value="db_sdm_testing"')
        ->toContain('name="DB_HOST" value="host.docker.internal"')
        ->not->toContain('sqlite')
        ->not->toContain(':memory:');
});

it('tidak memasang Horizon', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composer['require'])->not->toHaveKey('laravel/horizon')
        ->and($composer['require']['php'])->toBe('^8.5')
        ->and(file_exists(config_path('horizon.php')))->toBeFalse()
        ->and(file_exists(app_path('Providers/HorizonServiceProvider.php')))->toBeFalse()
        ->and(file_get_contents(base_path('bootstrap/providers.php')))->not->toContain('Horizon');
});

it('tidak merujuk kelas Horizon di kode aplikasi', function () {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $berkas) {
        if ($berkas->getExtension() === 'php') {
            expect(file_get_contents($berkas->getPathname()))->not->toContain('Laravel\\Horizon');
        }
    }
});

it('tidak mendefinisikan koneksi Redis di config', function () {
    expect(config('database.redis'))->toBeNull()
        ->and(config('queue.connections.redis'))->toBeNull()
        ->and(config('cache.stores.redis'))->toBeNull();
});
