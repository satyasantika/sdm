<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisDokumen extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'jenis_dokumen';

    protected $fillable = ['kode', 'nama', 'punya_masa_berlaku', 'is_identitas', 'wajib_untuk'];

    protected function casts(): array
    {
        return [
            'punya_masa_berlaku' => 'boolean',
            'is_identitas' => 'boolean',
        ];
    }
}
