<?php

namespace App\Actions\Usulan;

use App\Enums\StatusUsulan;
use App\Models\User;
use App\Models\UsulanPerubahan;

class BatalkanUsulanPerubahan
{
    public function handle(UsulanPerubahan $usulan, User $oleh, ?string $catatan = null): UsulanPerubahan
    {
        return app(UbahStatusUsulan::class)->handle($usulan, StatusUsulan::Dibatalkan, $oleh, $catatan);
    }
}
