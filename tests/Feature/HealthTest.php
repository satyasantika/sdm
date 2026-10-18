<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    cache()->put(config('app.kode').':detak-penjadwal', now()->getTimestamp(), 600);
});

test('endpoint health mengembalikan status ok dan struktur json benar tanpa redis', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonStructure(['status', 'aplikasi', 'versi', 'waktu', 'db', 'job_tertunda', 'job_gagal_24j', 'detak_penjadwal'])
        ->assertJson([
            'status' => 'ok', 'db' => true, 'versi' => config('app.version'),
            'job_tertunda' => 0, 'job_gagal_24j' => 0, 'detak_penjadwal' => 'ok',
        ])
        ->assertJsonMissingPath('redis');
});

test('endpoint health mengembalikan 503 ketika koneksi db gagal', function () {
    DB::shouldReceive('connection')->andThrow(new RuntimeException('db mati'));

    $this->getJson('/api/health')
        ->assertStatus(503)
        ->assertJson(['status' => 'gagal', 'db' => false]);
});

test('health menghitung job tertunda lebih dari 10 menit tanpa mengubah status', function () {
    DB::table('jobs')->insert([
        ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->subMinutes(11)->getTimestamp(), 'created_at' => now()->subMinutes(11)->getTimestamp()],
        ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => now()->subMinutes(2)->getTimestamp(), 'created_at' => now()->subMinutes(2)->getTimestamp()],
    ]);

    $this->getJson('/api/health')->assertOk()->assertJsonPath('job_tertunda', 1);
});

test('health menghitung job gagal dalam 24 jam terakhir', function () {
    $gagal = fn (string $kapan) => [
        'uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default',
        'payload' => '{}', 'exception' => 'x', 'failed_at' => $kapan,
    ];

    DB::table('failed_jobs')->insert([$gagal(now()->subHours(2)->toDateTimeString()), $gagal(now()->subDays(3)->toDateTimeString())]);

    $this->getJson('/api/health')->assertOk()->assertJsonPath('job_gagal_24j', 1);
});

test('health menandai detak penjadwal terlambat atau belum ada', function () {
    cache()->put(config('app.kode').':detak-penjadwal', now()->subMinutes(6)->getTimestamp(), 600);
    $this->getJson('/api/health')->assertOk()->assertJsonPath('detak_penjadwal', 'terlambat');

    cache()->forget(config('app.kode').':detak-penjadwal');
    $this->getJson('/api/health')->assertOk()->assertJsonPath('detak_penjadwal', 'belum-ada');
});
