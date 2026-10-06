<?php

use App\Actions\Laporan\SusunLaporanKepegawaian;
use App\Models\Keluarga;
use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

const NIK_UJI = '3278019901900042';
const NPWP_UJI = '998877665544332';
const REKENING_UJI = '5566778899';

test('kolom sensitif tersimpan sebagai ciphertext di basis data', function () {
    $pegawai = Pegawai::factory()->create(['nik' => NIK_UJI, 'npwp' => NPWP_UJI, 'nomor_rekening' => REKENING_UJI]);
    $keluarga = Keluarga::factory()->create(['pegawai_id' => $pegawai->id]);

    $baris = DB::table('pegawai')->where('id', $pegawai->id)->first();
    expect($baris->nik)->not->toContain(NIK_UJI)->and($baris->npwp)->not->toContain(NPWP_UJI)->and($baris->nomor_rekening)->not->toContain(REKENING_UJI)
        ->and($baris->nik_hash)->not->toContain(NIK_UJI)
        ->and($pegawai->fresh()->nik)->toBe(NIK_UJI);

    if ($keluarga->nik) {
        expect(DB::table('keluarga')->where('id', $keluarga->id)->value('nik'))->not->toContain($keluarga->nik);
    }
});

test('log aktivitas tidak memuat nilai sensitif polos', function () {
    $pegawai = Pegawai::factory()->create(['nik' => NIK_UJI, 'npwp' => NPWP_UJI, 'nomor_rekening' => REKENING_UJI, 'alamat' => 'Jl. Rahasia 1']);
    $pegawai->update(['nik' => '3278019901900099', 'npwp' => '111111111111111', 'nomor_rekening' => '1212121212', 'alamat' => 'Jl. Baru 2']);

    $json = Activity::all()->toJson();

    foreach ([NIK_UJI, NPWP_UJI, REKENING_UJI, '3278019901900099', '111111111111111', '1212121212', 'Jl. Rahasia 1', 'Jl. Baru 2'] as $nilai) {
        expect($json)->not->toContain($nilai);
    }
});

test('laporan duk pejabat dan pensiun tidak memuat nilai sensitif', function () {
    Pegawai::factory()->create(['nik' => NIK_UJI, 'npwp' => NPWP_UJI, 'nomor_rekening' => REKENING_UJI, 'tanggal_pensiun' => now()->addYear()->toDateString()]);
    $laporan = app(SusunLaporanKepegawaian::class);

    $semua = json_encode([$laporan->duk()->all(), $laporan->pejabat()->all(), $laporan->pensiun()->all()]);

    foreach ([NIK_UJI, NPWP_UJI, REKENING_UJI] as $nilai) {
        expect($semua)->not->toContain($nilai);
    }
});

test('berkas log aplikasi tidak memuat nik uji', function () {
    Pegawai::factory()->create(['nik' => NIK_UJI]);

    foreach (glob(storage_path('logs/*.log')) ?: [] as $berkas) {
        expect(file_get_contents($berkas))->not->toContain(NIK_UJI);
    }
});
