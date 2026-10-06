<?php

namespace App\Models;

use App\Enums\JenisPengingat;
use App\Enums\StatusPengingat;
use App\Models\Concerns\TercatatAktivitas;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Pegawai $pegawai
 * @property JenisPengingat $jenis
 * @property StatusPengingat $status
 * @property CarbonInterface $tanggal_jatuh_tempo
 * @property array<int, int>|null $tahap_terkirim
 */
class Pengingat extends Model
{
    use HasFactory, HasUuids, TercatatAktivitas;

    protected $table = 'pengingat';

    protected $fillable = [
        'pegawai_id', 'jenis', 'referensi_tabel', 'referensi_id', 'tanggal_jatuh_tempo', 'status', 'tahap_terkirim',
        'terakhir_dikirim_at', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisPengingat::class,
            'status' => StatusPengingat::class,
            'tanggal_jatuh_tempo' => 'date',
            'tahap_terkirim' => 'array',
            'terakhir_dikirim_at' => 'datetime',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    /** Sisa hari ke jatuh tempo (negatif bila sudah lewat). */
    public function sisaHari(): int
    {
        return (int) CarbonImmutable::today()->diffInDays(CarbonImmutable::parse($this->tanggal_jatuh_tempo)->startOfDay(), false);
    }

    public function sisaHariLabel(): string
    {
        $sisa = $this->sisaHari();

        return $sisa >= 0 ? "{$sisa} hari lagi" : 'lewat '.abs($sisa).' hari';
    }
}
