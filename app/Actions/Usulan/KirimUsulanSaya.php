<?php

namespace App\Actions\Usulan;

use App\Enums\JenisTautan;
use App\Enums\JenisUsulan;
use App\Filament\Forms\TautanBerkasField;
use App\Models\Pegawai;
use App\Models\User;
use App\Models\UsulanPerubahan;
use Illuminate\Validation\ValidationException;

/** Jembatan formulir swalayan → BuatUsulanPerubahan (+ AjukanUsulanPerubahan). */
class KirimUsulanSaya
{
    /**
     * @param  array<string, mixed>  $form  isian modal: kolom target, alasan, simpan_draf, tautan_* dan tautan_*_konfirmasi
     */
    public function handle(User $pengusul, Pegawai $pegawai, JenisUsulan $jenis, string $tabel, ?string $targetId, array $form): UsulanPerubahan
    {
        $draf = (bool) ($form['simpan_draf'] ?? false);
        $alasan = filled($form['alasan'] ?? null) ? (string) $form['alasan'] : null;
        unset($form['simpan_draf'], $form['alasan']);

        $tautan = TautanBerkasField::pisahkan($form);
        $bukti = isset($tautan['bukti']) && $tautan['bukti']['url'] !== '' ? $tautan['bukti'] : null;
        unset($tautan['bukti']);

        foreach ($tautan as $nama => $nilai) {
            if ($nilai['url'] === '') {
                continue;
            }

            if ((JenisTautan::tryFrom($nama)?->isSensitif() ?? false) && ! $nilai['konfirmasi']) {
                throw ValidationException::withMessages(['konfirmasi' => 'Konfirmasi berbagi terbatas wajib dicentang untuk berkas sensitif.']);
            }

            $form['tautan'][$nama] = $nilai['url'];
        }

        $usulan = app(BuatUsulanPerubahan::class)->handle($pengusul, $pegawai, $jenis, $tabel, $targetId, $form, $bukti, $alasan);

        return $draf ? $usulan : app(AjukanUsulanPerubahan::class)->handle($usulan, $pengusul);
    }
}
