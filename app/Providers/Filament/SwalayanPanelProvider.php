<?php

namespace App\Providers\Filament;

use App\Filament\Swalayan\Pages\Beranda;
use App\Http\Middleware\PastikanPersetujuanPrivasi;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SwalayanPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('swalayan')
            ->path('saya')
            ->login()
            ->passwordReset()
            ->profile()
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, fn () => view('filament.auth.samping', [
                'judul' => 'Data Saya',
                'subjudul' => 'Lihat profil kepegawaian Anda dan ajukan perubahan data dengan mudah.',
                'poin' => ['Profil dan riwayat karier Anda', 'Ajukan perubahan, pantau statusnya', 'Berkas cukup berupa tautan Drive'],
                'aksen' => 'teal',
            ]))
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => view('filament.auth.bantuan'))
            ->brandName('SDM FKIP — Data Saya')
            ->colors([
                'primary' => Color::Teal,
            ])
            ->databaseNotifications()
            ->topNavigation()
            ->maxContentWidth('5xl')
            ->discoverResources(in: app_path('Filament/Swalayan/Resources'), for: 'App\Filament\Swalayan\Resources')
            ->discoverPages(in: app_path('Filament/Swalayan/Pages'), for: 'App\Filament\Swalayan\Pages')
            ->pages([
                Beranda::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Swalayan/Widgets'), for: 'App\Filament\Swalayan\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                PastikanPersetujuanPrivasi::class,
            ]);
    }
}
