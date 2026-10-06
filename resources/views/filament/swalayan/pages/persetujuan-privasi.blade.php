<x-filament-panels::page>
    <x-filament::section :heading="'Versi '.$this->versi()">
        <div class="prose max-w-none dark:prose-invert">
            {{ $this->teksHtml() }}
        </div>
    </x-filament::section>

    <label class="flex items-start gap-2 text-sm">
        <input type="checkbox" wire:model.live="setuju" class="mt-1 rounded border-gray-300" />
        <span>Saya telah membaca dan menyetujui pemberitahuan privasi di atas.</span>
    </label>

    <div>
        <x-filament::button wire:click="setujui">Setuju</x-filament::button>
    </div>
</x-filament-panels::page>
