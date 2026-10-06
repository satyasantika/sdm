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

    <x-filament::section heading="Rasio dosen–mahasiswa (LAP-02)">
        @php($rasio = $this->rasio())
        <table class="w-full text-sm">
            <thead><tr class="border-b text-left"><th class="py-1">Prodi</th><th class="py-1 text-right">Dosen tetap</th><th class="py-1 text-right">Aktif mengajar</th><th class="py-1 text-right">Mahasiswa aktif</th><th class="py-1 text-right">Rasio</th></tr></thead>
            <tbody>
                @foreach ($rasio as $baris)
                    <tr class="border-b border-gray-100 {{ $baris['melampaui'] ? 'text-danger-600 font-medium' : '' }}">
                        <td class="py-1">{{ $baris['prodi'] }}</td>
                        <td class="py-1 text-right">{{ $baris['jumlah_dosen_tetap'] }}</td>
                        <td class="py-1 text-right">{{ $baris['dosen_tetap_aktif_mengajar'] }}</td>
                        <td class="py-1 text-right">{{ $baris['jumlah_mahasiswa_aktif'] ?? '—' }}</td>
                        <td class="py-1 text-right">{{ $baris['rasio'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::section>

    @livewire(\App\Filament\Admin\Widgets\SyaratUnggulSdmWidget::class, ['prodiId' => $this->prodiTerpilih()], key('syarat-unggul-'.$this->prodiTerpilih()))
</x-filament-panels::page>
