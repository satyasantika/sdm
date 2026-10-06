<?php

namespace Database\Seeders;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('sdm.superadmin.email');
        $password = config('sdm.superadmin.password');

        if (! $email || ! $password) {
            $this->command->warn('SEED_SUPERADMIN_EMAIL/SEED_SUPERADMIN_PASSWORD kosong; super-admin tidak dibuat.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Super Admin', 'password' => $password, 'is_aktif' => true, 'email_verified_at' => now()],
        );

        $user->assignRole(Peran::SuperAdmin->value);
    }
}
