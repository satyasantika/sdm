<?php

use App\Enums\KesimpulanBkd;
use App\Enums\Peran;
use App\Filament\Admin\Resources\Bkd\Pages\ListBkd;
use App\Filament\Imports\BkdImporter;
use App\Models\Aktivitas;
use App\Models\BarisImporGagal;
use App\Models\Bkd;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\ImportAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
});

function adminBkd(Peran $peran = Peran::AdminKepegawaian, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

/** @param  array<int, array<string, mixed>>  $baris */
function csvBkd(array $baris): string
{
    $kolom = ['nidn', 'nuptk', 'nip', 'nama', 'sks_pendidikan', 'sks_penelitian', 'sks_pengabdian', 'sks_penunjang', 'total_sks', 'kesimpulan'];
    $h = fopen('php://temp', 'r+');
    fputcsv($h, $kolom);
    foreach ($baris as $b) {
        fputcsv($h, array_map(fn ($k) => $b[$k] ?? '', $kolom));
    }
    rewind($h);

    return stream_get_contents($h);
}

function jalankanImpor(string $csv, Semester $semester, ?User $pelaku = null)
{
    // Job impor sinkron dapat mengganti user auth; pasang ulang sebelum tiap impor.
    test()->actingAs($pelaku ?? User::query()->latest('created_at')->whereHas('roles', fn ($q) => $q->where('name', 'admin-kepegawaian'))->firstOrFail());

    $kolom = ['nidn', 'nuptk', 'nip', 'nama', 'sks_pendidikan', 'sks_penelitian', 'sks_pengabdian', 'sks_penunjang', 'total_sks', 'kesimpulan'];

    return Livewire::test(ListBkd::class)->callAction(ImportAction::class, [
        'file' => UploadedFile::fake()->createWithContent('bkd.csv', $csv),
        'columnMap' => array_combine($kolom, $kolom),
        'semester_id' => $semester->id,
    ]);
}

test('impor csv: 3 baris tersimpan dan 1 gagal dengan pesan indonesia, angka koma dan kesimpulan dinormalisasi', function () {
    $semester = Semester::firstWhere('kode', '20261');
    $d1 = Pegawai::factory()->create(['nidn' => '0401010001', 'nuptk' => null]);
    $d2 = Pegawai::factory()->create(['nidn' => '0401010002', 'nuptk' => null]);
    $d3 = Pegawai::factory()->create(['nidn' => null, 'nuptk' => '1234567890123456']);
    $this->actingAs(adminBkd());

    jalankanImpor(csvBkd([
        ['nidn' => '0401010001', 'sks_pendidikan' => '12,5', 'sks_penelitian' => '3', 'sks_pengabdian' => '2', 'sks_penunjang' => '1', 'total_sks' => '18,5', 'kesimpulan' => 'Memenuhi'],
        ['nidn' => '0401010002', 'sks_pendidikan' => '8', 'total_sks' => '8', 'kesimpulan' => 'TM'],
        ['nuptk' => '1234567890123456', 'sks_pendidikan' => '10', 'kesimpulan' => ''],
        ['nidn' => '9999999999', 'sks_pendidikan' => '10', 'kesimpulan' => 'Memenuhi'],
    ]), $semester)->assertHasNoActionErrors();

    expect(Bkd::count())->toBe(3)
        ->and(Bkd::where('pegawai_id', $d1->id)->first()->sks_pendidikan)->toBe('12.50')
        ->and(Bkd::where('pegawai_id', $d1->id)->first()->total_sks)->toBe('18.50')
        ->and(Bkd::where('pegawai_id', $d2->id)->first()->kesimpulan)->toBe(KesimpulanBkd::TidakMemenuhi)
        ->and(Bkd::where('pegawai_id', $d3->id)->first()->kesimpulan)->toBe(KesimpulanBkd::BelumDinilai)
        ->and(Bkd::where('pegawai_id', $d3->id)->first()->total_sks)->toBe('10.00')
        ->and(Bkd::first()->sumber)->toBe('sister_impor')->and(Bkd::first()->import_id)->not->toBeNull()
        ->and(BarisImporGagal::count())->toBe(1)
        ->and(BarisImporGagal::first()->validation_error)->toBe('NIDN/NUPTK/NIP tidak ditemukan.');
});

test('impor ulang dengan sks berbeda memperbarui dan tidak menggandakan', function () {
    $semester = Semester::firstWhere('kode', '20261');
    $dosen = Pegawai::factory()->create(['nidn' => '0401010001']);
    $this->actingAs(adminBkd());

    jalankanImpor(csvBkd([['nidn' => '0401010001', 'sks_pendidikan' => '8', 'total_sks' => '8', 'kesimpulan' => 'TM']]), $semester);
    jalankanImpor(csvBkd([['nidn' => '0401010001', 'sks_pendidikan' => '12', 'total_sks' => '12', 'kesimpulan' => 'M']]), $semester);

    $bkd = Bkd::where('pegawai_id', $dosen->id)->get();
    expect($bkd)->toHaveCount(1)->and($bkd->first()->sks_pendidikan)->toBe('12.00')->and($bkd->first()->kesimpulan)->toBe(KesimpulanBkd::Memenuhi);
});

test('satu dosen di semester berbeda menghasilkan baris berbeda', function () {
    $dosen = Pegawai::factory()->create(['nidn' => '0401010001']);
    $this->actingAs(adminBkd());

    jalankanImpor(csvBkd([['nidn' => '0401010001', 'sks_pendidikan' => '8', 'kesimpulan' => 'M']]), Semester::firstWhere('kode', '20261'));
    jalankanImpor(csvBkd([['nidn' => '0401010001', 'sks_pendidikan' => '9', 'kesimpulan' => 'M']]), Semester::firstWhere('kode', '20252'));

    expect(Bkd::where('pegawai_id', $dosen->id)->count())->toBe(2);
});

test('tendik tidak dicocokkan sebagai dosen', function () {
    $semester = Semester::firstWhere('kode', '20261');
    Pegawai::factory()->tendik()->create(['nip' => '198501012010012001', 'nidn' => null]);
    $this->actingAs(adminBkd());

    jalankanImpor(csvBkd([['nip' => '198501012010012001', 'sks_pendidikan' => '8', 'kesimpulan' => 'M']]), $semester);

    expect(Bkd::count())->toBe(0)->and(BarisImporGagal::count())->toBe(1);
});

test('lock aktif menolak impor dengan pesan dan lock dilepas setelah impor selesai', function () {
    $semester = Semester::firstWhere('kode', '20261');
    Pegawai::factory()->create(['nidn' => '0401010001']);
    $this->actingAs(adminBkd());

    $lock = Cache::lock(ListBkd::kunciLock($semester->id), 900);
    expect($lock->get())->toBeTrue();

    jalankanImpor(csvBkd([['nidn' => '0401010001', 'sks_pendidikan' => '8', 'kesimpulan' => 'M']]), $semester)
        ->assertNotified('Impor BKD semester ini sedang berjalan.');
    expect(Bkd::count())->toBe(0);

    $lock->release();

    jalankanImpor(csvBkd([['nidn' => '0401010001', 'sks_pendidikan' => '8', 'kesimpulan' => 'M']]), $semester);
    expect(Bkd::count())->toBe(1)
        ->and(Cache::lock(ListBkd::kunciLock($semester->id), 5)->get())->toBeTrue();
});

test('ringkasan impor dicatat di log audit', function () {
    $semester = Semester::firstWhere('kode', '20261');
    Pegawai::factory()->create(['nidn' => '0401010001']);
    $admin = adminBkd();
    $this->actingAs($admin);

    jalankanImpor(csvBkd([
        ['nidn' => '0401010001', 'sks_pendidikan' => '8', 'kesimpulan' => 'M'],
        ['nidn' => '0000000000', 'sks_pendidikan' => '8', 'kesimpulan' => 'M'],
    ]), $semester);

    $log = Aktivitas::where('description', 'impor bkd selesai')->first();
    expect($log)->not->toBeNull()->and($log->causer_id)->toBe($admin->id)
        ->and($log->properties->get('total_baris'))->toBe(2)->and($log->properties->get('berhasil'))->toBe(1)->and($log->properties->get('gagal'))->toBe(1);
});

test('admin-prodi tidak melihat tombol impor', function () {
    $this->actingAs(adminBkd(Peran::AdminProdi, Prodi::factory()->create()));

    Livewire::test(ListBkd::class)->assertActionHidden(ImportAction::class);
});

test('kesimpulan dari teks sumber dinormalisasi', function () {
    expect(KesimpulanBkd::dariTeks('M'))->toBe(KesimpulanBkd::Memenuhi)
        ->and(KesimpulanBkd::dariTeks(' MEMENUHI '))->toBe(KesimpulanBkd::Memenuhi)
        ->and(KesimpulanBkd::dariTeks('T'))->toBe(KesimpulanBkd::TidakMemenuhi)
        ->and(KesimpulanBkd::dariTeks('Tidak  Memenuhi'))->toBe(KesimpulanBkd::TidakMemenuhi)
        ->and(KesimpulanBkd::dariTeks('TM'))->toBe(KesimpulanBkd::TidakMemenuhi)
        ->and(KesimpulanBkd::dariTeks('lain'))->toBe(KesimpulanBkd::BelumDinilai)
        ->and(KesimpulanBkd::dariTeks(null))->toBe(KesimpulanBkd::BelumDinilai)
        ->and(BkdImporter::angka('12,5'))->toBe(12.5)
        ->and(BkdImporter::angka('1.234,5'))->toBe(1234.5)
        ->and(BkdImporter::angka('12.5'))->toBe(12.5)
        ->and(BkdImporter::angka(''))->toBeNull();
});
