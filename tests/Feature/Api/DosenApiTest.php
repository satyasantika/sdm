<?php

use App\Actions\Api\BuatTokenKlienApi;
use App\Enums\Peran;
use App\Enums\StatusAktifPegawai;
use App\Models\JabatanFungsional;
use App\Models\JenisJabatanStruktural;
use App\Models\JenisSertifikasi;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatJabatanStruktural;
use App\Models\Sertifikasi;
use App\Models\TokenAkses;
use App\Models\User;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
    $admin = User::factory()->create(['email' => 'sa@unsil.ac.id'])->assignRole(Peran::SuperAdmin->value);
    $this->token = app(BuatTokenKlienApi::class)->handle($admin, 'Uji', 'uji')[1];
    $this->admin = $admin;
});

const KUNCI_TERLARANG = ['nik', 'nip', 'npwp', 'nomor_rekening', 'tanggal_lahir', 'alamat', 'no_hp', 'email_pribadi', 'keluarga', 'nik_hash'];

test('tanpa token 401 dan token tanpa ability sdm:read 403', function () {
    $this->getJson('/api/v1/dosen')->assertUnauthorized();
    $lain = User::find(TokenAksesId())->createToken('x', ['sdm:write'])->plainTextToken;
    app('auth')->forgetGuards();

    $this->withToken($lain)->getJson('/api/v1/dosen')->assertForbidden();
});

function TokenAksesId(): string
{
    return TokenAkses::firstOrFail()->tokenable_id;
}

test('respons hanya memuat kunci yang diizinkan dan tanpa data sensitif', function () {
    $prodi = Prodi::factory()->create(['kode' => 'PMAT']);
    $dosen = Pegawai::factory()->create([
        'prodi_id' => $prodi->id, 'nik' => '3278010101900001', 'nip' => '198001012005011001', 'npwp' => '123456789012345',
        'alamat' => 'Jl. Rahasia 9', 'no_hp' => '0812', 'nidn' => '0011223344',
    ]);
    $jenis = JenisSertifikasi::factory()->create(['is_serdos' => true]);
    Sertifikasi::factory()->create(['pegawai_id' => $dosen->id, 'jenis_sertifikasi_id' => $jenis->id]);

    $respons = $this->withToken($this->token)->getJson('/api/v1/dosen')->assertOk()
        ->assertHeader('X-Api-Version', '1')
        ->assertJsonStructure(['data' => [['id', 'nama_bergelar', 'nidn', 'nuptk', 'prodi' => ['kode', 'nama'], 'jabatan_fungsional', 'pendidikan_tertinggi', 'punya_serdos', 'status_aktif', 'diperbarui_pada']], 'links', 'meta']);

    foreach ($respons->json('data') as $item) {
        expect(array_keys($item))->toEqualCanonicalizing(['id', 'nama_bergelar', 'nidn', 'nuptk', 'prodi', 'jabatan_fungsional', 'pendidikan_tertinggi', 'punya_serdos', 'status_aktif', 'diperbarui_pada']);
        foreach (KUNCI_TERLARANG as $kunci) {
            expect($item)->not->toHaveKey($kunci);
        }
    }
    expect($respons->json('data.0.punya_serdos'))->toBeTrue();
    foreach (['3278010101900001', '198001012005011001', '123456789012345', 'Jl. Rahasia'] as $nilai) {
        expect($respons->getContent())->not->toContain($nilai);
    }
});

test('filter prodi jabfung status dan diperbarui_sejak bekerja', function () {
    $pmat = Prodi::factory()->create(['kode' => 'PMAT']);
    $pbio = Prodi::factory()->create(['kode' => 'PBIO']);
    $lektor = JabatanFungsional::factory()->create(['kode' => 'lektor']);
    $a = Pegawai::factory()->create(['prodi_id' => $pmat->id, 'jabatan_fungsional_id' => $lektor->id]);
    $b = Pegawai::factory()->create(['prodi_id' => $pbio->id]);
    $meninggal = Pegawai::factory()->create(['prodi_id' => $pmat->id, 'status_aktif' => StatusAktifPegawai::Meninggal]);
    Pegawai::query()->whereKey($b->id)->toBase()->update(['updated_at' => '2020-01-01']);

    $ids = fn (string $q) => collect($this->withToken($this->token)->getJson('/api/v1/dosen'.$q)->assertOk()->json('data'))->pluck('id')->all();

    expect($ids('?prodi=PMAT'))->toBe([$a->id])
        ->and($ids('?jabfung=lektor'))->toBe([$a->id])
        ->and($ids(''))->toContain($a->id, $b->id)->not->toContain($meninggal->id)
        ->and($ids('?status=meninggal'))->toBe([$meninggal->id])
        ->and($ids('?diperbarui_sejak='.now()->toDateString()))->toContain($a->id)->not->toContain($b->id);
});

test('parameter tidak valid menghasilkan 422 dan per_page dibatasi', function () {
    $this->withToken($this->token)->getJson('/api/v1/dosen?per_page=101')->assertStatus(422);
    $this->withToken($this->token)->getJson('/api/v1/dosen?status=ngawur')->assertStatus(422);
    $this->withToken($this->token)->getJson('/api/v1/dosen?diperbarui_sejak=kemarin')->assertStatus(422);

    Pegawai::factory()->count(3)->create();
    $this->withToken($this->token)->getJson('/api/v1/dosen?per_page=2')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.per_page', 2);
});

test('show dosen berhasil dan tendik atau id tak dikenal 404', function () {
    $dosen = Pegawai::factory()->create();
    $tendik = Pegawai::factory()->tendik()->create();

    $this->withToken($this->token)->getJson("/api/v1/dosen/{$dosen->id}")->assertOk()->assertJsonPath('data.id', $dosen->id);
    $this->withToken($this->token)->getJson("/api/v1/dosen/{$tendik->id}")->assertNotFound();
    $this->withToken($this->token)->getJson('/api/v1/dosen/'.fake()->uuid())->assertNotFound();
});

test('pejabat memuat pejabat aktif terurut tanpa data sensitif', function () {
    $dekan = JenisJabatanStruktural::factory()->create(['nama' => 'Dekan', 'urutan' => 1]);
    $kajur = JenisJabatanStruktural::factory()->create(['nama' => 'Kajur', 'urutan' => 5]);
    $pegawaiKajur = Pegawai::factory()->create(['nip' => '198001012005011001']);
    RiwayatJabatanStruktural::factory()->create(['jenis_jabatan_struktural_id' => $kajur->id, 'pegawai_id' => $pegawaiKajur->id]);
    RiwayatJabatanStruktural::factory()->create(['jenis_jabatan_struktural_id' => $dekan->id]);
    RiwayatJabatanStruktural::factory()->create(['jenis_jabatan_struktural_id' => $dekan->id, 'tmt_selesai' => '2020-01-01']);

    $respons = $this->withToken($this->token)->getJson('/api/v1/pejabat')->assertOk()->assertHeader('X-Api-Version', '1')
        ->assertJsonStructure(['data' => [['nama_bergelar', 'jabatan', 'unit_kerja', 'tmt_mulai']]]);

    expect(collect($respons->json('data'))->pluck('jabatan')->all())->toBe(['Dekan', 'Kajur'])
        ->and($respons->getContent())->not->toContain('198001012005011001');
});

test('permintaan ke-61 dalam semenit mendapat 429', function () {
    foreach (range(1, 60) as $i) {
        $this->withToken($this->token)->getJson('/api/v1/pejabat')->assertOk();
    }
    $this->withToken($this->token)->getJson('/api/v1/pejabat')->assertStatus(429);
});
