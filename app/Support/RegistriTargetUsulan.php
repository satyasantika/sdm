<?php

namespace App\Support;

use App\Actions\Pegawai\PerbaruiPegawai;
use App\Actions\Riwayat\HapusRiwayatJabatanFungsional;
use App\Actions\Riwayat\HapusRiwayatPangkat;
use App\Actions\Riwayat\SimpanKeluarga;
use App\Actions\Riwayat\SimpanPelatihan;
use App\Actions\Riwayat\SimpanPenghargaan;
use App\Actions\Riwayat\SimpanRiwayatJabatanFungsional;
use App\Actions\Riwayat\SimpanRiwayatJabatanStruktural;
use App\Actions\Riwayat\SimpanRiwayatKgb;
use App\Actions\Riwayat\SimpanRiwayatPangkat;
use App\Actions\Riwayat\SimpanRiwayatPendidikan;
use App\Actions\Riwayat\SimpanSertifikasi;
use App\Actions\Riwayat\SimpanStudiLanjut;
use App\Models\DokumenPegawai;
use App\Models\Keluarga;
use App\Models\Pegawai;
use App\Models\Pelatihan;
use App\Models\Penghargaan;
use App\Models\RiwayatJabatanFungsional;
use App\Models\RiwayatJabatanStruktural;
use App\Models\RiwayatKgb;
use App\Models\RiwayatPangkat;
use App\Models\RiwayatPendidikan;
use App\Models\Sertifikasi;
use App\Models\StudiLanjut;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Daftar putih target usulan perubahan: tabel → model, kolom yang boleh diusulkan, aturan validasi,
 * dan aksi penerapan. Kolom sistem (is_terkini, sumber, pegawai_id, status_aktif, dst.) tidak pernah ada di sini.
 */
class RegistriTargetUsulan
{
    /** Kolom sensitif yang wajib bukti bila diubah di biodata. */
    private const BIODATA_BUTUH_BUKTI = ['nik', 'npwp', 'nomor_rekening'];

    /** Riwayat yang selalu wajib bukti pada tambah/ubah. */
    private const RIWAYAT_BUTUH_BUKTI = [
        'riwayat_jabatan_fungsional', 'riwayat_pangkat', 'riwayat_kgb', 'riwayat_pendidikan', 'sertifikasi',
    ];

    /** @return array<string, array{model: class-string<Model>, label: string, kolom: list<string>, aturan: array<string, list<mixed>>}> */
    private static function definisi(): array
    {
        $tgl = ['nullable', 'date'];

        return [
            'pegawai' => [
                'model' => Pegawai::class, 'label' => 'Biodata',
                'kolom' => ['gelar_depan', 'gelar_belakang', 'tempat_lahir', 'tanggal_lahir', 'agama', 'status_perkawinan', 'alamat',
                    'no_hp', 'email_pribadi', 'nik', 'npwp', 'nama_bank', 'nomor_rekening', 'nidn', 'nuptk'],
                'aturan' => [
                    'gelar_depan' => ['nullable', 'max:50'], 'gelar_belakang' => ['nullable', 'max:80'], 'tempat_lahir' => ['nullable', 'max:80'],
                    'tanggal_lahir' => $tgl, 'agama' => ['nullable', 'max:20'], 'status_perkawinan' => ['nullable', 'in:belum_kawin,kawin,cerai_hidup,cerai_mati'],
                    'alamat' => ['nullable', 'max:1000'], 'no_hp' => ['nullable', 'max:20'], 'email_pribadi' => ['nullable', 'email', 'max:150'],
                    'nik' => ['nullable', 'regex:/^\d{16}$/'], 'npwp' => ['nullable', 'max:30'], 'nama_bank' => ['nullable', 'max:60'],
                    'nomor_rekening' => ['nullable', 'max:30'], 'nidn' => ['nullable', 'regex:/^\d{10}$/'], 'nuptk' => ['nullable', 'regex:/^\d{16}$/'],
                ],
            ],
            'riwayat_jabatan_fungsional' => [
                'model' => RiwayatJabatanFungsional::class, 'label' => 'Riwayat jabatan fungsional',
                'kolom' => ['jabatan_fungsional_id', 'tmt', 'nomor_sk', 'tanggal_sk', 'angka_kredit', 'keterangan'],
                'aturan' => ['jabatan_fungsional_id' => ['required', 'uuid'], 'tmt' => ['required', 'date'], 'nomor_sk' => ['required', 'max:100'],
                    'tanggal_sk' => $tgl, 'angka_kredit' => ['nullable', 'numeric', 'min:0'], 'keterangan' => ['nullable', 'max:255']],
            ],
            'riwayat_pangkat' => [
                'model' => RiwayatPangkat::class, 'label' => 'Riwayat pangkat',
                'kolom' => ['golongan_id', 'tmt', 'jenis_kenaikan', 'nomor_sk', 'tanggal_sk', 'masa_kerja_tahun', 'masa_kerja_bulan'],
                'aturan' => ['golongan_id' => ['required', 'uuid'], 'tmt' => ['required', 'date'], 'jenis_kenaikan' => ['required', 'max:30'],
                    'nomor_sk' => ['required', 'max:100'], 'tanggal_sk' => $tgl, 'masa_kerja_tahun' => ['nullable', 'integer', 'between:0,60'],
                    'masa_kerja_bulan' => ['nullable', 'integer', 'between:0,11']],
            ],
            'riwayat_kgb' => [
                'model' => RiwayatKgb::class, 'label' => 'Riwayat KGB',
                'kolom' => ['tmt', 'golongan_id', 'gaji_pokok', 'nomor_sk', 'tanggal_sk', 'masa_kerja_tahun', 'masa_kerja_bulan'],
                'aturan' => ['tmt' => ['required', 'date'], 'golongan_id' => ['nullable', 'uuid'], 'gaji_pokok' => ['nullable', 'numeric', 'min:0'],
                    'nomor_sk' => ['required', 'max:100'], 'tanggal_sk' => $tgl, 'masa_kerja_tahun' => ['nullable', 'integer', 'between:0,60'],
                    'masa_kerja_bulan' => ['nullable', 'integer', 'between:0,11']],
            ],
            'riwayat_jabatan_struktural' => [
                'model' => RiwayatJabatanStruktural::class, 'label' => 'Riwayat jabatan struktural',
                'kolom' => ['jenis_jabatan_struktural_id', 'unit_kerja_id', 'tmt_mulai', 'tmt_selesai', 'nomor_sk', 'tanggal_sk'],
                'aturan' => ['jenis_jabatan_struktural_id' => ['required', 'uuid'], 'unit_kerja_id' => ['nullable', 'uuid'], 'tmt_mulai' => ['required', 'date'],
                    'tmt_selesai' => $tgl, 'nomor_sk' => ['required', 'max:100'], 'tanggal_sk' => $tgl],
            ],
            'riwayat_pendidikan' => [
                'model' => RiwayatPendidikan::class, 'label' => 'Riwayat pendidikan',
                'kolom' => ['jenjang_pendidikan_id', 'nama_pt', 'negara', 'nama_prodi', 'bidang_ilmu', 'gelar', 'tahun_masuk', 'tahun_lulus',
                    'nomor_ijazah', 'ipk', 'judul_tugas_akhir'],
                'aturan' => ['jenjang_pendidikan_id' => ['required', 'uuid'], 'nama_pt' => ['required', 'max:150'], 'negara' => ['nullable', 'max:60'],
                    'nama_prodi' => ['nullable', 'max:150'], 'bidang_ilmu' => ['nullable', 'max:150'], 'gelar' => ['nullable', 'max:30'],
                    'tahun_masuk' => ['nullable', 'integer', 'min:1950'], 'tahun_lulus' => ['nullable', 'integer', 'min:1950'],
                    'nomor_ijazah' => ['nullable', 'max:100'], 'ipk' => ['nullable', 'numeric', 'between:0,4'], 'judul_tugas_akhir' => ['nullable', 'max:500']],
            ],
            'sertifikasi' => [
                'model' => Sertifikasi::class, 'label' => 'Sertifikasi',
                'kolom' => ['jenis_sertifikasi_id', 'nama', 'nomor_sertifikat', 'nomor_registrasi', 'bidang', 'penerbit', 'tahun', 'tanggal_terbit', 'tanggal_kedaluwarsa'],
                'aturan' => ['jenis_sertifikasi_id' => ['required', 'uuid'], 'nama' => ['required', 'max:200'], 'nomor_sertifikat' => ['nullable', 'max:100'],
                    'nomor_registrasi' => ['nullable', 'max:100'], 'bidang' => ['nullable', 'max:150'], 'penerbit' => ['nullable', 'max:150'],
                    'tahun' => ['nullable', 'integer', 'min:1950'], 'tanggal_terbit' => $tgl, 'tanggal_kedaluwarsa' => $tgl],
            ],
            'penghargaan' => [
                'model' => Penghargaan::class, 'label' => 'Penghargaan',
                'kolom' => ['kategori', 'nama', 'pemberi', 'tingkat', 'tanggal', 'nomor_sk'],
                'aturan' => ['kategori' => ['nullable', 'max:30'], 'nama' => ['required', 'max:200'], 'pemberi' => ['nullable', 'max:150'],
                    'tingkat' => ['required', 'max:20'], 'tanggal' => $tgl, 'nomor_sk' => ['nullable', 'max:100']],
            ],
            'pelatihan' => [
                'model' => Pelatihan::class, 'label' => 'Pelatihan',
                'kolom' => ['nama', 'jenis', 'penyelenggara', 'tingkat', 'tanggal_mulai', 'tanggal_selesai', 'jumlah_jam'],
                'aturan' => ['nama' => ['required', 'max:200'], 'jenis' => ['required', 'max:30'], 'penyelenggara' => ['nullable', 'max:150'],
                    'tingkat' => ['nullable', 'max:20'], 'tanggal_mulai' => ['required', 'date'], 'tanggal_selesai' => $tgl,
                    'jumlah_jam' => ['nullable', 'integer', 'between:1,2000']],
            ],
            'keluarga' => [
                'model' => Keluarga::class, 'label' => 'Keluarga',
                'kolom' => ['hubungan', 'nama', 'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pekerjaan', 'status_tunjangan', 'tanggal_nikah'],
                'aturan' => ['hubungan' => ['required', 'in:suami,istri,anak,ayah,ibu'], 'nama' => ['required', 'max:150'], 'nik' => ['nullable', 'regex:/^\d{16}$/'],
                    'tempat_lahir' => ['nullable', 'max:80'], 'tanggal_lahir' => $tgl, 'jenis_kelamin' => ['nullable', 'in:L,P'], 'pekerjaan' => ['nullable', 'max:100'],
                    'status_tunjangan' => ['nullable', 'boolean'], 'tanggal_nikah' => $tgl],
            ],
            'studi_lanjut' => [
                'model' => StudiLanjut::class, 'label' => 'Studi lanjut',
                'kolom' => ['jenjang_pendidikan_id', 'jenis', 'nama_pt', 'negara', 'nama_prodi', 'sumber_biaya', 'nomor_sk', 'tanggal_mulai',
                    'tanggal_selesai_rencana', 'tanggal_selesai_aktual', 'status'],
                'aturan' => ['jenjang_pendidikan_id' => ['required', 'uuid'], 'jenis' => ['required', 'in:tugas_belajar,izin_belajar'], 'nama_pt' => ['required', 'max:150'],
                    'negara' => ['nullable', 'max:60'], 'nama_prodi' => ['nullable', 'max:150'], 'sumber_biaya' => ['nullable', 'max:100'], 'nomor_sk' => ['nullable', 'max:100'],
                    'tanggal_mulai' => ['required', 'date'], 'tanggal_selesai_rencana' => $tgl, 'tanggal_selesai_aktual' => $tgl,
                    'status' => ['required', 'in:berjalan,diperpanjang,selesai,berhenti']],
            ],
        ] + self::dokumen();
    }

    /**
     * Tabel dokumen_pegawai dibuat di F7.1; terdaftar hanya bila model sudah ada.
     *
     * @return array<string, array{model: class-string<Model>, label: string, kolom: list<string>, aturan: array<string, list<mixed>>}>
     */
    private static function dokumen(): array
    {
        if (! class_exists(DokumenPegawai::class)) {
            return [];
        }

        // Model dibuat di F7.1; sebelum itu class-string belum dapat dibuktikan oleh analisis statis.
        /** @phpstan-ignore return.type */
        return [
            'dokumen_pegawai' => [
                'model' => DokumenPegawai::class, 'label' => 'Dokumen kepegawaian',
                'kolom' => ['jenis_dokumen_id', 'nomor', 'tanggal_terbit', 'tanggal_kedaluwarsa', 'catatan'],
                'aturan' => ['jenis_dokumen_id' => ['required', 'uuid'], 'nomor' => ['nullable', 'max:100'], 'tanggal_terbit' => ['nullable', 'date'],
                    'tanggal_kedaluwarsa' => ['nullable', 'date'], 'catatan' => ['nullable', 'max:255']],
            ],
        ];
    }

    /** @return list<string> */
    public static function tabel(): array
    {
        return array_keys(self::definisi());
    }

    public static function ada(string $tabel): bool
    {
        return array_key_exists($tabel, self::definisi());
    }

    /** @return array{model: class-string<Model>, label: string, kolom: list<string>, aturan: array<string, list<mixed>>} */
    private static function entri(string $tabel): array
    {
        return self::definisi()[$tabel] ?? throw new InvalidArgumentException("Target usulan [{$tabel}] tidak terdaftar.");
    }

    public static function label(string $tabel): string
    {
        return self::definisi()[$tabel]['label'] ?? $tabel;
    }

    /** @return class-string<Model> */
    public static function model(string $tabel): string
    {
        return self::entri($tabel)['model'];
    }

    /** @return list<string> */
    public static function kolomBoleh(string $tabel): array
    {
        return self::entri($tabel)['kolom'];
    }

    /** @return array<string, list<mixed>> */
    public static function validasi(string $tabel): array
    {
        return self::entri($tabel)['aturan'];
    }

    /**
     * Hanya kolom daftar putih yang dipertahankan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function saring(string $tabel, array $data): array
    {
        return array_intersect_key($data, array_flip(self::kolomBoleh($tabel)));
    }

    /** @param  list<string>  $kolomBerubah */
    public static function butuhBukti(string $tabel, array $kolomBerubah): bool
    {
        if ($tabel === 'pegawai') {
            return array_intersect($kolomBerubah, self::BIODATA_BUTUH_BUKTI) !== [];
        }

        return in_array($tabel, self::RIWAYAT_BUTUH_BUKTI, true) && $kolomBerubah !== [];
    }

    /**
     * Snapshot kolom daftar putih dari record target (enum → nilai, tanggal → Y-m-d).
     *
     * @return array<string, mixed>
     */
    public static function snapshot(string $tabel, Model $target): array
    {
        $snapshot = [];

        foreach (self::kolomBoleh($tabel) as $kolom) {
            $nilai = $target->getAttribute($kolom);
            $snapshot[$kolom] = $nilai instanceof \BackedEnum ? $nilai->value : ($nilai instanceof \DateTimeInterface ? $nilai->format('Y-m-d') : $nilai);
        }

        return $snapshot;
    }

    /** Label kolom untuk tampilan diff. */
    public static function labelKolom(string $kolom): string
    {
        return ucfirst(str_replace(['_id', '_'], ['', ' '], $kolom));
    }

    /**
     * Kolom sensitif yang ditampilkan tersamar di diff.
     *
     * @return list<string>
     */
    public static function kolomSensitif(string $tabel): array
    {
        return match ($tabel) {
            'pegawai' => ['nik', 'npwp', 'nomor_rekening'],
            'keluarga' => ['nik'],
            default => [],
        };
    }

    /**
     * Menerapkan data ke target (simpan). Mengembalikan record target.
     *
     * @param  array<string, mixed>  $data
     */
    public static function terapkan(string $tabel, Pegawai $pegawai, array $data, User $oleh, mixed $record): Model
    {
        if (in_array($tabel, ['riwayat_jabatan_fungsional', 'riwayat_pangkat'], true)) {
            $data += ['sumber' => 'usulan'];
        }

        return match ($tabel) {
            'pegawai' => app(PerbaruiPegawai::class)->handle($pegawai, $data),
            'riwayat_jabatan_fungsional' => app(SimpanRiwayatJabatanFungsional::class)->handle($pegawai, $data, $oleh, $record),
            'riwayat_pangkat' => app(SimpanRiwayatPangkat::class)->handle($pegawai, $data, $record),
            'riwayat_kgb' => app(SimpanRiwayatKgb::class)->handle($pegawai, $data, $record),
            'riwayat_jabatan_struktural' => app(SimpanRiwayatJabatanStruktural::class)->handle($pegawai, $data, $record),
            'riwayat_pendidikan' => app(SimpanRiwayatPendidikan::class)->handle($pegawai, $data, $record),
            'sertifikasi' => app(SimpanSertifikasi::class)->handle($pegawai, $data, $record),
            'penghargaan' => app(SimpanPenghargaan::class)->handle($pegawai, $data, $record),
            'pelatihan' => app(SimpanPelatihan::class)->handle($pegawai, $data, $record),
            'keluarga' => app(SimpanKeluarga::class)->handle($pegawai, $data, $record),
            'studi_lanjut' => app(SimpanStudiLanjut::class)->handle($pegawai, $data, $oleh, $record, true),
            default => throw new InvalidArgumentException("Penerapan target [{$tabel}] belum didukung."),
        };
    }

    /** Menghapus record target (menghormati BR-23). */
    public static function hapus(string $tabel, mixed $record): void
    {
        match ($tabel) {
            'riwayat_jabatan_fungsional' => app(HapusRiwayatJabatanFungsional::class)->handle($record),
            'riwayat_pangkat' => app(HapusRiwayatPangkat::class)->handle($record),
            'riwayat_pendidikan' => app(SimpanRiwayatPendidikan::class)->hapus($record),
            'pegawai' => throw new InvalidArgumentException('Biodata pegawai tidak dapat dihapus.'),
            default => $record instanceof Model ? $record->delete() : null,
        };
    }
}
