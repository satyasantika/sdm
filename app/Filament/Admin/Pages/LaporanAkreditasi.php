<?php

namespace App\Filament\Admin\Pages;

use App\Actions\Laporan\HitungRasioDosenMahasiswa;
use App\Actions\Laporan\HitungStatistikDasbor;
use App\Filament\Exports\BebanKerjaDtpsExporter;
use App\Filament\Exports\PengembanganKompetensiExporter;
use App\Filament\Exports\ProfilDosenProdiExporter;
use App\Filament\Exports\RasioDosenMahasiswaExporter;
use App\Filament\Exports\RekognisiDtpsExporter;
use App\Filament\Exports\TenagaKependidikanExporter;
use App\Models\Prodi;
use App\Models\Semester;
use App\Support\BatasEkspor;
use App\Support\CakupanProdi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class LaporanAkreditasi extends Page
{
    protected string $view = 'filament.admin.pages.laporan-akreditasi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Laporan Akreditasi';

    protected static ?string $title = 'Laporan Akreditasi';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('laporan.lihat');
    }

    public function mount(): void
    {
        $this->form->fill([
            'prodi_id' => CakupanProdi::terkunci(auth()->user()),
            'tanggal' => now()->toDateString(),
            'semester_id' => Semester::aktifSekarang()?->id,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('prodi_id')->label('Program studi')->required()->live()
                ->options(fn (): array => ($terkunci = CakupanProdi::terkunci(auth()->user())) !== null
                    ? array_intersect_key(Prodi::opsiAktif(), [$terkunci => true])
                    : Prodi::opsiAktif())
                ->disabled(fn (): bool => CakupanProdi::terkunci(auth()->user()) !== null)
                ->dehydrated(),
            DatePicker::make('tanggal')->label('Tanggal acuan')->default(now())->live(),
            Select::make('semester_id')->label('Semester (rasio)')->live()
                ->options(fn (): array => Semester::query()->orderByDesc('kode')->get()->mapWithKeys(fn (Semester $s): array => [$s->id => $s->label])->all()),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form'),
        ]);
    }

    /** Prodi terpilih; admin-prodi selalu prodinya sendiri (server-side). */
    public function prodiTerpilih(): ?string
    {
        return CakupanProdi::terkunci(auth()->user()) ?? ($this->data['prodi_id'] ?? null);
    }

    /**
     * Rasio per prodi untuk semester terpilih (default aktif); admin-prodi hanya baris prodinya.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rasio(): Collection
    {
        $semester = filled($this->data['semester_id'] ?? null) ? Semester::find($this->data['semester_id']) : Semester::aktifSekarang();

        if (! $semester) {
            return collect();
        }

        $baris = app(HitungRasioDosenMahasiswa::class)->handle($semester);

        if ($terkunci = CakupanProdi::terkunci(auth()->user())) {
            $baris = $baris->where('prodi_id', $terkunci)->values();
        }

        return $baris;
    }

    /** @return array<string, mixed>|null */
    public function statistik(): ?array
    {
        $prodi = $this->prodiTerpilih();

        return $prodi ? app(HitungStatistikDasbor::class)->handle($prodi) : null;
    }

    protected function getHeaderActions(): array
    {
        $batasi = fn (Builder $query): Builder => CakupanProdi::batasi($query, auth()->user(), null);
        $prodi = fn () => $this->prodiTerpilih();

        return [
            ExportAction::make('eksporProfil')
                ->exporter(ProfilDosenProdiExporter::class)
                ->label('Ekspor profil dosen tetap')
                ->fileDisk('tmp')
                ->options(fn (): array => ['prodi_id' => $prodi()])
                ->modifyQueryUsing(fn (Builder $query): Builder => $batasi($query)->when($prodi(), fn (Builder $q, string $id) => $q->where('prodi_id', $id)))
                ->visible(fn (): bool => (bool) auth()->user()?->can('laporan.ekspor'))
                ->before(BatasEkspor::sebelum()),
            ActionGroup::make([
                ExportAction::make('eksporBeban')->exporter(BebanKerjaDtpsExporter::class)->label('Beban kerja DTPS')->fileDisk('tmp')
                    ->modifyQueryUsing(fn (Builder $query, array $options): Builder => CakupanProdi::batasi($query, auth()->user())
                        ->where('semester_id', $options['semester_id'] ?? null)
                        ->when($prodi(), fn (Builder $q, string $id) => $q->whereHas('pegawai', fn (Builder $p) => $p->where('prodi_id', $id))))
                    ->before(BatasEkspor::sebelum()),
                ExportAction::make('eksporRekognisi')->exporter(RekognisiDtpsExporter::class)->label('Rekognisi DTPS')->fileDisk('tmp')
                    ->modifyQueryUsing(fn (Builder $query): Builder => CakupanProdi::batasi($query, auth()->user())
                        ->when($prodi(), fn (Builder $q, string $id) => $q->whereHas('pegawai', fn (Builder $p) => $p->where('prodi_id', $id))))
                    ->before(BatasEkspor::sebelum()),
                ExportAction::make('eksporPengembangan')->exporter(PengembanganKompetensiExporter::class)->label('Pengembangan kompetensi')->fileDisk('tmp')
                    ->modifyQueryUsing(fn (Builder $query, array $options): Builder => CakupanProdi::batasi($query, auth()->user())
                        ->whereHas('pegawai', fn (Builder $p) => $p->where('jenis_pegawai', $options['jenis_pegawai'] ?? 'dosen')
                            ->when($prodi(), fn (Builder $q, string $id) => $q->where('prodi_id', $id))))
                    ->before(BatasEkspor::sebelum()),
                ExportAction::make('eksporRasio')->exporter(RasioDosenMahasiswaExporter::class)->label('Rasio dosen–mahasiswa')->fileDisk('tmp')
                    ->modifyQueryUsing(fn (Builder $query): Builder => ($terkunci = CakupanProdi::terkunci(auth()->user())) ? $query->whereKey($terkunci) : $query)
                    ->before(BatasEkspor::sebelum()),
                ExportAction::make('eksporTendik')->exporter(TenagaKependidikanExporter::class)->label('Tenaga kependidikan')->fileDisk('tmp')
                    ->visible(fn (): bool => CakupanProdi::terkunci(auth()->user()) === null)
                    ->before(BatasEkspor::sebelum()),
            ])->label('Ekspor DKPS SDM')->icon(Heroicon::OutlinedArrowDownTray)->button()
                ->visible(fn (): bool => (bool) auth()->user()?->can('laporan.ekspor')),
            Action::make('eksporRingkasan')->label('Unduh ringkasan kualifikasi')->icon(Heroicon::OutlinedTableCells)
                ->visible(fn (): bool => (bool) auth()->user()?->can('laporan.ekspor') && $this->prodiTerpilih() !== null)
                ->before(BatasEkspor::sebelum())
                ->action(fn () => $this->unduhRingkasan()),
        ];
    }

    /** Ekspor kecil di-stream langsung (tidak disimpan di server). */
    public function unduhRingkasan(): StreamedResponse
    {
        $s = $this->statistik() ?? [];
        $total = max(1, (int) ($s['jumlah_dosen'] ?? 0));

        return response()->streamDownload(function () use ($s, $total): void {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Kategori', 'Nilai', 'Jumlah dosen', 'Persentase']);
            foreach (['dosen_per_jabatan' => 'Jabatan akademik', 'dosen_per_pendidikan' => 'Pendidikan tertinggi'] as $kunci => $label) {
                foreach ($s[$kunci] ?? [] as $nilai => $jumlah) {
                    fputcsv($h, [$label, $nilai, $jumlah, round($jumlah / $total * 100, 1).'%']);
                }
            }
            fclose($h);
        }, 'ringkasan-kualifikasi-dosen.csv', ['Content-Type' => 'text/csv']);
    }
}
