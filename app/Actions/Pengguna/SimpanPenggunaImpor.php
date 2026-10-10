<?php

namespace App\Actions\Pengguna;

use App\Models\Prodi;
use App\Models\User;
use App\Notifications\AkunSwalayanDibuat;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** Menyimpan satu baris impor pengguna (berkas maupun tempel dari Excel): isi atribut, peran, dan notifikasi atur sandi. */
class SimpanPenggunaImpor
{
    /**
     * @param  array{name: string, email: string, peran: string, kode_prodi?: ?string, nip?: ?string, nidn?: ?string, no_hp?: ?string}  $data
     */
    public function handle(User $record, array $data): User
    {
        $record->name = $data['name'];
        $record->email = $data['email'];
        $record->nip = $data['nip'] ?? null;
        $record->nidn = $data['nidn'] ?? null;
        $record->no_hp = $data['no_hp'] ?? null;
        $record->is_aktif = true;

        if (filled($data['kode_prodi'] ?? null)) {
            $record->prodi_id = Prodi::where('kode', $data['kode_prodi'])->value('id');
        }

        $baru = ! $record->exists;

        if ($baru) {
            $record->password = Str::password(32);
        }

        DB::transaction(function () use ($record, $data): void {
            $record->save();
            $record->syncRoles([$data['peran']]);
        });

        if ($baru) {
            $token = Password::broker()->createToken($record);
            $record->notify(new AkunSwalayanDibuat(Filament::getPanel('admin')->getResetPasswordUrl($token, $record)));
        }

        return $record;
    }
}
