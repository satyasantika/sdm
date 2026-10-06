<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hanya kolom non-sensitif (BR-17): tanpa nik, nip, npwp, rekening, tanggal lahir, alamat, kontak, keluarga.
 *
 * @mixin Pegawai
 */
class DosenResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_bergelar' => $this->nama_bergelar,
            'nidn' => $this->nidn,
            'nuptk' => $this->nuptk,
            'prodi' => $this->prodi ? ['kode' => $this->prodi->kode, 'nama' => $this->prodi->nama] : null,
            'jabatan_fungsional' => $this->jabatanFungsional ? ['kode' => $this->jabatanFungsional->kode, 'nama' => $this->jabatanFungsional->nama] : null,
            'pendidikan_tertinggi' => $this->pendidikanTertinggi ? ['jenjang' => $this->pendidikanTertinggi->jenjangPendidikan->nama] : null,
            'punya_serdos' => $this->sertifikasi->contains(fn ($s): bool => (bool) $s->jenisSertifikasi->is_serdos),
            'status_aktif' => $this->status_aktif->value,
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
