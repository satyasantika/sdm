<?php

namespace Database\Seeders;

use App\Models\Konfigurasi;
use Illuminate\Database\Seeder;

class KonfigurasiSeeder extends Seeder
{
    private const PRIVASI = <<<'MD'
# Pemberitahuan Privasi Data Kepegawaian

> Draf — perlu verifikasi bersama bagian hukum sebelum diberlakukan.

## Data yang dikumpulkan
Biodata, identitas (NIK, NPWP, rekening), riwayat jabatan, pangkat, pendidikan, sertifikasi, dan tautan berkas pendukung.

## Tujuan
Administrasi kepegawaian, penyusunan laporan akreditasi, serta pelaporan ke universitas dan kementerian.

## Pihak yang mengakses
Admin kepegawaian fakultas, admin program studi (terbatas pada prodinya), pimpinan, dan Anda sendiri.
Data identitas hanya tampil kepada pihak berwenang dan setiap aksesnya dicatat.

## Hak Anda
Anda dapat melihat data Anda dan mengajukan koreksi melalui fitur usulan perubahan; admin akan memverifikasinya.

## Kontak
Admin Kepegawaian FKIP Universitas Siliwangi.
MD;

    public function run(): void
    {
        $verifikasi = ' — perlu verifikasi';

        $baris = [
            ['bup_dosen', 65, 'int', 'UU 14/2005 (batas usia pensiun dosen)'.$verifikasi.'; pastikan dengan regulasi terbaru'],
            ['bup_profesor', 70, 'int', 'UU 14/2005 (batas usia pensiun profesor)'.$verifikasi.'; pastikan dengan regulasi terbaru'],
            ['bup_tendik', 58, 'int', 'Batas usia pensiun tendik (bergantung jabatan)'.$verifikasi],
            ['pembulatan_tmt_pensiun', 'awal_bulan_berikutnya', 'string', 'tepat | akhir_bulan | awal_bulan_berikutnya'.$verifikasi],
            ['interval_kp_bulan', 48, 'int', 'Interval kenaikan pangkat reguler (bulan)'.$verifikasi],
            ['interval_kgb_bulan', 24, 'int', 'Interval kenaikan gaji berkala (bulan)'.$verifikasi],
            ['tahap_pengingat_hari', [90, 30, 7], 'json', 'Tahap pengingat sebelum jatuh tempo (BR-19)'],
            ['cakrawala_pengingat_hari', 180, 'int', 'Rentang pengingat yang ditampilkan'],
            ['ambang_segera_berakhir_hari', 90, 'int', 'Ambang status Segera Berakhir'],
            ['interval_periksa_tautan_hari', 7, 'int', 'Pemeriksaan ulang keteraksesan tautan berkas (BR-29)'],
            ['syarat_unggul_sdm', [
                'S1' => [
                    '3_tahun' => ['min_dtps_doktor' => 1, 'min_dtps_lektor_ke_atas' => 2],
                    '5_tahun' => ['min_dtps_doktor' => 2, 'min_dtps_lektor_ke_atas' => 2, 'min_dtps_lektor_kepala_ke_atas' => 1],
                ],
            ], 'json', 'Peraturan BAN-PT 27/2025 (LAMDIK IAPSK 3.0 Buku 4 Unggul, Sarjana); jenjang lain belum dikonfigurasi'.$verifikasi],
            ['ambang_rasio_dosen_mahasiswa', '', 'string', 'Ambang rasio mahasiswa per dosen tetap (kosong = tidak ditandai); perlu verifikasi instrumen akreditasi'],
            ['versi_kebijakan_privasi', '2026.1', 'string', 'Versi kebijakan privasi (BR-18)'],
            ['teks_kebijakan_privasi', self::PRIVASI, 'string', 'Draf Markdown pemberitahuan privasi'.$verifikasi],
        ];

        foreach ($baris as [$kunci, $nilai, $tipe, $keterangan]) {
            Konfigurasi::firstOrCreate(['kunci' => $kunci], [
                'nilai' => Konfigurasi::serialisasi($nilai),
                'tipe' => $tipe,
                'keterangan' => $keterangan,
            ]);
        }
    }
}
