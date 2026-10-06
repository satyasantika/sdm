<?php

namespace App\Filament\Admin\Resources\Bkd\Pages;

use App\Filament\Admin\Resources\Bkd\BkdResource;
use App\Filament\Admin\Widgets\BkdRingkasanWidget;
use App\Filament\Exports\RekapBkdExporter;
use App\Filament\Imports\BkdImporter;
use App\Models\Pegawai;
use App\Support\BatasEkspor;
use App\Support\BkdRingkasan;
use App\Support\CakupanProdi;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListBkd extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = BkdResource::class;

    /** @return array<class-string> */
    protected function getHeaderWidgets(): array
    {
        return [BkdRingkasanWidget::class];
    }

    /** Kunci lock impor BKD per semester (BR-22); dilepas saat ImportCompleted atau habis TTL 15 menit. */
    public static function kunciLock(string $semesterId): string
    {
        return 'sdm:lock:impor-bkd:'.$semesterId;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Input manual'),
            ExportAction::make()
                ->exporter(RekapBkdExporter::class)
                ->label('Ekspor rekap')
                ->fileDisk('tmp')
                ->visible(fn (): bool => (bool) auth()->user()?->can('bkd.ekspor'))
                ->modifyQueryUsing(fn (Builder $query): Builder => CakupanProdi::batasi($query, auth()->user()))
                ->before(BatasEkspor::sebelum()),
            Action::make('tanpaData')->label('Dosen tanpa data BKD')->icon(Heroicon::OutlinedExclamationTriangle)->color('warning')
                ->modalHeading('Dosen tetap tanpa data BKD')->modalSubmitAction(false)->modalCancelActionLabel('Tutup')
                ->modalContent(function () {
                    $filter = $this->tableFilters ?? [];
                    $nama = BkdRingkasan::tanpaData($filter['semester_id']['value'] ?? null, $filter['prodi']['value'] ?? null, auth()->user())
                        ->orderBy('nama')->get()->map(fn (Pegawai $p): string => $p->nama_bergelar.' ('.($p->nidn ?? '-').')')->all();

                    return view('filament.admin.daftar-nama', ['nama' => $nama]);
                })
                ->visible(fn (): bool => (bool) auth()->user()?->can('bkd.lihat')),
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
