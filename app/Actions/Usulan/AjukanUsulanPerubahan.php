<?php

namespace App\Actions\Usulan;

use App\Enums\Peran;
use App\Enums\StatusUsulan;
use App\Models\User;
use App\Models\UsulanPerubahan;
use App\Notifications\UsulanDiajukan;
use Illuminate\Support\Facades\Notification;

class AjukanUsulanPerubahan
{
    public function handle(UsulanPerubahan $usulan, User $oleh): UsulanPerubahan
    {
        $usulan = app(UbahStatusUsulan::class)->handle($usulan, StatusUsulan::Diajukan, $oleh, null, ['diajukan_at' => now()]);

        Notification::send(
            User::query()->whereHas('roles', fn ($q) => $q->where('name', Peran::AdminKepegawaian->value))->get(),
            new UsulanDiajukan($usulan),
        );

        return $usulan;
    }
}
