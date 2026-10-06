<?php

namespace App\Actions\Pegawai;

use App\Enums\JenisPegawai;
use App\Enums\Peran;
use App\Models\Pegawai;
use App\Models\User;
use App\Notifications\AkunSwalayanDibuat;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BuatAkunPegawai
{
    public function handle(Pegawai $pegawai): User
    {
        if ($pegawai->user_id !== null) {
            throw ValidationException::withMessages(['pegawai' => "{$pegawai->nama_bergelar} sudah memiliki akun."]);
        }

        if (blank($pegawai->email_unsil)) {
            throw ValidationException::withMessages(['email_unsil' => "{$pegawai->nama_bergelar} belum memiliki surel Unsil."]);
        }

        if (User::where('email', $pegawai->email_unsil)->exists()) {
            throw ValidationException::withMessages(['email_unsil' => "Surel {$pegawai->email_unsil} sudah dipakai akun lain."]);
        }

        $user = DB::transaction(function () use ($pegawai): User {
            $user = User::create([
                'name' => $pegawai->nama_bergelar,
                'email' => $pegawai->email_unsil,
                'password' => Str::password(32),
                'nip' => $pegawai->nip,
                'nidn' => $pegawai->nidn,
                'prodi_id' => $pegawai->prodi_id,
                'is_aktif' => true,
            ]);
            $user->assignRole(($pegawai->jenis_pegawai === JenisPegawai::Dosen ? Peran::Dosen : Peran::Tendik)->value);
            $pegawai->update(['user_id' => $user->getKey()]);

            return $user;
        });

        $token = Password::broker()->createToken($user);
        $user->notify(new AkunSwalayanDibuat(Filament::getPanel('admin')->getResetPasswordUrl($token, $user)));

        return $user;
    }
}
