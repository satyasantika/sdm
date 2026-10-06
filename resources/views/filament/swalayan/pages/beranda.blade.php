<x-filament-panels::page>
    <x-filament::section heading="Data kepegawaian saya">
        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
            @foreach ($this->ringkasan() as $label => $nilai)
                <div>
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="text-sm font-medium text-gray-950 dark:text-white">{{ $nilai }}</dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>

    <x-filament::section heading="Pengingat saya">
        @forelse ($this->pengingatSaya() as $pengingat)
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 py-2 last:border-0 dark:border-white/10">
                <div>
                    <div class="text-sm font-medium">{{ $pengingat->jenis->getLabel() }}</div>
                    <div class="text-xs text-gray-500">{{ $pengingat->tanggal_jatuh_tempo->translatedFormat('d F Y') }} · {{ $pengingat->sisaHariLabel() }}</div>
                </div>
                <x-filament::badge :color="$pengingat->status->getColor()">{{ $pengingat->status->getLabel() }}</x-filament::badge>
            </div>
        @empty
            <p class="text-sm text-gray-500">Tidak ada pengingat.</p>
        @endforelse
    </x-filament::section>

    <x-filament::section heading="Usulan terakhir">
        @forelse ($this->usulanTerakhir() as $usulan)
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 py-2 last:border-0 dark:border-white/10">
                <div>
                    <div class="text-sm font-medium">{{ $usulan->jenis->getLabel() }}: {{ $usulan->labelTarget() }}</div>
                    <div class="text-xs text-gray-500">{{ $usulan->created_at?->translatedFormat('d F Y') }}</div>
                </div>
                <x-filament::badge :color="$usulan->status->getColor()">{{ $usulan->status->getLabel() }}</x-filament::badge>
            </div>
        @empty
            <p class="text-sm text-gray-500">Belum ada usulan perubahan.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
