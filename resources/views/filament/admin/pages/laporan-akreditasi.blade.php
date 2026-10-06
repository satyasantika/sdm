<x-filament-panels::page>
    {{ $this->content }}

    @php($s = $this->statistik())

    <x-filament::section heading="Ringkasan kualifikasi dosen (LAP-03)">
        @if ($s === null)
            <p class="text-sm text-gray-500">Pilih program studi.</p>
        @else
            @php($total = max(1, $s['jumlah_dosen']))
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <h3 class="mb-2 text-sm font-semibold">Per jabatan akademik</h3>
                    <table class="w-full text-sm">
                        @foreach ($s['dosen_per_jabatan'] as $nama => $jumlah)
                            <tr class="border-b border-gray-100"><td class="py-1">{{ $nama }}</td><td class="py-1 text-right">{{ $jumlah }}</td><td class="py-1 text-right text-gray-500">{{ round($jumlah / $total * 100, 1) }}%</td></tr>
                        @endforeach
                    </table>
                </div>
                <div>
                    <h3 class="mb-2 text-sm font-semibold">Per pendidikan tertinggi</h3>
                    <table class="w-full text-sm">
                        @foreach ($s['dosen_per_pendidikan'] as $nama => $jumlah)
                            <tr class="border-b border-gray-100"><td class="py-1">{{ $nama }}</td><td class="py-1 text-right">{{ $jumlah }}</td><td class="py-1 text-right text-gray-500">{{ round($jumlah / $total * 100, 1) }}%</td></tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif
    </x-filament::section>

    @livewire(\App\Filament\Admin\Widgets\SyaratUnggulSdmWidget::class, ['prodiId' => $this->prodiTerpilih()], key('syarat-unggul-'.$this->prodiTerpilih()))
</x-filament-panels::page>
