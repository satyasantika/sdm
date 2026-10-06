<?php

namespace App\Filament\Imports;

use App\Enums\JenisPegawai;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\StatusKepegawaian;
use App\Models\UnitKerja;
use App\Rules\NikBelumTerdaftar;
use App\Support\HashIdentitas;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class PegawaiImporter extends Importer
{
    protected static ?string $model = Pegawai::class;

    public static function getColumns(): array
    {
        $unik = fn (string $kolom) => fn (?Pegawai $record): array => [Rule::unique('pegawai', $kolom)->ignore($record?->getKey())];

        return [
            ImportColumn::make('jenis_pegawai')->label('Jenis pegawai')->requiredMapping()->example('dosen')
                ->rules(['required', Rule::in(['dosen', 'tendik'])]),
            ImportColumn::make('gelar_depan')->ignoreBlankState()->rules(['nullable', 'max:50'])->example('Dr.'),
            ImportColumn::make('nama')->requiredMapping()->rules(['required', 'max:150'])->example('Siti Aminah'),
            ImportColumn::make('gelar_belakang')->ignoreBlankState()->rules(['nullable', 'max:80'])->example('M.Pd.'),
            ImportColumn::make('nip')->ignoreBlankState()->example('198501012010012001')
                ->rules(fn (?Pegawai $record) => ['nullable', 'regex:/^\d{18}$/', ...$unik('nip')($record)]),
            ImportColumn::make('nidn')->ignoreBlankState()->example('0401018501')
                ->rules(fn (?Pegawai $record) => ['nullable', 'regex:/^\d{10}$/', ...$unik('nidn')($record)]),
            ImportColumn::make('nuptk')->ignoreBlankState()->example('1234567890123456')
                ->rules(fn (?Pegawai $record) => ['nullable', 'regex:/^\d{16}$/', ...$unik('nuptk')($record)]),
            ImportColumn::make('nik')->ignoreBlankState()->example('3200000000000000')
                ->rules(fn (?Pegawai $record) => ['nullable', 'regex:/^\d{16}$/', new NikBelumTerdaftar($record?->getKey())]),
            ImportColumn::make('tempat_lahir')->ignoreBlankState()->rules(['nullable', 'max:80'])->example('Tasikmalaya'),
            ImportColumn::make('tanggal_lahir')->ignoreBlankState()->example('1985-01-01')
                ->castStateUsing(fn (?string $state): ?string => self::tanggal($state))
                ->rules(['nullable', 'date_format:Y-m-d']),
            ImportColumn::make('jenis_kelamin')->ignoreBlankState()->rules(['nullable', Rule::in(['L', 'P'])])->example('P'),
            ImportColumn::make('kode_status_kepegawaian')->label('Kode status kepegawaian')->requiredMapping()->example('pns')
                ->rules(['required', Rule::exists('status_kepegawaian', 'kode')])
                ->fillRecordUsing(fn (Pegawai $record, ?string $state) => $record->status_kepegawaian_id = StatusKepegawaian::where('kode', $state)->value('id')),
            ImportColumn::make('kode_prodi')->label('Kode prodi')->ignoreBlankState()->example('PMAT')
                ->rules(['nullable', Rule::exists('prodi', 'kode')])
                ->fillRecordUsing(fn (Pegawai $record, ?string $state) => $record->prodi_id = Prodi::where('kode', $state)->value('id')),
            ImportColumn::make('kode_unit_kerja')->label('Kode unit kerja')->ignoreBlankState()->example('SB-UMUM')
                ->rules(['nullable', Rule::exists('unit_kerja', 'kode')])
                ->fillRecordUsing(fn (Pegawai $record, ?string $state) => $record->unit_kerja_id = UnitKerja::where('kode', $state)->value('id')),
            ImportColumn::make('email_unsil')->ignoreBlankState()->example('siti@unsil.ac.id')
                ->rules(fn (?Pegawai $record) => ['nullable', 'email', ...$unik('email_unsil')($record)]),
            ImportColumn::make('no_hp')->ignoreBlankState()->rules(['nullable', 'max:20'])->example('081234567890'),
            ImportColumn::make('tmt_masuk')->ignoreBlankState()->example('2015-03-01')
                ->castStateUsing(fn (?string $state): ?string => self::tanggal($state))
                ->rules(['nullable', 'date_format:Y-m-d']),
        ];
    }

    /** @return array<string, string> */
    public function getValidationMessages(): array
    {
        return [
            'jenis_pegawai.in' => 'Jenis pegawai harus dosen atau tendik.',
            'nip.regex' => 'NIP harus 18 digit angka.',
            'nip.unique' => 'NIP sudah terdaftar.',
            'nidn.regex' => 'NIDN harus 10 digit angka.',
            'nidn.unique' => 'NIDN sudah terdaftar.',
            'nuptk.regex' => 'NUPTK harus 16 digit angka.',
            'nuptk.unique' => 'NUPTK sudah terdaftar.',
            'nik.regex' => 'NIK harus 16 digit angka.',
            'tanggal_lahir.date_format' => 'Tanggal harus berformat Y-m-d atau d/m/Y.',
            'tmt_masuk.date_format' => 'Tanggal harus berformat Y-m-d atau d/m/Y.',
            'kode_status_kepegawaian.exists' => 'Kode status kepegawaian tidak dikenal.',
            'kode_prodi.exists' => 'Kode prodi tidak dikenal.',
            'kode_unit_kerja.exists' => 'Kode unit kerja tidak dikenal.',
            'email_unsil.unique' => 'Surel Unsil sudah terdaftar.',
        ];
    }

    /**
     * Mencocokkan record yang ada: NIP bila terisi, jika tidak NIDN, jika tidak hash NIK.
     * Bila NIP terisi tetapi belum ada, baris dianggap pegawai baru (NIDN/NIK ganda akan ditolak validasi).
     */
    public function resolveRecord(): Pegawai
    {
        $data = $this->data;

        $ada = match (true) {
            filled($data['nip'] ?? null) => Pegawai::withTrashed()->where('nip', $data['nip'])->first(),
            filled($data['nidn'] ?? null) => Pegawai::withTrashed()->where('nidn', $data['nidn'])->first(),
            filled($data['nik'] ?? null) => Pegawai::withTrashed()->where('nik_hash', HashIdentitas::nik($data['nik']))->first(),
            default => null,
        };

        return $ada ?? new Pegawai;
    }

    /** BR-02: dosen wajib prodi, tendik wajib unit kerja. */
    protected function beforeSave(): void
    {
        /** @var Pegawai $record */
        $record = $this->record;
        $jenis = $record->jenis_pegawai;

        if ($jenis === JenisPegawai::Dosen && blank($record->prodi_id)) {
            throw new RowImportFailedException('Dosen wajib memiliki kode prodi.');
        }

        if ($jenis === JenisPegawai::Tendik && blank($record->unit_kerja_id)) {
            throw new RowImportFailedException('Tendik wajib memiliki kode unit kerja.');
        }
    }

    public function saveRecord(): void
    {
        try {
            DB::transaction(fn () => $this->record->save());
        } catch (Throwable $e) {
            throw new RowImportFailedException('Baris gagal disimpan: '.$e->getMessage());
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $berhasil = number_format($import->successful_rows);
        $gagal = $import->getFailedRowsCount();

        $isi = "Impor pegawai selesai: {$berhasil} baris berhasil";

        return $gagal > 0
            ? $isi.', '.number_format($gagal).' baris gagal. Unduh daftar baris gagal untuk diperbaiki.'
            : $isi.'.';
    }

    public function getJobQueue(): ?string
    {
        return 'impor';
    }

    private static function tanggal(?string $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            try {
                $tanggal = CarbonImmutable::createFromFormat('!'.$format, trim($state));
            } catch (InvalidFormatException) {
                continue;
            }

            if ($tanggal->format($format) === trim($state)) {
                return $tanggal->format('Y-m-d');
            }
        }

        return $state;
    }
}
