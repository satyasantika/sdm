<?php

namespace App\Jobs;

use App\Actions\Laporan\SusunLaporanKepegawaian;
use App\Models\User;
use App\Support\KeluaranSementara;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use InvalidArgumentException;

/** Membuat PDF laporan besar di antrean ekspor, menyimpannya sementara (≤ 24 jam), lalu memberi tahu pembuatnya. */
class BuatPdfLaporan implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public readonly string $jenis, public readonly string $userId)
    {
        $this->onQueue('ekspor');
    }

    public function handle(SusunLaporanKepegawaian $susun): void
    {
        $user = User::findOrFail($this->userId);

        [$tampilan, $judul, $data] = match ($this->jenis) {
            'duk' => ['pdf.duk', 'Daftar Urut Kepangkatan', $susun->duk($user)],
            'pejabat' => ['pdf.pejabat', 'Rekap Pejabat', $susun->pejabat($user)],
            default => throw new InvalidArgumentException("Jenis laporan [{$this->jenis}] tidak dikenal."),
        };

        $isi = Pdf::loadView($tampilan, ['baris' => $data])->setPaper('a4', $this->jenis === 'duk' ? 'landscape' : 'portrait')->output();

        KeluaranSementara::simpan($user, $judul, $this->jenis.'.pdf', $isi);
    }
}
