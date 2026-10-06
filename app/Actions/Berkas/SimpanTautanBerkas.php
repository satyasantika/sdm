<?php

namespace App\Actions\Berkas;

use App\Enums\JenisTautan;
use App\Enums\StatusCekTautan;
use App\Jobs\PeriksaTautanBerkas;
use App\Models\Pegawai;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Rules\TautanBerkasValid;
use App\Support\DriveUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SimpanTautanBerkas
{
    public function handle(
        Model $pemilik,
        JenisTautan $jenis,
        string $url,
        ?string $label = null,
        ?Pegawai $pegawai = null,
        ?User $oleh = null,
        bool $konfirmasiTerbatas = false,
    ): TautanBerkas {
        $url = trim($url);
        self::validasi($url, $jenis, $konfirmasiTerbatas);

        $pegawaiId = $pegawai?->getKey()
            ?? ($pemilik instanceof Pegawai ? $pemilik->getKey() : $pemilik->getAttribute('pegawai_id'));

        $tautan = DB::transaction(function () use ($pemilik, $jenis, $url, $label, $pegawaiId, $oleh, $konfirmasiTerbatas): TautanBerkas {
            $tautan = TautanBerkas::create([
                'pemilik_type' => $pemilik->getMorphClass(),
                'pemilik_id' => $pemilik->getKey(),
                'pegawai_id' => $pegawaiId,
                'jenis' => $jenis,
                'label' => $label,
                'url' => $url,
                'penyedia' => DriveUrl::penyedia($url),
                'drive_file_id' => DriveUrl::fileId($url),
                'is_sensitif' => $jenis->isSensitif(),
                'status_cek' => StatusCekTautan::Belum,
                'ditambahkan_oleh' => $oleh?->getKey(),
            ]);

            if ($konfirmasiTerbatas) {
                self::catatKonfirmasi($tautan, $oleh);
            }

            return $tautan;
        });

        PeriksaTautanBerkas::dispatch($tautan->getKey());

        return $tautan;
    }

    public static function validasi(string $url, JenisTautan $jenis, bool $konfirmasiTerbatas): void
    {
        Validator::make(
            ['url' => $url],
            ['url' => ['required', new TautanBerkasValid]],
            attributes: ['url' => 'tautan'],
        )->validate();

        if ($jenis->isSensitif() && ! $konfirmasiTerbatas) {
            throw ValidationException::withMessages([
                'konfirmasi' => 'Berkas '.$jenis->getLabel().' bersifat sensitif; konfirmasi bahwa berkas sudah dibagikan terbatas (domain unsil.ac.id), bukan publik.',
            ]);
        }
    }

    public static function catatKonfirmasi(TautanBerkas $tautan, ?User $oleh): void
    {
        activity()
            ->performedOn($tautan)
            ->causedBy($oleh)
            ->withProperties(['konfirmasi_berbagi_terbatas' => true])
            ->log('konfirmasi berbagi terbatas');
    }
}
