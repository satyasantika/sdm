<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Bkd\Pages\CreateBkd;
use App\Filament\Admin\Resources\Bkd\Pages\EditBkd;
use App\Filament\Admin\Resources\Bkd\Pages\ListBkd;
use App\Filament\Admin\Widgets\BkdRingkasanWidget;
use App\Filament\Exports\RekapBkdExporter;
use App\Filament\Swalayan\Pages\BkdSaya;
use App\Models\Bkd;
use App\Models\Pegawai;
use App\Models\PersetujuanPrivasi;
use App\Models\Prodi;
use App\Models\Semester;
use App\Models\StatusKepegawaian;
use App\Models\User;
use App\Support\BkdRingkasan;
use App\Support\Konfigurasi;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\ExportAction;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
});

function akunRekap(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function dosenDi(Prodi $prodi, array $atribut = []): Pegawai
{
    return Pegawai::factory()->create(['prodi_id' => $prodi->id] + $atribut);
}

test('admin-prodi hanya melihat bkd dosen prodinya', function () {
    $semester = Semester::aktifSekarang();
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $bkdPmat = Bkd::factory()->create(['pegawai_id' => dosenDi($pmat)->id, 'semester_id' => $semester->id]);
    $bkdPbio = Bkd::factory()->create(['pegawai_id' => dosenDi($pbio)->id, 'semester_id' => $semester->id]);
    $this->actingAs(akunRekap(Peran::AdminProdi, $pmat));

    Livewire::test(ListBkd::class)->loadTable()->assertCanSeeTableRecords([$bkdPmat])->assertCanNotSeeTableRecords([$bkdPbio]);
});

test('ringkasan menghitung dosen tetap tanpa baris bkd pada semester itu', function () {
    $semester = Semester::aktifSekarang();
    $lain = Semester::firstWhere('kode', '20252');
    $prodi = Prodi::factory()->create();
    $a = dosenDi($prodi);
    $b = dosenDi($prodi);
    $c = dosenDi($prodi);
    dosenDi($prodi, ['status_aktif' => 'pensiun']);
    $kontrak = StatusKepegawaian::factory()->create(['dihitung_dosen_tetap' => false]);
    dosenDi($prodi, ['status_kepegawaian_id' => $kontrak->id]);
    Bkd::factory()->create(['pegawai_id' => $a->id, 'semester_id' => $semester->id, 'kesimpulan' => 'memenuhi']);
    Bkd::factory()->create(['pegawai_id' => $b->id, 'semester_id' => $semester->id, 'kesimpulan' => 'tidak_memenuhi']);
    Bkd::factory()->create(['pegawai_id' => $c->id, 'semester_id' => $lain->id, 'kesimpulan' => 'memenuhi']);

    $hasil = BkdRingkasan::hitung($semester->id, $prodi->id);

    expect($hasil)->toBe(['dosen_tetap' => 3, 'memenuhi' => 1, 'tidak_memenuhi' => 1, 'belum_ada_data' => 1])
        ->and(BkdRingkasan::tanpaData($semester->id, $prodi->id)->pluck('id')->all())->toBe([$c->id]);
});

test('ringkasan admin-prodi dibatasi prodinya dan widget menampilkannya', function () {
    $semester = Semester::aktifSekarang();
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    dosenDi($pmat);
    dosenDi($pbio);
    dosenDi($pbio);
    $admin = akunRekap(Peran::AdminProdi, $pmat);

    expect(BkdRingkasan::hitung($semester->id, null, $admin)['dosen_tetap'])->toBe(1)
        ->and(BkdRingkasan::hitung($semester->id, null, akunRekap(Peran::AdminKepegawaian))['dosen_tetap'])->toBe(3);

    $this->actingAs($admin);
    Livewire::test(BkdRingkasanWidget::class, ['tableFilters' => ['semester_id' => ['value' => $semester->id]]])
        ->assertSee('Dosen tetap')->assertSee('Belum ada data BKD');
});

test('ekspor tidak memuat kolom sensitif', function () {
    $header = collect(RekapBkdExporter::getColumns())->map(fn ($k) => mb_strtolower($k->getLabel()))->all();
    $nama = collect(RekapBkdExporter::getColumns())->map(fn ($k) => $k->getName())->all();

    expect($header)->toContain('nama', 'nidn', 'total sks', 'kesimpulan');
    foreach (['nik', 'npwp', 'rekening', 'alamat', 'tanggal_lahir', 'keluarga'] as $terlarang) {
        expect(implode(' ', [...$header, ...$nama]))->not->toContain($terlarang);
    }
});

test('ekspor menghasilkan berkas csv tanpa data sensitif di disk tmp', function () {
    Storage::fake('tmp');
    $semester = Semester::aktifSekarang();
    $prodi = Prodi::factory()->create();
    $dosen = dosenDi($prodi, ['nik' => '3278010101900001', 'tanggal_lahir' => '1980-05-17', 'alamat' => 'Jl. Rahasia 9', 'nama' => 'Dosen Ekspor', 'gelar_belakang' => null]);
    Bkd::factory()->create(['pegawai_id' => $dosen->id, 'semester_id' => $semester->id, 'total_sks' => 14]);
    $this->actingAs(akunRekap(Peran::AdminKepegawaian));
    $kolom = collect(RekapBkdExporter::getColumns())->mapWithKeys(fn ($k) => [$k->getName() => $k->getName()])->all();

    Livewire::test(ListBkd::class)->callAction(ExportAction::class, ['columnMap' => array_map(fn ($n) => ['isEnabled' => true, 'label' => $n], $kolom)]);

    $berkas = collect(Storage::disk('tmp')->allFiles())->first(fn ($f) => str_ends_with($f, '.csv'));
    expect($berkas)->not->toBeNull();
    $isi = Storage::disk('tmp')->get($berkas);
    expect($isi)->toContain('Dosen Ekspor')->not->toContain('3278010101900001')->not->toContain('1980-05-17')->not->toContain('Jl. Rahasia');
});

test('admin-kepegawaian dapat input manual dan koreksi dengan sumber manual', function () {
    $semester = Semester::aktifSekarang();
    $dosen = Pegawai::factory()->create();
    $this->actingAs(akunRekap(Peran::AdminKepegawaian));

    Livewire::test(CreateBkd::class)
        ->fillForm(['pegawai_id' => $dosen->id, 'semester_id' => $semester->id, 'total_sks' => 14, 'kesimpulan' => 'memenuhi'])
        ->call('create')->assertHasNoFormErrors();

    $bkd = Bkd::first();
    expect($bkd->sumber)->toBe('manual');

    Livewire::test(CreateBkd::class)
        ->fillForm(['pegawai_id' => $dosen->id, 'semester_id' => $semester->id, 'kesimpulan' => 'memenuhi'])
        ->call('create')->assertHasFormErrors(['semester_id' => 'unique']);

    Livewire::test(EditBkd::class, ['record' => $bkd->getKey()])
        ->fillForm(['kesimpulan' => 'tidak_memenuhi', 'catatan' => 'Koreksi'])
        ->call('save')->assertHasNoFormErrors();
    expect($bkd->fresh()->kesimpulan->value)->toBe('tidak_memenuhi');
});

test('admin-prodi tidak dapat input manual dan pimpinan hanya melihat', function () {
    $prodi = Prodi::factory()->create();
    $bkd = Bkd::factory()->create(['pegawai_id' => dosenDi($prodi)->id, 'semester_id' => Semester::aktifSekarang()->id]);
    $adminProdi = akunRekap(Peran::AdminProdi, $prodi);
    $pimpinan = akunRekap(Peran::Pimpinan);

    expect($adminProdi->can('create', Bkd::class))->toBeFalse()->and($adminProdi->can('update', $bkd))->toBeFalse()
        ->and($adminProdi->can('view', $bkd))->toBeTrue()
        ->and($pimpinan->can('view', $bkd))->toBeTrue()->and($pimpinan->can('update', $bkd))->toBeFalse()
        ->and($adminProdi->can('delete', $bkd))->toBeFalse();
});

test('dosen melihat bkd sendiri di saya dan tidak milik orang lain', function () {
    $semester = Semester::aktifSekarang();
    $user = User::factory()->create(['email' => 'dosen'.fake()->unique()->numerify('####').'@unsil.ac.id']);
    $user->assignRole(Peran::Dosen->value);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    PersetujuanPrivasi::create(['user_id' => $user->id, 'versi' => Konfigurasi::get('versi_kebijakan_privasi'), 'disetujui_at' => now()]);
    Bkd::factory()->create(['pegawai_id' => $pegawai->id, 'semester_id' => $semester->id, 'total_sks' => 14.5]);
    Bkd::factory()->create(['semester_id' => Semester::firstWhere('kode', '20252')->id, 'total_sks' => 99.99]);

    $this->actingAs($user)->get(BkdSaya::getUrl(panel: 'swalayan'))
        ->assertOk()->assertSee($semester->label)->assertSee('14.50')->assertDontSee('99.99');
});
