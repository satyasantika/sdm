@props(['tautan' => null, 'pratinjau' => false])

@php
    use App\Contracts\PenyimpananBerkas;

    $penyimpanan = app(PenyimpananBerkas::class);
    $boleh = $tautan && auth()->user()?->can('buka', $tautan);
@endphp

@if ($tautan)
    <div {{ $attributes->class(['flex flex-col gap-2']) }}>
        <div class="flex items-center gap-2">
            @if ($boleh)
                <a href="{{ $penyimpanan->urlBuka($tautan) }}" target="_blank" rel="noopener noreferrer"
                   class="text-sm font-medium text-primary-600 hover:underline">
                    Buka berkas
                </a>
            @else
                <span class="text-sm text-gray-500">Berkas tersedia</span>
            @endif
            <x-filament::badge :color="$tautan->status_cek->getColor()" size="sm">
                {{ $tautan->status_cek->getLabel() }}
            </x-filament::badge>
        </div>

        @if ($boleh && $pratinjau && ($url = $penyimpanan->urlPratinjau($tautan)))
            <iframe src="{{ $url }}" class="h-64 w-full rounded border" loading="lazy" referrerpolicy="no-referrer"></iframe>
        @endif
    </div>
@else
    <span class="text-sm text-gray-400">—</span>
@endif
