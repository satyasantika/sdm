<?php

namespace App\Actions\Pegawai;

use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;

class BuatPegawai
{
    /** @param  array<string, mixed>  $data */
    public function handle(array $data): Pegawai
    {
        return DB::transaction(fn (): Pegawai => Pegawai::create($data));
    }
}
