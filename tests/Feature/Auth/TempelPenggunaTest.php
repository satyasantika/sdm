<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\TempelPengguna;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Prodi;
use App\Models\User;
use App\Notifications\AkunSwalayanDibuat;
use Database\Seeders\PeranDanIzinSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    Prodi::factory()->create(['kode' => 'PMAT']);
});

function adminTempel(Peran $peran = Peran::SuperAdmin): User
{
    return tap(User::factory()->create(['email' => $peran->value.'@unsil.ac.id']), fn (User $u) => $u->assignRole($peran->value));
}

function teksExcel(string ...$baris): string
{
    return implode("\n", array_map(fn (string $b): string => str_replace('|', "\t", $b), $baris));
}

test('pratinjau menandai baris valid, baru/perbarui, dan baris bermasalah tanpa menyimpan apa pun', function () {
    User::factory()->create(['email' => 'ada@unsil.ac.id']);
    $this->actingAs(adminTempel());

    $teks = teksExcel(
        'Nama|Surel|Peran|Kode prodi|NIP',
        'Budi Santoso|budi@unsil.ac.id|dosen|PMAT|198501012010012001',
        'Ada Sudah|ada@unsil.ac.id|tendik||',
        'Salah Peran|salah@unsil.ac.id|super-admin||',
        'Prodi Tanpa|ap@unsil.ac.id|admin-prodi||',
        'Surel Luar|luar@gmail.com|dosen|PMAT|',
        'Kembar|budi@unsil.ac.id|dosen|PMAT|',
    );

    $komponen = Livewire::test(TempelPengguna::class)->set('teks', $teks)->call('pratinjau');
    $hasil = $komponen->get('pratinjau');

    expect($hasil['galat'])->toBeNull()
        ->and($hasil['valid'])->toBe(2)
        ->and($hasil['tidak_valid'])->toBe(4)
        ->and($hasil['baris'][0]['aksi'])->toBe('Buat baru')
        ->and($hasil['baris'][1]['aksi'])->toBe('Perbarui')
        ->and($hasil['baris'][2]['pesan'][0])->toContain('tidak dapat dibuat lewat impor')
        ->and($hasil['baris'][3]['pesan'][0])->toContain('wajib memiliki kode prodi')
        ->and($hasil['baris'][4]['pesan'][0])->toContain('@unsil.ac.id')
        ->and($hasil['baris'][5]['pesan'][0])->toContain('baris 2');

    expect(User::where('email', 'budi@unsil.ac.id')->exists())->toBeFalse();
});

test('surel @staff.unsil.ac.id diterima, domain lain tetap ditolak', function () {
    $this->actingAs(adminTempel());

    $hasil = Livewire::test(TempelPengguna::class)
        ->set('teks', teksExcel('Nama|Surel|Peran', 'Staf|staf@staff.unsil.ac.id|tendik|', 'Palsu|x@evil-unsil.ac.id|tendik|', 'Palsu2|x@staff.unsil.ac.id.evil.com|tendik|'))
        ->call('pratinjau')->get('pratinjau');

    expect($hasil['baris'][0]['valid'])->toBeTrue()
        ->and($hasil['baris'][1]['valid'])->toBeFalse()
        ->and($hasil['baris'][1]['pesan'][0])->toBe('Surel harus berakhiran @unsil.ac.id atau @staff.unsil.ac.id.')
        ->and($hasil['baris'][2]['valid'])->toBeFalse();
});

test('akun berdomain @staff.unsil.ac.id dapat mengakses panel admin', function () {
    $user = User::factory()->create(['email' => 'staf@staff.unsil.ac.id']);
    $user->assignRole(Peran::AdminKepegawaian->value);

    expect($user->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue()
        ->and(User::surelDiizinkan('x@gmail.com'))->toBeFalse();
});

test('impor menyimpan hanya baris valid, memberi peran, dan mengirim notifikasi atur sandi', function () {
    Notification::fake();
    $this->actingAs(adminTempel());

    $teks = teksExcel(
        'Nama|Surel|Peran|Kode prodi',
        'Budi Santoso|budi@unsil.ac.id|dosen|PMAT',
        'Ani Tendik|ani@unsil.ac.id|tendik|',
        'Salah|salah@unsil.ac.id|klien-api|',
    );

    Livewire::test(TempelPengguna::class)->set('teks', $teks)->call('pratinjau')->call('impor');

    $budi = User::firstWhere('email', 'budi@unsil.ac.id');
    expect($budi)->not->toBeNull()
        ->and($budi->hasRole('dosen'))->toBeTrue()
        ->and($budi->prodi_id)->not->toBeNull()
        ->and(User::where('email', 'salah@unsil.ac.id')->exists())->toBeFalse();

    Notification::assertSentTo($budi, AkunSwalayanDibuat::class);
    Notification::assertSentTimes(AkunSwalayanDibuat::class, 2);
});

test('judul kolom wajib dan batas baris ditegakkan', function () {
    $this->actingAs(adminTempel());

    $a = Livewire::test(TempelPengguna::class)->set('teks', teksExcel('Budi|budi@unsil.ac.id|dosen'))->call('pratinjau')->get('pratinjau');
    expect($a['galat'])->toContain('tidak ditemukan');

    $banyak = teksExcel('Nama|Surel|Peran', ...array_fill(0, 501, 'X|x@unsil.ac.id|dosen'));
    $b = Livewire::test(TempelPengguna::class)->set('teks', $banyak)->call('pratinjau')->get('pratinjau');
    expect($b['galat'])->toContain('Maksimal 500');
});

test('hanya super-admin yang dapat membuka halaman tempel', function () {
    $this->actingAs(adminTempel(Peran::AdminKepegawaian))
        ->get(UserResource::getUrl('tempel'))->assertForbidden();

    $this->actingAs(adminTempel())
        ->get(UserResource::getUrl('tempel'))->assertOk();
});

test('daftar pengguna menampilkan tombol tempel dari Excel untuk super-admin', function () {
    $this->actingAs(adminTempel());

    Livewire::test(ListUsers::class)->assertActionVisible('tempel');
});
