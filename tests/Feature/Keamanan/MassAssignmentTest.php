<?php

use App\Enums\Peran;
use App\Filament\Swalayan\Pages\ProfilSaya;
use App\Models\Pegawai;
use App\Models\PersetujuanPrivasi;
use App\Models\Prodi;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Support\Konfigurasi;
use App\Support\RegistriTargetUsulan;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
});

test('payload usulan yang menyisipkan prodi_id dan status_aktif tidak mengubah kolom itu', function () {
    $asal = Prodi::factory()->create();
    $tujuan = Prodi::factory()->create();
    $user = User::factory()->create(['email' => 'dsn@unsil.ac.id'])->assignRole(Peran::Dosen->value);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id, 'prodi_id' => $asal->id, 'alamat' => 'Lama']);
    PersetujuanPrivasi::create(['user_id' => $user->id, 'versi' => Konfigurasi::get('versi_kebijakan_privasi'), 'disetujui_at' => now()]);

    Livewire::actingAs($user)->test(ProfilSaya::class)
        ->callAction('ajukan_biodata', ['alamat' => 'Baru', 'prodi_id' => $tujuan->id, 'status_aktif' => 'pensiun', 'user_id' => 'x', 'jenis_pegawai' => 'tendik']);

    $usulan = UsulanPerubahan::first();
    expect($usulan?->data_baru)->toBe(['alamat' => 'Baru']);
    $pegawai->refresh();
    expect($pegawai->prodi_id)->toBe($asal->id)->and($pegawai->status_aktif->value)->toBe('aktif')->and($pegawai->alamat)->toBe('Lama');
});

test('registri target usulan membuang kolom di luar daftar putih', function () {
    $disaring = RegistriTargetUsulan::class;
    $hasil = $disaring::saring('pegawai', ['alamat' => 'x', 'prodi_id' => 'y', 'status_aktif' => 'pensiun', 'is_aktif' => true]);

    expect(array_keys($hasil))->toBe(['alamat']);
});

test('model sensitif memakai fillable eksplisit tanpa guarded kosong', function () {
    foreach ([Pegawai::class, User::class, UsulanPerubahan::class] as $kelas) {
        expect((new $kelas)->getGuarded())->not->toBe([]);
    }
    expect((new User)->getFillable())->not->toContain('password_hash')->and((new User)->isFillable('app_authentication_secret'))->toBeFalse()
        ->and((new User)->isFillable('is_super'))->toBeFalse();
});
