<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Tabel infrastruktur kerangka kerja yang dikecualikan (STANDAR-TEKNIS §4a butir 6). */
const MIGRASI_INFRASTRUKTUR = [
    '0001_01_01_000002_create_jobs_table.php',
    '0001_01_01_000001_create_cache_table.php',
];

test('semua model aplikasi memakai HasUuids', function () {
    $model = collect(glob(app_path('Models/*.php')))
        ->map(fn (string $berkas) => 'App\\Models\\'.basename($berkas, '.php'))
        ->filter(fn (string $kelas) => is_subclass_of($kelas, Model::class));

    expect($model)->not->toBeEmpty();

    foreach ($model as $kelas) {
        expect(class_uses_recursive($kelas))->toContain(HasUuids::class);
    }
});

test('migrasi aplikasi tidak memakai kunci berurutan atau morph non-uuid', function () {
    $dilarang = ['->id()', 'foreignId(', 'bigIncrements(', 'increments(', '$table->morphs(', '->nullableMorphs('];

    foreach (glob(database_path('migrations/*.php')) as $berkas) {
        if (in_array(basename($berkas), MIGRASI_INFRASTRUKTUR, true)) {
            continue;
        }

        $isi = file_get_contents($berkas);

        foreach ($dilarang as $pola) {
            expect(str_contains($isi, $pola))->toBeFalse(basename($berkas)." memuat {$pola}");
        }
    }
});

test('user memakai uuid versi 7 sebagai primary key', function () {
    $user = User::factory()->create();

    expect(Str::isUuid($user->id))->toBeTrue()
        ->and($user->id[14])->toBe('7')
        ->and($user->fresh()->id)->toBe($user->id);
});

test('route model binding bekerja dengan uuid', function () {
    $user = User::factory()->create();

    Route::middleware('web')->get('/_uji/{user}', fn (User $user) => $user->email)
        ->whereUuid('user');

    $this->get("/_uji/{$user->id}")->assertOk()->assertSee($user->email);
    $this->get('/_uji/bukan-uuid')->assertNotFound();
});
