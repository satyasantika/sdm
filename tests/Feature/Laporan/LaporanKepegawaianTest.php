<?php

use App\Actions\Laporan\CetakProfilPegawai;
use App\Actions\Laporan\SusunLaporanKepegawaian;
use App\Actions\Riwayat\SimpanRiwayatPangkat;
use App\Enums\Peran;
use App\Filament\Admin\Pages\LaporanKepegawaian;
use App\Filament\Admin\Resources\Pegawai\Pages\ViewPegawai;
use App\Filament\Swalayan\Pages\ProfilSaya;
use App\Jobs\BuatPdfLaporan;
use App\Models\Golongan;
use App\Models\JenisJabatanStruktural;
use App\Models\Keluarga;
use App\Models\Pegawai;
use App\Models\PersetujuanPrivasi;
use App\Models\Prodi;
use App\Models\RiwayatJabatanStruktural;
use App\Models\StatusKepegawaian;
use App\Models\User;
use App\Support\KeluaranSementara;
use App\Support\Konfigurasi;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterKepegawaianSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterKepegawaianSeeder::class);
    Storage::fake('tmp');
    Cache::flush();
});

function akunLapKepeg(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function pnsDenganPangkat(string $nama, string $kodeGolongan, string $tmt, string $statusKode = 'pns'): Pegawai
{
    $pegawai = Pegawai::factory()->create([
        'nama' => $nama, 'gelar_belakang' => null,
        'status_kepegawaian_id' => StatusKepegawaian::firstWhere('kode', $statusKode)->id,
    ]);
    $golongan = Golongan::where('jenis', 'pns')->where('kode', $kodeGolongan)->first();
    app(SimpanRiwayatPangkat::class)->handle($pegawai, [
        'golongan_id' => $golongan->id, 'tmt' => $tmt, 'nomor_sk' => 'SK/'.$tmt, 'jenis_kenaikan' => 'reguler',
    ]);

    return $pegawai;
}

test('duk hanya berisi pns dan terurut golongan desc lalu tmt asc', function () {
    pnsDenganPangkat('Pns Iiia Baru', 'III/a', '2022-04-01');
    pnsDenganPangkat('Pns Ivb', 'IV/b', '2020-04-01');
    pnsDenganPangkat('Pns Iiia Lama', 'III/a', '2018-04-01');
    pnsDenganPangkat('Cpns Tidak Ikut', 'III/a', '2025-01-01', 'cpns');
    $pppk = Pegawai::factory()->create(['nama' => 'Pppk Tidak Ikut', 'status_kepegawaian_id' => StatusKepegawaian::firstWhere('kode', 'pppk')->id]);
    Pegawai::factory()->create(['nama' => 'Kontrak Tidak Ikut', 'status_kepegawaian_id' => StatusKepegawaian::firstWhere('kode', 'non-asn-kontrak')->id]);

    $duk = app(SusunLaporanKepegawaian::class)->duk();

    expect($duk->pluck('nama')->all())->toBe(['Pns Ivb', 'Pns Iiia Lama', 'Pns Iiia Baru'])
        ->and($duk->first()['golongan'])->toContain('IV/b');
});

test('duk dan pensiun admin-prodi hanya prodinya dan pensiun lima tahun diurutkan', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    Pegawai::factory()->create(['prodi_id' => $pmat->id, 'nama' => 'Pensiun Pmat', 'gelar_belakang' => null, 'tanggal_lahir' => now()->subYears(63)->subMonths(3)->toDateString()]);
    Pegawai::factory()->create(['prodi_id' => $pbio->id, 'nama' => 'Pensiun Pbio', 'gelar_belakang' => null, 'tanggal_lahir' => now()->subYears(62)->toDateString()]);
    $adminPmat = akunLapKepeg(Peran::AdminProdi, $pmat);

    $semua = app(SusunLaporanKepegawaian::class)->pensiun();
    $terbatas = app(SusunLaporanKepegawaian::class)->pensiun($adminPmat);

    expect($semua->pluck('nama')->all())->toBe(['Pensiun Pmat', 'Pensiun Pbio'])->and($terbatas->pluck('nama')->all())->toBe(['Pensiun Pmat']);
});

test('pejabat aktif diurutkan menurut urutan jabatan', function () {
    $dekan = JenisJabatanStruktural::factory()->create(['nama' => 'Dekan Uji', 'urutan' => 1]);
    $kajur = JenisJabatanStruktural::factory()->create(['nama' => 'Ketua Jurusan Uji', 'urutan' => 5]);
    RiwayatJabatanStruktural::factory()->create(['jenis_jabatan_struktural_id' => $kajur->id]);
    RiwayatJabatanStruktural::factory()->create(['jenis_jabatan_struktural_id' => $dekan->id]);
    RiwayatJabatanStruktural::factory()->create(['jenis_jabatan_struktural_id' => $dekan->id, 'tmt_selesai' => '2020-01-01']);

    $pejabat = app(SusunLaporanKepegawaian::class)->pejabat();

    expect($pejabat->pluck('jabatan')->all())->toBe(['Dekan Uji', 'Ketua Jurusan Uji']);
});

test('profil pegawai tidak memuat nik npwp rekening alamat atau keluarga', function () {
    $pegawai = Pegawai::factory()->create([
        'nama' => 'Dosen Profil', 'gelar_belakang' => null, 'nik' => '3278010101900001', 'npwp' => '12.345.678.9-012.345',
        'nomor_rekening' => '1234567890', 'alamat' => 'Jl. Rahasia 99', 'nip' => '198001012005011001',
    ]);
    Keluarga::factory()->create(['pegawai_id' => $pegawai->id, 'nama' => 'Nama Anak Rahasia']);
    $pegawai->load(['prodi', 'unitKerja', 'statusKepegawaian', 'jabatanFungsional', 'golongan', 'riwayatJabatanFungsional', 'riwayatPangkat', 'riwayatJabatanStruktural', 'riwayatPendidikan', 'sertifikasi', 'penghargaan', 'pelatihan']);

    $html = view('pdf.profil-pegawai', ['pegawai' => $pegawai])->render();

    expect($html)->toContain('Dosen Profil')->not->toContain('3278010101900001')->not->toContain('12.345.678.9')
        ->not->toContain('1234567890')->not->toContain('Jl. Rahasia')->not->toContain('Nama Anak Rahasia')->not->toContain('198001012005011001');
});

test('pdf profil dihasilkan sebagai berkas pdf', function () {
    $dosen = akunLapKepeg(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $dosen->id]);

    $pdf = app(CetakProfilPegawai::class)->handle($dosen, $pegawai);

    expect(str_starts_with($pdf, '%PDF'))->toBeTrue();
});

test('dosen dapat mencetak profil sendiri tetapi tidak profil orang lain', function () {
    $dosen = akunLapKepeg(Peran::Dosen);
    $milik = Pegawai::factory()->create(['user_id' => $dosen->id]);
    $lain = Pegawai::factory()->create();

    expect(str_starts_with(app(CetakProfilPegawai::class)->handle($dosen, $milik), '%PDF'))->toBeTrue()
        ->and(fn () => app(CetakProfilPegawai::class)->handle($dosen, $lain))->toThrow(AuthorizationException::class);
});

test('admin-prodi tidak dapat mencetak profil dosen prodi lain tetapi dapat prodinya', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $adminPmat = akunLapKepeg(Peran::AdminProdi, $pmat);

    expect(str_starts_with(app(CetakProfilPegawai::class)->handle($adminPmat, Pegawai::factory()->create(['prodi_id' => $pmat->id])), '%PDF'))->toBeTrue()
        ->and(fn () => app(CetakProfilPegawai::class)->handle($adminPmat, Pegawai::factory()->create(['prodi_id' => $pbio->id])))->toThrow(AuthorizationException::class);
});

test('aksi cetak profil tersedia di halaman view pegawai dan profil saya', function () {
    $pegawai = Pegawai::factory()->create();
    $this->actingAs(akunLapKepeg(Peran::AdminKepegawaian));
    Livewire::test(ViewPegawai::class, ['record' => $pegawai->getKey()])->assertActionVisible('cetakProfil')->callAction('cetakProfil')->assertFileDownloaded();

    $dosen = akunLapKepeg(Peran::Dosen);
    Pegawai::factory()->create(['user_id' => $dosen->id]);
    PersetujuanPrivasi::create(['user_id' => $dosen->id, 'versi' => Konfigurasi::get('versi_kebijakan_privasi'), 'disetujui_at' => now()]);
    $this->actingAs($dosen);
    Livewire::test(ProfilSaya::class)->callAction('cetakProfil')->assertFileDownloaded();
});

test('pdf besar dibuat lewat job di antrean ekspor', function () {
    Queue::fake([BuatPdfLaporan::class]);
    $this->actingAs(akunLapKepeg(Peran::AdminKepegawaian));

    Livewire::test(LaporanKepegawaian::class)->callAction('dukPdf');

    Queue::assertPushed(BuatPdfLaporan::class, fn (BuatPdfLaporan $job) => $job->queue === 'ekspor' && $job->jenis === 'duk');
});

test('job pdf menyimpan berkas sementara dan hanya pembuat yang dapat mengunduh', function () {
    pnsDenganPangkat('Pns Satu', 'III/a', '2020-04-01');
    $pembuat = akunLapKepeg(Peran::AdminKepegawaian);
    $lain = akunLapKepeg(Peran::AdminKepegawaian);

    (new BuatPdfLaporan('duk', $pembuat->id))->handle(app(SusunLaporanKepegawaian::class));

    $notifikasi = $pembuat->notifications()->first();
    expect($notifikasi)->not->toBeNull()->and($notifikasi->data['title'])->toBe('Daftar Urut Kepangkatan siap diunduh');
    $id = collect(Storage::disk('tmp')->allFiles('laporan'))->map(fn ($f) => pathinfo($f, PATHINFO_FILENAME))->first();

    $this->actingAs($pembuat)->get(route('keluaran.unduh', $id))->assertOk()->assertDownload('duk.pdf');
    $this->actingAs($lain)->get(route('keluaran.unduh', $id))->assertForbidden();
    auth()->logout();
    $this->get(route('keluaran.unduh', $id))->assertRedirect();
});

test('unduhan kedaluwarsa atau tidak ada menghasilkan 404', function () {
    $pembuat = akunLapKepeg(Peran::AdminKepegawaian);
    $id = KeluaranSementara::simpan($pembuat, 'Uji', 'uji.pdf', '%PDF-x');
    Cache::forget("sdm:keluaran:{$id}");

    $this->actingAs($pembuat)->get(route('keluaran.unduh', $id))->assertNotFound();
});

test('excel duk dan pensiun diunduh sebagai xlsx', function () {
    pnsDenganPangkat('Pns Excel', 'III/b', '2019-04-01');
    $this->actingAs(akunLapKepeg(Peran::AdminKepegawaian));

    Livewire::test(LaporanKepegawaian::class)->callAction('dukExcel')->assertFileDownloaded('duk.xlsx');
    $this->actingAs(akunLapKepeg(Peran::AdminKepegawaian));
    Livewire::test(LaporanKepegawaian::class)->callAction('pensiunExcel')->assertFileDownloaded('pensiun-5-tahun.xlsx');
});

test('halaman laporan kepegawaian hanya untuk pemegang izin laporan', function () {
    $dosen = akunLapKepeg(Peran::Dosen);

    expect(LaporanKepegawaian::canAccess())->toBeFalse();
    $this->actingAs(akunLapKepeg(Peran::AdminKepegawaian));
    expect(LaporanKepegawaian::canAccess())->toBeTrue();
});
