<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MasterProdiSeeder::class,
            PeranDanIzinSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
