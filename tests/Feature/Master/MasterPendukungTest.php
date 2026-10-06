<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\Semester\Pages\CreateSemester;
use App\Filament\Admin\Resources\Semester\SemesterResource;
use App\Models\JenisDokumen;
use App\Models\JenjangPendidikan;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\MasterPendukungSeeder;
use Database\Seeders\PeranDanIzinSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(MasterPendukungSeeder::class);
});

function akunMaster(Peran $peran): User
{
    return tap(User::factory()->create([
        'email' => $peran->value.'@unsil.ac.id',
        'app_authentication_secret' => 'ABCDEFGHIJKLMNOP',
    ]), fn (User $u) => $u->assignRole($peran->value));
}

test('seeder menghasilkan master pendukung lengkap', function () {
    expect(JenjangPendidikan::count())->toBe(10)
        ->and(JenjangPendidikan::firstWhere('kode', 'S3')->urutan)->toBe(10)
        ->and(JenisDokumen::where('is_identitas', true)->pluck('kode')->sort()->values()->all())->toBe(['buku-rekening', 'kk', 'ktp', 'npwp'])
        ->and(JenisDokumen::firstWhere('kode', 'paspor')->punya_masa_berlaku)->toBeTrue()
        ->and(Semester::count())->toBe(5);
});

test('hanya satu semester aktif dan mengaktifkan menonaktifkan yang lain', function () {
    expect(Semester::aktifSekarang()->kode)->toBe('20261');

    Semester::firstWhere('kode', '20252')->aktifkan();

    expect(Semester::aktif()->count())->toBe(1)->and(Semester::aktifSekarang()->kode)->toBe('20252');

    Semester::firstWhere('kode', '20251')->update(['is_aktif' => true]);

    expect(Semester::aktif()->pluck('kode')->all())->toBe(['20251']);
});

test('label semester benar', function () {
    expect(Semester::firstWhere('kode', '20251')->label)->toBe('2025/2026 Ganjil')
        ->and(Semester::firstWhere('kode', '20252')->label)->toBe('2025/2026 Genap');
});

test('kode semester 20253 ditolak', function () {
    $this->actingAs(akunMaster(Peran::AdminKepegawaian));

    Livewire::test(CreateSemester::class)
        ->fillForm(['kode' => '20253', 'tahun_akademik' => '2025/2026', 'jenis' => 'ganjil'])
        ->call('create')
        ->assertHasFormErrors(['kode' => 'regex']);
});

test('tahun akademik dan jenis harus konsisten dengan kode', function () {
    $this->actingAs(akunMaster(Peran::AdminKepegawaian));

    Livewire::test(CreateSemester::class)
        ->fillForm(['kode' => '20271', 'tahun_akademik' => '2025/2026', 'jenis' => 'genap'])
        ->call('create')
        ->assertHasFormErrors(['tahun_akademik', 'jenis']);

    Livewire::test(CreateSemester::class)
        ->fillForm(['kode' => '20271', 'tahun_akademik' => '2027/2028', 'jenis' => 'ganjil'])
        ->call('create')
        ->assertHasNoFormErrors();
});

test('admin-prodi hanya dapat membaca semester', function () {
    $this->actingAs(akunMaster(Peran::AdminProdi));

    $this->get(SemesterResource::getUrl('index'))->assertOk();
    $this->get(SemesterResource::getUrl('create'))->assertForbidden();
});
