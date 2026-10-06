<?php

use App\Jobs\SegarkanStatistikDasbor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Jadwal harian SDM (zona waktu Asia/Jakarta). Jalankan lokal: php artisan schedule:work.
Schedule::command('sdm:tandai-kedaluwarsa')->dailyAt('00:30')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();
Schedule::command('sdm:hitung-pengingat')->dailyAt('01:00')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();
Schedule::command('sdm:periksa-tautan')->weeklyOn(1, '03:00')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();
Schedule::command('sdm:bersihkan-tmp')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('activitylog:clean')->monthlyOn(1, '02:00')->timezone('Asia/Jakarta')->onOneServer();
Schedule::command('queue:prune-batches')->weekly();
Schedule::command('queue:prune-failed --hours=720')->weekly();
Schedule::command('sdm:kirim-pengingat')->dailyAt('07:00')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();
Schedule::job(new SegarkanStatistikDasbor)->hourly()->onOneServer();
