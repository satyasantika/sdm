@php
    use App\Support\Penyamar;
    use App\Support\RegistriTargetUsulan;

    $record = $getRecord();
    $lama = $record->data_lama ?? [];
    $baru = $record->data_baru ?? [];
    $tautan = $baru['tautan'] ?? [];
    unset($baru['tautan']);
    $target = $record->targetSaatIni();
    $saatIni = $target ? RegistriTargetUsulan::snapshot($record->target_tabel, $target) : [];
    $sensitif = RegistriTargetUsulan::kolomSensitif($record->target_tabel);
    $kolom = array_values(array_unique([...array_keys($lama), ...array_keys($baru)]));

    $tampil = function (string $k, mixed $nilai) use ($sensitif): string {
        if ($nilai === null || $nilai === '') {
            return '—';
        }
        if (in_array($k, $sensitif, true)) {
            return match ($k) {
                'nik' => Penyamar::nik((string) $nilai),
                'npwp' => Penyamar::npwp((string) $nilai),
                default => Penyamar::rekening((string) $nilai),
            };
        }

        return is_scalar($nilai) ? (string) $nilai : json_encode($nilai, JSON_UNESCAPED_UNICODE);
    };
@endphp

<div class="overflow-x-auto text-sm">
    @if ($record->adaKonflik())
        <div class="mb-3 rounded border border-amber-300 bg-amber-50 p-2 text-amber-900">
            Peringatan: data target berubah sejak usulan diajukan.
        </div>
    @endif
    <table class="w-full text-left">
        <thead>
            <tr class="border-b">
                <th class="py-1 pe-3">Kolom</th>
                <th class="py-1 pe-3">Nilai saat ini</th>
                <th class="py-1 pe-3">Saat diajukan</th>
                <th class="py-1">Usulan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kolom as $k)
                @php($berubah = array_key_exists($k, $baru) && (string) ($lama[$k] ?? '') !== (string) ($baru[$k] ?? ''))
                <tr class="border-b border-gray-100 {{ $berubah ? 'bg-amber-50 dark:bg-amber-500/10' : '' }}">
                    <td class="py-1 pe-3 font-medium">{{ RegistriTargetUsulan::labelKolom($k) }}</td>
                    <td class="py-1 pe-3">{{ $tampil($k, $saatIni[$k] ?? null) }}</td>
                    <td class="py-1 pe-3">{{ $tampil($k, $lama[$k] ?? null) }}</td>
                    <td class="py-1">{{ $record->jenis->value === 'hapus_riwayat' ? '(dihapus)' : $tampil($k, $baru[$k] ?? null) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if ($tautan !== [])
        <p class="mt-2 text-gray-500">Tautan berkas diusulkan untuk: {{ implode(', ', array_keys($tautan)) }}</p>
    @endif
</div>
