<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersetujuanPrivasi extends Model
{
    use HasUuids;

    protected $table = 'persetujuan_privasi';

    protected $fillable = ['user_id', 'versi', 'disetujui_at', 'ip', 'user_agent'];

    protected function casts(): array
    {
        return ['disetujui_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
