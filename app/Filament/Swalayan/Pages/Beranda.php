<?php

namespace App\Filament\Swalayan\Pages;

use App\Models\Pegawai;
use App\Models\UsulanPerubahan;
use Filament\Pages\Dashboard;
use Illuminate\Support\Collection;

class Beranda extends Dashboard
{
    protected string $view = 'filament.swalayan.pages.beranda';

    protected static ?string $title = 'Beranda';

    public function getPegawai(): Pegawai
    {
        return Pegawai::query()
            ->with(['prodi', 'unitKerja', 'statusKepegawaian', 'jabatanFungsional', 'golongan', 'pendidikanTertinggi.jenjangPendidikan'])
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    /** @return array<string, string> */
    public function ringkasan(): array
    {
        $p = $this->getPegawai();

        return [
            'Nama' => $p->nama_bergelar,
            'NIP' => $p->nip ?? '-',
            'NIDN' => $p->nidn ?? '-',
            'Prodi / Unit kerja' => $p->prodi->nama ?? $p->unitKerja->nama ?? '-',
            'Status kepegawaian' => $p->statusKepegawaian->nama,
            'Jabatan fungsional' => $p->jabatanFungsional->nama ?? '-',
            'Golongan' => $p->golongan->label ?? '-',
            'Pendidikan tertinggi' => $p->pendidikanTertinggi->jenjangPendidikan->nama ?? '-',
            'Tanggal pensiun' => $p->tanggal_pensiun?->translatedFormat('d F Y') ?? '-',
        ];
    }

    /** @return Collection<int, UsulanPerubahan> */
    public function usulanTerakhir(): Collection
    {
        return UsulanPerubahan::query()
            ->where('diajukan_oleh', auth()->id())
            ->latest()
            ->limit(5)
            ->get();
    }
}
