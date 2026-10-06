<?php

namespace App\Models;

use App\Enums\KesimpulanBkd;
use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Pegawai $pegawai
 * @property-read Semester $semester
 * @property KesimpulanBkd $kesimpulan
 * @property string|null $total_sks
 */
class Bkd extends Model
{
    use HasFactory, HasUuids, TercatatAktivitas;

    protected $table = 'bkd';

    protected $fillable = [
        'pegawai_id', 'semester_id', 'sks_pendidikan', 'sks_penelitian', 'sks_pengabdian', 'sks_penunjang', 'total_sks',
        'kewajiban_khusus', 'kesimpulan', 'sumber', 'import_id', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'sks_pendidikan' => 'decimal:2',
            'sks_penelitian' => 'decimal:2',
            'sks_pengabdian' => 'decimal:2',
            'sks_penunjang' => 'decimal:2',
            'total_sks' => 'decimal:2',
            'kesimpulan' => KesimpulanBkd::class,
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
