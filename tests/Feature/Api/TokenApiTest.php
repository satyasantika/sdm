<?php

use App\Actions\Api\BuatTokenKlienApi;
use App\Enums\Peran;
use App\Filament\Admin\Resources\TokenApi\Pages\ListTokenApi;
use App\Models\TokenAkses;
use App\Models\User;
use Database\Seeders\KonfigurasiSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(KonfigurasiSeeder::class);
});

function akunToken(Peran $peran): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.fake()->unique()->numerify('####').'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('super-admin membuat token: tampil sekali dan tersimpan sebagai hash', function () {
    $this->actingAs(akunToken(Peran::SuperAdmin));

    $komponen = Livewire::test(ListTokenApi::class)
        ->callAction('buatToken', ['klien' => 'Akreditasi', 'token' => 'integrasi-1', 'hari' => 30])
        ->assertHasNoActionErrors();

    $token = TokenAkses::firstOrFail();
    $klien = User::where('email', 'klien-akreditasi@sdm.fkip.local')->firstOrFail();
    $polos = (fn () => $this->tokenPolos)->call($komponen->instance());

    expect($polos)->toContain('|')->and($token->token)->not->toBe($polos)->and($token->token)->toBe(hash('sha256', explode('|', $polos, 2)[1]))
        ->and($token->abilities)->toBe(['sdm:read'])
        ->and($token->expires_at->isSameDay(now()->addDays(30)))->toBeTrue()
        ->and($klien->hasRole(Peran::KlienApi->value))->toBeTrue()
        ->and(Activity::where('log_name', 'token-api')->count())->toBe(1);

    $komponen->assertActionVisible('tampilToken');
});

test('admin-kepegawaian tidak boleh mengelola token', function () {
    $this->actingAs(akunToken(Peran::AdminKepegawaian));

    expect(fn () => app(BuatTokenKlienApi::class)->handle(auth()->user(), 'X', 'y'))->toThrow(HttpException::class);
    Livewire::test(ListTokenApi::class)->assertForbidden();
});

test('klien api tidak dapat mengakses panel mana pun', function () {
    [$klien] = app(BuatTokenKlienApi::class)->handle(akunToken(Peran::SuperAdmin), 'LMS', 'lms-1');

    foreach (['admin', 'swalayan'] as $id) {
        expect($klien->canAccessPanel(Filament::getPanel($id)))->toBeFalse();
    }
    $this->get('/admin')->assertRedirect();
    $this->actingAs($klien)->get('/admin')->assertForbidden();
    $this->actingAs($klien)->get('/saya')->assertForbidden();
});

test('token valid mengakses api, token dicabut atau tanpa ability ditolak', function () {
    $admin = akunToken(Peran::SuperAdmin);
    [$klien, $polos] = app(BuatTokenKlienApi::class)->handle($admin, 'Keuangan', 'keu-1');

    $this->withToken($polos)->getJson('/api/v1/dosen')->assertOk();

    $tanpaAbility = $klien->createToken('lain', ['sdm:write'])->plainTextToken;
    app('auth')->forgetGuards();
    $this->withToken($tanpaAbility)->getJson('/api/v1/dosen')->assertForbidden();

    TokenAkses::where('name', 'keu-1')->delete();

    app('auth')->forgetGuards();
    $this->withToken($polos)->getJson('/api/v1/dosen')->assertUnauthorized();
    $this->flushHeaders()->getJson('/api/v1/dosen')->assertUnauthorized();
});

test('super-admin dapat mencabut token lewat panel dan tercatat', function () {
    $admin = akunToken(Peran::SuperAdmin);
    app(BuatTokenKlienApi::class)->handle($admin, 'Keuangan', 'keu-1');
    $this->actingAs($admin);

    Livewire::test(ListTokenApi::class)->loadTable()
        ->callAction(TestAction::make('delete')->table(TokenAkses::where('name', 'keu-1')->first()))->assertNotified('Token dicabut.');

    expect(TokenAkses::count())->toBe(0)->and(Activity::where('description', 'Token klien API dicabut')->count())->toBe(1);
});

test('limit api 60 per menit per token', function () {
    [, $polos] = app(BuatTokenKlienApi::class)->handle(akunToken(Peran::SuperAdmin), 'Batas', 'b-1');

    foreach (range(1, 60) as $i) {
        $this->withToken($polos)->getJson('/api/v1/dosen')->assertOk();
    }
    $this->withToken($polos)->getJson('/api/v1/dosen')->assertStatus(429);
});
