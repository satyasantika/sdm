<?php

namespace App\Models;

use App\Enums\JenisGolongan;
use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * @property-read string $label
 * @property JenisGolongan $jenis
 */
class Golongan extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'golongan';

    protected $fillable = ['jenis', 'kode', 'pangkat', 'urutan', 'is_aktif'];

    protected function casts(): array
    {
        return [
            'jenis' => JenisGolongan::class,
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

    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->pangkat ? "{$this->kode} — {$this->pangkat}" : $this->kode);
    }

    /** @return array<string, string> id => label, di-cache pada sdm:master:golongan. */
    public static function opsiAktif(): array
    {
        return Cache::remember('sdm:master:golongan', 86400, fn (): array => static::query()->aktif()->urut()->get()->mapWithKeys(fn (self $g): array => [$g->id => $g->jenis->getLabel().' '.$g->label])->all());
    }
}
