<?php

use App\Enums\Peran;
use App\Models\Pegawai;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Rules\TautanBerkasValid;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Validator;

beforeEach(fn () => $this->seed(PeranDanIzinSeeder::class));

test('tautan berbahaya atau tidak diizinkan ditolak', function (string $url) {
    $v = Validator::make(['u' => $url], ['u' => [new TautanBerkasValid]]);

    expect($v->fails())->toBeTrue();
})->with([
    'javascript' => 'javascript:alert(1)',
    'data' => 'data:text/html,<script>alert(1)</script>',
    'http polos' => 'http://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view',
    'ip privat' => 'https://192.168.1.10/file.pdf',
    'loopback' => 'https://127.0.0.1/file.pdf',
    'metadata cloud' => 'https://169.254.169.254/latest/meta-data',
    'localhost' => 'https://localhost/file',
    'pemendek bitly' => 'https://bit.ly/abc123',
    'pemendek tinyurl' => 'https://tinyurl.com/abc123',
    'domain luar' => 'https://evil.example.com/file.pdf',
    'domain mirip' => 'https://drive.google.com.evil.example.com/file.pdf',
    'userinfo menyamar' => 'https://drive.google.com@evil.example.com/file.pdf',
    'tanpa skema' => 'drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view',
]);

test('tautan google drive sah diterima', function () {
    $v = Validator::make(['u' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz012345/view?usp=sharing'], ['u' => [new TautanBerkasValid]]);

    expect($v->passes())->toBeTrue();
});

test('rute buka tautan tidak menerima parameter tujuan dan hanya mengarah ke url tersimpan', function () {
    $pegawai = Pegawai::factory()->create();
    $user = User::factory()->create(['email' => 'adm@unsil.ac.id', 'app_authentication_secret' => 'ABCDEFGHIJKLMNOP'])->assignRole(Peran::AdminKepegawaian->value);
    $tautan = TautanBerkas::factory()->create(['pemilik_id' => $pegawai->id, 'pegawai_id' => $pegawai->id, 'is_sensitif' => false]);

    $this->actingAs($user)->get(route('tautan.buka', $tautan).'?url=https://evil.example.com&redirect=https://evil.example.com')
        ->assertRedirect($tautan->url);
});

test('rute buka tautan menolak id non-uuid dan pengguna tanpa hak', function () {
    $user = User::factory()->create(['email' => 'dsn@unsil.ac.id'])->assignRole(Peran::Dosen->value);
    $milikOrang = Pegawai::factory()->create();
    $tautan = TautanBerkas::factory()->create(['pemilik_id' => $milikOrang->id, 'pegawai_id' => $milikOrang->id, 'is_sensitif' => true]);

    $this->actingAs($user)->get('/tautan/1/buka')->assertNotFound();
    $this->actingAs($user)->get(route('tautan.buka', $tautan))->assertForbidden();
});
