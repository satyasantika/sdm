<?php

namespace App\Support;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Berkas keluaran sementara (disk tmp, ≤ 24 jam) yang hanya dapat diunduh pembuatnya. */
class KeluaranSementara
{
    public static function simpan(User $pembuat, string $judul, string $namaBerkas, string $isi): string
    {
        $id = (string) Str::uuid7();
        $path = "laporan/{$id}.".pathinfo($namaBerkas, PATHINFO_EXTENSION);
        $jam = (int) config('berkas.umur_tmp_jam', 24);

        Storage::disk((string) config('berkas.disk_tmp', 'tmp'))->put($path, $isi);
        Cache::put("sdm:keluaran:{$id}", ['user_id' => $pembuat->getKey(), 'path' => $path, 'nama' => $namaBerkas], now()->addHours($jam));

        Notification::make()
            ->title("{$judul} siap diunduh")
            ->body("Berkas dihapus otomatis dalam {$jam} jam.")
            ->success()
            ->actions([Action::make('unduh')->label('Unduh')->url(route('keluaran.unduh', $id))])
            ->sendToDatabase($pembuat);

        return $id;
    }

    /** @return array{user_id: string, path: string, nama: string}|null */
    public static function cari(string $id): ?array
    {
        /** @var array{user_id: string, path: string, nama: string}|null $meta */
        $meta = Cache::get("sdm:keluaran:{$id}");

        return $meta;
    }
}
