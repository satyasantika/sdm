<?php

namespace App\Filament\Admin\Resources\Bkd\Pages;

use App\Filament\Admin\Resources\Bkd\BkdResource;
use App\Filament\Imports\BkdImporter;
use Filament\Actions\ImportAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListBkd extends ListRecords
{
    protected static string $resource = BkdResource::class;

    /** Kunci lock impor BKD per semester (BR-22); dilepas saat ImportCompleted atau habis TTL 15 menit. */
    public static function kunciLock(string $semesterId): string
    {
        return 'sdm:lock:impor-bkd:'.$semesterId;
    }

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(BkdImporter::class)
                ->label('Impor BKD (SISTER)')
                ->chunkSize(100)
                ->visible(fn (): bool => (bool) auth()->user()?->can('bkd.impor'))
                ->before(function (ImportAction $action, array $data): void {
                    $semesterId = (string) ($data['semester_id'] ?? '');
                    $lock = Cache::lock(self::kunciLock($semesterId), 900);

                    if (! $lock->get()) {
                        Notification::make()->warning()->title('Impor BKD semester ini sedang berjalan.')->send();
                        $action->halt();

                        return;
                    }

                    Cache::put(self::kunciLock($semesterId).':pemilik', $lock->owner(), 900);
                }),
        ];
    }
}
