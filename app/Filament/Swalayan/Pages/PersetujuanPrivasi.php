<?php

namespace App\Filament\Swalayan\Pages;

use App\Models\PersetujuanPrivasi as Persetujuan;
use App\Support\Konfigurasi;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PersetujuanPrivasi extends Page
{
    protected string $view = 'filament.swalayan.pages.persetujuan-privasi';

    protected static ?string $title = 'Pemberitahuan Privasi';

    protected static ?string $slug = 'persetujuan-privasi';

    protected static bool $shouldRegisterNavigation = false;

    public bool $setuju = false;

    public function teksHtml(): HtmlString
    {
        $teks = (string) Konfigurasi::get('teks_kebijakan_privasi', '');

        return new HtmlString(Str::markdown($teks, ['html_input' => 'strip', 'allow_unsafe_links' => false]));
    }

    public function versi(): string
    {
        return (string) Konfigurasi::get('versi_kebijakan_privasi', '1');
    }

    public function setujui(): void
    {
        if (! $this->setuju) {
            Notification::make()->warning()->title('Centang pernyataan persetujuan terlebih dahulu.')->send();

            return;
        }

        $user = auth()->user();

        $persetujuan = Persetujuan::firstOrCreate(
            ['user_id' => $user->getKey(), 'versi' => $this->versi()],
            ['disetujui_at' => now(), 'ip' => request()->ip(), 'user_agent' => mb_substr((string) request()->userAgent(), 0, 255)],
        );

        activity()->performedOn($persetujuan)->causedBy($user)
            ->withProperties(['versi' => $this->versi()])->log('menyetujui kebijakan privasi');

        $this->redirect(Beranda::getUrl());
    }
}
