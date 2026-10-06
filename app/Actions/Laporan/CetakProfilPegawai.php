<?php

namespace App\Actions\Laporan;

use App\Models\Pegawai;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;

/** Lembar profil pegawai (CV kepegawaian) PDF: tanpa NIK/NPWP/rekening/alamat/keluarga (BR-17). */
class CetakProfilPegawai
{
    public function handle(User $user, Pegawai $pegawai): string
    {
        $pemilik = $pegawai->user_id !== null && $pegawai->user_id === $user->getKey();

        if (! $pemilik && ! $user->can('view', $pegawai)) {
            throw new AuthorizationException('Anda tidak berwenang mencetak profil ini.');
        }

        $pegawai = Pegawai::query()->with([
            'prodi', 'unitKerja', 'statusKepegawaian', 'jabatanFungsional', 'golongan',
            'riwayatJabatanFungsional.jabatanFungsional', 'riwayatPangkat.golongan',
            'riwayatJabatanStruktural.jenisJabatanStruktural', 'riwayatJabatanStruktural.unitKerja',
            'riwayatPendidikan.jenjangPendidikan', 'sertifikasi.jenisSertifikasi', 'penghargaan', 'pelatihan',
        ])->findOrFail($pegawai->getKey());

        return Pdf::loadView('pdf.profil-pegawai', ['pegawai' => $pegawai])->setPaper('a4')->output();
    }

    public function namaBerkas(Pegawai $pegawai): string
    {
        return 'profil-'.str($pegawai->nama)->slug().'.pdf';
    }
}
