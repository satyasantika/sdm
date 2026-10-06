<?php

namespace App\Jobs;

use App\Enums\StatusCekTautan;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Notifications\TautanBerkasBermasalah;
use App\Rules\TautanBerkasValid;
use App\Support\DriveUrl;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Memeriksa keteraksesan tautan secara anonim (tanpa cookie), tanpa mengunduh isi (BR-29).
 * Hanya host daftar putih yang diikuti; host yang resolve ke IP privat/loopback ditolak (anti-SSRF).
 */
class PeriksaTautanBerkas implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public function __construct(public readonly string $tautanId)
    {
        $this->onQueue('tautan');
    }

    public function uniqueId(): string
    {
        return $this->tautanId;
    }

    public function handle(): void
    {
        $tautan = TautanBerkas::find($this->tautanId);
        if (! $tautan) {
            return;
        }

        $hasil = $this->periksa($tautan->url);
        if ($hasil === null) {
            return;
        }

        [$kode, $terbatas] = $hasil;
        $sebelumnya = $tautan->status_cek;
        $status = $this->tentukanStatus($tautan, $kode, $terbatas);

        $tautan->forceFill([
            'status_cek' => $status ?? $sebelumnya,
            'kode_http_terakhir' => $kode,
            'dicek_pada' => now(),
        ])->save();

        if ($status !== null && $status->bermasalah() && $status !== $sebelumnya) {
            $this->beritahu($tautan, $status);
        }
    }

    /** @return array{0: int, 1: bool}|null [kode http akhir, diarahkan ke login] atau null bila tidak diperiksa/gagal jaringan */
    private function periksa(string $url): ?array
    {
        $maks = (int) config('berkas.maks_redirect', 3);

        for ($lompatan = 0; $lompatan <= $maks; $lompatan++) {
            $host = DriveUrl::host($url);
            if ($host === null || ! $this->hostAman($host)) {
                return null;
            }

            try {
                $respons = Http::withoutRedirecting()
                    ->timeout((int) config('berkas.timeout_cek_detik', 10))
                    ->withHeaders(['User-Agent' => 'SDM-FKIP-Unsil-PemeriksaTautan'])
                    ->get($url);
            } catch (Throwable) {
                return null;
            }

            if (! $respons->redirect()) {
                return [$respons->status(), false];
            }

            $lokasi = (string) $respons->header('Location');
            $url = $this->absolut($url, $lokasi);

            if (str_contains((string) DriveUrl::host($url), 'accounts.google.com')) {
                return [$respons->status(), true];
            }
        }

        return null;
    }

    private function tentukanStatus(TautanBerkas $tautan, int $kode, bool $terbatas): ?StatusCekTautan
    {
        return match (true) {
            $terbatas, in_array($kode, [401, 403], true) => StatusCekTautan::Terbatas,
            $kode >= 200 && $kode < 300 => $tautan->is_sensitif ? StatusCekTautan::TerlaluTerbuka : StatusCekTautan::DapatDiakses,
            in_array($kode, [404, 410], true) => StatusCekTautan::TidakDapatDiakses,
            default => null,
        };
    }

    private function hostAman(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (! TautanBerkasValid::hostDiizinkan($host) && $host !== 'accounts.google.com') {
            return false;
        }

        $alamat = gethostbynamel($host) ?: [];
        foreach ($alamat as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    private function absolut(string $dasar, string $lokasi): string
    {
        if (preg_match('#^https?://#i', $lokasi)) {
            return $lokasi;
        }

        $skema = parse_url($dasar, PHP_URL_SCHEME);
        $host = parse_url($dasar, PHP_URL_HOST);

        return $skema.'://'.$host.'/'.ltrim($lokasi, '/');
    }

    private function beritahu(TautanBerkas $tautan, StatusCekTautan $status): void
    {
        $penerima = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'admin-kepegawaian'))->get();

        $pemilik = $tautan->pegawai?->user;
        if ($pemilik) {
            $penerima->push($pemilik);
        }

        foreach ($penerima->unique('id') as $user) {
            $user->notify(new TautanBerkasBermasalah($tautan, $status));
        }
    }
}
