<?php

namespace App\Models;

use App\Enums\JenisTautan;
use App\Models\Concerns\PunyaTautanBerkas;
use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Pegawai $pegawai
 * @property-read JenjangPendidikan $jenjangPendidikan
 * @property int|null $tahun_lulus
 */
class RiwayatPendidikan extends Model
{
    use HasFactory, HasUuids, PunyaTautanBerkas, SoftDeletes, TercatatAktivitas;

    protected $table = 'riwayat_pendidikan';

    protected $fillable = [
        'pegawai_id', 'jenjang_pendidikan_id', 'nama_pt', 'negara', 'nama_prodi', 'bidang_ilmu', 'gelar', 'tahun_masuk',
        'tahun_lulus', 'nomor_ijazah', 'ipk', 'judul_tugas_akhir', 'is_pendidikan_tertinggi',
    ];

    protected function casts(): array
    {
        return ['ipk' => 'decimal:2', 'tahun_masuk' => 'integer', 'tahun_lulus' => 'integer', 'is_pendidikan_tertinggi' => 'boolean'];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function jenjangPendidikan(): BelongsTo
    {
        return $this->belongsTo(JenjangPendidikan::class);
    }

    public function berkasIjazah(): ?TautanBerkas
    {
        return $this->tautan(JenisTautan::Ijazah);
    }

    public function berkasTranskrip(): ?TautanBerkas
    {
        return $this->tautan(JenisTautan::Transkrip);
    }
}
