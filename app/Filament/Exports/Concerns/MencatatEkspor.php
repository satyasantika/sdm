<?php

namespace App\Filament\Exports\Concerns;

use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/** Mencatat ringkasan ekspor (prodi, jumlah baris) di log audit saat ekspor selesai. */
trait MencatatEkspor
{
    abstract protected static function deskripsiLog(): string;

    public static function modifyCompletedNotification(Notification $notification, Export $export): Notification
    {
        activity()
            ->performedOn($export)
            ->causedBy($export->user instanceof Model ? $export->user : null)
            ->withProperties([
                'exporter' => class_basename(static::class),
                'prodi_id' => $export->getOptions()['prodi_id'] ?? null,
                'baris' => $export->successful_rows,
            ])
            ->log(static::deskripsiLog());

        return $notification;
    }
}
