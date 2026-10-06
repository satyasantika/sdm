<?php

namespace App\Actions\Usulan;

use App\Actions\Berkas\SimpanTautanBerkas;
use App\Enums\JenisTautan;
use App\Enums\JenisUsulan;
use App\Enums\StatusUsulan;
use App\Models\Pegawai;
use App\Models\RiwayatStatusUsulan;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Rules\TautanBerkasValid;
use App\Support\RegistriTargetUsulan;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BuatUsulanPerubahan
{
    /**
     * @param  array<string, mixed>  $dataBaru  kolom daftar putih + (opsional) 'tautan' => [nama => url] untuk record target
     * @param  array{url?: string, konfirmasi?: bool}|null  $bukti  tautan bukti pendukung
     */
    public function handle(
        User $pengusul,
        Pegawai $pegawai,
        JenisUsulan $jenis,
        string $targetTabel,
        ?string $targetId,
        array $dataBaru,
        ?array $bukti = null,
        ?string $alasan = null,
    ): UsulanPerubahan {
        // BR-04: hanya pemilik data yang mengusulkan perubahan datanya.
        if (! $pengusul->can('usulan.ajukan') || $pegawai->user_id === null || $pegawai->user_id !== $pengusul->getKey()) {
            throw new AuthorizationException('Anda hanya dapat mengusulkan perubahan data Anda sendiri.');
        }

        if (! RegistriTargetUsulan::ada($targetTabel)) {
            throw ValidationException::withMessages(['target_tabel' => 'Data yang diusulkan tidak termasuk daftar yang diizinkan.']);
        }

        if (($targetTabel === 'pegawai') !== ($jenis === JenisUsulan::UbahBiodata)) {
            throw ValidationException::withMessages(['jenis' => 'Jenis usulan tidak sesuai dengan data yang diusulkan.']);
        }

        $target = $this->targetMilikPegawai($pegawai, $jenis, $targetTabel, $targetId);
        $tautan = $this->tautanTarget($dataBaru);
        $lama = $target ? $this->snapshot($targetTabel, $target) : null;

        $baru = null;
        if ($jenis !== JenisUsulan::HapusRiwayat) {
            $baru = $this->dataBaru($targetTabel, $jenis, RegistriTargetUsulan::saring($targetTabel, $dataBaru), $lama);
            if ($tautan !== []) {
                $baru['tautan'] = $tautan;
            }
        }

        $kunci = $pegawai->getKey().':'.$targetTabel.':'.($target?->getKey() ?? 'baru-'.Str::uuid7());

        if (UsulanPerubahan::where('kunci_aktif', $kunci)->exists()) {
            throw ValidationException::withMessages(['usulan' => 'Masih ada usulan aktif untuk data ini.']);
        }

        try {
            return DB::transaction(function () use ($pengusul, $pegawai, $jenis, $targetTabel, $target, $lama, $baru, $alasan, $kunci, $bukti): UsulanPerubahan {
                $usulan = UsulanPerubahan::create([
                    'pegawai_id' => $pegawai->getKey(),
                    'diajukan_oleh' => $pengusul->getKey(),
                    'jenis' => $jenis,
                    'target_tabel' => $targetTabel,
                    'target_id' => $target?->getKey(),
                    'data_lama' => $lama,
                    'data_baru' => $baru,
                    'target_updated_at' => $target?->getAttribute('updated_at'),
                    'alasan' => $alasan,
                    'status' => StatusUsulan::Draf,
                    'kunci_aktif' => $kunci,
                ]);

                RiwayatStatusUsulan::create([
                    'usulan_perubahan_id' => $usulan->getKey(),
                    'dari_status' => null,
                    'ke_status' => StatusUsulan::Draf,
                    'oleh_user_id' => $pengusul->getKey(),
                ]);

                $this->simpanBukti($usulan, $pegawai, $pengusul, $bukti, $targetTabel, $baru, $lama);

                return $usulan;
            });
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'kunci_aktif')) {
                throw ValidationException::withMessages(['usulan' => 'Masih ada usulan aktif untuk data ini.']);
            }

            throw $e;
        }
    }

    private function targetMilikPegawai(Pegawai $pegawai, JenisUsulan $jenis, string $tabel, ?string $targetId): ?Model
    {
        if ($tabel === 'pegawai') {
            return $pegawai;
        }

        if ($jenis === JenisUsulan::TambahRiwayat) {
            return null;
        }

        $target = $targetId ? RegistriTargetUsulan::model($tabel)::query()->whereKey($targetId)->where('pegawai_id', $pegawai->getKey())->first() : null;

        if (! $target) {
            throw new AuthorizationException('Data yang dituju tidak ditemukan atau bukan milik Anda.');
        }

        return $target;
    }

    /**
     * @param  array<string, mixed>  $dataBaru
     * @return array<string, string>
     */
    private function tautanTarget(array $dataBaru): array
    {
        $tautan = [];

        foreach ((array) ($dataBaru['tautan'] ?? []) as $nama => $url) {
            if (blank($url)) {
                continue;
            }

            Validator::make(['tautan' => $url], ['tautan' => [new TautanBerkasValid]], attributes: ['tautan' => "tautan {$nama}"])->validate();
            $tautan[(string) $nama] = (string) $url;
        }

        return $tautan;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $lama
     * @return array<string, mixed>
     */
    private function dataBaru(string $tabel, JenisUsulan $jenis, array $data, ?array $lama): array
    {
        $data = array_map(fn ($v) => $v instanceof BackedEnum ? $v->value : ($v instanceof DateTimeInterface ? $v->format('Y-m-d') : $v), $data);

        // Biodata: hanya kolom yang benar-benar berubah yang diusulkan.
        if ($tabel === 'pegawai') {
            $data = array_filter($data, fn ($nilai, $kolom) => (string) ($lama[$kolom] ?? '') !== (string) ($nilai ?? ''), ARRAY_FILTER_USE_BOTH);
        }

        $lengkap = $jenis === JenisUsulan::UbahRiwayat ? array_merge($lama ?? [], $data) : $data;
        $aturan = RegistriTargetUsulan::validasi($tabel);
        if ($tabel === 'pegawai') {
            $aturan = array_intersect_key($aturan, $data);
        }

        Validator::make($lengkap, $aturan)->validate();

        if ($jenis !== JenisUsulan::TambahRiwayat) {
            $berubah = array_filter($lengkap, fn ($nilai, $kolom) => array_key_exists($kolom, $data) && (string) ($lama[$kolom] ?? '') !== (string) ($nilai ?? ''), ARRAY_FILTER_USE_BOTH);

            if ($berubah === []) {
                throw ValidationException::withMessages(['data' => 'Tidak ada perubahan yang diusulkan.']);
            }
        }

        return $jenis === JenisUsulan::UbahRiwayat ? $lengkap : $data;
    }

    /** @return array<string, mixed> */
    private function snapshot(string $tabel, Model $target): array
    {
        $snapshot = [];

        foreach (RegistriTargetUsulan::kolomBoleh($tabel) as $kolom) {
            $nilai = $target->getAttribute($kolom);
            $snapshot[$kolom] = $nilai instanceof BackedEnum ? $nilai->value : ($nilai instanceof DateTimeInterface ? $nilai->format('Y-m-d') : $nilai);
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>|null  $baru
     * @param  array<string, mixed>|null  $lama
     * @param  array{url?: string, konfirmasi?: bool}|null  $bukti
     */
    private function simpanBukti(UsulanPerubahan $usulan, Pegawai $pegawai, User $pengusul, ?array $bukti, string $tabel, ?array $baru, ?array $lama): void
    {
        $kolomBerubah = array_keys(array_diff_key($baru ?? [], ['tautan' => 1]));
        $url = trim((string) ($bukti['url'] ?? ''));

        if ($url === '' && RegistriTargetUsulan::butuhBukti($tabel, $kolomBerubah)) {
            throw ValidationException::withMessages(['bukti' => 'Perubahan ini wajib disertai tautan berkas bukti.']);
        }

        if ($url !== '') {
            app(SimpanTautanBerkas::class)->handle($usulan, JenisTautan::BuktiUsulan, $url, 'Bukti usulan', $pegawai, $pengusul, (bool) ($bukti['konfirmasi'] ?? false));
        }
    }
}
