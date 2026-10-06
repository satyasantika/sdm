<?php

use App\Enums\Peran;
use App\Filament\Admin\Resources\JabatanFungsional\JabatanFungsionalResource;
use App\Models\JabatanFungsional;
use App\Models\JenisJabatanStruktural;
use App\Models\User;
use Database\Seeders\MasterJabatanSeeder;
use Database\Seeders\PeranDanIzinSeeder;

beforeEach(function () {
    $this->seed(PeranDanIzinSeeder::class);
    $this->seed(MasterJabatanSeeder::class);
});

test('jenjang berikutnya lektor adalah lektor kepala dan profesor tidak punya', function () {
    $lektor = JabatanFungsional::firstWhere('kode', 'lektor');
    $profesor = JabatanFungsional::firstWhere('kode', 'profesor');

    expect($lektor->jenjangBerikutnya()->kode)->toBe('lektor-kepala')
        ->and($profesor->jenjangBerikutnya())->toBeNull()
        ->and($profesor->is_puncak)->toBeTrue();
});

test('lebih rendah dari membandingkan urutan pada kelompok sama', function () {
    $asisten = JabatanFungsional::firstWhere('kode', 'asisten-ahli');
    $lektor = JabatanFungsional::firstWhere('kode', 'lektor');

    expect($asisten->lebihRendahDari($lektor))->toBeTrue()
        ->and($lektor->lebihRendahDari($asisten))->toBeFalse()
        ->and($lektor->lebihRendahDari($lektor))->toBeFalse();
});

test('lima jenjang dosen terseed tanpa angka syarat hard-coded', function () {
    $dosen = JabatanFungsional::where('kelompok', 'dosen')->orderBy('urutan')->get();

    expect($dosen->pluck('kode')->all())->toBe(['tenaga-pengajar', 'asisten-ahli', 'lektor', 'lektor-kepala', 'profesor'])
        ->and($dosen->pluck('masa_kerja_minimal_bulan')->filter()->all())->toBeEmpty();
});

test('syarat yang diubah lewat model terbaca ulang', function () {
    $lektor = JabatanFungsional::firstWhere('kode', 'lektor');

    $lektor->update(['masa_kerja_minimal_bulan' => 24, 'angka_kredit_minimal' => 200]);

    expect(JabatanFungsional::find($lektor->id)->masa_kerja_minimal_bulan)->toBe(24)
        ->and(JabatanFungsional::find($lektor->id)->angka_kredit_minimal)->toBe('200.00');
});

test('seeder jabatan struktural lengkap dan idempoten', function () {
    $this->seed(MasterJabatanSeeder::class);

    expect(JenisJabatanStruktural::count())->toBe(9)
        ->and(JenisJabatanStruktural::where('kategori', 'tugas_tambahan')->count())->toBe(4);
});

test('admin-prodi tidak dapat mengubah jabatan fungsional', function () {
    $user = tap(User::factory()->create(['email' => 'prodi@unsil.ac.id']), fn ($u) => $u->assignRole(Peran::AdminProdi->value));
    $lektor = JabatanFungsional::firstWhere('kode', 'lektor');

    $this->actingAs($user)->get(JabatanFungsionalResource::getUrl('index'))->assertOk();
    $this->actingAs($user)->get(JabatanFungsionalResource::getUrl('edit', ['record' => $lektor]))->assertForbidden();
});
