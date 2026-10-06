<?php

use App\Actions\Berkas\GantiTautanBerkas;
use App\Actions\Berkas\SimpanTautanBerkas;
use App\Enums\JenisTautan;
use App\Enums\PenyediaBerkas;
use App\Enums\Peran;
use App\Enums\StatusCekTautan;
use App\Jobs\PeriksaTautanBerkas;
use App\Models\Aktivitas;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Notifications\TautanBerkasBermasalah;
use App\Rules\TautanBerkasValid;
use App\Support\DriveUrl;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Bus::fake([PeriksaTautanBerkas::class]);
});

function tautanValid(): string
{
    return 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view?usp=sharing';
}

function penggunaBerkas(Peran $peran, ?Prodi $prodi = null): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function tautanUntuk(Pegawai $pegawai, bool $sensitif, string $jenis = 'lainnya'): TautanBerkas
{
    return TautanBerkas::factory()->create([
        'pemilik_id' => $pegawai->id, 'pegawai_id' => $pegawai->id,
        'is_sensitif' => $sensitif, 'jenis' => $jenis, 'penyedia' => PenyediaBerkas::GoogleDrive,
    ]);
}

test('tautan drive valid tersimpan dengan id berkas dan penyedia', function () {
    $pegawai = Pegawai::factory()->create();

    $tautan = app(SimpanTautanBerkas::class)->handle($pegawai, JenisTautan::Foto, tautanValid(), 'Foto profil');

    expect($tautan->penyedia)->toBe(PenyediaBerkas::GoogleDrive)
        ->and($tautan->drive_file_id)->toBe('1AbCdEfGhIjKlMnOpQrStUvWxYz012345')
        ->and($tautan->is_sensitif)->toBeFalse()
        ->and($tautan->status_cek)->toBe(StatusCekTautan::Belum)
        ->and($tautan->pegawai_id)->toBe($pegawai->id);
    Bus::assertDispatched(PeriksaTautanBerkas::class);
});

test('drive url mengekstrak id dari berbagai pola dan menolak folder', function () {
    $id = '1AbCdEfGhIjKlMnOpQrStUvWxYz012345';

    expect(DriveUrl::fileId("https://drive.google.com/open?id={$id}"))->toBe($id)
        ->and(DriveUrl::fileId("https://drive.google.com/uc?id={$id}&export=download"))->toBe($id)
        ->and(DriveUrl::fileId("https://docs.google.com/document/d/{$id}/edit"))->toBe($id)
        ->and(DriveUrl::fileId("https://docs.google.com/spreadsheets/d/{$id}/edit"))->toBe($id)
        ->and(DriveUrl::folder("https://drive.google.com/drive/folders/{$id}"))->toBeTrue()
        ->and(DriveUrl::fileId("https://drive.google.com/drive/folders/{$id}"))->toBeNull()
        ->and(DriveUrl::penyedia('https://docs.google.com/document/d/x'))->toBe(PenyediaBerkas::GoogleDocs)
        ->and(DriveUrl::penyedia('https://simpeg.unsil.ac.id/a'))->toBe(PenyediaBerkas::Unsil);
});

test('tautan tidak valid ditolak', function (string $url) {
    $hasil = Validator::make(['url' => $url], ['url' => [new TautanBerkasValid]]);

    expect($hasil->fails())->toBeTrue();
})->with([
    'http polos' => 'http://drive.google.com/file/d/1AbCdEfGhIjKl/view',
    'pemendek bit.ly' => 'https://bit.ly/abc',
    'pemendek s.id' => 'https://s.id/abc',
    'javascript' => 'javascript:alert(1)',
    'domain luar' => 'https://evil.example.com/file.pdf',
    'domain menyerupai' => 'https://drive.google.com.evil.com/file/d/1AbCdEfGhIjKl/view',
    'folder' => 'https://drive.google.com/drive/folders/1AbCdEfGhIjKlMnOpQrStUv',
]);

test('domain unsil dan subdomainnya diterima', function () {
    foreach (['https://simpeg.unsil.ac.id/berkas/1', 'https://unsil.ac.id/x', tautanValid()] as $url) {
        expect(Validator::make(['url' => $url], ['url' => [new TautanBerkasValid]])->passes())->toBeTrue();
    }
});

test('jenis sensitif tanpa konfirmasi berbagi terbatas ditolak', function () {
    $pegawai = Pegawai::factory()->create();

    expect(fn () => app(SimpanTautanBerkas::class)->handle($pegawai, JenisTautan::Ijazah, tautanValid()))
        ->toThrow(ValidationException::class);

    $tautan = app(SimpanTautanBerkas::class)->handle($pegawai, JenisTautan::Ijazah, tautanValid(), null, null, null, true);

    expect($tautan->is_sensitif)->toBeTrue()
        ->and(Aktivitas::where('description', 'konfirmasi berbagi terbatas')->count())->toBe(1);
});

test('mengganti tautan mencatat url lama dan baru di log', function () {
    $pegawai = Pegawai::factory()->create();
    $tautan = app(SimpanTautanBerkas::class)->handle($pegawai, JenisTautan::Foto, tautanValid());
    $baru = 'https://drive.google.com/file/d/9ZyXwVuTsRqPoNmLkJiHgFeDcBa987654/view';

    app(GantiTautanBerkas::class)->handle($tautan, $baru);

    $log = Aktivitas::where('subject_id', $tautan->id)->where('event', 'updated')->first();
    expect(json_encode([$log->attribute_changes, $log->properties]))->toContain('1AbCdEfGhIjKl')->toContain('9ZyXwVuTsRqPoNm')
        ->and($tautan->fresh()->drive_file_id)->toBe('9ZyXwVuTsRqPoNmLkJiHgFeDcBa987654');
});

test('job: berkas sensitif terbuka anonim menjadi terlalu terbuka dan memberi notifikasi', function () {
    Notification::fake();
    $admin = penggunaBerkas(Peran::AdminKepegawaian);
    $dosen = penggunaBerkas(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $dosen->id]);
    $tautan = tautanUntuk($pegawai, true, 'ijazah');
    Http::fake(['drive.google.com/*' => Http::response('isi', 200)]);

    (new PeriksaTautanBerkas($tautan->id))->handle();

    expect($tautan->fresh()->status_cek)->toBe(StatusCekTautan::TerlaluTerbuka)
        ->and($tautan->fresh()->kode_http_terakhir)->toBe(200)
        ->and($tautan->fresh()->dicek_pada)->not->toBeNull();
    Notification::assertSentTo([$admin, $dosen], TautanBerkasBermasalah::class);
});

test('job: berkas non sensitif 200 dapat diakses', function () {
    $tautan = tautanUntuk(Pegawai::factory()->create(), false);
    Http::fake(['drive.google.com/*' => Http::response('isi', 200)]);

    (new PeriksaTautanBerkas($tautan->id))->handle();

    expect($tautan->fresh()->status_cek)->toBe(StatusCekTautan::DapatDiakses);
});

test('job: redirect ke login google berarti terbatas', function () {
    $tautan = tautanUntuk(Pegawai::factory()->create(), true, 'sk');
    Http::fake(['drive.google.com/*' => Http::response('', 302, ['Location' => 'https://accounts.google.com/ServiceLogin?x=1'])]);

    (new PeriksaTautanBerkas($tautan->id))->handle();

    expect($tautan->fresh()->status_cek)->toBe(StatusCekTautan::Terbatas);
});

test('job: 404 tidak dapat diakses dan memberi notifikasi', function () {
    Notification::fake();
    $admin = penggunaBerkas(Peran::AdminKepegawaian);
    $tautan = tautanUntuk(Pegawai::factory()->create(), false);
    Http::fake(['drive.google.com/*' => Http::response('', 404)]);

    (new PeriksaTautanBerkas($tautan->id))->handle();

    expect($tautan->fresh()->status_cek)->toBe(StatusCekTautan::TidakDapatDiakses);
    Notification::assertSentTo($admin, TautanBerkasBermasalah::class);
});

test('job: galat jaringan tidak mengubah status', function () {
    $tautan = tautanUntuk(Pegawai::factory()->create(), false);
    Http::fake(fn () => throw new ConnectionException('timeout'));

    (new PeriksaTautanBerkas($tautan->id))->handle();

    expect($tautan->fresh()->status_cek)->toBe(StatusCekTautan::Belum)->and($tautan->fresh()->dicek_pada)->toBeNull();
});

test('job: host ip privat atau di luar daftar putih tidak diminta', function (string $url) {
    $tautan = tautanUntuk(Pegawai::factory()->create(), false);
    $tautan->forceFill(['url' => $url])->save();
    Http::fake();

    (new PeriksaTautanBerkas($tautan->id))->handle();

    Http::assertNothingSent();
    expect($tautan->fresh()->status_cek)->toBe(StatusCekTautan::Belum);
})->with([
    'loopback' => 'https://127.0.0.1/rahasia',
    'privat 10.x' => 'https://10.0.0.5/berkas',
    'luar daftar putih' => 'https://internal.example.com/berkas',
]);

test('buka: pemilik diarahkan ke url tersimpan dan akses sensitif tercatat', function () {
    $dosen = penggunaBerkas(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $dosen->id]);
    $tautan = tautanUntuk($pegawai, true, 'ijazah');

    $this->actingAs($dosen)->get(route('tautan.buka', $tautan))->assertRedirect($tautan->url);

    $log = Aktivitas::where('log_name', 'akses-berkas')->first();
    expect($log)->not->toBeNull()->and($log->subject_id)->toBe($tautan->id)
        ->and(route('tautan.buka', $tautan))->toContain($tautan->id);
});

test('buka: tamu diarahkan ke halaman login', function () {
    $tautan = tautanUntuk(Pegawai::factory()->create(), false);

    $this->get(route('tautan.buka', $tautan))->assertRedirect(route('filament.admin.auth.login'));
});

test('buka: admin-prodi prodi lain 403, prodi sendiri non sensitif diizinkan', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $dosenPbio = Pegawai::factory()->create(['prodi_id' => $pbio->id]);
    $dosenPmat = Pegawai::factory()->create(['prodi_id' => $pmat->id]);
    $adminPmat = penggunaBerkas(Peran::AdminProdi, $pmat);

    $this->actingAs($adminPmat)->get(route('tautan.buka', tautanUntuk($dosenPbio, false)))->assertForbidden();
    $this->actingAs($adminPmat)->get(route('tautan.buka', tautanUntuk($dosenPmat, false)))->assertRedirect();
    $this->actingAs($adminPmat)->get(route('tautan.buka', tautanUntuk($dosenPmat, true, 'sk')))->assertForbidden();
});

test('buka: pimpinan 403 untuk sensitif dan boleh non sensitif; admin-kepegawaian selalu boleh', function () {
    $pegawai = Pegawai::factory()->create();
    $pimpinan = penggunaBerkas(Peran::Pimpinan);

    $this->actingAs($pimpinan)->get(route('tautan.buka', tautanUntuk($pegawai, true, 'sk')))->assertForbidden();
    $this->actingAs($pimpinan)->get(route('tautan.buka', tautanUntuk($pegawai, false)))->assertRedirect();
    $this->actingAs(penggunaBerkas(Peran::AdminKepegawaian))->get(route('tautan.buka', tautanUntuk($pegawai, true, 'sk')))->assertRedirect();
});

test('buka: dosen lain tidak boleh membuka berkas orang lain', function () {
    $pegawai = Pegawai::factory()->create();

    $this->actingAs(penggunaBerkas(Peran::Dosen))->get(route('tautan.buka', tautanUntuk($pegawai, false)))->assertForbidden();
});
