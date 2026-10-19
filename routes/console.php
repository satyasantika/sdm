<?php

use App\Jobs\SegarkanStatistikDasbor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal harian SDM (zona waktu Asia/Jakarta). Jalankan lokal: php artisan schedule:work.
Schedule::command('sdm:tandai-kedaluwarsa')->dailyAt('00:30')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();
Schedule::command('sdm:hitung-pengingat')->dailyAt('01:00')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();
Schedule::command('sdm:periksa-tautan')->weeklyOn(1, '03:00')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();
Schedule::command('sdm:bersihkan-tmp')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('activitylog:clean')->monthlyOn(1, '02:00')->timezone('Asia/Jakarta')->onOneServer();
Schedule::command('sdm:kirim-pengingat')->dailyAt('07:00')->timezone('Asia/Jakarta')->withoutOverlapping()->onOneServer();
Schedule::job(new SegarkanStatistikDasbor)->hourly()->onOneServer();

// Profil Ringan (STANDAR-TEKNIS §1b): tanpa Horizon/Redis. Penjadwal menyalakan worker tiap menit dan
// berhenti saat antrean kosong; jeda proses job paling lama ±1 menit.
Schedule::command('queue:work database --queue=default --stop-when-empty --max-time=55 --timeout=60 --tries=3')
    ->everyMinute()->withoutOverlapping(2)->runInBackground();

Schedule::command('queue:work database-berat --queue=berat --stop-when-empty --max-time=600 --timeout=900 --tries=2 --memory=512')
    ->everyMinute()->withoutOverlapping(30)->runInBackground();

// Detak penjadwal: dibaca /api/health; peringatan bila lebih tua dari 5 menit.
Schedule::call(fn () => cache()->put(config('app.kode').':detak-penjadwal', now()->getTimestamp(), 600))
    ->everyMinute()->name('detak-penjadwal');

Schedule::command('queue:prune-failed --hours=720')->dailyAt('03:00');
Schedule::command('queue:prune-batches --hours=168')->dailyAt('03:05');
