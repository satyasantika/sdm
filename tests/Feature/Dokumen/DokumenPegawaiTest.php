<?php

use App\Actions\Riwayat\SimpanDokumenPegawai;
use App\Actions\Usulan\AjukanUsulanPerubahan;
use App\Actions\Usulan\BuatUsulanPerubahan;
use App\Actions\Usulan\SetujuiUsulanPerubahan;
use App\Enums\JenisUsulan;
use App\Enums\Peran;
use App\Enums\StatusBerlaku;
use App\Filament\Admin\Resources\DokumenPegawai\Pages\ListDokumenPegawai;
use App\Filament\Admin\Resources\Pegawai\Pages\EditPegawai;
use App\Filament\Admin\Resources\Pegawai\RelationManagers\DokumenRelationManager;
use App\Filament\Admin\Widgets\DokumenKedaluwarsaWidget;
use App\Models\DokumenPegawai;
use App\Models\JenisDokumen;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
    Carbon::setTestNow('2026-10-06');
});

afterEach(fn () => Carbon::setTestNow());

function jenisDok(string $kode): JenisDokumen
{
    return JenisDokumen::firstWhere('kode', $kode);
}

function akunDokumen(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('paspor tanpa tanggal kedaluwarsa ditolak', function () {
    expect(fn () => app(SimpanDokumenPegawai::class)->handle(Pegawai::factory()->create(), ['jenis_dokumen_id' => jenisDok('paspor')->id]))
        ->toThrow(ValidationException::class);
});

test('status berlaku mengikuti tanggal kedaluwarsa', function () {
    $pegawai = Pegawai::factory()->create();

    $segera = app(SimpanDokumenPegawai::class)->handle($pegawai, ['jenis_dokumen_id' => jenisDok('paspor')->id, 'tanggal_kedaluwarsa' => '2026-11-05']);
    $lewat = app(SimpanDokumenPegawai::class)->handle($pegawai, ['jenis_dokumen_id' => jenisDok('bpjs-kesehatan')->id, 'tanggal_kedaluwarsa' => '2026-01-01']);
    $tanpa = app(SimpanDokumenPegawai::class)->handle($pegawai, ['jenis_dokumen_id' => jenisDok('karpeg')->id]);

    expect($segera->status_berlaku)->toBe(StatusBerlaku::SegeraBerakhir)
        ->and($lewat->status_berlaku)->toBe(StatusBerlaku::Kedaluwarsa)
        ->and($tanpa->status_berlaku)->toBe(StatusBerlaku::TanpaBatas);
});

test('nomor dokumen identitas dikosongkan', function () {
    $ktp = app(SimpanDokumenPegawai::class)->handle(Pegawai::factory()->create(), ['jenis_dokumen_id' => jenisDok('ktp')->id, 'nomor' => '3278010101900001']);

    expect($ktp->fresh()->nomor)->toBeNull();
});

test('admin-prodi tidak melihat ktp dosen prodinya tetapi melihat karpeg', function () {
    $prodi = Prodi::factory()->create();
    $pegawai = Pegawai::factory()->create(['prodi_id' => $prodi->id]);
    $ktp = DokumenPegawai::factory()->create(['pegawai_id' => $pegawai->id, 'jenis_dokumen_id' => jenisDok('ktp')->id]);
    $karpeg = DokumenPegawai::factory()->create(['pegawai_id' => $pegawai->id, 'jenis_dokumen_id' => jenisDok('karpeg')->id]);
    $adminProdi = akunDokumen(Peran::AdminProdi, $prodi);

    expect($adminProdi->can('view', $ktp))->toBeFalse()->and($adminProdi->can('view', $karpeg))->toBeTrue()
        ->and(akunDokumen(Peran::AdminKepegawaian)->can('view', $ktp))->toBeTrue();

    $this->actingAs($adminProdi);
    Livewire::test(ListDokumenPegawai::class)->loadTable()
        ->filterTable('status_berlaku', null)
        ->assertCanSeeTableRecords([$karpeg])->assertCanNotSeeTableRecords([$ktp]);
});

test('pemilik boleh melihat dokumen identitas sendiri', function () {
    $dosen = akunDokumen(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $dosen->id]);
    $ktp = DokumenPegawai::factory()->create(['pegawai_id' => $pegawai->id, 'jenis_dokumen_id' => jenisDok('ktp')->id]);

    expect($dosen->can('view', $ktp))->toBeTrue();
});

test('admin-prodi dapat mencatat dokumen non identitas untuk dosen prodinya tetapi tidak identitas', function () {
    $prodi = Prodi::factory()->create();
    $pegawai = Pegawai::factory()->create(['prodi_id' => $prodi->id]);
    $this->actingAs(akunDokumen(Peran::AdminProdi, $prodi));
    $url = 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view';

    Livewire::test(DokumenRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->callAction(TestAction::make('create')->table(), [
            'jenis_dokumen_id' => jenisDok('karpeg')->id, 'tautan_dokumen' => $url, 'tautan_dokumen_konfirmasi' => true,
        ])->assertHasNoFormErrors();

    $dokumen = DokumenPegawai::where('pegawai_id', $pegawai->id)->first();
    expect($dokumen->berkas()->is_sensitif)->toBeTrue()->and($dokumen->berkas()->jenis->value)->toBe('dokumen_kepegawaian');

    expect(fn () => app(SimpanDokumenPegawai::class)->handle($pegawai, ['jenis_dokumen_id' => jenisDok('ktp')->id], akunDokumen(Peran::AdminProdi, $prodi)))
        ->toThrow(ValidationException::class);
});

test('dokumen baru wajib disertai tautan', function () {
    $pegawai = Pegawai::factory()->create();
    $this->actingAs(akunDokumen(Peran::AdminKepegawaian));

    Livewire::test(DokumenRelationManager::class, ['ownerRecord' => $pegawai, 'pageClass' => EditPegawai::class])
        ->callAction(TestAction::make('create')->table(), ['jenis_dokumen_id' => jenisDok('karpeg')->id]);

    expect(DokumenPegawai::count())->toBe(0);
});

test('usulan tautan dokumen dari swalayan dapat disetujui dan menghasilkan record', function () {
    Notification::fake();
    $dosen = akunDokumen(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $dosen->id]);
    $url = 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view';

    $usulan = app(BuatUsulanPerubahan::class)->handle($dosen, $pegawai, JenisUsulan::TambahRiwayat, 'dokumen_pegawai', null, [
        'jenis_dokumen_id' => jenisDok('ktp')->id, 'tanggal_terbit' => '2024-01-01', 'tautan' => ['dokumen' => $url],
    ], null, 'Tambah KTP');
    app(AjukanUsulanPerubahan::class)->handle($usulan, $dosen);

    app(SetujuiUsulanPerubahan::class)->handle($usulan, akunDokumen(Peran::AdminKepegawaian));

    $dokumen = DokumenPegawai::where('pegawai_id', $pegawai->id)->first();
    expect($dokumen)->not->toBeNull()->and($dokumen->berkas()->url)->toBe($url)
        ->and($dokumen->berkas()->jenis->value)->toBe('dokumen_identitas');
});

test('widget menghitung dokumen per status', function () {
    $pegawai = Pegawai::factory()->create();
    foreach (['2026-10-10', '2026-12-01', '2028-01-01'] as $tgl) {
        app(SimpanDokumenPegawai::class)->handle($pegawai, ['jenis_dokumen_id' => jenisDok('paspor')->id, 'tanggal_kedaluwarsa' => $tgl]);
    }
    $this->actingAs(akunDokumen(Peran::AdminKepegawaian));

    Livewire::test(DokumenKedaluwarsaWidget::class)->assertSee('Segera berakhir')->assertSee('Kedaluwarsa');
    expect(DokumenPegawai::where('status_berlaku', 'segera_berakhir')->count())->toBe(2);
});
