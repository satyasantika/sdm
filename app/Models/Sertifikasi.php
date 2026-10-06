<?php

namespace App\Models;

use App\Enums\StatusBerlaku;
use App\Models\Concerns\PunyaTautanBerkas;
use App\Models\Concerns\TercatatAktivitas;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Pegawai $pegawai
 * @property-read JenisSertifikasi $jenisSertifikasi
 * @property CarbonInterface|null $tanggal_kedaluwarsa
 * @property StatusBerlaku $status_berlaku
 */
class Sertifikasi extends Model
{
    use HasFactory, HasUuids, PunyaTautanBerkas, SoftDeletes, TercatatAktivitas;

    protected $table = 'sertifikasi';

    protected $fillable = [
        'pegawai_id', 'jenis_sertifikasi_id', 'nama', 'nomor_sertifikat', 'nomor_registrasi', 'bidang', 'penerbit',
        'tahun', 'tanggal_terbit', 'tanggal_kedaluwarsa', 'status_berlaku',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
            'tanggal_kedaluwarsa' => 'date',
            'status_berlaku' => StatusBerlaku::class,
            'tahun' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $sertifikasi): void {
            $sertifikasi->status_berlaku = StatusBerlaku::dariTanggal($sertifikasi->tanggal_kedaluwarsa);
        });
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function jenisSertifikasi(): BelongsTo
    {
        return $this->belongsTo(JenisSertifikasi::class);
    }
}
