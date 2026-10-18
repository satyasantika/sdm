<?php

use App\Jobs\BuatPdfLaporan;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Profil Ringan (STANDAR-TEKNIS §1b): dua antrean di database, worker dinyalakan penjadwal.
 */
it('menjadwalkan dua worker, detak penjadwal, dan pemangkasan antrean', function () {
    $jadwal = collect(app(Schedule::class)->events())
        ->map(fn ($e) => (string) ($e->command ?? $e->description))
        ->implode("\n");

    expect($jadwal)
        ->toContain('queue:work database --queue=default --stop-when-empty')
        ->toContain('queue:work database-berat --queue=berat --stop-when-empty')
        ->toContain('detak-penjadwal')
        ->toContain('queue:prune-failed')
        ->toContain('queue:prune-batches')
        ->not->toContain('horizon:snapshot');
});

it('mengarahkan pdf laporan ke koneksi database-berat dan antrean berat', function () {
    $job = new BuatPdfLaporan('duk', 'pengguna-uji');

    expect($job->connection)->toBe('database-berat')->and($job->queue)->toBe('berat');
});
