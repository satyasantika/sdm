<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisSertifikasi extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'jenis_sertifikasi';

    protected $fillable = ['kode', 'nama', 'is_serdos', 'punya_masa_berlaku'];

    protected function casts(): array
    {
        return [
            'is_serdos' => 'boolean',
            'punya_masa_berlaku' => 'boolean',
        ];
    }
}
