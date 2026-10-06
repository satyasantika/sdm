<?php

use App\Actions\Pegawai\TampilkanDataSensitif;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Pegawai\Pages\ViewPegawai;
use App\Filament\Admin\Resources\Pegawai\PegawaiResource;
use App\Models\Aktivitas;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\User;
use App\Support\Penyamar;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    RateLimiter::clear('tampil-sensitif');
});

function penggunaSensitif(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('penyamar mengembalikan format yang benar', function () {
    expect(Penyamar::nik('3278010101900001'))->toBe('3278********0001')
        ->and(Penyamar::npwp('12.345.678.9-012.345'))->toBe('****2345')
        ->and(Penyamar::rekening('1234567890'))->toBe('****7890')
        ->and(Penyamar::nik(null))->toBe('—')
        ->and(Penyamar::rekening(''))->toBe('—');
});

test('model menyediakan accessor tersamar', function () {
    $pegawai = Pegawai::factory()->make(['nik' => '3278010101900001', 'nomor_rekening' => '1234567890']);

    expect($pegawai->nik_tersamar)->toBe('3278********0001')->and($pegawai->rekening_tersamar)->toBe('****7890');
});

test('admin-kepegawaian menampilkan nik dan tercatat satu log akses sensitif', function () {
    $pegawai = Pegawai::factory()->create(['nik' => '3278010101900001']);
    $admin = penggunaSensitif(Peran::AdminKepegawaian);

    $nilai = app(TampilkanDataSensitif::class)->handle($admin, $pegawai, 'nik');

    $log = Aktivitas::where('log_name', 'akses-sensitif')->get();
    expect($nilai)->toBe('3278010101900001')
        ->and($log)->toHaveCount(1)
        ->and($log->first()->properties->get('kolom'))->toBe('nik')
        ->and($log->first()->causer_id)->toBe($admin->id)
        ->and(json_encode($log->first()->properties))->not->toContain('3278010101900001');
});

test('admin-prodi dan pimpinan ditolak dan tidak ada log tampil', function () {
    $prodi = Prodi::factory()->create();
    $pegawai = Pegawai::factory()->create(['prodi_id' => $prodi->id]);

    foreach ([penggunaSensitif(Peran::AdminProdi, $prodi), penggunaSensitif(Peran::Pimpinan)] as $user) {
        expect(fn () => app(TampilkanDataSensitif::class)->handle($user, $pegawai, 'nik'))->toThrow(AuthorizationException::class);
    }

    expect(Aktivitas::where('log_name', 'akses-sensitif')->count())->toBe(0);
});

test('pemilik data boleh melihat data sendiri', function () {
    $dosen = penggunaSensitif(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $dosen->id, 'npwp' => '12.345.678.9-012.345']);

    expect(app(TampilkanDataSensitif::class)->handle($dosen, $pegawai, 'npwp'))->toBe('12.345.678.9-012.345');
});

test('pemanggilan ke-11 dalam semenit ditolak', function () {
    $pegawai = Pegawai::factory()->create();
    $admin = penggunaSensitif(Peran::AdminKepegawaian);

    foreach (range(1, 10) as $i) {
        app(TampilkanDataSensitif::class)->handle($admin, $pegawai, 'nik');
    }

    expect(fn () => app(TampilkanDataSensitif::class)->handle($admin, $pegawai, 'nik'))->toThrow(ThrottleRequestsException::class)
        ->and(Aktivitas::where('log_name', 'akses-sensitif')->count())->toBe(10);
});

test('kolom alamat ditolak oleh action', function () {
    $pegawai = Pegawai::factory()->create();

    expect(fn () => app(TampilkanDataSensitif::class)->handle(penggunaSensitif(Peran::SuperAdmin), $pegawai, 'alamat'))
        ->toThrow(InvalidArgumentException::class);
});

test('halaman view untuk admin-prodi tidak memuat nik polos dan hanya tahun lahir', function () {
    $prodi = Prodi::factory()->create();
    $pegawai = Pegawai::factory()->create(['prodi_id' => $prodi->id, 'nik' => '3278010101900001', 'tanggal_lahir' => '1980-05-17', 'alamat' => 'Jl. Rahasia 99']);
    $this->actingAs(penggunaSensitif(Peran::AdminProdi, $prodi));

    $this->get(PegawaiResource::getUrl('view', ['record' => $pegawai]))
        ->assertOk()
        ->assertDontSee('3278010101900001')
        ->assertSee('3278********0001')
        ->assertSee('1980')
        ->assertDontSee('17 Mei 1980')
        ->assertDontSee('Jl. Rahasia 99');
});

test('admin-kepegawaian melihat tombol tampil dan alamat; nilai polos muncul hanya setelah aksi', function () {
    $pegawai = Pegawai::factory()->create(['nik' => '3278010101900001', 'alamat' => 'Jl. Terbuka 1']);
    $this->actingAs(penggunaSensitif(Peran::AdminKepegawaian));

    Livewire::test(ViewPegawai::class, ['record' => $pegawai->getKey()])
        ->assertDontSee('3278010101900001')
        ->assertSee('Jl. Terbuka 1')
        ->mountInfolistAction('nik_tersamar', 'tampil_nik')
        ->assertActionDataSet(['nilai' => '3278010101900001']);

    expect(Aktivitas::where('log_name', 'akses-sensitif')->count())->toBe(1);
});
