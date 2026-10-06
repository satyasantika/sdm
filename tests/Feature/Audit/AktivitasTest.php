<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Aktivitas\AktivitasResource;
use App\Models\Aktivitas;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function aktor(Peran $peran): User
{
    return tap(User::factory()->create(['email' => $peran->value.'@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP']), fn (User $u) => $u->assignRole($peran->value));
}

test('mengubah name user menghasilkan activity dengan nilai lama dan baru', function () {
    $user = User::factory()->create(['name' => 'Lama']);

    $user->update(['name' => 'Baru']);

    $log = Aktivitas::where('event', 'updated')->latest()->first();
    $perubahan = json_encode([$log->attribute_changes, $log->properties]);

    expect($log->subject_id)->toBe($user->id)
        ->and($perubahan)->toContain('Lama')->toContain('Baru');
});

test('mengubah password tidak menyimpan nilai password', function () {
    $user = User::factory()->create();

    $user->update(['password' => 'rahasia-baru-123']);

    $semua = Aktivitas::all()->map(fn ($a) => json_encode([$a->attribute_changes, $a->properties, $a->description]))->implode(' ');
    $hash = $user->fresh()->password;

    expect($semua)->not->toContain('rahasia-baru-123')
        ->and($semua)->not->toContain($hash)
        ->and($semua)->toContain('kolom_sensitif_diubah')->toContain('password');
});

test('admin-kepegawaian dapat membuka daftar log', function () {
    $this->actingAs(aktor(Peran::AdminKepegawaian))
        ->get(AktivitasResource::getUrl('index'))
        ->assertOk();
});

test('admin-prodi mendapat 403 pada daftar log', function () {
    $this->actingAs(aktor(Peran::AdminProdi))
        ->get(AktivitasResource::getUrl('index'))
        ->assertForbidden();
});

test('log audit bersifat baca-saja', function () {
    $admin = aktor(Peran::SuperAdmin);
    $log = Aktivitas::first();

    expect(AktivitasResource::canCreate())->toBeFalse()
        ->and($admin->can('delete', $log ?? new Aktivitas))->toBeTrue(); // super-admin lolos Gate::before
    $kepegawaian = aktor(Peran::AdminKepegawaian);
    expect($kepegawaian->can('update', $log ?? new Aktivitas))->toBeFalse()
        ->and($kepegawaian->can('delete', $log ?? new Aktivitas))->toBeFalse();
});
