<?php

use Filament\Actions\Exports\Exporter;
use Filament\Actions\Imports\Importer;

function berkasPhpDi(string $direktori): Generator
{
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($direktori)) as $berkas) {
        if ($berkas->getExtension() === 'php') {
            yield $berkas->getPathname() => file_get_contents($berkas->getPathname());
        }
    }
}

it('hanya memakai antrean default dan berat', function () {
    foreach (berkasPhpDi(app_path()) as $jalur => $isi) {
        preg_match_all('/onQueue\(\s*[\'"]([^\'"]+)[\'"]/', $isi, $cocok);

        foreach ($cocok[1] as $nama) {
            expect($nama)->toBeIn(['default', 'berat'], str_replace(app_path().'/', '', $jalur)." memakai antrean '{$nama}'");
        }
    }
});

it('mengarahkan semua importer dan exporter Filament ke antrean berat di koneksi database-berat', function () {
    $kelas = collect([...glob(app_path('Filament/Exports/*.php')), ...glob(app_path('Filament/Imports/*.php'))])
        ->map(fn (string $berkas) => 'App\\Filament\\'.basename(dirname($berkas)).'\\'.basename($berkas, '.php'))
        ->filter(fn (string $nama) => is_subclass_of($nama, Exporter::class) || is_subclass_of($nama, Importer::class));

    expect($kelas)->not->toBeEmpty();

    foreach ($kelas as $nama) {
        $objek = (new ReflectionClass($nama))->newInstanceWithoutConstructor();

        expect($objek->getJobQueue())->toBe('berat', $nama)
            ->and($objek->getJobConnection())->toBe('database-berat', $nama);
    }
});

it('tidak memakai Redis di kode aplikasi', function () {
    foreach (berkasPhpDi(app_path()) as $isi) {
        expect($isi)
            ->not->toContain('Redis::')
            ->not->toContain('Illuminate\\Support\\Facades\\Redis');
    }
});

it('membatasi Cache::lock pada pemakaian yang terpaksa (lock lintas permintaan)', function () {
    // §1b: penomoran/transaksi bersaing memakai DB::transaction + lockForUpdate + UNIQUE. Cache::lock hanya untuk
    // kunci yang harus bertahan melewati satu permintaan (impor BKD per semester sampai ImportCompleted, BR-22)
    // dan job tunggal HitungUlangTanggalPensiun. Keduanya dicatat sebagai keputusan terbuka di laporan Fase B.
    $diizinkan = [
        'Filament/Admin/Resources/Bkd/Pages/ListBkd.php',
        'Listeners/SelesaikanImporBkd.php',
        'Jobs/HitungUlangTanggalPensiun.php',
    ];

    foreach (berkasPhpDi(app_path()) as $jalur => $isi) {
        $relatif = str_replace(app_path().'/', '', $jalur);

        if (in_array($relatif, $diizinkan, true)) {
            continue;
        }

        expect($isi)->not->toContain('Cache::lock')->not->toContain('Cache::restoreLock');
    }
});

it('tidak menyaring driver basis data hanya dengan mysql', function () {
    // 'mariadb' adalah driver tersendiri di Laravel 13; perbandingan === 'mysql' diam-diam melewatkannya.
    foreach (berkasPhpDi(app_path()) as $jalur => $isi) {
        expect($isi)->not->toMatch('/getDriverName\(\)\s*===?\s*[\'"]mysql[\'"]/', str_replace(app_path().'/', '', $jalur));
    }
});
