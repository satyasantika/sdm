<?php

use App\Actions\Pengingat\KirimPengingatJatuhTempo;
use App\Enums\Peran;
use App\Models\Pegawai;
use App\Models\Pengingat;
use App\Models\Prodi;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\PengingatJatuhTempo;
use App\Notifications\RingkasanPengingatHarian;
use Carbon\Carbon;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Carbon::setTestNow('2026-10-06');
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    Notification::fake();
});

afterEach(fn () => Carbon::setTestNow());

function penerima(Peran $peran, ?Prodi $prodi = null, array $atribut = []): User
{
    return tap(User::factory()->create($atribut + [
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'prodi_id' => $prodi?->id,
    ]), fn (User $u) => $u->assignRole($peran->value));
}

function pengingatDalam(int $hari, ?Pegawai $pegawai = null): Pengingat
{
    return Pengingat::factory()->create([
        'pegawai_id' => ($pegawai ?? Pegawai::factory()->create())->id,
        'tanggal_jatuh_tempo' => now()->addDays($hari)->toDateString(),
    ]);
}

test('sisa 85 hari mengirim tahap 90 sekali dan tidak dobel pada hari yang sama', function () {
    $user = penerima(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $pengingat = pengingatDalam(85, $pegawai);

    $hasil = app(KirimPengingatJatuhTempo::class)->handle();

    expect($hasil['pengingat'])->toBe(1)->and($pengingat->fresh()->tahap_terkirim)->toBe([90])
        ->and($pengingat->fresh()->terakhir_dikirim_at)->not->toBeNull();
    Notification::assertSentToTimes($user, PengingatJatuhTempo::class, 1);

    app(KirimPengingatJatuhTempo::class)->handle();
    Notification::assertSentToTimes($user, PengingatJatuhTempo::class, 1);
});

test('sisa 29 hari mengirim tahap 30 walau tahap 90 sudah terkirim', function () {
    $user = penerima(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $pengingat = pengingatDalam(29, $pegawai);
    $pengingat->update(['tahap_terkirim' => [90]]);

    app(KirimPengingatJatuhTempo::class)->handle();

    expect($pengingat->fresh()->tahap_terkirim)->toBe([90, 30]);
    Notification::assertSentToTimes($user, PengingatJatuhTempo::class, 1);
});

test('pengingat yang masih jauh tidak dikirim dan lewat tempo dikirim sekali', function () {
    $user = penerima(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    pengingatDalam(150, $pegawai);
    $lewat = pengingatDalam(-3, $pegawai);
    $lewat->update(['status' => 'lewat_tempo']);

    $hasil = app(KirimPengingatJatuhTempo::class)->handle();
    app(KirimPengingatJatuhTempo::class)->handle();

    expect($hasil['pengingat'])->toBe(1)->and($lewat->fresh()->tahap_terkirim)->toContain(0);
    Notification::assertSentToTimes($user, PengingatJatuhTempo::class, 1);
});

test('pengingat yang diabaikan atau selesai tidak dikirim', function () {
    $pengingat = pengingatDalam(10);
    $pengingat->update(['status' => 'diabaikan']);

    expect(app(KirimPengingatJatuhTempo::class)->handle()['pengingat'])->toBe(0);
});

test('admin-kepegawaian menerima satu ringkasan harian dan admin-prodi ringkasan prodinya', function () {
    $pmat = Prodi::factory()->create();
    $pbio = Prodi::factory()->create();
    $admin = penerima(Peran::AdminKepegawaian);
    $adminPmat = penerima(Peran::AdminProdi, $pmat);
    $adminPbio = penerima(Peran::AdminProdi, $pbio);
    pengingatDalam(10, Pegawai::factory()->create(['prodi_id' => $pmat->id]));
    pengingatDalam(20, Pegawai::factory()->create(['prodi_id' => $pmat->id]));
    pengingatDalam(25, Pegawai::factory()->create(['prodi_id' => $pbio->id]));

    app(KirimPengingatJatuhTempo::class)->handle();

    Notification::assertSentToTimes($admin, RingkasanPengingatHarian::class, 1);
    Notification::assertSentTo($admin, RingkasanPengingatHarian::class, fn ($n) => $n->pengingat->count() === 3);
    Notification::assertSentTo($adminPmat, RingkasanPengingatHarian::class, fn ($n) => $n->pengingat->count() === 2);
    Notification::assertSentTo($adminPbio, RingkasanPengingatHarian::class, fn ($n) => $n->pengingat->count() === 1);
    Notification::assertNotSentTo($adminPbio, PengingatJatuhTempo::class);
});

test('pegawai tanpa akun tetap tercatat dan admin mendapat ringkasan', function () {
    $admin = penerima(Peran::AdminKepegawaian);
    $pengingat = pengingatDalam(5);

    app(KirimPengingatJatuhTempo::class)->handle();

    expect($pengingat->fresh()->tahap_terkirim)->toBe([90, 30, 7]);
    Notification::assertSentTo($admin, RingkasanPengingatHarian::class);
    Notification::assertNotSentTo($admin, PengingatJatuhTempo::class);
});

test('whatsapp tidak dipanggil bila dimatikan dan dipanggil dengan nomor 62 bila dihidupkan', function () {
    Http::fake(['gateway.test/*' => Http::response(['status' => true], 200)]);
    $user = penerima(Peran::Dosen, null, ['no_hp' => '081234567890']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $notifikasi = new PengingatJatuhTempo(pengingatDalam(10, $pegawai));

    config(['services.whatsapp.enabled' => false, 'services.whatsapp.endpoint' => 'https://gateway.test/send', 'services.whatsapp.token' => 'rahasia-token']);
    expect($notifikasi->via($user))->not->toContain(WhatsAppChannel::class);
    (new WhatsAppChannel)->send($user, $notifikasi);
    Http::assertNothingSent();

    config(['services.whatsapp.enabled' => true]);
    expect($notifikasi->via($user))->toContain(WhatsAppChannel::class);
    (new WhatsAppChannel)->send($user, $notifikasi);

    Http::assertSent(fn ($request) => $request->url() === 'https://gateway.test/send'
        && $request['target'] === '6281234567890'
        && $request->hasHeader('Authorization', 'rahasia-token')
        && str_contains($request['message'], 'Kenaikan pangkat'));
});

test('kegagalan http whatsapp melempar exception agar antrean mencoba ulang', function () {
    Http::fake(['gateway.test/*' => Http::response('error', 500)]);
    $user = penerima(Peran::Dosen, null, ['no_hp' => '081234567890']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    config(['services.whatsapp.enabled' => true, 'services.whatsapp.endpoint' => 'https://gateway.test/send', 'services.whatsapp.token' => 'rahasia-token']);

    expect(fn () => (new WhatsAppChannel)->send($user, new PengingatJatuhTempo(pengingatDalam(10, $pegawai))))
        ->toThrow(RuntimeException::class, 'HTTP 500');
});

test('whatsapp dilewati bila nomor kosong dan nomor dinormalisasi', function () {
    Http::fake();
    $user = penerima(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id, 'no_hp' => null]);
    config(['services.whatsapp.enabled' => true, 'services.whatsapp.endpoint' => 'https://gateway.test/send']);

    (new WhatsAppChannel)->send($user, new PengingatJatuhTempo(pengingatDalam(10, $pegawai)));
    Http::assertNothingSent();

    expect(WhatsAppChannel::normalisasi('0812-3456-7890'))->toBe('6281234567890')
        ->and(WhatsAppChannel::normalisasi('+62 812 3456 7890'))->toBe('6281234567890')
        ->and(WhatsAppChannel::normalisasi('6281234567890'))->toBe('6281234567890')
        ->and(WhatsAppChannel::normalisasi(null))->toBeNull();
});

test('isi pesan tidak mengandung nik pegawai', function () {
    $user = penerima(Peran::Dosen);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id, 'nik' => '3278010101900001', 'nip' => '198501012010012001']);
    $notifikasi = new PengingatJatuhTempo(pengingatDalam(10, $pegawai));

    $semua = $notifikasi->pesan().json_encode($notifikasi->toDatabase($user)).$notifikasi->toWhatsApp($user)['pesan'].json_encode($notifikasi->toMail($user)->introLines);

    expect($semua)->not->toContain('3278010101900001')->not->toContain('198501012010012001')->toContain($pegawai->nama);
});

test('perintah sdm:kirim-pengingat berjalan dan idempoten', function () {
    penerima(Peran::AdminKepegawaian);
    pengingatDalam(30);

    Artisan::call('sdm:kirim-pengingat');
    expect(Artisan::output())->toContain('1 pengingat diproses');

    Artisan::call('sdm:kirim-pengingat');
    expect(Artisan::output())->toContain('0 pengingat diproses');
});
