<?php

namespace App\Models;

use App\Enums\StatusAktifPegawai;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusPegawai extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'riwayat_status_pegawai';

    protected $fillable = ['pegawai_id', 'dari_status', 'ke_status', 'tmt', 'nomor_sk', 'catatan', 'oleh_user_id'];

    protected function casts(): array
    {
        return [
            'dari_status' => StatusAktifPegawai::class,
            'ke_status' => StatusAktifPegawai::class,
            'tmt' => 'date',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function oleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh_user_id');
    }
}
