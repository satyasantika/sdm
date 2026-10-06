<?php

namespace App\Models;

use App\Enums\JenisTautan;
use App\Enums\StatusBerlaku;
use App\Models\Concerns\PunyaTautanBerkas;
use App\Models\Concerns\TercatatAktivitas;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Pegawai $pegawai
 * @property-read JenisDokumen $jenisDokumen
 * @property CarbonInterface|null $tanggal_kedaluwarsa
 * @property StatusBerlaku $status_berlaku
 */
class DokumenPegawai extends Model
{
    use HasFactory, HasUuids, PunyaTautanBerkas, SoftDeletes, TercatatAktivitas;

    protected $table = 'dokumen_pegawai';

    protected $fillable = ['pegawai_id', 'jenis_dokumen_id', 'nomor', 'tanggal_terbit', 'tanggal_kedaluwarsa', 'status_berlaku', 'catatan'];

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
            'tanggal_kedaluwarsa' => 'date',
            'status_berlaku' => StatusBerlaku::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $dokumen): void {
            $dokumen->status_berlaku = StatusBerlaku::dariTanggal($dokumen->tanggal_kedaluwarsa);
        });
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function jenisDokumen(): BelongsTo
    {
        return $this->belongsTo(JenisDokumen::class);
    }

    /** Jenis tautan berkas dokumen: identitas atau kepegawaian (selalu sensitif). */
    public function jenisTautanUntuk(string $nama): ?JenisTautan
    {
        if ($nama !== 'dokumen') {
            return JenisTautan::tryFrom($nama);
        }

        return $this->jenisDokumen->is_identitas ? JenisTautan::DokumenIdentitas : JenisTautan::DokumenKepegawaian;
    }

    /** Tautan berkas dokumen ini (jenis mengikuti is_identitas). */
    public function berkas(): ?TautanBerkas
    {
        return $this->tautan($this->jenisTautanUntuk('dokumen') ?? JenisTautan::DokumenKepegawaian);
    }
}
