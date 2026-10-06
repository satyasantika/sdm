<?php

use App\Actions\Pengingat\UbahStatusPengingat;
use App\Enums\Peran;
use App\Enums\StatusBerlaku;
use App\Enums\StatusPengingat;
use App\Exceptions\TransisiUsulanTidakSah;
use App\Filament\Admin\Resources\Pengingat\Pages\ListPengingat;
use App\Jobs\HitungUlangPengingat;
use App\Jobs\PeriksaTautanBerkas;
use App\Models\DokumenPegawai;
use App\Models\JenisDokumen;
use App\Models\Pegawai;
use App\Models\Pengingat;
use App\Models\PersetujuanPrivasi;
use App\Models\Prodi;
use App\Models\Sertifikasi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Support\Konfigurasi;
use Carbon\Carbon;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-10-06');
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
});

afterEach(fn () => Carbon::setTestNow());

function akunPengingat(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('jadwal memuat perintah harian di zona waktu jakarta', function () {
    $this->artisan('schedule:list --timezone=Asia/Jakarta')
        ->expectsOutputToContain('sdm:tandai-kedaluwarsa')
        ->expectsOutputToContain('sdm:hitung-pengingat')
        ->expectsOutputToContain('sdm:periksa-tautan')
        ->expectsOutputToContain('sdm:bersihkan-tmp')
        ->expectsOutputToContain('activitylog:clean')
        ->assertSuccessful();

    $acara = fn (string $perintah) => collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains((string) $e->command, $perintah));

    expect($acara('sdm:tandai-kedaluwarsa')->expression)->toBe('30 0 * * *')
        ->and($acara('sdm:hitung-pengingat')->expression)->toBe('0 1 * * *')
        ->and($acara('sdm:hitung-pengingat')->timezone)->toBe('Asia/Jakarta')
        ->and($acara('sdm:periksa-tautan')->expression)->toBe('0 3 * * 1');
});

test('tandai kedaluwarsa memperbarui dokumen sertifikasi dan pengingat lewat tanggal', function () {
    $pegawai = Pegawai::factory()->create();
    $jenis = JenisDokumen::factory()->create(['punya_masa_berlaku' => true]);
    $dokumen = DokumenPegawai::factory()->create(['pegawai_id' => $pegawai->id, 'jenis_dokumen_id' => $jenis->id, 'tanggal_kedaluwarsa' => '2026-12-01']);
    $sertifikat = Sertifikasi::factory()->create(['pegawai_id' => $pegawai->id, 'tanggal_kedaluwarsa' => '2027-12-01']);
    $pengingat = Pengingat::factory()->create(['pegawai_id' => $pegawai->id, 'tanggal_jatuh_tempo' => '2026-10-10', 'status' => 'aktif']);
    expect($dokumen->status_berlaku)->toBe(StatusBerlaku::SegeraBerakhir);

    Carbon::setTestNow('2026-12-10');
    Artisan::call('sdm:tandai-kedaluwarsa');

    expect($dokumen->fresh()->status_berlaku)->toBe(StatusBerlaku::Kedaluwarsa)
        ->and($sertifikat->fresh()->status_berlaku)->toBe(StatusBerlaku::Berlaku)
        ->and($pengingat->fresh()->status)->toBe(StatusPengingat::LewatTempo)
        ->and(Artisan::output())->toContain('Dokumen diperbarui: 1');
});

test('hitung pengingat tidak dispatch bila lock dipegang', function () {
    Queue::fake([HitungUlangPengingat::class]);
    $lock = Cache::lock('sdm:lock:pengingat-harian', 1800);
    expect($lock->get())->toBeTrue();

    Artisan::call('sdm:hitung-pengingat');
    Queue::assertNotPushed(HitungUlangPengingat::class);

    $lock->release();
    Artisan::call('sdm:hitung-pengingat');
    Queue::assertPushed(HitungUlangPengingat::class);
});

test('periksa tautan mengantrekan sensitif lebih dahulu dan menghormati interval', function () {
    Bus::fake([PeriksaTautanBerkas::class]);
    $biasa = TautanBerkas::factory()->create(['is_sensitif' => false, 'dicek_pada' => null]);
    $sensitif = TautanBerkas::factory()->create(['is_sensitif' => true, 'dicek_pada' => null]);
    TautanBerkas::factory()->create(['dicek_pada' => now()->subDay()]);
    $lama = TautanBerkas::factory()->create(['dicek_pada' => now()->subDays(10)]);

    Artisan::call('sdm:periksa-tautan');

    $urutan = [];
    Bus::assertDispatched(PeriksaTautanBerkas::class, function ($job) use (&$urutan) {
        $urutan[] = $job->tautanId;

        return true;
    });
    expect($urutan)->toHaveCount(3)->and($urutan[0])->toBe($sensitif->id)->and($urutan)->toContain($biasa->id, $lama->id);
});

test('bersihkan tmp menghapus berkas lebih tua dari 24 jam', function () {
    Storage::fake('tmp');
    Storage::disk('tmp')->put('ekspor/lama.csv', 'x');
    Storage::disk('tmp')->put('ekspor/baru.csv', 'y');
    touch(Storage::disk('tmp')->path('ekspor/lama.csv'), now()->subHours(30)->getTimestamp());

    Artisan::call('sdm:bersihkan-tmp');

    expect(Storage::disk('tmp')->exists('ekspor/lama.csv'))->toBeFalse()->and(Storage::disk('tmp')->exists('ekspor/baru.csv'))->toBeTrue();
});

test('admin-prodi hanya melihat pengingat prodinya', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $milik = Pengingat::factory()->create(['pegawai_id' => Pegawai::factory()->create(['prodi_id' => $pmat->id])->id]);
    $lain = Pengingat::factory()->create(['pegawai_id' => Pegawai::factory()->create(['prodi_id' => $pbio->id])->id]);
    $this->actingAs(akunPengingat(Peran::AdminProdi, $pmat));

    Livewire::test(ListPengingat::class)->loadTable()->assertCanSeeTableRecords([$milik])->assertCanNotSeeTableRecords([$lain]);
});

test('abaikan tanpa catatan ditolak dan tindaklanjuti berhasil', function () {
    $pengingat = Pengingat::factory()->create();
    $admin = akunPengingat(Peran::AdminKepegawaian);

    expect(fn () => app(UbahStatusPengingat::class)->handle($pengingat, StatusPengingat::Diabaikan, $admin, ''))->toThrow(ValidationException::class);

    $this->actingAs($admin);
    Livewire::test(ListPengingat::class)->loadTable()
        ->callAction(TestAction::make('abaikan')->table($pengingat), ['catatan' => 'Sudah ditangani di luar sistem'])
        ->assertNotified('Status pengingat diperbarui.');
    expect($pengingat->fresh()->status)->toBe(StatusPengingat::Diabaikan)->and($pengingat->fresh()->catatan)->toBe('Sudah ditangani di luar sistem');

    $lagi = Pengingat::factory()->create();
    app(UbahStatusPengingat::class)->handle($lagi, StatusPengingat::Ditindaklanjuti, $admin);
    expect($lagi->fresh()->status)->toBe(StatusPengingat::Ditindaklanjuti)
        ->and(fn () => app(UbahStatusPengingat::class)->handle($pengingat->fresh(), StatusPengingat::Aktif, $admin))->toThrow(TransisiUsulanTidakSah::class);
});

test('admin-prodi tidak dapat mengubah status pengingat', function () {
    $prodi = Prodi::factory()->create();
    $pengingat = Pengingat::factory()->create(['pegawai_id' => Pegawai::factory()->create(['prodi_id' => $prodi->id])->id]);
    $adminProdi = akunPengingat(Peran::AdminProdi, $prodi);

    expect($adminProdi->can('view', $pengingat))->toBeTrue()->and($adminProdi->can('update', $pengingat))->toBeFalse();

    $this->actingAs($adminProdi);
    Livewire::test(ListPengingat::class)->loadTable()->assertActionHidden(TestAction::make('abaikan')->table($pengingat));
});

test('dosen melihat pengingatnya di beranda saya', function () {
    $user = akunPengingat(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    PersetujuanPrivasi::create(['user_id' => $user->id, 'versi' => Konfigurasi::get('versi_kebijakan_privasi'), 'disetujui_at' => now()]);
    Pengingat::factory()->create(['pegawai_id' => $pegawai->id, 'jenis' => 'kgb', 'tanggal_jatuh_tempo' => '2026-11-01']);
    Pengingat::factory()->create(['jenis' => 'pensiun', 'tanggal_jatuh_tempo' => '2026-11-02']);

    $this->actingAs($user)->get('/saya')->assertOk()->assertSee('Kenaikan gaji berkala')->assertSee('26 hari lagi')->assertDontSee('Pensiun');
});
