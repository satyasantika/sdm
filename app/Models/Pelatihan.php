<?php

namespace App\Models;

use App\Enums\JenisPelatihan;
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
 * @property JenisPelatihan $jenis
 * @property TingkatKegiatan|null $tingkat
 * @property CarbonInterface $tanggal_mulai
 * @property int|null $jumlah_jam
 */
class Pelatihan extends Model
{
    use HasFactory, HasUuids, PunyaTautanBerkas, SoftDeletes, TercatatAktivitas;

    protected $table = 'pelatihan';

    protected $fillable = ['pegawai_id', 'nama', 'jenis', 'penyelenggara', 'tingkat', 'tanggal_mulai', 'tanggal_selesai', 'jumlah_jam'];

    protected function casts(): array
    {
        return [
            'jenis' => JenisPelatihan::class,
            'tingkat' => TingkatKegiatan::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'jumlah_jam' => 'integer',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }
}
