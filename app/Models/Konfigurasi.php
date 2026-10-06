<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read mixed $nilai_terketik
 */
class Konfigurasi extends Model
{
    use HasUuids, TercatatAktivitas;

    protected $table = 'konfigurasi';

    protected $fillable = ['kunci', 'nilai', 'tipe', 'keterangan', 'diubah_oleh'];

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }

    /** Nilai dengan tipe sesuai kolom `tipe` (int, bool, json, string). */
    protected function nilaiTerketik(): Attribute
    {
        return Attribute::get(fn (): mixed => match ($this->tipe) {
            'int' => (int) $this->nilai,
            'bool' => filter_var($this->nilai, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->nilai, true),
            default => $this->nilai,
        });
    }

    public static function tipeDari(mixed $nilai): string
    {
        return match (true) {
            is_int($nilai) => 'int',
            is_bool($nilai) => 'bool',
            is_array($nilai) => 'json',
            default => 'string',
        };
    }

    public static function serialisasi(mixed $nilai): string
    {
        return match (true) {
            is_bool($nilai) => $nilai ? '1' : '0',
            is_array($nilai) => (string) json_encode($nilai, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => (string) $nilai,
        };
    }
}
