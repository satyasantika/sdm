<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MasterProdiSeeder::class,
            MasterKepegawaianSeeder::class,
            MasterJabatanSeeder::class,
            MasterPendukungSeeder::class,
            KonfigurasiSeeder::class,
            PeranDanIzinSeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoSeeder::class);
        }
    }
}
