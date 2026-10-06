<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

test('endpoint health mengembalikan status ok dan struktur json benar', function () {
    Redis::shouldReceive('connection->ping')->once()->andReturn(true);

    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonStructure(['status', 'aplikasi', 'versi', 'waktu', 'db', 'redis'])
        ->assertJson(['status' => 'ok', 'db' => true, 'redis' => true, 'versi' => config('app.version')]);
});

test('endpoint health mengembalikan 503 ketika koneksi db gagal', function () {
    Redis::shouldReceive('connection->ping')->andReturn(true);
    DB::shouldReceive('connection')->andThrow(new RuntimeException('db mati'));

    $this->getJson('/api/health')
        ->assertStatus(503)
        ->assertJson(['status' => 'gagal', 'db' => false, 'redis' => true]);
});
