<?php

namespace App\Http\Resources\Api\V1;

use App\Models\RiwayatJabatanStruktural;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RiwayatJabatanStruktural */
class PejabatResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'nama_bergelar' => $this->pegawai->nama_bergelar,
            'jabatan' => $this->jenisJabatanStruktural->nama,
            'unit_kerja' => $this->unitKerja?->nama,
            'tmt_mulai' => $this->tmt_mulai->toDateString(),
        ];
    }
}
