<?php

namespace App\Models;

use App\Models\Concerns\TercatatAktivitas;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisJabatanStruktural extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TercatatAktivitas;

    protected $table = 'jenis_jabatan_struktural';

    protected $fillable = ['kode', 'nama', 'kategori', 'urutan', 'is_aktif'];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }
}
