<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Prodi extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'prodi';

    protected $fillable = ['kode', 'kode_pddikti', 'nama', 'jenjang', 'kode_eksternal', 'is_aktif'];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }

    public function unitKerja(): HasMany
    {
        return $this->hasMany(UnitKerja::class);
    }

    /** @return array<string, string> id => label, di-cache pada sdm:master:prodi. */
    public static function opsiAktif(): array
    {
        return Cache::remember('sdm:master:prodi', 86400, fn (): array => static::query()->where('is_aktif', true)->orderBy('nama')->pluck('nama', 'id')->all());
    }
}
