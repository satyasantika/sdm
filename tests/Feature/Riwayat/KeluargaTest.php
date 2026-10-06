<?php

use App\Actions\Riwayat\SimpanKeluarga;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use App\Filament\Admin\Resources\Pegawai\RelationManagers\KeluargaRelationManager;
use App\Models\Aktivitas;
use App\Models\Keluarga;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

function akunKeluarga(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('nik keluarga terenkripsi di db dan tersamar di accessor', function () {
    $pegawai = Pegawai::factory()->create();
    $k = app(SimpanKeluarga::class)->handle($pegawai, ['hubungan' => 'anak', 'nama' => 'Anak Satu', 'nik' => '3278010101150001']);

    $mentah = DB::table('keluarga')->where('id', $k->id)->value('nik');
    expect($mentah)->not->toBe('3278010101150001')->and($mentah)->toStartWith('eyJ')
        ->and($k->fresh()->nik)->toBe('3278010101150001')
        ->and($k->fresh()->nik_tersamar)->toBe('3278********0001')
        ->and($k->fresh()->toArray())->not->toHaveKey('nik');
});

test('nik tidak masuk activity log dan kosong tidak menghapus nik lama', function () {
    $pegawai = Pegawai::factory()->create();
    $k = app(SimpanKeluarga::class)->handle($pegawai, ['hubungan' => 'anak', 'nama' => 'Anak', 'nik' => '3278010101150001', 'tanggal_lahir' => '2015-01-01']);
    app(SimpanKeluarga::class)->handle($pegawai, ['hubungan' => 'anak', 'nama' => 'Anak Diubah', 'nik' => ''], $k);

    $log = Aktivitas::all()->map(fn ($a) => json_encode([$a->attribute_changes, $a->properties]))->implode(' ');
    expect($log)->not->toContain('3278010101150001')->not->toContain('2015-01-01')
        ->and($k->fresh()->nik)->toBe('3278010101150001')->and($k->fresh()->nama)->toBe('Anak Diubah');
});

test('validasi nik dan tanggal nikah', function () {
    $pegawai = Pegawai::factory()->create();

    expect(fn () => app(SimpanKeluarga::class)->handle($pegawai, ['hubungan' => 'anak', 'nama' => 'A', 'nik' => '123']))->toThrow(ValidationException::class)
        ->and(fn () => app(SimpanKeluarga::class)->handle($pegawai, ['hubungan' => 'anak', 'nama' => 'A', 'tanggal_nikah' => '2020-01-01']))->toThrow(ValidationException::class);

    expect(app(SimpanKeluarga::class)->handle($pegawai, ['hubungan' => 'istri', 'nama' => 'Istri', 'tanggal_nikah' => '2010-05-01']))->toBeInstanceOf(Keluarga::class);
});

test('pasangan ganda hanya peringatan', function () {
    $pegawai = Pegawai::factory()->create();
    $aksi = app(SimpanKeluarga::class);
    $aksi->handle($pegawai, ['hubungan' => 'istri', 'nama' => 'Istri 1']);
    expect($aksi->adaPasanganGanda($pegawai))->toBeFalse();

    $aksi->handle($pegawai, ['hubungan' => 'istri', 'nama' => 'Istri 2']);
    expect($aksi->adaPasanganGanda($pegawai))->toBeTrue()->and(Keluarga::count())->toBe(2);
});

test('admin-prodi dan pimpinan ditolak oleh policy dan tab tidak muncul', function () {
    $prodi = Prodi::factory()->create();
    $pegawai = Pegawai::factory()->create(['prodi_id' => $prodi->id]);
    $keluarga = Keluarga::factory()->create(['pegawai_id' => $pegawai->id, 'nama' => 'Nama Anak Rahasia']);

    foreach ([akunKeluarga(Peran::AdminProdi, $prodi), akunKeluarga(Peran::Pimpinan)] as $user) {
        expect($user->can('viewAny', Keluarga::class))->toBeFalse()->and($user->can('view', $keluarga))->toBeFalse();
        $this->actingAs($user)->get(PegawaiResource::getUrl('view', ['record' => $pegawai]))
            ->assertOk()->assertDontSee('Nama Anak Rahasia')->assertDontSee('Tambah anggota');
    }
});

test('pemilik boleh melihat tetapi tidak mengubah; admin-kepegawaian dapat menambah', function () {
    $dosen = akunKeluarga(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $dosen->id]);
    $keluarga = Keluarga::factory()->create(['pegawai_id' => $pegawai->id]);
    $lain = Keluarga::factory()->create();

    expect($dosen->can('view', $keluarga))->toBeTrue()->and($dosen->can('view', $lain))->toBeFalse()
        ->and($dosen->can('update', $keluarga))->toBeFalse()->and($dosen->can('create', Keluarga::class))->toBeFalse();

    $this->actingAs(akunKeluarga(Peran::AdminKepegawaian));
    Livewire::test(KeluargaRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->callAction(TestAction::make('create')->table(), ['hubungan' => 'anak', 'nama' => 'Anak Baru', 'nik' => '3278010101200001'])
        ->assertHasNoFormErrors();

    expect(Keluarga::where('nama', 'Anak Baru')->exists())->toBeTrue();
});
