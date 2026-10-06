<?php

namespace App\Models;

use App\Enums\JenisGolongan;
use App\Enums\KelompokStatus;
use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * @property KelompokStatus $kelompok
 * @property JenisGolongan|null $jenis_golongan
 */
class StatusKepegawaian extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'status_kepegawaian';

    protected $fillable = ['kode', 'nama', 'kelompok', 'jenis_golongan', 'berlaku_kenaikan_pangkat', 'berlaku_kgb', 'dihitung_dosen_tetap', 'urutan', 'is_aktif'];

    protected function casts(): array
    {
        return [
            'kelompok' => KelompokStatus::class,
            'jenis_golongan' => JenisGolongan::class,
            'berlaku_kenaikan_pangkat' => 'boolean',
            'berlaku_kgb' => 'boolean',
            'dihitung_dosen_tetap' => 'boolean',
            'is_aktif' => 'boolean',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    public function scopeUrut(Builder $query): Builder
    {
        return $query->orderBy('urutan');
    }

    /** @return array<string, string> id => label, di-cache pada sdm:master:status-kepegawaian. */
    public static function opsiAktif(): array
    {
        return Cache::remember('sdm:master:status-kepegawaian', 86400, fn (): array => static::query()->aktif()->urut()->pluck('nama', 'id')->all());
    }
}
