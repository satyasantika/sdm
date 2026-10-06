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
 * @property-read JabatanFungsional $jabatanFungsional
 * @property CarbonInterface $tmt
 */
class RiwayatJabatanFungsional extends Model
{
    use HasFactory, HasUuids, PunyaRiwayatTerkini, PunyaTautanBerkas, SoftDeletes, TercatatAktivitas;

    protected $table = 'riwayat_jabatan_fungsional';

    protected $fillable = [
        'pegawai_id', 'jabatan_fungsional_id', 'tmt', 'nomor_sk', 'tanggal_sk', 'angka_kredit',
        'is_terkini', 'is_koreksi', 'sumber', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tmt' => 'date',
            'tanggal_sk' => 'date',
            'angka_kredit' => 'decimal:2',
            'is_terkini' => 'boolean',
            'is_koreksi' => 'boolean',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function jabatanFungsional(): BelongsTo
    {
        return $this->belongsTo(JabatanFungsional::class);
    }
}
