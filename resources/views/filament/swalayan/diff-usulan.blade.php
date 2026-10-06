@php
    use App\Support\Penyamar;
    use App\Support\RegistriTargetUsulan;

    $lama = $usulan->data_lama ?? [];
    $baru = $usulan->data_baru ?? [];
    $tautan = $baru['tautan'] ?? [];
    unset($baru['tautan']);
    $sensitif = RegistriTargetUsulan::kolomSensitif($usulan->target_tabel);
    $kolom = array_values(array_unique([...array_keys($lama), ...array_keys($baru)]));

    $tampil = function (string $kolom, mixed $nilai) use ($sensitif): string {
        if ($nilai === null || $nilai === '') {
            return '—';
        }

        if (in_array($kolom, $sensitif, true)) {
            return match ($kolom) {
                'nik' => Penyamar::nik((string) $nilai),
                'npwp' => Penyamar::npwp((string) $nilai),
                default => Penyamar::rekening((string) $nilai),
            };
        }

        return is_scalar($nilai) ? (string) $nilai : json_encode($nilai, JSON_UNESCAPED_UNICODE);
    };
@endphp

<div class="space-y-3 text-sm">
    <div><span class="text-gray-500">Jenis:</span> {{ $usulan->jenis->getLabel() }} — {{ $usulan->labelTarget() }}</div>
    @if ($usulan->alasan)
        <div><span class="text-gray-500">Alasan:</span> {{ $usulan->alasan }}</div>
    @endif
    @if ($usulan->catatan_verifikator)
        <div><span class="text-gray-500">Catatan verifikator:</span> {{ $usulan->catatan_verifikator }}</div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b">
                    <th class="py-1 pe-2">Kolom</th>
                    <th class="py-1 pe-2">Saat ini / lama</th>
                    <th class="py-1">Usulan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kolom as $k)
                    @php($berubah = array_key_exists($k, $baru) && (string) ($lama[$k] ?? '') !== (string) ($baru[$k] ?? ''))
                    @continue(! $berubah && $usulan->jenis->value === 'ubah_riwayat' && false)
                    <tr class="border-b border-gray-100 {{ $berubah ? 'bg-amber-50 dark:bg-amber-500/10' : '' }}">
                        <td class="py-1 pe-2 font-medium">{{ RegistriTargetUsulan::labelKolom($k) }}</td>
                        <td class="py-1 pe-2">{{ $tampil($k, $lama[$k] ?? null) }}</td>
                        <td class="py-1">{{ $usulan->jenis->value === 'hapus_riwayat' ? '(dihapus)' : $tampil($k, $baru[$k] ?? null) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($tautan !== [])
        <div class="text-gray-500">Tautan berkas diusulkan: {{ implode(', ', array_keys($tautan)) }}</div>
    @endif
</div>
