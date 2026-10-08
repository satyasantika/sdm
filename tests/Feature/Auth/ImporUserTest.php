<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Imports\UserImporter;
use App\Models\Prodi;
use App\Models\User;
use App\Notifications\AkunSwalayanDibuat;
use Database\Seeders\PeranDanIzinSeeder;
use Filament\Actions\ImportAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Prodi::factory()->create(['kode' => 'PMAT']);
});

function akunAdmin(Peran $peran, array $atribut = []): User
{
    return tap(User::factory()->create(array_merge([
        'email' => $peran->value.fake()->unique()->numerify('###').'@unsil.ac.id',
    ], $atribut)), fn (User $u) => $u->assignRole($peran->value));
}

function barisUser(array $timpa = []): array
{
    return array_merge([
        'name' => 'Budi Santoso', 'email' => 'budi.santoso@unsil.ac.id', 'peran' => 'dosen', 'kode_prodi' => 'PMAT',
    ], $timpa);
}

test('baris valid diimpor, mendapat peran, dan menerima notifikasi set password', function () {
    Notification::fake();

    UserImporter::test()->import(barisUser())->assertImported();

    $user = User::firstWhere('email', 'budi.santoso@unsil.ac.id');
    expect($user)->not->toBeNull()
        ->and($user->hasRole('dosen'))->toBeTrue()
        ->and($user->prodi_id)->toBe(Prodi::firstWhere('kode', 'PMAT')->id)
        ->and($user->is_aktif)->toBeTrue();

    Notification::assertSentTo($user, AkunSwalayanDibuat::class);
});

test('peran super-admin ditolak lewat impor', function () {
    $hasil = UserImporter::test()->import(barisUser(['peran' => 'super-admin']));

    expect($hasil->errors()->first('peran'))->toBe('Peran tidak dikenal atau tidak dapat dibuat lewat impor (super-admin dan klien-api tidak diizinkan).');
});

test('admin-prodi wajib memiliki kode prodi', function () {
    UserImporter::test()->import(barisUser(['peran' => 'admin-prodi', 'kode_prodi' => '']))
        ->assertHasRowFailure('Peran Admin Prodi wajib memiliki kode prodi.');
});

test('impor ulang dengan surel sama memperbarui tanpa menggandakan', function () {
    UserImporter::test()->import(barisUser())->assertImported();
    UserImporter::test()->import(barisUser(['name' => 'Budi Santoso Baru']))->assertImported();

    expect(User::where('email', 'budi.santoso@unsil.ac.id')->count())->toBe(1)
        ->and(User::firstWhere('email', 'budi.santoso@unsil.ac.id')->name)->toBe('Budi Santoso Baru');
});

test('impor csv lewat aksi: baris valid tersimpan dan baris peran terlarang gagal', function () {
    $this->actingAs(akunAdmin(Peran::SuperAdmin));

    $kolom = ['name', 'email', 'peran', 'kode_prodi'];
    $baris = [
        ['Dosen Satu', 'dosen.satu@unsil.ac.id', 'dosen', 'PMAT'],
        ['Tendik Dua', 'tendik.dua@unsil.ac.id', 'tendik', ''],
        ['Super Tiga', 'super.tiga@unsil.ac.id', 'super-admin', ''],
    ];
    $csv = implode("\n", [implode(',', $kolom), ...array_map(fn ($b) => implode(',', $b), $baris)]);

    Livewire::test(ListUsers::class)
        ->callAction(ImportAction::class, [
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
            'columnMap' => array_combine($kolom, $kolom),
        ])
        ->assertHasNoActionErrors();

    expect(User::whereIn('email', ['dosen.satu@unsil.ac.id', 'tendik.dua@unsil.ac.id'])->count())->toBe(2)
        ->and(User::where('email', 'super.tiga@unsil.ac.id')->exists())->toBeFalse();
});
