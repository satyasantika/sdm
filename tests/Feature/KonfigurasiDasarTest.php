<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class KonfigurasiDasarTest extends TestCase
{
    public function test_zona_waktu_aplikasi_adalah_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());
    }

    public function test_locale_aplikasi_adalah_indonesia(): void
    {
        $this->assertSame('id', app()->getLocale());
        $this->assertSame('id_ID', config('app.faker_locale'));
    }

    public function test_pesan_validasi_berbahasa_indonesia(): void
    {
        $pesan = Validator::make([], ['nama' => 'required'])->errors()->first('nama');

        $this->assertSame('Isian nama wajib diisi.', $pesan);
    }

    public function test_redis_memakai_database_terpisah_untuk_cache_dan_antrean(): void
    {
        $this->assertSame('1', (string) config('database.redis.cache.database'));
        $this->assertSame('2', (string) config('database.redis.queue.database'));
        $this->assertSame('queue', config('queue.connections.redis.connection'));
        $this->assertSame(660, config('queue.connections.redis.retry_after'));
    }
}
