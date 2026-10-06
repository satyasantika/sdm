<?php

namespace App\Models;

use App\Enums\JenisUsulan;
use App\Enums\StatusUsulan;
use App\Models\Concerns\PunyaTautanBerkas;
use App\Models\Concerns\TercatatAktivitas;
use App\Support\RegistriTargetUsulan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Pegawai $pegawai
 * @property-read User $pengusul
 * @property JenisUsulan $jenis
 * @property StatusUsulan $status
 * @property string $target_tabel
 * @property string|null $target_id
 * @property array<string, mixed>|null $data_lama
 * @property array<string, mixed>|null $data_baru
 * @property array<int, array<string, mixed>>|null $snapshot_tautan
 * @property CarbonInterface|null $target_updated_at
 * @property CarbonInterface|null $diajukan_at
 */
class UsulanPerubahan extends Model
{
    use HasFactory, HasUuids, PunyaTautanBerkas, TercatatAktivitas;

    protected $table = 'usulan_perubahan';

    protected $fillable = [
        'pegawai_id', 'diajukan_oleh', 'jenis', 'target_tabel', 'target_id', 'data_lama', 'data_baru',
        'target_updated_at', 'snapshot_tautan', 'alasan', 'status', 'catatan_verifikator', 'diverifikasi_oleh',
        'diajukan_at', 'diverifikasi_at', 'diterapkan_at', 'kunci_aktif',
    ];

    protected $hidden = ['data_lama', 'data_baru', 'snapshot_tautan'];

    /** @return list<string> */
    public static function kolomSensitif(): array
    {
        return ['data_lama', 'data_baru', 'snapshot_tautan'];
    }

    protected function casts(): array
    {
        return [
            'data_lama' => 'encrypted:array',
            'data_baru' => 'encrypted:array',
            'snapshot_tautan' => 'encrypted:array',
            'jenis' => JenisUsulan::class,
            'status' => StatusUsulan::class,
            'target_updated_at' => 'datetime',
            'diajukan_at' => 'datetime',
            'diverifikasi_at' => 'datetime',
            'diterapkan_at' => 'datetime',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function pengusul(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusUsulan::class)->with('oleh')->orderBy('created_at');
    }

    public function labelTarget(): string
    {
        return RegistriTargetUsulan::label($this->target_tabel);
    }

    /** Record target saat ini (null untuk tambah riwayat atau bila sudah dihapus). */
    public function targetSaatIni(): ?Model
    {
        if ($this->target_id === null) {
            return null;
        }

        return RegistriTargetUsulan::model($this->target_tabel)::query()->find($this->target_id);
    }

    /** Data target berubah sejak usulan diajukan (BR-06). */
    public function adaKonflik(): bool
    {
        $target = $this->targetSaatIni();

        if ($target === null || $this->target_updated_at === null) {
            return false;
        }

        return $target->getAttribute('updated_at')?->toDateTimeString() !== $this->target_updated_at->toDateTimeString();
    }

    /** Usulan menunggu keputusan verifikator. */
    public function menungguVerifikasi(): bool
    {
        return $this->status === StatusUsulan::Diajukan;
    }
}
