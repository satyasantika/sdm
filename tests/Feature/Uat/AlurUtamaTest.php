<?php

use App\Actions\Api\BuatTokenKlienApi;
use App\Actions\Laporan\HitungStatistikDasbor;
use App\Actions\Pegawai\BuatAkunPegawai;
use App\Actions\Pegawai\BuatPegawai;
use App\Actions\Riwayat\SimpanRiwayatPendidikan;
use App\Actions\Usulan\KirimUsulanSaya;
use App\Actions\Usulan\SetujuiUsulanPerubahan;
use App\Enums\JenisPegawai;
use App\Enums\JenisUsulan;
use App\Enums\Peran;
use App\Enums\StatusUsulan;
use App\Filament\Admin\Pages\LaporanAkreditasi;
use App\Filament\Exports\ProfilDosenProdiExporter;
use App\Filament\Swalayan\Pages\PersetujuanPrivasi;
use App\Models\JenjangPendidikan;
use App\Models\Prodi;
use App\Models\StatusKepegawaian;
use App\Models\User;
use App\Notifications\AkunSwalayanDibuat;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterKepegawaianSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * UAT end-to-end tingkat feature (docs/05-UJI-PENERIMAAN.md): dari pembuatan dosen sampai muncul di API.
 */
test('alur utama: dosen baru menambah s3, disetujui, lalu tampil di dasbor, ekspor, dan api', function () {
    Notification::fake();
    Storage::fake('tmp');
    foreach ([PeranDanIzinSeeder::class, KonfigurasiSeeder::class, MasterKepegawaianSeeder::class, MasterPendukungSeeder::class] as $seeder) {
        $this->seed($seeder);
    }
    $prodi = Prodi::factory()->create(['kode' => 'PMAT']);
    $admin = User::factory()->create(['email' => 'kepeg@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP'])->assignRole(Peran::AdminKepegawaian->value);
    $superAdmin = User::factory()->create(['email' => 'sa@unsil.ac.id'])->assignRole(Peran::SuperAdmin->value);
    $s2 = JenjangPendidikan::firstWhere('kode', 'S2');
    $s3 = JenjangPendidikan::firstWhere('kode', 'S3');

    // 1. Admin kepegawaian membuat dosen (bergelar S2) dan akunnya.
    $this->actingAs($admin);
    $dosen = app(BuatPegawai::class)->handle([
        'jenis_pegawai' => JenisPegawai::Dosen, 'nama' => 'Dewi Uat', 'gelar_belakang' => 'M.Pd.', 'nik' => '3278010101900077',
        'nip' => '198501012010012001', 'nidn' => '0401018577', 'email_unsil' => 'dewi.uat@unsil.ac.id', 'prodi_id' => $prodi->id,
        'status_kepegawaian_id' => StatusKepegawaian::firstWhere('kode', 'pns')->id, 'tanggal_lahir' => '1985-01-01',
        'jenis_kelamin' => 'P', 'agama' => 'Islam', 'status_perkawinan' => 'kawin', 'tmt_masuk' => '2010-01-01',
    ]);
    app(SimpanRiwayatPendidikan::class)->handle($dosen, ['jenjang_pendidikan_id' => $s2->id, 'nama_pt' => 'Universitas S2 Uat', 'tahun_lulus' => 2010]);
    $akun = app(BuatAkunPegawai::class)->handle($dosen);
    Notification::assertSentTo($akun, AkunSwalayanDibuat::class);

    $sebelum = app(HitungStatistikDasbor::class)->handle(null);
    expect($sebelum['persen_s3'])->toBe(0.0)->and($sebelum['jumlah_dosen'])->toBe(1);

    // 2. Dosen menyetujui pemberitahuan privasi.
    $this->actingAs($akun)->get('/saya')->assertRedirect(PersetujuanPrivasi::getUrl(panel: 'swalayan'));
    Livewire::actingAs($akun)->test(PersetujuanPrivasi::class)->set('setuju', true)->call('setujui');
    $this->actingAs($akun)->get('/saya')->assertOk();

    // 3. Dosen mengajukan tambah S3 dengan bukti (tautan, bukan unggahan).
    $usulan = app(KirimUsulanSaya::class)->handle($akun, $dosen, JenisUsulan::TambahRiwayat, 'riwayat_pendidikan', null, [
        'jenjang_pendidikan_id' => $s3->id, 'nama_pt' => 'Universitas S3 Uat', 'bidang_ilmu' => 'Pendidikan Matematika', 'tahun_lulus' => 2024,
        'tautan_bukti' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view', 'tautan_bukti_konfirmasi' => true,
        'alasan' => 'Lulus doktor 2024',
    ]);
    expect($usulan->status)->toBe(StatusUsulan::Diajukan)->and($dosen->fresh()->pendidikanTertinggi->jenjangPendidikan->kode)->toBe('S2');

    // 4. Admin menyetujui → S3 menjadi pendidikan tertinggi.
    app(SetujuiUsulanPerubahan::class)->handle($usulan, $admin);
    expect($usulan->fresh()->status)->toBe(StatusUsulan::Disetujui)->and($dosen->fresh()->pendidikanTertinggi->jenjangPendidikan->kode)->toBe('S3');

    // 5. Statistik dasbor: persentase S3 naik.
    $sesudah = app(HitungStatistikDasbor::class)->handle(null);
    expect($sesudah['persen_s3'])->toBe(100.0)->and($sesudah['dosen_per_pendidikan'])->toBe(['S3' => 1]);

    // 6. Ekspor profil dosen memuat S3.
    $this->actingAs($admin);
    $kolom = collect(ProfilDosenProdiExporter::getColumns())->mapWithKeys(fn ($k) => [$k->getName() => $k->getName()])->all();
    Livewire::test(LaporanAkreditasi::class)->set('data.prodi_id', $prodi->id)
        ->callAction('eksporProfil', ['columnMap' => array_map(fn ($n) => ['isEnabled' => true, 'label' => $n], $kolom)]);
    $csv = Storage::disk('tmp')->get(collect(Storage::disk('tmp')->allFiles())->first(fn ($f) => str_ends_with($f, '.csv')));
    expect($csv)->toContain('Dewi Uat')->toContain('Universitas S3 Uat')->toContain('Pendidikan Matematika')->not->toContain('3278010101900077');

    // 7. API memuat pendidikan tertinggi S3 tanpa data sensitif.
    [, $token] = app(BuatTokenKlienApi::class)->handle($superAdmin, 'Uat', 'uat-1');
    $respons = $this->withToken($token)->getJson('/api/v1/dosen?prodi=PMAT')->assertOk()->assertJsonCount(1, 'data');
    $respons->assertJsonPath('data.0.nama_bergelar', 'Dewi Uat, M.Pd.')->assertJsonPath('data.0.pendidikan_tertinggi.jenjang', $s3->nama);
    expect($respons->getContent())->not->toContain('3278010101900077')->not->toContain('198501012010012001');
});
