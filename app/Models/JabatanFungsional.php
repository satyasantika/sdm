<?php

namespace App\Models;

use App\Enums\KelompokJabatan;
use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class JabatanFungsional extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'jabatan_fungsional';

    protected $fillable = ['kode', 'nama', 'kelompok', 'rumpun', 'urutan', 'angka_kredit_minimal', 'masa_kerja_minimal_bulan', 'golongan_minimal_id', 'is_puncak', 'dasar_hukum', 'is_aktif'];

    protected function casts(): array
    {
        return [
            'kelompok' => KelompokJabatan::class,
            'angka_kredit_minimal' => 'decimal:2',
            'masa_kerja_minimal_bulan' => 'integer',
            'is_puncak' => 'boolean',
            'is_aktif' => 'boolean',
        ];
    }

    public function golonganMinimal(): BelongsTo
    {
        return $this->belongsTo(Golongan::class, 'golongan_minimal_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    /** Jenjang di atasnya pada kelompok & rumpun yang sama (BR-09, BR-15). */
    public function jenjangBerikutnya(): ?self
    {
        return static::query()
            ->aktif()
            ->where('kelompok', $this->kelompok)
            ->where('rumpun', $this->rumpun)
            ->where('urutan', $this->urutan + 1)
            ->first();
    }

    public function lebihRendahDari(self $lain): bool
    {
        return $this->kelompok === $lain->kelompok
            && $this->rumpun === $lain->rumpun
            && $this->urutan < $lain->urutan;
    }

    /** @return array<string, string> id => label, di-cache pada sdm:master:jabatan-fungsional. */
    public static function opsiAktif(): array
    {
        return Cache::remember('sdm:master:jabatan-fungsional', 86400, fn (): array => static::query()->aktif()->orderBy('kelompok')->orderBy('urutan')->pluck('nama', 'id')->all());
    }
}
