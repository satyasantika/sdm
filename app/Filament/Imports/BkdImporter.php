<?php

namespace App\Filament\Imports;

use App\Enums\KesimpulanBkd;
use App\Models\Bkd;
use App\Models\Pegawai;
use App\Models\Semester;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Select;

/** Impor rekap BKD dari ekspor Excel/CSV SISTER. Nama kolom SISTER perlu diverifikasi (lihat docs/PEMETAAN-KOLOM-BKD.md). */
class BkdImporter extends Importer
{
    protected static ?string $model = Bkd::class;

    public static function getColumns(): array
    {
        $angka = fn (string $nama, array $tebak) => ImportColumn::make($nama)->guess($tebak)->ignoreBlankState()
            ->castStateUsing(fn (?string $state): ?float => self::angka($state))
            ->rules(['nullable', 'numeric', 'min:0', 'max:999.99'])
            ->example('12,5');

        $identitas = fn (string $nama, array $tebak, string $contoh) => ImportColumn::make($nama)->guess($tebak)->example($contoh)
            ->fillRecordUsing(fn () => null);

        return [
            $identitas('nidn', ['nidn', 'NIDN'], '0401018501'),
            $identitas('nuptk', ['nuptk', 'NUPTK'], '1234567890123456'),
            $identitas('nip', ['nip', 'NIP'], '198501012010012001'),
            $identitas('nama', ['nama', 'Nama', 'nama dosen'], 'Siti Aminah'),
            $angka('sks_pendidikan', ['sks_pendidikan', 'pendidikan', 'sks pendidikan']),
            $angka('sks_penelitian', ['sks_penelitian', 'penelitian', 'sks penelitian']),
            $angka('sks_pengabdian', ['sks_pengabdian', 'pengabdian', 'sks pengabdian']),
            $angka('sks_penunjang', ['sks_penunjang', 'penunjang', 'sks penunjang']),
            $angka('total_sks', ['total_sks', 'total', 'jumlah sks']),
            ImportColumn::make('kesimpulan')->guess(['kesimpulan', 'Kesimpulan', 'status'])->example('Memenuhi')
                ->castStateUsing(fn (?string $state): string => KesimpulanBkd::dariTeks($state)->value),
            ImportColumn::make('kewajiban_khusus')->guess(['kewajiban_khusus', 'kewajiban khusus'])->ignoreBlankState()
                ->rules(['nullable', 'max:30']),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('semester_id')->label('Semester')->required()
                ->options(fn (): array => Semester::query()->orderByDesc('kode')->get()->mapWithKeys(fn (Semester $s): array => [$s->id => $s->label])->all())
                ->default(fn (): ?string => Semester::aktifSekarang()?->id),
        ];
    }

    /** BR-21: pencocokan NIDN → NUPTK → NIP; upsert per (pegawai, semester). */
    public function resolveRecord(): Bkd
    {
        $data = $this->data;
        $pegawai = null;

        foreach (['nidn', 'nuptk', 'nip'] as $kolom) {
            if (filled($data[$kolom] ?? null)) {
                $pegawai = Pegawai::query()->dosen()->where($kolom, trim((string) $data[$kolom]))->first();

                if ($pegawai) {
                    break;
                }
            }
        }

        if (! $pegawai) {
            throw new RowImportFailedException('NIDN/NUPTK/NIP tidak ditemukan.');
        }

        $bkd = Bkd::firstOrNew(['pegawai_id' => $pegawai->getKey(), 'semester_id' => $this->options['semester_id']]);
        $bkd->sumber = 'sister_impor';
        $bkd->import_id = $this->import->getKey();

        return $bkd;
    }

    protected function beforeSave(): void
    {
        /** @var Bkd $bkd */
        $bkd = $this->record;

        if ($bkd->total_sks === null) {
            $jumlah = collect(['sks_pendidikan', 'sks_penelitian', 'sks_pengabdian', 'sks_penunjang'])
                ->sum(fn (string $kolom): float => (float) $bkd->getAttribute($kolom));
            $bkd->total_sks = (string) $jumlah;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $berhasil = number_format($import->successful_rows);
        $gagal = $import->getFailedRowsCount();

        $isi = "Impor BKD selesai: {$berhasil} baris berhasil";

        return $gagal > 0 ? $isi.', '.number_format($gagal).' baris gagal. Unduh baris gagal untuk diperbaiki.' : $isi.'.';
    }

    public function getJobQueue(): ?string
    {
        return 'impor';
    }

    /** "12,5" → 12.5 (format Indonesia). */
    public static function angka(?string $state): ?float
    {
        if ($state === null || trim($state) === '') {
            return null;
        }

        $bersih = str_replace(['.', ','], ['', '.'], trim($state));

        // "12.5" (titik desimal tanpa koma) tetap dibaca 12.5.
        if (! str_contains($state, ',') && substr_count($state, '.') === 1) {
            $bersih = trim($state);
        }

        return is_numeric($bersih) ? (float) $bersih : null;
    }
}
