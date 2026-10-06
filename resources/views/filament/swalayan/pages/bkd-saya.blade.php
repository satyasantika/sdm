<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @forelse ($this->riwayat() as $bkd)
            <x-filament::section :heading="$bkd->semester->label">
                <x-slot name="afterHeader">
                    <x-filament::badge :color="$bkd->kesimpulan->getColor()">{{ $bkd->kesimpulan->getLabel() }}</x-filament::badge>
                </x-slot>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-gray-500">Pendidikan</dt><dd>{{ $bkd->sks_pendidikan ?? '-' }}</dd>
                    <dt class="text-gray-500">Penelitian</dt><dd>{{ $bkd->sks_penelitian ?? '-' }}</dd>
                    <dt class="text-gray-500">Pengabdian</dt><dd>{{ $bkd->sks_pengabdian ?? '-' }}</dd>
                    <dt class="text-gray-500">Penunjang</dt><dd>{{ $bkd->sks_penunjang ?? '-' }}</dd>
                    <dt class="font-medium">Total SKS</dt><dd class="font-medium">{{ $bkd->total_sks ?? '-' }}</dd>
                </dl>
            </x-filament::section>
        @empty
            <p class="text-sm text-gray-500">Belum ada data BKD tercatat.</p>
        @endforelse
    </div>
</x-filament-panels::page>
