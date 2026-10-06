<?php

namespace App\Actions\Usulan;

use App\Enums\StatusUsulan;
use App\Models\User;
use App\Models\UsulanPerubahan;

class AjukanUsulanPerubahan
{
    public function handle(UsulanPerubahan $usulan, User $oleh): UsulanPerubahan
    {
        return app(UbahStatusUsulan::class)->handle($usulan, StatusUsulan::Diajukan, $oleh, null, ['diajukan_at' => now()]);
    }
}
