<?php

namespace App\Models;

use App\Enums\JenisTautan;
use App\Enums\PenyediaBerkas;
use App\Enums\StatusCekTautan;
use App\Models\Concerns\TercatatAktivitas;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property JenisTautan $jenis
 * @property PenyediaBerkas $penyedia
 * @property StatusCekTautan $status_cek
 * @property bool $is_sensitif
 * @property string $url
 * @property string|null $pegawai_id
 * @property CarbonInterface|null $dicek_pada
 * @property-read Pegawai|null $pegawai
 */
class TautanBerkas extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'tautan_berkas';

    protected $fillable = [
        'pemilik_type', 'pemilik_id', 'pegawai_id', 'jenis', 'label', 'url', 'penyedia', 'drive_file_id',
        'is_sensitif', 'status_cek', 'dicek_pada', 'kode_http_terakhir', 'ditambahkan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'jenis' => JenisTautan::class,
            'penyedia' => PenyediaBerkas::class,
            'status_cek' => StatusCekTautan::class,
            'is_sensitif' => 'boolean',
            'dicek_pada' => 'datetime',
        ];
    }

    /** BR-24: perubahan url dicatat (URL lama → baru). */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['url', 'status_cek'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function pemilik(): MorphTo
    {
        return $this->morphTo('pemilik');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function penambah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditambahkan_oleh');
    }
}
