<?php

use Illuminate\Support\Facades\Route;

test('halaman 404 representatif dengan tombol kembali dan beranda', function () {
    $this->get('/rute-yang-tidak-ada-sama-sekali')
        ->assertStatus(404)
        ->assertSee('404')
        ->assertSee('Kembali', escape: false)
        ->assertSee('Beranda', escape: false)
        ->assertSee('history.back()', escape: false);
});

test('halaman 403 representatif saat akses ditolak', function () {
    Route::get('/_tes-403', fn () => abort(403))->middleware('web');

    $this->get('/_tes-403')
        ->assertStatus(403)
        ->assertSee('403')
        ->assertSee('Kembali', escape: false)
        ->assertSee('Beranda', escape: false);
});

test('halaman 500 representatif saat server error', function () {
    Route::get('/_tes-500', fn () => abort(500))->middleware('web');

    $this->get('/_tes-500')
        ->assertStatus(500)
        ->assertSee('500')
        ->assertSee('Kembali', escape: false)
        ->assertSee('Beranda', escape: false);
});

test('halaman eror tidak membocorkan jejak exception', function () {
    Route::get('/_tes-500-trace', fn () => abort(500))->middleware('web');

    $this->get('/_tes-500-trace')
        ->assertDontSee('Stack trace', escape: false)
        ->assertDontSee('vendor/laravel', escape: false);
});
