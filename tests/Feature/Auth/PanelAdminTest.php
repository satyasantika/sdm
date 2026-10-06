<?php

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;

test('tamu diarahkan ke halaman login admin', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('halaman login berbahasa indonesia dengan merek sdm fkip unsil', function () {
    $this->get('/admin/login')->assertOk()->assertSee('SDM FKIP Unsil')->assertSee('Masuk');
});

test('user aktif ber-email unsil dapat membuka admin', function () {
    $user = User::factory()->create(['email' => 'budi@unsil.ac.id']);

    $this->actingAs($user)->get('/admin')->assertOk()->assertSee('v'.config('app.version'));
});

test('user tidak aktif ditolak dengan 403', function () {
    $user = User::factory()->create(['email' => 'budi@unsil.ac.id', 'is_aktif' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('user ber-email di luar unsil ditolak di lingkungan non-local', function () {
    $user = User::factory()->create(['email' => 'budi@contoh.com']);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('last_login_at terisi setelah login', function () {
    $user = User::factory()->create(['email' => 'budi@unsil.ac.id']);
    expect($user->last_login_at)->toBeNull();

    auth()->login($user);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('seeder super admin membaca kredensial dari konfigurasi', function () {
    config([
        'sdm.superadmin.email' => 'root@unsil.ac.id',
        'sdm.superadmin.password' => 'rahasia-uji-123',
    ]);

    $this->seed(SuperAdminSeeder::class);

    expect(User::where('email', 'root@unsil.ac.id')->exists())->toBeTrue();
});
