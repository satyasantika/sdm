<?php

namespace App\Listeners;

use App\Filament\Admin\Resources\Bkd\Pages\ListBkd;
use App\Filament\Imports\BkdImporter;
use Filament\Actions\Imports\Events\ImportCompleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Melepas lock impor BKD per semester dan mencatat ringkasan impor di log audit. */
class SelesaikanImporBkd
{
    public function handle(ImportCompleted $event): void
    {
        $impor = $event->getImport();

        if ($impor->importer !== BkdImporter::class) {
            return;
        }

        $semesterId = (string) ($event->getOptions()['semester_id'] ?? '');
        $kunci = ListBkd::kunciLock($semesterId);

        if ($pemilik = Cache::pull($kunci.':pemilik')) {
            Cache::restoreLock($kunci, $pemilik)->release();
        }

        activity()
            ->performedOn($impor)
            ->causedBy($impor->user instanceof Model ? $impor->user : null)
            ->withProperties([
                'semester_id' => $semesterId,
                'total_baris' => $impor->total_rows,
                'berhasil' => $impor->successful_rows,
                'gagal' => $impor->getFailedRowsCount(),
            ])
            ->log('impor bkd selesai');
    }
}
