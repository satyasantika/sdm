<?php

test('dasbor horizon dapat diakses di lingkungan local', function () {
    $this->app['env'] = 'local';

    $this->get('/horizon')->assertOk();
});

test('lingkungan local memiliki empat supervisor bernama', function () {
    $supervisor = config('horizon.environments.local');

    expect(array_keys($supervisor))->toBe([
        'supervisor-default', 'supervisor-impor', 'supervisor-ekspor', 'supervisor-notifikasi',
    ])
        ->and($supervisor['supervisor-notifikasi']['queue'])->toBe(['notifikasi'])
        ->and(config('queue.connections.redis.retry_after'))->toBe(660);
});

test('snapshot horizon terjadwal setiap lima menit', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('horizon:snapshot')->assertSuccessful();
});
