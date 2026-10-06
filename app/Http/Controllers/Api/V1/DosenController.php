<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusAktifPegawai;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DosenResource;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class DosenController extends Controller
{
    private const RELASI = ['prodi', 'jabatanFungsional', 'pendidikanTertinggi.jenjangPendidikan', 'sertifikasi.jenisSertifikasi'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'prodi' => ['sometimes', 'string', 'max:20'],
            'jabfung' => ['sometimes', 'string', 'max:60'],
            'status' => ['sometimes', Rule::enum(StatusAktifPegawai::class)],
            'diperbarui_sejak' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $query = Pegawai::query()->dosen()->with(self::RELASI)
            ->when(isset($data['status']),
                fn (Builder $q) => $q->where('status_aktif', $data['status']),
                fn (Builder $q) => $q->where('status_aktif', '!=', StatusAktifPegawai::Meninggal->value))
            ->when(isset($data['prodi']), fn (Builder $q) => $q->whereHas('prodi', fn (Builder $p) => $p->where('kode', $data['prodi'])))
            ->when(isset($data['jabfung']), fn (Builder $q) => $q->whereHas('jabatanFungsional', fn (Builder $j) => $j->where('kode', $data['jabfung'])))
            ->when(isset($data['diperbarui_sejak']), function (Builder $q) use ($data): void {
                $sejak = $data['diperbarui_sejak'];
                $q->where(fn (Builder $w) => $w->whereDate('updated_at', '>=', $sejak)
                    ->orWhereHas('sertifikasi', fn (Builder $r) => $r->whereDate('updated_at', '>=', $sejak))
                    ->orWhereHas('riwayatPendidikan', fn (Builder $r) => $r->whereDate('updated_at', '>=', $sejak))
                    ->orWhereHas('riwayatJabatanFungsional', fn (Builder $r) => $r->whereDate('updated_at', '>=', $sejak)));
            })
            ->orderBy('nama')->orderBy('id');

        return DosenResource::collection($query->paginate((int) ($data['per_page'] ?? 50))->withQueryString());
    }

    public function show(string $pegawai): DosenResource
    {
        return new DosenResource(Pegawai::query()->dosen()->with(self::RELASI)->findOrFail($pegawai));
    }
}
