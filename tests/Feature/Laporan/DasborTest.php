<?php

use App\Actions\Laporan\HitungStatistikDasbor;
use App\Enums\Peran;
use App\Filament\Admin\Widgets\DasborStatistikWidget;
use App\Filament\Admin\Widgets\DosenPerProdiWidget;
use App\Filament\Admin\Widgets\PensiunLimaTahunWidget;
use App\Jobs\SegarkanStatistikDasbor;
use App\Models\JabatanFungsional;
use App\Models\JenisSertifikasi;
use App\Models\JenjangPendidikan;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatPendidikan;
use App\Models\Sertifikasi;
use App\Models\User;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterJabatanSeeder;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterJabatanSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
    Queue::fake([SegarkanStatistikDasbor::class]);
    Cache::flush();
});

function akunDasbor(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function dosenJabatan(Prodi $prodi, string $kodeJabatan): Pegawai
{
    return Pegawai::factory()->create(['prodi_id' => $prodi->id, 'jabatan_fungsional_id' => JabatanFungsional::firstWhere('kode', $kodeJabatan)->id]);
}

test('statistik sesuai data: 3 lektor 2 lektor kepala, pendidikan s3 dan serdos', function () {
    $prodi = Prodi::factory()->create();
    foreach (range(1, 3) as $i) {
        dosenJabatan($prodi, 'lektor');
    }
    $kepala1 = dosenJabatan($prodi, 'lektor-kepala');
    dosenJabatan($prodi, 'lektor-kepala');
    RiwayatPendidikan::factory()->create(['pegawai_id' => $kepala1->id, 'jenjang_pendidikan_id' => JenjangPendidikan::firstWhere('kode', 'S3')->id, 'is_pendidikan_tertinggi' => true]);
    Sertifikasi::factory()->create(['pegawai_id' => $kepala1->id, 'jenis_sertifikasi_id' => JenisSertifikasi::firstWhere('kode', 'serdos')->id]);
    Pegawai::factory()->tendik()->create();
    Pegawai::factory()->create(['prodi_id' => $prodi->id, 'status_aktif' => 'tugas_belajar']);

    $s = app(HitungStatistikDasbor::class)->handle();

    expect($s['jumlah_dosen'])->toBe(5)->and($s['jumlah_tendik'])->toBe(1)
        ->and($s['dosen_per_jabatan']['Lektor'])->toBe(3)->and($s['dosen_per_jabatan']['Lektor Kepala'])->toBe(2)
        ->and($s['dosen_per_pendidikan']['S3'])->toBe(1)->and($s['dosen_per_pendidikan']['Belum ada'])->toBe(4)
        ->and($s['persen_s3'])->toBe(20.0)->and($s['jumlah_serdos'])->toBe(1)->and($s['persen_serdos'])->toBe(20.0)
        ->and($s['tugas_belajar'])->toBe(1)->and($s['dosen_per_prodi'][$prodi->nama])->toBe(5);
});

test('pensiun lima tahun dikelompokkan per tahun', function () {
    $prodi = Prodi::factory()->create();
    Pegawai::factory()->create(['prodi_id' => $prodi->id, 'tanggal_lahir' => now()->subYears(63)->toDateString()]);
    Pegawai::factory()->create(['prodi_id' => $prodi->id, 'tanggal_lahir' => now()->subYears(63)->toDateString()]);
    Pegawai::factory()->create(['prodi_id' => $prodi->id, 'tanggal_lahir' => now()->subYears(30)->toDateString()]);

    $s = app(HitungStatistikDasbor::class)->handle();

    expect(array_sum($s['pensiun_per_tahun']))->toBe(2)->and(array_keys($s['pensiun_per_tahun']))->each->toBeInt();
});

test('panggilan kedua diambil dari cache tanpa kueri', function () {
    $prodi = Prodi::factory()->create();
    dosenJabatan($prodi, 'lektor');
    app(HitungStatistikDasbor::class)->handle();

    DB::enableQueryLog();
    app(HitungStatistikDasbor::class)->handle();

    expect(DB::getQueryLog())->toHaveCount(0)->and(Cache::has('sdm:dasbor:statistik:fakultas'))->toBeTrue();
});

test('mengubah jabatan fungsional membersihkan cache fakultas dan prodi', function () {
    $prodi = Prodi::factory()->create();
    $dosen = dosenJabatan($prodi, 'lektor');
    app(HitungStatistikDasbor::class)->handle();
    app(HitungStatistikDasbor::class)->handle((string) $prodi->id);
    expect(Cache::has('sdm:dasbor:statistik:fakultas'))->toBeTrue()->and(Cache::has("sdm:dasbor:statistik:prodi:{$prodi->id}"))->toBeTrue();

    $dosen->update(['jabatan_fungsional_id' => JabatanFungsional::firstWhere('kode', 'lektor-kepala')->id]);

    expect(Cache::has('sdm:dasbor:statistik:fakultas'))->toBeFalse()->and(Cache::has("sdm:dasbor:statistik:prodi:{$prodi->id}"))->toBeFalse();
    Queue::assertPushed(SegarkanStatistikDasbor::class);
    expect(app(HitungStatistikDasbor::class)->handle()['dosen_per_jabatan'])->toHaveKey('Lektor Kepala');
});

test('segarkan dilakukan sekali per jendela debounce', function () {
    $prodi = Prodi::factory()->create();
    Pegawai::factory()->count(3)->create(['prodi_id' => $prodi->id]);

    Queue::assertPushed(SegarkanStatistikDasbor::class, 1);
});

test('admin-prodi hanya melihat angka prodinya di dasbor', function () {
    $pmat = Prodi::factory()->create(['nama' => 'Prodi Matematika Uji']);
    $pbio = Prodi::factory()->create(['nama' => 'Prodi Biologi Uji']);
    dosenJabatan($pmat, 'lektor');
    dosenJabatan($pbio, 'lektor');
    dosenJabatan($pbio, 'lektor');
    Pegawai::factory()->create(['prodi_id' => $pbio->id, 'nama' => 'Nama Dosen Pbio', 'tanggal_lahir' => now()->subYears(64)->toDateString(), 'gelar_belakang' => null]);
    $this->actingAs(akunDasbor(Peran::AdminProdi, $pmat));

    Livewire::test(DasborStatistikWidget::class)->assertSee('Dosen aktif');
    Livewire::test(DosenPerProdiWidget::class)->loadTable()
        ->assertSee('Prodi Matematika Uji')->assertDontSee('Prodi Biologi Uji');
    Livewire::test(PensiunLimaTahunWidget::class)->loadTable()->assertDontSee('Nama Dosen Pbio');
});

test('pimpinan membuka dasbor dan filter prodi mempersempit', function () {
    $pmat = Prodi::factory()->create(['nama' => 'Prodi Matematika Uji']);
    $pbio = Prodi::factory()->create(['nama' => 'Prodi Biologi Uji']);
    dosenJabatan($pmat, 'lektor');
    dosenJabatan($pbio, 'lektor');
    $this->actingAs(akunDasbor(Peran::Pimpinan));

    $this->get('/admin')->assertOk();
    Livewire::test(DosenPerProdiWidget::class, ['pageFilters' => ['prodi_id' => $pmat->id]])->loadTable()
        ->assertSee('Prodi Matematika Uji')->assertDontSee('Prodi Biologi Uji');
});

test('dosen tidak dapat membuka dasbor admin', function () {
    $dosen = User::factory()->create(['email' => 'dosen@unsil.ac.id']);
    $dosen->assignRole(Peran::Dosen->value);

    $this->actingAs($dosen)->get('/admin')->assertForbidden();
});
