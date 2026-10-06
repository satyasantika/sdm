<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Prodi $prodi
 * @property-read Semester $semester
 * @property int $jumlah_mahasiswa_aktif
 */
class JumlahMahasiswaProdi extends Model
{
    use HasFactory, HasUuids, TercatatAktivitas;

    protected $table = 'jumlah_mahasiswa_prodi';

    protected $fillable = ['prodi_id', 'semester_id', 'jumlah_mahasiswa_aktif', 'sumber', 'diinput_oleh'];

    protected function casts(): array
    {
        return ['jumlah_mahasiswa_aktif' => 'integer'];
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
