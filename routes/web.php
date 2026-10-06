<?php

use App\Http\Controllers\BukaTautanController;
use App\Http\Controllers\UnduhKeluaranController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

    if ($user === null) {
        return redirect()->route('filament.admin.auth.login');
    }

    return redirect()->to($user->can('swalayan.akses') && $user->pegawai()->exists() ? '/saya' : '/admin');
})->name('beranda');

Route::get('/tautan/{tautanBerkas}/buka', BukaTautanController::class)
    ->middleware(['auth', 'throttle:buka-tautan'])
    ->whereUuid('tautanBerkas')
    ->name('tautan.buka');

Route::get('/keluaran/{id}', UnduhKeluaranController::class)
    ->middleware(['auth', 'throttle:buka-tautan'])
    ->whereUuid('id')
    ->name('keluaran.unduh');
