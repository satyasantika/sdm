<?php

use App\Actions\Laporan\HitungSyaratUnggulSdm;
use App\Enums\Peran;
use App\Filament\Admin\Pages\LaporanAkreditasi;
use App\Filament\Admin\Widgets\SyaratUnggulSdmWidget;
use App\Filament\Exports\ProfilDosenProdiExporter;
use App\Models\Aktivitas;
use App\Models\Ekspor;
use App\Models\JabatanFungsional;
use App\Models\JenisSertifikasi;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatPendidikan;
use App\Models\Sertifikasi;
use App\Models\StatusKepegawaian;
use App\Models\User;
use App\Support\CakupanProdi;
use App\Support\Konfigurasi;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterJabatanSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterJabatanSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
    Storage::fake('tmp');
    Cache::flush();
});

function akunLaporan(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function dosenLaporan(Prodi $prodi, array $atribut = [], ?string $jenjang = null, ?string $jabatan = null): Pegawai
{
    $pegawai = Pegawai::factory()->create($atribut + ['prodi_id' => $prodi->id, 'gelar_belakang' => null, 'jabatan_fungsional_id' => $jabatan ? JabatanFungsional::firstWhere('kode', $jabatan)->id : null]);

    if ($jenjang) {
        RiwayatPendidikan::factory()->create([
            'pegawai_id' => $pegawai->id, 'jenjang_pendidikan_id' => JenjangPendidikan::firstWhere('kode', $jenjang)->id,
            'nama_pt' => "PT {$jenjang} Contoh", 'bidang_ilmu' => "Bidang {$jenjang}", 'is_pendidikan_tertinggi' => true,
        ]);
    }

    return $pegawai;
}

function jalankanEkspor(User $pelaku, ?Prodi $prodi, string $aksi = 'eksporProfil'): string
{
    test()->actingAs($pelaku);
    $kolom = collect(ProfilDosenProdiExporter::getColumns())->mapWithKeys(fn ($k) => [$k->getName() => $k->getName()])->all();

    $komponen = Livewire::test(LaporanAkreditasi::class);
    if ($prodi && CakupanProdiTerkunci($pelaku) === null) {
        $komponen->set('data.prodi_id', $prodi->id);
    }
    $komponen->callAction($aksi, ['columnMap' => array_map(fn ($n) => ['isEnabled' => true, 'label' => $n], $kolom)]);

    $berkas = collect(Storage::disk('tmp')->allFiles())->first(fn ($f) => str_ends_with($f, '.csv'));

    return $berkas ? Storage::disk('tmp')->get($berkas) : '';
}

function CakupanProdiTerkunci(User $u): ?string
{
    return CakupanProdi::terkunci($u);
}

test('admin-prodi hanya mengekspor dosen prodinya dan dosen kontrak non tetap tidak ikut', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    dosenLaporan($pmat, ['nama' => 'Dosen Pmat Tetap']);
    dosenLaporan($pbio, ['nama' => 'Dosen Pbio Tetap']);
    $kontrak = StatusKepegawaian::factory()->create(['dihitung_dosen_tetap' => false]);
    dosenLaporan($pmat, ['nama' => 'Dosen Pmat Kontrak', 'status_kepegawaian_id' => $kontrak->id]);

    $isi = jalankanEkspor(akunLaporan(Peran::AdminProdi, $pmat), $pbio);

    expect($isi)->toContain('Dosen Pmat Tetap')->not->toContain('Dosen Pbio Tetap')->not->toContain('Dosen Pmat Kontrak');
});

test('header berkas tidak memuat data sensitif dan isi tidak membocorkan nik', function () {
    $prodi = Prodi::factory()->create();
    dosenLaporan($prodi, ['nama' => 'Dosen Sensitif', 'nik' => '3278010101900001', 'tanggal_lahir' => '1980-05-17', 'alamat' => 'Jl. Rahasia 9', 'nomor_rekening' => '1234567890', 'nip' => '198001012005011001']);

    $isi = jalankanEkspor(akunLaporan(Peran::AdminKepegawaian), $prodi);
    $header = mb_strtolower(strtok($isi, "\n"));

    foreach (['nik', 'tanggal lahir', 'alamat', 'rekening', 'npwp'] as $terlarang) {
        expect($header)->not->toContain($terlarang);
    }
    expect($isi)->toContain('Dosen Sensitif')->not->toContain('3278010101900001')->not->toContain('1980-05-17')
        ->not->toContain('Jl. Rahasia')->not->toContain('198001012005011001');
});

test('kolom s3 terisi untuk dosen bergelar doktor dan serdos tercatat', function () {
    $prodi = Prodi::factory()->create();
    $dosen = dosenLaporan($prodi, ['nama' => 'Dosen Doktor'], 'S3', 'lektor-kepala');
    Sertifikasi::factory()->create(['pegawai_id' => $dosen->id, 'jenis_sertifikasi_id' => JenisSertifikasi::firstWhere('kode', 'serdos')->id, 'nomor_registrasi' => 'REG-123']);

    $isi = jalankanEkspor(akunLaporan(Peran::AdminKepegawaian), $prodi);

    expect($isi)->toContain('PT S3 Contoh')->toContain('Bidang S3')->toContain('Lektor Kepala')->toContain('Ya (REG-123)');
});

test('ekspor ke-6 dalam semenit ditolak', function () {
    $prodi = Prodi::factory()->create();
    dosenLaporan($prodi);
    $admin = akunLaporan(Peran::AdminKepegawaian);
    $this->actingAs($admin);
    $kolom = collect(ProfilDosenProdiExporter::getColumns())->mapWithKeys(fn ($k) => [$k->getName() => ['isEnabled' => true, 'label' => $k->getName()]])->all();

    foreach (range(1, 5) as $i) {
        $this->actingAs($admin);
        Livewire::test(LaporanAkreditasi::class)->set('data.prodi_id', $prodi->id)->callAction('eksporProfil', ['columnMap' => $kolom]);
    }
    $jumlahSebelum = Ekspor::count();

    $this->actingAs($admin);
    Livewire::test(LaporanAkreditasi::class)->set('data.prodi_id', $prodi->id)->callAction('eksporProfil', ['columnMap' => $kolom]);
    expect(RateLimiter::tooManyAttempts('ekspor:'.$admin->id, 5))->toBeTrue();

    expect($jumlahSebelum)->toBe(5)->and(Ekspor::count())->toBe(5);
});

test('pimpinan dapat mengekspor semua prodi dan ekspor tercatat di log audit', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    dosenLaporan($pmat, ['nama' => 'Dosen Pmat Semua']);
    dosenLaporan($pbio, ['nama' => 'Dosen Pbio Semua']);

    $isi = jalankanEkspor(akunLaporan(Peran::Pimpinan), null);

    expect($isi)->toContain('Dosen Pmat Semua')->toContain('Dosen Pbio Semua');
    $log = Aktivitas::where('description', 'ekspor profil dosen')->first();
    expect($log)->not->toBeNull()->and($log->properties->get('baris'))->toBe(2);
});

test('syarat unggul: 1 doktor dan 2 lektor memenuhi 3 tahun tetapi belum 5 tahun', function () {
    $prodi = Prodi::factory()->create(['jenjang' => 'S1']);
    dosenLaporan($prodi, [], 'S3', 'lektor');
    dosenLaporan($prodi, [], 'S2', 'lektor');
    dosenLaporan($prodi, [], 'S2', 'asisten-ahli');

    $hasil = app(HitungSyaratUnggulSdm::class)->handle($prodi);

    expect($hasil['doktor'])->toBe(1)->and($hasil['lektor_ke_atas'])->toBe(2)->and($hasil['lektor_kepala_ke_atas'])->toBe(0)
        ->and($hasil['horizon']['3_tahun']['terpenuhi'])->toBeTrue()->and($hasil['horizon']['5_tahun']['terpenuhi'])->toBeFalse();
});

test('dosen non dtps tidak dihitung dan perubahan konfigurasi mengubah hasil tanpa ubah kode', function () {
    $prodi = Prodi::factory()->create(['jenjang' => 'S1']);
    dosenLaporan($prodi, [], 'S3', 'lektor');
    dosenLaporan($prodi, [], 'S2', 'lektor');
    dosenLaporan($prodi, ['sesuai_kompetensi_inti_ps' => false], 'S3', 'lektor-kepala');

    $hasil = app(HitungSyaratUnggulSdm::class)->handle($prodi);
    expect($hasil['dtps'])->toBe(2)->and($hasil['doktor'])->toBe(1)->and($hasil['horizon']['3_tahun']['terpenuhi'])->toBeTrue();

    Konfigurasi::set('syarat_unggul_sdm', ['S1' => ['3_tahun' => ['min_dtps_doktor' => 2, 'min_dtps_lektor_ke_atas' => 2]]]);
    Cache::forget('sdm:statistik:syarat-unggul:'.$prodi->id);

    expect(app(HitungSyaratUnggulSdm::class)->handle($prodi)['horizon']['3_tahun']['terpenuhi'])->toBeFalse();
});

test('jenjang yang belum dikonfigurasi menghasilkan null dan widget memberi pesan', function () {
    $prodi = Prodi::factory()->create(['jenjang' => 'S2']);

    expect(app(HitungSyaratUnggulSdm::class)->handle($prodi))->toBeNull();

    $this->actingAs(akunLaporan(Peran::AdminKepegawaian));
    Livewire::test(SyaratUnggulSdmWidget::class, ['prodiId' => $prodi->id])->assertSee('belum dikonfigurasi');
});

test('hasil syarat unggul di-cache dan dibersihkan saat data berubah', function () {
    $prodi = Prodi::factory()->create(['jenjang' => 'S1']);
    $dosen = dosenLaporan($prodi, [], 'S2', 'lektor');
    app(HitungSyaratUnggulSdm::class)->handle($prodi);
    expect(Cache::has('sdm:statistik:syarat-unggul:'.$prodi->id))->toBeTrue();

    $dosen->update(['jabatan_fungsional_id' => JabatanFungsional::firstWhere('kode', 'lektor-kepala')->id]);

    expect(Cache::has('sdm:statistik:syarat-unggul:'.$prodi->id))->toBeFalse();
});

test('halaman laporan akreditasi menampilkan ringkasan kualifikasi prodi', function () {
    $prodi = Prodi::factory()->create(['nama' => 'Prodi Laporan Uji']);
    dosenLaporan($prodi, [], 'S3', 'lektor');
    dosenLaporan($prodi, [], 'S2', 'lektor');
    $this->actingAs(akunLaporan(Peran::AdminKepegawaian));

    Livewire::test(LaporanAkreditasi::class)->set('data.prodi_id', $prodi->id)
        ->assertSee('Per jabatan akademik')->assertSee('Lektor')->assertSee('50%')
        ->assertSee('Syarat unggul');
});

test('ekspor tendik dan admin-prodi tidak dapat membuka laporan prodi lain', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    dosenLaporan($pbio, ['nama' => 'Dosen Pbio Rahasia']);
    $this->actingAs(akunLaporan(Peran::AdminProdi, $pmat));

    $komponen = Livewire::test(LaporanAkreditasi::class)->set('data.prodi_id', $pbio->id);

    expect($komponen->instance()->prodiTerpilih())->toBe($pmat->id);
    $komponen->assertDontSee('Dosen Pbio Rahasia');
});
