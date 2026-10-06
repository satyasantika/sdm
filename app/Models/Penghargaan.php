<?php

namespace App\Models;

use App\Enums\KategoriRekognisi;
use App\Enums\TingkatKegiatan;
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
 * @property KategoriRekognisi $kategori
 * @property TingkatKegiatan $tingkat
 * @property CarbonInterface|null $tanggal
 */
class Penghargaan extends Model
{
    use HasFactory, HasUuids, PunyaTautanBerkas, SoftDeletes, TercatatAktivitas;

    protected $table = 'penghargaan';

    protected $fillable = ['pegawai_id', 'kategori', 'nama', 'pemberi', 'tingkat', 'tanggal', 'nomor_sk'];

    protected function casts(): array
    {
        return ['kategori' => KategoriRekognisi::class, 'tingkat' => TingkatKegiatan::class, 'tanggal' => 'date'];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }
}
