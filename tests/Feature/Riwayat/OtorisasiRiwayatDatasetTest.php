<?php

use App\Enums\Peran;
use App\Models\Keluarga;
use App\Models\Pegawai;
use App\Models\Pelatihan;
use App\Models\Penghargaan;
use App\Models\Prodi;
use App\Models\RiwayatJabatanFungsional;
use App\Models\RiwayatJabatanStruktural;
use App\Models\RiwayatKgb;
use App\Models\RiwayatPangkat;
use App\Models\RiwayatPendidikan;
use App\Models\Sertifikasi;
use App\Models\StudiLanjut;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;

/**
 * Matriks otorisasi riwayat (PRD 3.2). Kunci: model => jenis izin tulis.
 * 'akademik' = riwayat-akademik.kelola (admin-prodi boleh di prodinya), 'kepegawaian' = riwayat-kepegawaian.kelola.
 */
const MODEL_RIWAYAT = [
    RiwayatJabatanFungsional::class => 'akademik',
    RiwayatPendidikan::class => 'akademik',
    Sertifikasi::class => 'akademik',
    Penghargaan::class => 'akademik',
    Pelatihan::class => 'akademik',
    StudiLanjut::class => 'akademik',
    RiwayatPangkat::class => 'kepegawaian',
    RiwayatKgb::class => 'kepegawaian',
    RiwayatJabatanStruktural::class => 'kepegawaian',
    Keluarga::class => 'keluarga',
];

const PERAN_UJI = ['admin-kepegawaian', 'admin-prodi-sama', 'admin-prodi-lain', 'pimpinan', 'dosen-pemilik', 'dosen-lain'];

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function pelakuMatriks(string $kode, Prodi $prodiSama, Prodi $prodiLain, Pegawai $pegawai): User
{
    $peran = match ($kode) {
        'admin-kepegawaian' => Peran::AdminKepegawaian,
        'admin-prodi-sama', 'admin-prodi-lain' => Peran::AdminProdi,
        'pimpinan' => Peran::Pimpinan,
        default => Peran::Dosen,
    };

    $user = User::factory()->create([
        'email' => $kode.fake()->unique()->numerify('###').'@unsil.ac.id',
        'prodi_id' => match ($kode) {
            'admin-prodi-sama' => $prodiSama->id,
            'admin-prodi-lain' => $prodiLain->id,
            default => null,
        },
    ]);
    $user->assignRole($peran->value);

    if ($kode === 'dosen-pemilik') {
        $pegawai->update(['user_id' => $user->id]);
    }

    return $user;
}

/** @return array{view: bool, create: bool, update: bool, delete: bool} */
function harapan(string $kode, string $jenis): array
{
    $tulisAkademik = ['admin-kepegawaian', 'admin-prodi-sama', 'admin-prodi-lain'];

    return match (true) {
        $jenis === 'keluarga' => [
            'view' => in_array($kode, ['admin-kepegawaian', 'dosen-pemilik'], true),
            'create' => $kode === 'admin-kepegawaian',
            'update' => $kode === 'admin-kepegawaian',
            'delete' => $kode === 'admin-kepegawaian',
        ],
        $jenis === 'akademik' => [
            'view' => in_array($kode, ['admin-kepegawaian', 'admin-prodi-sama', 'pimpinan', 'dosen-pemilik'], true),
            // create(User) tidak mengenal prodi; pembatasan prodi ditegakkan pada record pemilik (RelationManager).
            'create' => in_array($kode, $tulisAkademik, true),
            'update' => in_array($kode, ['admin-kepegawaian', 'admin-prodi-sama'], true),
            'delete' => $kode === 'admin-kepegawaian',
        ],
        default => [
            'view' => in_array($kode, ['admin-kepegawaian', 'admin-prodi-sama', 'pimpinan', 'dosen-pemilik'], true),
            'create' => $kode === 'admin-kepegawaian',
            'update' => $kode === 'admin-kepegawaian',
            'delete' => $kode === 'admin-kepegawaian',
        ],
    };
}

dataset('model_riwayat', array_keys(MODEL_RIWAYAT));
dataset('peran_uji', PERAN_UJI);

test('matriks otorisasi policy riwayat sesuai PRD 3.2', function (string $model, string $kode) {
    $prodiSama = Prodi::factory()->create();
    $prodiLain = Prodi::factory()->create();
    $pegawai = Pegawai::factory()->create(['prodi_id' => $prodiSama->id]);
    $user = pelakuMatriks($kode, $prodiSama, $prodiLain, $pegawai);
    $riwayat = $model::factory()->create(['pegawai_id' => $pegawai->id]);
    $harapan = harapan($kode, MODEL_RIWAYAT[$model]);

    expect($user->can('view', $riwayat))->toBe($harapan['view'], "{$model} view oleh {$kode}")
        ->and($user->can('create', $model))->toBe($harapan['create'], "{$model} create oleh {$kode}")
        ->and($user->can('update', $riwayat))->toBe($harapan['update'], "{$model} update oleh {$kode}")
        ->and($user->can('delete', $riwayat))->toBe($harapan['delete'], "{$model} delete oleh {$kode}");
})->with('model_riwayat', 'peran_uji');

test('super-admin lolos semua aksi pada semua model riwayat', function (string $model) {
    $pegawai = Pegawai::factory()->create();
    $super = User::factory()->create(['email' => 'sa'.fake()->unique()->numerify('###').'@unsil.ac.id']);
    $super->assignRole(Peran::SuperAdmin->value);
    $riwayat = $model::factory()->create(['pegawai_id' => $pegawai->id]);

    expect($super->can('view', $riwayat))->toBeTrue()->and($super->can('delete', $riwayat))->toBeTrue();
})->with('model_riwayat');
