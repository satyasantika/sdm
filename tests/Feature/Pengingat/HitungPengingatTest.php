<?php

use App\Actions\Pegawai\HitungTanggalPensiun;
use App\Actions\Pengingat\HitungPengingatPegawai;
use App\Actions\Riwayat\SimpanRiwayatJabatanFungsional;
use App\Actions\Riwayat\SimpanRiwayatPangkat;
use App\Enums\JenisPengingat;
use App\Enums\StatusPengingat;
use App\Jobs\HitungUlangPengingat;
use App\Jobs\HitungUlangTanggalPensiun;
use App\Models\DokumenPegawai;
use App\Models\Golongan;
use App\Models\JabatanFungsional;
use App\Models\JenisDokumen;
use App\Models\Pegawai;
use App\Models\Pengingat;
use App\Models\Sertifikasi;
use App\Models\StatusKepegawaian;
use App\Models\StudiLanjut;
use App\Models\User;
use App\Support\Konfigurasi;
use Carbon\Carbon;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\MasterJabatanSeeder;
use Database\Seeders\MasterKepegawaianSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Carbon::setTestNow('2026-10-06');
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $this->seed(MasterKepegawaianSeeder::class);
    $this->seed(MasterJabatanSeeder::class);
});

afterEach(fn () => Carbon::setTestNow());

function pegawaiPns(string $statusKode = 'pns', array $tambahan = []): Pegawai
{
    return Pegawai::factory()->create(array_merge(['status_kepegawaian_id' => StatusKepegawaian::firstWhere('kode', $statusKode)->id, 'tanggal_lahir' => '1985-01-01'], $tambahan));
}

function pangkatTmt(Pegawai $pegawai, string $tmt, string $kode = 'III/c'): void
{
    app(SimpanRiwayatPangkat::class)->handle($pegawai, [
        'golongan_id' => Golongan::where('jenis', 'pns')->where('kode', $kode)->first()->id, 'tmt' => $tmt, 'nomor_sk' => 'SK/'.$tmt, 'jenis_kenaikan' => 'reguler',
    ]);
}

function jabfungTmt(Pegawai $pegawai, string $kode, string $tmt): void
{
    app(SimpanRiwayatJabatanFungsional::class)->handle($pegawai, [
        'jabatan_fungsional_id' => JabatanFungsional::firstWhere('kode', $kode)->id, 'tmt' => $tmt, 'nomor_sk' => 'SK/'.$kode,
    ], User::factory()->create(['email' => 'x'.fake()->unique()->numerify('####').'@unsil.ac.id']));
}

function ambil(Pegawai $pegawai, JenisPengingat $jenis)
{
    return Pengingat::where('pegawai_id', $pegawai->id)->where('jenis', $jenis)->get();
}

test('pns iii/c tmt 2023-04-01 dengan interval 48 bulan jatuh tempo 2027-04-01', function () {
    $pegawai = pegawaiPns();
    pangkatTmt($pegawai, '2023-04-01');

    app(HitungPengingatPegawai::class)->handle($pegawai);

    $kp = ambil($pegawai, JenisPengingat::KenaikanPangkat);
    expect($kp)->toHaveCount(1)->and($kp->first()->tanggal_jatuh_tempo->toDateString())->toBe('2027-04-01')
        ->and($kp->first()->status)->toBe(StatusPengingat::Aktif)->and($kp->first()->referensi_tabel)->toBe('riwayat_pangkat');
});

test('kgb mengikuti tmt kgb atau pangkat dan hanya bila status berlaku kgb', function () {
    $pns = pegawaiPns();
    pangkatTmt($pns, '2025-01-01');
    app(HitungPengingatPegawai::class)->handle($pns);
    expect(ambil($pns, JenisPengingat::Kgb)->first()->tanggal_jatuh_tempo->toDateString())->toBe('2027-01-01');

    $blu = pegawaiPns('non-asn-tetap-blu');
    app(HitungPengingatPegawai::class)->handle($blu);
    expect(ambil($blu, JenisPengingat::Kgb))->toHaveCount(0)->and(ambil($blu, JenisPengingat::KenaikanPangkat))->toHaveCount(0);
});

test('lektor tmt 2024-10-01 dengan masa kerja 24 bulan lewat tempo tetap dibuat dan profesor tidak', function () {
    JabatanFungsional::firstWhere('kode', 'lektor')->update(['masa_kerja_minimal_bulan' => 24]);
    $lektor = pegawaiPns();
    jabfungTmt($lektor, 'lektor', '2024-10-01');
    app(HitungPengingatPegawai::class)->handle($lektor);

    $jf = ambil($lektor, JenisPengingat::KenaikanJabfung);
    expect($jf)->toHaveCount(1)->and($jf->first()->tanggal_jatuh_tempo->toDateString())->toBe('2026-10-01')
        ->and($jf->first()->status)->toBe(StatusPengingat::LewatTempo);

    JabatanFungsional::firstWhere('kode', 'profesor')->update(['masa_kerja_minimal_bulan' => 24]);
    $profesor = pegawaiPns();
    jabfungTmt($profesor, 'profesor', '2024-10-01');
    app(HitungPengingatPegawai::class)->handle($profesor);
    expect(ambil($profesor, JenisPengingat::KenaikanJabfung))->toHaveCount(0);
});

test('syarat masa kerja kosong tidak membuat pengingat jabfung', function () {
    $pegawai = pegawaiPns();
    jabfungTmt($pegawai, 'lektor', '2024-10-01');

    app(HitungPengingatPegawai::class)->handle($pegawai);

    expect(ambil($pegawai, JenisPengingat::KenaikanJabfung))->toHaveCount(0);
});

test('pensiun dalam cakrawala menghasilkan pengingat dan di luar cakrawala tidak', function () {
    $dekat = pegawaiPns('pns', ['tanggal_lahir' => '1961-06-15']);
    $jauh = pegawaiPns('pns', ['tanggal_lahir' => '1985-01-01']);

    app(HitungPengingatPegawai::class)->handle($dekat);
    app(HitungPengingatPegawai::class)->handle($jauh);

    expect(ambil($dekat, JenisPengingat::Pensiun))->toHaveCount(1)->and(ambil($jauh, JenisPengingat::Pensiun))->toHaveCount(0);
});

test('menjalankan dua kali tidak menggandakan', function () {
    $pegawai = pegawaiPns();
    pangkatTmt($pegawai, '2023-04-01');

    app(HitungPengingatPegawai::class)->handle($pegawai);
    app(HitungPengingatPegawai::class)->handle($pegawai);

    expect(Pengingat::where('pegawai_id', $pegawai->id)->count())->toBe(2); // KP + KGB
});

test('dokumen sertifikasi dan studi lanjut yang akan berakhir menghasilkan pengingat', function () {
    $pegawai = pegawaiPns();
    $jenisDok = JenisDokumen::factory()->create(['punya_masa_berlaku' => true]);
    DokumenPegawai::factory()->create(['pegawai_id' => $pegawai->id, 'jenis_dokumen_id' => $jenisDok->id, 'tanggal_kedaluwarsa' => '2026-12-01']);
    Sertifikasi::factory()->create(['pegawai_id' => $pegawai->id, 'tanggal_kedaluwarsa' => '2026-09-01']);
    StudiLanjut::factory()->create(['pegawai_id' => $pegawai->id, 'tanggal_selesai_rencana' => '2027-01-15']);

    app(HitungPengingatPegawai::class)->handle($pegawai);

    expect(ambil($pegawai, JenisPengingat::DokumenKedaluwarsa))->toHaveCount(1)
        ->and(ambil($pegawai, JenisPengingat::SertifikasiKedaluwarsa)->first()->status)->toBe(StatusPengingat::LewatTempo)
        ->and(ambil($pegawai, JenisPengingat::StudiLanjutBerakhir))->toHaveCount(1);
});

test('pegawai nonaktif tidak dihitung', function () {
    $pegawai = pegawaiPns('pns', ['status_aktif' => 'pensiun']);
    pangkatTmt($pegawai, '2023-04-01');

    expect(app(HitungPengingatPegawai::class)->handle($pegawai))->toBeEmpty();
});

test('mengubah interval kp memicu hitung ulang dan hasilnya berubah', function () {
    $pegawai = pegawaiPns();
    pangkatTmt($pegawai, '2023-04-01');
    app(HitungPengingatPegawai::class)->handle($pegawai);

    Queue::fake([HitungUlangPengingat::class, HitungUlangTanggalPensiun::class]);
    Konfigurasi::set('interval_kp_bulan', 36);
    Queue::assertPushed(HitungUlangPengingat::class);
    Queue::assertPushed(HitungUlangTanggalPensiun::class);

    (new HitungUlangPengingat)->handle(app(HitungPengingatPegawai::class));

    $kp = ambil($pegawai, JenisPengingat::KenaikanPangkat);
    $per = fn (string $tgl) => $kp->first(fn (Pengingat $p): bool => $p->tanggal_jatuh_tempo->toDateString() === $tgl);
    expect($per('2026-04-01')->status)->toBe(StatusPengingat::LewatTempo)
        ->and($per('2027-04-01')->status)->toBe(StatusPengingat::Selesai);
});

test('menambah riwayat pangkat baru menandai pengingat kp lama selesai', function () {
    $pegawai = pegawaiPns();
    pangkatTmt($pegawai, '2023-04-01');
    app(HitungPengingatPegawai::class)->handle($pegawai);
    expect(ambil($pegawai, JenisPengingat::KenaikanPangkat)->first()->status)->toBe(StatusPengingat::Aktif);

    pangkatTmt($pegawai, '2026-10-01', 'III/d');

    expect(ambil($pegawai, JenisPengingat::KenaikanPangkat)->first()->status)->toBe(StatusPengingat::Selesai)
        ->and(ambil($pegawai, JenisPengingat::Kgb)->first()->status)->toBe(StatusPengingat::Selesai);
});

test('perubahan master syarat jabatan memicu hitung ulang', function () {
    Queue::fake([HitungUlangPengingat::class, HitungUlangTanggalPensiun::class]);

    JabatanFungsional::firstWhere('kode', 'lektor')->update(['masa_kerja_minimal_bulan' => 30]);

    Queue::assertPushed(HitungUlangPengingat::class);
});

test('hitung ulang tanggal pensiun memperbarui semua pegawai setelah bup berubah', function () {
    $dosen = Pegawai::factory()->create(['tanggal_lahir' => '1965-03-15']);
    expect($dosen->fresh()->tanggal_pensiun->toDateString())->toBe('2030-04-01');

    Queue::fake([HitungUlangPengingat::class, HitungUlangTanggalPensiun::class]);
    Konfigurasi::set('bup_dosen', 67);
    (new HitungUlangTanggalPensiun)->handle(app(HitungTanggalPensiun::class));

    expect($dosen->fresh()->tanggal_pensiun->toDateString())->toBe('2032-04-01');
});

test('status pengingat mengikuti diagram transisi', function () {
    expect(StatusPengingat::Aktif->bolehBerpindahKe(StatusPengingat::Diabaikan))->toBeTrue()
        ->and(StatusPengingat::LewatTempo->bolehBerpindahKe(StatusPengingat::Diabaikan))->toBeFalse()
        ->and(StatusPengingat::Ditindaklanjuti->bolehBerpindahKe(StatusPengingat::Selesai))->toBeTrue()
        ->and(StatusPengingat::Selesai->bolehBerpindahKe(StatusPengingat::Aktif))->toBeFalse()
        ->and(StatusPengingat::Diabaikan->bolehBerpindahKe(StatusPengingat::Selesai))->toBeFalse();
});
