<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

/**
 * Halaman profil (dipakai di panel admin dan swalayan).
 *
 * Ketika kata sandi benar-benar diganti: flag `wajib_ganti_sandi` dicabut
 * (tanpa itu middleware PaksaGantiSandi terus mengalihkan ke sini tanpa
 * henti) dan sesi di perangkat lain dikeluarkan.
 */
class UbahProfil extends EditProfile
{
    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        $kataSandiBaru = $this->data['password'] ?? null;

        if (filled($kataSandiBaru)) {
            $data['wajib_ganti_sandi'] = false;
        }

        $record = parent::handleRecordUpdate($record, $data);

        if (filled($kataSandiBaru)) {
            /** @var SessionGuard $guard */
            $guard = Filament::auth();
            $guard->logoutOtherDevices($kataSandiBaru);
        }

        return $record;
    }
}
