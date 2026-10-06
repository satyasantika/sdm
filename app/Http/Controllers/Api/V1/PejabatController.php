<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusAktifPegawai;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PejabatResource;
use App\Models\RiwayatJabatanStruktural;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PejabatController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $daftar = RiwayatJabatanStruktural::query()->aktif()
            ->whereHas('pegawai', fn (Builder $q) => $q->whereIn('status_aktif', [StatusAktifPegawai::Aktif->value, StatusAktifPegawai::TugasBelajar->value]))
            ->with(['pegawai', 'jenisJabatanStruktural', 'unitKerja'])
            ->get()
            ->sortBy(fn (RiwayatJabatanStruktural $r): int => (int) $r->jenisJabatanStruktural->urutan)
            ->values();

        return PejabatResource::collection($daftar);
    }
}
