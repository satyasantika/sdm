<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property-read string $label
 */
class Semester extends Model
{
    use HasFactory, HasUuids, TercatatAktivitas;

    protected $table = 'semester';

    protected $fillable = ['kode', 'tahun_akademik', 'jenis', 'tanggal_mulai', 'tanggal_selesai', 'is_aktif'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_aktif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $semester): void {
            if ($semester->is_aktif && ($semester->wasChanged('is_aktif') || $semester->wasRecentlyCreated)) {
                DB::transaction(fn () => static::where('id', '!=', $semester->id)->where('is_aktif', true)->update(['is_aktif' => false]));
            }
        });
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    public static function aktifSekarang(): ?self
    {
        return static::aktif()->first();
    }

    public function aktifkan(): void
    {
        DB::transaction(function (): void {
            static::where('id', '!=', $this->id)->update(['is_aktif' => false]);
            $this->update(['is_aktif' => true]);
        });
    }

    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => $this->tahun_akademik.' '.ucfirst($this->jenis));
    }
}
