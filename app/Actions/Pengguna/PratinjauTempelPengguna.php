<?php

namespace App\Actions\Pengguna;

use App\Enums\Peran;
use App\Filament\Imports\UserImporter;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Membaca teks hasil salin dari Excel (kolom dipisah tab; baris pertama judul), memvalidasi tiap baris tanpa
 * menyimpan apa pun, dan melaporkan status per baris. Aturan sama dengan UserImporter.
 */
class PratinjauTempelPengguna
{
    public const MAKS_BARIS = 500;

    /** Judul kolom (huruf kecil, tanpa spasi/garis bawah) → kunci data. */
    private const ALIAS = [
        'nama' => 'name', 'name' => 'name',
        'surel' => 'email', 'email' => 'email',
        'peran' => 'peran', 'role' => 'peran',
        'kodeprodi' => 'kode_prodi', 'prodi' => 'kode_prodi',
        'nip' => 'nip', 'nidn' => 'nidn',
        'nohp' => 'no_hp', 'hp' => 'no_hp', 'nomorhp' => 'no_hp',
    ];

    /**
     * @return array{galat: ?string, baris: list<array{nomor: int, data: array<string, ?string>, valid: bool, aksi: ?string, pesan: list<string>}>, valid: int, tidak_valid: int}
     */
    public function handle(string $teks): array
    {
        $kosong = fn (?string $galat): array => ['galat' => $galat, 'baris' => [], 'valid' => 0, 'tidak_valid' => 0];

        $garis = array_values(array_filter(preg_split('/\r\n|\n|\r/', trim($teks)) ?: [], fn (string $g): bool => trim($g) !== ''));

        if ($garis === []) {
            return $kosong('Belum ada data. Tempel hasil salin dari Excel, termasuk baris judul kolom.');
        }

        $pemisah = $this->pemisah($garis[0]);
        $peta = $this->petaKolom(str_getcsv(array_shift($garis), $pemisah, '"', ''));

        foreach (['name' => 'Nama', 'email' => 'Surel', 'peran' => 'Peran'] as $kunci => $label) {
            if (! in_array($kunci, $peta, true)) {
                return $kosong("Kolom \"{$label}\" tidak ditemukan pada baris judul. Baris pertama harus berisi judul kolom (Nama, Surel, Peran, Kode prodi, NIP, NIDN, No HP).");
            }
        }

        if (count($garis) > self::MAKS_BARIS) {
            return $kosong('Maksimal '.self::MAKS_BARIS.' baris per tempel; Anda menempel '.count($garis).' baris.');
        }

        $baris = [];
        $emailTerlihat = [];

        foreach ($garis as $i => $garisData) {
            $sel = str_getcsv($garisData, $pemisah, '"', '');
            $data = array_fill_keys(['name', 'email', 'peran', 'kode_prodi', 'nip', 'nidn', 'no_hp'], null);

            foreach ($peta as $indeks => $kunci) {
                $nilai = trim((string) ($sel[$indeks] ?? ''));
                $data[$kunci] = $nilai === '' ? null : $nilai;
            }

            $data['email'] = $data['email'] !== null ? mb_strtolower($data['email']) : null;
            $data['peran'] = $data['peran'] !== null ? mb_strtolower($data['peran']) : null;

            $pesan = $this->validasi($data);

            if ($data['email'] !== null) {
                if (isset($emailTerlihat[$data['email']])) {
                    $pesan[] = 'Surel sama dengan baris '.$emailTerlihat[$data['email']].' pada data yang ditempel.';
                } else {
                    $emailTerlihat[$data['email']] = $i + 2;
                }
            }

            $aksi = null;

            if ($pesan === []) {
                $aksi = User::where('email', $data['email'])->exists() ? 'Perbarui' : 'Buat baru';
            }

            $baris[] = ['nomor' => $i + 2, 'data' => $data, 'valid' => $pesan === [], 'aksi' => $aksi, 'pesan' => $pesan];
        }

        $valid = count(array_filter($baris, fn (array $b): bool => $b['valid']));

        return ['galat' => null, 'baris' => $baris, 'valid' => $valid, 'tidak_valid' => count($baris) - $valid];
    }

    private function pemisah(string $judul): string
    {
        foreach (["\t", ';', ','] as $kandidat) {
            if (str_contains($judul, $kandidat)) {
                return $kandidat;
            }
        }

        return "\t";
    }

    /**
     * @param  array<int, string|null>  $judul
     * @return array<int, string>
     */
    private function petaKolom(array $judul): array
    {
        $peta = [];

        foreach ($judul as $indeks => $nama) {
            $normal = preg_replace('/[\s_.\-]+/', '', mb_strtolower(trim((string) $nama)));

            if (isset(self::ALIAS[$normal]) && ! in_array(self::ALIAS[$normal], $peta, true)) {
                $peta[$indeks] = self::ALIAS[$normal];
            }
        }

        return $peta;
    }

    /**
     * @param  array<string, ?string>  $data
     * @return list<string>
     */
    private function validasi(array $data): array
    {
        $pesan = [];

        if ($data['peran'] === Peran::AdminProdi->value && blank($data['kode_prodi'])) {
            $pesan[] = 'Peran Admin Prodi wajib memiliki kode prodi.';
        }

        $pemeriksa = Validator::make($data, [
            'name' => ['required', 'max:150'],
            'email' => [
                'required', 'email', 'max:150',
                User::aturanDomainSurel(),
            ],
            'peran' => ['required', Rule::in(UserImporter::peranDiizinkan())],
            'kode_prodi' => ['nullable', Rule::exists('prodi', 'kode')],
            'nip' => ['nullable', 'max:18'],
            'nidn' => ['nullable', 'max:10'],
            'no_hp' => ['nullable', 'max:20'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Surel wajib diisi.',
            'email.email' => 'Surel tidak valid.',
            'email.ends_with' => User::pesanDomainSurel(),
            'peran.required' => 'Peran wajib diisi.',
            'peran.in' => 'Peran tidak dikenal atau tidak dapat dibuat lewat impor (super-admin dan klien-api tidak diizinkan).',
            'kode_prodi.exists' => 'Kode prodi tidak dikenal.',
            'nip.max' => 'NIP maksimal 18 karakter.',
            'nidn.max' => 'NIDN maksimal 10 karakter.',
            'no_hp.max' => 'No. HP maksimal 20 karakter.',
            'name.max' => 'Nama maksimal 150 karakter.',
            'email.max' => 'Surel maksimal 150 karakter.',
        ]);

        return [...$pesan, ...$pemeriksa->errors()->all()];
    }
}
