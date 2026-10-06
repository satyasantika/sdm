<?php

namespace Tests\Feature;

use Tests\TestCase;

class HorizonTest extends TestCase
{
    public function test_dasbor_horizon_dapat_diakses_di_lingkungan_local(): void
    {
        $this->app['env'] = 'local';

        $this->get('/horizon')->assertOk();
    }

    public function test_lingkungan_local_memiliki_empat_supervisor_bernama(): void
    {
        $supervisor = config('horizon.environments.local');

        $this->assertSame(
            ['supervisor-default', 'supervisor-impor', 'supervisor-ekspor', 'supervisor-notifikasi'],
            array_keys($supervisor),
        );
        $this->assertSame(['notifikasi'], $supervisor['supervisor-notifikasi']['queue']);
        $this->assertSame(660, config('queue.connections.redis.retry_after'));
    }

    public function test_snapshot_horizon_terjadwal_setiap_lima_menit(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('horizon:snapshot')->assertSuccessful();
    }
}
