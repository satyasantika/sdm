<?php

namespace App\Models;

use App\Models\Concerns\PunyaRiwayatTerkini;
use App\Models\Concerns\PunyaTautanBerkas;
use App\Models\Concerns\TercatatAktivitas;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CarbonInterface $tmt
 * @property-read Golongan|null $golongan
 */
class RiwayatKgb extends Model
{
    use HasFactory, HasUuids, PunyaRiwayatTerkini, PunyaTautanBerkas, SoftDeletes, TercatatAktivitas;

    protected $table = 'riwayat_kgb';

    protected $fillable = ['pegawai_id', 'golongan_id', 'tmt', 'nomor_sk', 'tanggal_sk', 'gaji_pokok', 'masa_kerja_tahun', 'masa_kerja_bulan', 'is_terkini'];

    protected function casts(): array
    {
        return [
            'tmt' => 'date',
            'tanggal_sk' => 'date',
            'is_terkini' => 'boolean',
            'gaji_pokok' => 'decimal:2',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function golongan(): BelongsTo
    {
        return $this->belongsTo(Golongan::class);
    }

    /** Masa kerja golongan, mis. "5 th 3 bl". */
    public function masaKerjaLabel(): string
    {
        if ($this->masa_kerja_tahun === null && $this->masa_kerja_bulan === null) {
            return '-';
        }

        return (int) $this->masa_kerja_tahun.' th '.(int) $this->masa_kerja_bulan.' bl';
    }
}
