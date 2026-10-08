<?php

namespace App\Filament\Imports;

use App\Enums\Peran;
use App\Models\Prodi;
use App\Models\User;
use App\Notifications\AkunSwalayanDibuat;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/** Impor massal pengguna sistem oleh super-admin. Peran super-admin dan klien-api tidak dapat dibuat lewat impor. */
class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    /** @return list<string> */
    public static function peranDiizinkan(): array
    {
        return [Peran::AdminKepegawaian->value, Peran::AdminProdi->value, Peran::Pimpinan->value, Peran::Dosen->value, Peran::Tendik->value];
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')->label('Nama')->requiredMapping()->example('Budi Santoso')
                ->rules(['required', 'max:150']),
            ImportColumn::make('email')->label('Surel')->requiredMapping()->example('budi@unsil.ac.id')
                ->rules(fn (?User $record) => [
                    'required', 'email', 'max:150',
                    Rule::unique('users', 'email')->ignore($record?->getKey()),
                    fn (): string => app()->environment('local') ? 'nullable' : 'ends_with:@unsil.ac.id',
                ]),
            ImportColumn::make('peran')->label('Peran')->requiredMapping()->example('dosen')
                ->rules(['required', Rule::in(self::peranDiizinkan())]),
            ImportColumn::make('kode_prodi')->label('Kode prodi')->ignoreBlankState()->example('PMAT')
                ->rules(['nullable', Rule::exists('prodi', 'kode')]),
            ImportColumn::make('nip')->label('NIP')->ignoreBlankState()->rules(['nullable', 'max:18'])->example('198501012010012001'),
            ImportColumn::make('nidn')->label('NIDN')->ignoreBlankState()->rules(['nullable', 'max:10'])->example('0401018501'),
            ImportColumn::make('no_hp')->label('No. HP')->ignoreBlankState()->rules(['nullable', 'max:20'])->example('081234567890'),
        ];
    }

    /** @return array<string, string> */
    public function getValidationMessages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Surel wajib diisi.',
            'email.email' => 'Surel tidak valid.',
            'email.unique' => 'Surel sudah dipakai pengguna lain.',
            'email.ends_with' => 'Surel harus berakhiran @unsil.ac.id.',
            'peran.required' => 'Peran wajib diisi.',
            'peran.in' => 'Peran tidak dikenal atau tidak dapat dibuat lewat impor (super-admin dan klien-api tidak diizinkan).',
            'kode_prodi.exists' => 'Kode prodi tidak dikenal.',
            'nip.max' => 'NIP maksimal 18 karakter.',
            'nidn.max' => 'NIDN maksimal 10 karakter.',
        ];
    }

    public function resolveRecord(): User
    {
        return User::firstWhere('email', $this->data['email']) ?? new User;
    }

    /** BR: admin-prodi wajib memiliki prodi. */
    protected function beforeValidate(): void
    {
        if (($this->data['peran'] ?? null) === Peran::AdminProdi->value && blank($this->data['kode_prodi'] ?? null)) {
            throw new RowImportFailedException('Peran Admin Prodi wajib memiliki kode prodi.');
        }
    }

    public function fillRecord(): void
    {
        /** @var User $record */
        $record = $this->record;
        $record->name = $this->data['name'];
        $record->email = $this->data['email'];
        $record->nip = $this->data['nip'] ?? null;
        $record->nidn = $this->data['nidn'] ?? null;
        $record->no_hp = $this->data['no_hp'] ?? null;
        $record->is_aktif = true;

        if (filled($this->data['kode_prodi'] ?? null)) {
            $record->prodi_id = Prodi::where('kode', $this->data['kode_prodi'])->value('id');
        }
    }

    public function saveRecord(): void
    {
        /** @var User $record */
        $record = $this->record;
        $baru = ! $record->exists;

        if ($baru) {
            $record->password = Str::password(32);
        }

        try {
            DB::transaction(fn () => $record->save());
        } catch (Throwable $e) {
            throw new RowImportFailedException('Baris gagal disimpan: '.$e->getMessage());
        }

        $record->syncRoles([$this->data['peran']]);

        if ($baru) {
            $token = Password::broker()->createToken($record);
            $record->notify(new AkunSwalayanDibuat(Filament::getPanel('admin')->getResetPasswordUrl($token, $record)));
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $berhasil = number_format($import->successful_rows);
        $gagal = $import->getFailedRowsCount();

        $isi = "Impor pengguna selesai: {$berhasil} baris berhasil";

        return $gagal > 0
            ? $isi.', '.number_format($gagal).' baris gagal. Unduh daftar baris gagal untuk diperbaiki.'
            : $isi.'.';
    }

    public function getJobQueue(): ?string
    {
        return 'impor';
    }
}
