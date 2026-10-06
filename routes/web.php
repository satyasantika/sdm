<?php

use App\Http\Controllers\BukaTautanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tautan/{tautanBerkas}/buka', BukaTautanController::class)
    ->middleware(['auth', 'throttle:buka-tautan'])
    ->whereUuid('tautanBerkas')
    ->name('tautan.buka');
