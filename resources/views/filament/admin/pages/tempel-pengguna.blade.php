<x-filament-panels::page>
    <x-filament::section heading="1. Tempel data dari Excel" description="Salin sel di Excel termasuk baris judul (Nama, Surel, Peran, Kode prodi, NIP, NIDN, No HP), lalu tempel di bawah. Kolom Nama, Surel, dan Peran wajib. Peran: admin-kepegawaian, admin-prodi, pimpinan, dosen, tendik. Maksimal 500 baris.">
        <textarea wire:model="teks" rows="8" spellcheck="false" placeholder="Nama&#9;Surel&#9;Peran&#9;Kode prodi&#10;Budi Santoso&#9;budi@unsil.ac.id&#9;dosen&#9;PMAT" class="fi-input block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm dark:border-white/10 dark:bg-white/5"></textarea>

        <div class="mt-4 flex gap-3">
            <x-filament::button wire:click="pratinjau" icon="heroicon-o-eye">Pratinjau</x-filament::button>
            @if ($pratinjau)
                <x-filament::button color="gray" wire:click="ulangi">Bersihkan pratinjau</x-filament::button>
            @endif
        </div>
    </x-filament::section>

    @if ($pratinjau)
        <x-filament::section heading="2. Pratinjau">
            @if ($pratinjau['galat'])
                <p class="text-sm text-danger-600">{{ $pratinjau['galat'] }}</p>
            @else
                <p class="mb-3 text-sm">
                    <strong>{{ $pratinjau['valid'] }}</strong> baris valid,
                    <strong class="{{ $pratinjau['tidak_valid'] > 0 ? 'text-danger-600' : '' }}">{{ $pratinjau['tidak_valid'] }}</strong> baris bermasalah.
                    Hanya baris valid yang diimpor; baris bermasalah dilewati. Akun baru menerima surel untuk mengatur kata sandi.
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                        <thead>
                            <tr>
                                <th class="px-2 py-2">Baris</th>
                                <th class="px-2 py-2">Status</th>
                                <th class="px-2 py-2">Nama</th>
                                <th class="px-2 py-2">Surel</th>
                                <th class="px-2 py-2">Peran</th>
                                <th class="px-2 py-2">Prodi</th>
                                <th class="px-2 py-2">NIP</th>
                                <th class="px-2 py-2">NIDN</th>
                                <th class="px-2 py-2">No HP</th>
                                <th class="px-2 py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($pratinjau['baris'] as $baris)
                                <tr wire:key="baris-{{ $baris['nomor'] }}" class="{{ $baris['valid'] ? '' : 'bg-danger-50 dark:bg-danger-500/10' }}">
                                    <td class="px-2 py-1">{{ $baris['nomor'] }}</td>
                                    <td class="px-2 py-1">
                                        @if ($baris['valid'])
                                            <x-filament::badge :color="$baris['aksi'] === 'Buat baru' ? 'success' : 'info'">{{ $baris['aksi'] }}</x-filament::badge>
                                        @else
                                            <x-filament::badge color="danger">Ditolak</x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-2 py-1">{{ $baris['data']['name'] }}</td>
                                    <td class="px-2 py-1">{{ $baris['data']['email'] }}</td>
                                    <td class="px-2 py-1">{{ $baris['data']['peran'] }}</td>
                                    <td class="px-2 py-1">{{ $baris['data']['kode_prodi'] }}</td>
                                    <td class="px-2 py-1">{{ $baris['data']['nip'] }}</td>
                                    <td class="px-2 py-1">{{ $baris['data']['nidn'] }}</td>
                                    <td class="px-2 py-1">{{ $baris['data']['no_hp'] }}</td>
                                    <td class="px-2 py-1 text-danger-600">{{ implode(' ', $baris['pesan']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($pratinjau['valid'] > 0)
                    <div class="mt-4">
                        <x-filament::button color="success" icon="heroicon-o-arrow-down-tray"
                            wire:click="impor" wire:confirm="Impor {{ $pratinjau['valid'] }} baris valid sekarang?">
                            Impor {{ $pratinjau['valid'] }} baris valid
                        </x-filament::button>
                    </div>
                @endif
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
