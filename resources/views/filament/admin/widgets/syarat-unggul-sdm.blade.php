<x-filament-widgets::widget>
    <x-filament::section heading="Syarat unggul SDM (LAMDIK)">
        @if (! $prodi)
            <p class="text-sm text-gray-500">Pilih program studi untuk melihat indikator.</p>
        @elseif ($hasil === null)
            <p class="text-sm text-gray-500">Syarat unggul untuk jenjang {{ $prodi->jenjang }} belum dikonfigurasi (perlu verifikasi).</p>
        @else
            <dl class="mb-3 grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                <div><dt class="text-gray-500">DTPS</dt><dd class="font-medium">{{ $hasil['dtps'] }}</dd></div>
                <div><dt class="text-gray-500">Berpendidikan doktor</dt><dd class="font-medium">{{ $hasil['doktor'] }}</dd></div>
                <div><dt class="text-gray-500">Lektor ke atas</dt><dd class="font-medium">{{ $hasil['lektor_ke_atas'] }}</dd></div>
                <div><dt class="text-gray-500">Lektor kepala ke atas</dt><dd class="font-medium">{{ $hasil['lektor_kepala_ke_atas'] }}</dd></div>
            </dl>
            <div class="flex flex-wrap gap-2">
                @foreach ($hasil['horizon'] as $nama => $h)
                    <x-filament::badge :color="$h['terpenuhi'] ? 'success' : 'danger'">
                        Syarat unggul {{ str_replace('_', ' ', $nama) }}: {{ $h['terpenuhi'] ? 'terpenuhi' : 'belum' }}
                    </x-filament::badge>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
