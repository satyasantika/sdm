<?php

namespace App\Models;

use App\Models\Concerns\PunyaTautanBerkas;
use App\Models\Concerns\TercatatAktivitas;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CarbonInterface $tmt_mulai
 * @property CarbonInterface|null $tmt_selesai
 * @property-read string $periode
 * @property-read Pegawai $pegawai
 * @property-read JenisJabatanStruktural $jenisJabatanStruktural
 * @property-read UnitKerja|null $unitKerja
 */
class RiwayatJabatanStruktural extends Model
{
    use HasFactory, HasUuids, PunyaTautanBerkas, SoftDeletes, TercatatAktivitas;

    protected $table = 'riwayat_jabatan_struktural';

    protected $fillable = [
        'pegawai_id', 'jenis_jabatan_struktural_id', 'unit_kerja_id', 'tmt_mulai', 'tmt_selesai', 'nomor_sk', 'tanggal_sk',
    ];

    protected function casts(): array
    {
        return ['tmt_mulai' => 'date', 'tmt_selesai' => 'date', 'tanggal_sk' => 'date'];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function jenisJabatanStruktural(): BelongsTo
    {
        return $this->belongsTo(JenisJabatanStruktural::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    /** Masih menjabat: tanggal selesai kosong atau belum lewat. */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('tmt_selesai')->orWhereDate('tmt_selesai', '>=', now()->toDateString()));
    }

    public function isAktif(): bool
    {
        return $this->tmt_selesai === null || $this->tmt_selesai->greaterThanOrEqualTo(now()->startOfDay());
    }

    /** Mis. "1 Maret 2023 – sekarang". */
    protected function periode(): Attribute
    {
        return Attribute::get(fn (): string => $this->tmt_mulai->translatedFormat('j F Y').' – '
            .($this->tmt_selesai?->translatedFormat('j F Y') ?? 'sekarang'));
    }
}
