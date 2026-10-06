<?php

namespace App\Models;

use App\Enums\StatusUsulan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusUsulan extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'riwayat_status_usulan';

    protected $fillable = ['usulan_perubahan_id', 'dari_status', 'ke_status', 'oleh_user_id', 'catatan'];

    protected function casts(): array
    {
        return ['dari_status' => StatusUsulan::class, 'ke_status' => StatusUsulan::class];
    }

    public function usulan(): BelongsTo
    {
        return $this->belongsTo(UsulanPerubahan::class, 'usulan_perubahan_id');
    }

    public function oleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh_user_id');
    }
}
