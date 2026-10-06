<?php

namespace App\Models;

use App\Enums\HubunganKeluarga;
use App\Enums\JenisKelamin;
use App\Models\Concerns\TercatatAktivitas;
use App\Support\Penyamar;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Pegawai $pegawai
 * @property HubunganKeluarga $hubungan
 * @property string|null $nik
 * @property-read string $nik_tersamar
 */
class Keluarga extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'keluarga';

    protected $fillable = [
        'pegawai_id', 'hubungan', 'nama', 'nik', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'pekerjaan',
        'status_tunjangan', 'tanggal_nikah',
    ];

    protected $hidden = ['nik'];

    /** @return list<string> */
    public static function kolomSensitif(): array
    {
        return ['nik', 'tanggal_lahir'];
    }

    protected function casts(): array
    {
        return [
            'hubungan' => HubunganKeluarga::class,
            'jenis_kelamin' => JenisKelamin::class,
            'nik' => 'encrypted',
            'tanggal_lahir' => 'date',
            'tanggal_nikah' => 'date',
            'status_tunjangan' => 'boolean',
        ];
    }

    protected function nikTersamar(): Attribute
    {
        return Attribute::get(fn (): string => Penyamar::nik($this->nik));
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }
}
