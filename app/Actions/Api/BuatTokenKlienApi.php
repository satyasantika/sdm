<?php

namespace App\Actions\Api;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Membuat klien API (user layanan tanpa akses panel) beserta token berability sdm:read (BR-27). */
class BuatTokenKlienApi
{
    public const HARI_BERLAKU_DEFAULT = 365;

    /** @return array{0: User, 1: string} klien dan token polos (hanya tersedia saat ini) */
    public function handle(User $pembuat, string $namaKlien, string $namaToken, int $hariBerlaku = self::HARI_BERLAKU_DEFAULT): array
    {
        abort_unless($pembuat->can('api.kelola-token'), 403);

        return DB::transaction(function () use ($pembuat, $namaKlien, $namaToken, $hariBerlaku): array {
            $email = 'klien-'.Str::slug($namaKlien).'@sdm.fkip.local';
            $klien = User::query()->where('email', $email)->first() ?? User::forceCreate([
                'name' => 'Klien API '.$namaKlien,
                'email' => $email,
                'password' => Str::random(64),
                'is_aktif' => true,
            ]);
            $klien->syncRoles([Peran::KlienApi->value]);

            $token = $klien->createToken($namaToken, ['sdm:read'], now()->addDays($hariBerlaku));

            activity('token-api')->performedOn($klien)->causedBy($pembuat)
                ->withProperties(['token_id' => $token->accessToken->getKey(), 'nama_token' => $namaToken, 'berlaku_hari' => $hariBerlaku])
                ->log('Token klien API dibuat');

            return [$klien, $token->plainTextToken];
        });
    }
}
