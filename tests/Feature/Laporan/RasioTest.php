<?php

use App\Actions\Laporan\HitungRasioDosenMahasiswa;
use App\Enums\Peran;
use App\Filament\Admin\Pages\LaporanAkreditasi;
use App\Filament\Admin\Resources\JumlahMahasiswaProdi\JumlahMahasiswaProdiResource;
use App\Filament\Admin\Resources\JumlahMahasiswaProdi\Pages\CreateJumlahMahasiswaProdi;
use App\Models\JumlahMahasiswaProdi;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\Semester;
use App\Models\User;
use App\Support\Konfigurasi;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
    Cache::flush();
});

function akunRasio(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function barisRasio(Prodi $prodi, Semester $semester): array
{
    return app(HitungRasioDosenMahasiswa::class)->handle($semester)->firstWhere('prodi_id', (string) $prodi->id);
}

test('10 dosen tetap dan 250 mahasiswa menghasilkan rasio 1 : 25,0', function () {
    $semester = Semester::aktifSekarang();
    $prodi = Prodi::factory()->create();
    Pegawai::factory()->count(10)->create(['prodi_id' => $prodi->id]);
    JumlahMahasiswaProdi::factory()->create(['prodi_id' => $prodi->id, 'semester_id' => $semester->id, 'jumlah_mahasiswa_aktif' => 250]);

    $baris = barisRasio($prodi, $semester);

    expect($baris['rasio'])->toBe('1 : 25,0')->and($baris['jumlah_dosen_tetap'])->toBe(10)->and($baris['jumlah_mahasiswa_aktif'])->toBe(250);
});

test('dosen tugas belajar dihitung dosen tetap tetapi bukan aktif mengajar', function () {
    $semester = Semester::aktifSekarang();
    $prodi = Prodi::factory()->create();
    Pegawai::factory()->count(3)->create(['prodi_id' => $prodi->id]);
    Pegawai::factory()->create(['prodi_id' => $prodi->id, 'status_aktif' => 'tugas_belajar']);

    $baris = barisRasio($prodi, $semester);

    expect($baris['jumlah_dosen_tetap'])->toBe(4)->and($baris['dosen_tetap_aktif_mengajar'])->toBe(3);
});

test('prodi tanpa data mahasiswa menampilkan tanda hubung', function () {
    $prodi = Prodi::factory()->create();
    Pegawai::factory()->create(['prodi_id' => $prodi->id]);

    $baris = barisRasio($prodi, Semester::aktifSekarang());

    expect($baris['rasio'])->toBe('—')->and($baris['jumlah_mahasiswa_aktif'])->toBeNull()->and($baris['melampaui'])->toBeFalse();
});

test('ambang opsional menandai rasio yang terlampaui', function () {
    $semester = Semester::aktifSekarang();
    $prodi = Prodi::factory()->create();
    Pegawai::factory()->count(2)->create(['prodi_id' => $prodi->id]);
    JumlahMahasiswaProdi::factory()->create(['prodi_id' => $prodi->id, 'semester_id' => $semester->id, 'jumlah_mahasiswa_aktif' => 100]);

    expect(barisRasio($prodi, $semester)['melampaui'])->toBeFalse();

    Konfigurasi::set('ambang_rasio_dosen_mahasiswa', '40');
    Cache::flush();
    expect(barisRasio($prodi, $semester)['melampaui'])->toBeTrue();
});

test('cache terhapus saat jumlah mahasiswa atau data pegawai berubah', function () {
    $semester = Semester::aktifSekarang();
    $prodi = Prodi::factory()->create();
    Pegawai::factory()->count(2)->create(['prodi_id' => $prodi->id]);
    $data = JumlahMahasiswaProdi::factory()->create(['prodi_id' => $prodi->id, 'semester_id' => $semester->id, 'jumlah_mahasiswa_aktif' => 100]);
    expect(barisRasio($prodi, $semester)['rasio'])->toBe('1 : 50,0')->and(Cache::has('sdm:dasbor:rasio:'.$semester->id))->toBeTrue();

    $data->update(['jumlah_mahasiswa_aktif' => 60]);
    expect(Cache::has('sdm:dasbor:rasio:'.$semester->id))->toBeFalse()->and(barisRasio($prodi, $semester)['rasio'])->toBe('1 : 30,0');

    Pegawai::factory()->create(['prodi_id' => $prodi->id]);
    expect(Cache::has('sdm:dasbor:rasio:'.$semester->id))->toBeFalse();
});

test('admin-prodi tidak dapat mengisi jumlah mahasiswa prodi lain', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $semester = Semester::aktifSekarang();
    $this->actingAs(akunRasio(Peran::AdminProdi, $pmat));

    Livewire::test(CreateJumlahMahasiswaProdi::class)
        ->fillForm(['prodi_id' => $pbio->id, 'semester_id' => $semester->id, 'jumlah_mahasiswa_aktif' => 120, 'sumber' => 'PDDIKTI'])
        ->call('create')->assertHasNoFormErrors();

    $data = JumlahMahasiswaProdi::first();
    expect($data->prodi_id)->toBe($pmat->id)->and($data->diinput_oleh)->not->toBeNull();

    $lain = JumlahMahasiswaProdi::factory()->create(['prodi_id' => $pbio->id]);
    expect(auth()->user()->can('update', $lain))->toBeFalse()->and(auth()->user()->can('update', $data))->toBeTrue();
    $this->get(JumlahMahasiswaProdiResource::getUrl('edit', ['record' => $lain]))->assertStatus(404);
});

test('sumber wajib dan duplikat prodi semester ditolak', function () {
    $prodi = Prodi::factory()->create();
    $semester = Semester::aktifSekarang();
    JumlahMahasiswaProdi::factory()->create(['prodi_id' => $prodi->id, 'semester_id' => $semester->id]);
    $this->actingAs(akunRasio(Peran::AdminKepegawaian));

    Livewire::test(CreateJumlahMahasiswaProdi::class)
        ->fillForm(['prodi_id' => $prodi->id, 'semester_id' => $semester->id, 'jumlah_mahasiswa_aktif' => 10, 'sumber' => ''])
        ->call('create')->assertHasFormErrors(['sumber' => 'required', 'semester_id' => 'unique']);
});

test('halaman laporan menampilkan rasio dan admin-prodi hanya baris prodinya', function () {
    $semester = Semester::aktifSekarang();
    $pmat = Prodi::factory()->create(['nama' => 'Prodi Rasio Matematika']);
    $pbio = Prodi::factory()->create(['nama' => 'Prodi Rasio Biologi']);
    Pegawai::factory()->count(2)->create(['prodi_id' => $pmat->id]);
    Pegawai::factory()->create(['prodi_id' => $pbio->id]);
    JumlahMahasiswaProdi::factory()->create(['prodi_id' => $pmat->id, 'semester_id' => $semester->id, 'jumlah_mahasiswa_aktif' => 50]);

    $this->actingAs(akunRasio(Peran::AdminKepegawaian));
    Livewire::test(LaporanAkreditasi::class)->assertSee('Prodi Rasio Matematika')->assertSee('Prodi Rasio Biologi')->assertSee('1 : 25,0');

    $this->actingAs(akunRasio(Peran::AdminProdi, $pmat));
    Livewire::test(LaporanAkreditasi::class)->assertSee('Prodi Rasio Matematika')->assertDontSee('Prodi Rasio Biologi');
});
