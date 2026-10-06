<?php

namespace App\Models;

use App\Enums\JenisStudiLanjut;
use App\Enums\StatusStudiLanjut;
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
 * @property-read JenjangPendidikan $jenjangPendidikan
 * @property JenisStudiLanjut $jenis
 * @property StatusStudiLanjut $status
 * @property CarbonInterface $tanggal_mulai
 * @property CarbonInterface|null $tanggal_selesai_aktual
 */
class StudiLanjut extends Model
{
    use HasFactory, HasUuids, PunyaTautanBerkas, SoftDeletes, TercatatAktivitas;

    protected $table = 'studi_lanjut';

    protected $fillable = [
        'pegawai_id', 'jenjang_pendidikan_id', 'jenis', 'nama_pt', 'negara', 'nama_prodi', 'sumber_biaya', 'nomor_sk',
        'tanggal_mulai', 'tanggal_selesai_rencana', 'tanggal_selesai_aktual', 'status',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisStudiLanjut::class,
            'status' => StatusStudiLanjut::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai_rencana' => 'date',
            'tanggal_selesai_aktual' => 'date',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function jenjangPendidikan(): BelongsTo
    {
        return $this->belongsTo(JenjangPendidikan::class);
    }
}
