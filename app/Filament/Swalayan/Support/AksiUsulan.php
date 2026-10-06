<?php

namespace App\Filament\Swalayan\Support;

use App\Actions\Usulan\KirimUsulanSaya;
use App\Enums\JenisTautan;
use App\Enums\JenisUsulan;
use App\Filament\Forms\TautanBerkasField;
use App\Filament\Schemas\BiodataForm;
use App\Filament\Schemas\DokumenForm;
use App\Filament\Schemas\JabatanFungsionalForm;
use App\Filament\Schemas\KeluargaForm;
use App\Filament\Schemas\KgbForm;
use App\Filament\Schemas\PangkatForm;
use App\Filament\Schemas\PelatihanForm;
use App\Filament\Schemas\PendidikanForm;
use App\Filament\Schemas\PenghargaanForm;
use App\Filament\Schemas\SertifikasiForm;
use App\Filament\Schemas\StrukturalForm;
use App\Filament\Schemas\StudiLanjutForm;
use App\Models\Pegawai;
use App\Support\RegistriTargetUsulan;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** Aksi pengajuan perubahan data oleh pegawai (BR-04): selalu menjadi usulan, tidak pernah menulis langsung. */
class AksiUsulan
{
    /** @var array<string, class-string> */
    public const FORM = [
        'pegawai' => BiodataForm::class,
        'riwayat_jabatan_fungsional' => JabatanFungsionalForm::class,
        'riwayat_pangkat' => PangkatForm::class,
        'riwayat_kgb' => KgbForm::class,
        'riwayat_jabatan_struktural' => StrukturalForm::class,
        'riwayat_pendidikan' => PendidikanForm::class,
        'sertifikasi' => SertifikasiForm::class,
        'penghargaan' => PenghargaanForm::class,
        'pelatihan' => PelatihanForm::class,
        'keluarga' => KeluargaForm::class,
        'studi_lanjut' => StudiLanjutForm::class,
        'dokumen_pegawai' => DokumenForm::class,
    ];

    public static function pegawaiSaya(): Pegawai
    {
        return Pegawai::query()->with('statusKepegawaian')->where('user_id', auth()->id())->firstOrFail();
    }

    /** @return array<int, mixed> */
    public static function ekstra(): array
    {
        return [
            Textarea::make('alasan')->label('Alasan perubahan')->maxLength(500)->columnSpanFull(),
            TautanBerkasField::make('bukti', JenisTautan::BuktiUsulan, 'Tautan bukti pendukung')->columnSpanFull(),
            Toggle::make('simpan_draf')->label('Simpan sebagai draf (jangan kirim dulu)')->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    public static function skema(string $tabel): array
    {
        $kelas = self::FORM[$tabel];

        return [...$kelas::components(self::pegawaiSaya()), ...self::ekstra()];
    }

    public static function biodata(): Action
    {
        return Action::make('ajukan_biodata')->label('Ajukan perubahan biodata')->icon(Heroicon::OutlinedPencilSquare)
            ->modalHeading('Ajukan perubahan biodata')->modalWidth('3xl')
            ->fillForm(fn (): array => BiodataForm::nilaiAwal(self::pegawaiSaya()))
            ->schema(fn (): array => self::skema('pegawai'))
            ->action(fn (array $data) => self::kirim(JenisUsulan::UbahBiodata, 'pegawai', null, $data));
    }

    public static function tambah(string $tabel): Action
    {
        return Action::make('usulkan_tambah_'.$tabel)->label('Usulkan tambah: '.RegistriTargetUsulan::label($tabel))->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Usulkan tambah '.mb_strtolower(RegistriTargetUsulan::label($tabel)))->modalWidth('3xl')
            ->schema(fn (): array => self::skema($tabel))
            ->action(fn (array $data) => self::kirim(JenisUsulan::TambahRiwayat, $tabel, null, $data));
    }

    public static function ubah(string $tabel): Action
    {
        return Action::make('usulkan_ubah_'.$tabel)->label('Usulkan ubah')->icon(Heroicon::OutlinedPencil)->size('sm')
            ->modalHeading('Usulkan ubah '.mb_strtolower(RegistriTargetUsulan::label($tabel)))->modalWidth('3xl')
            ->fillForm(fn (Model $record): array => RegistriTargetUsulan::snapshot($tabel, $record))
            ->schema(fn (): array => self::skema($tabel))
            ->action(fn (array $data, Model $record) => self::kirim(JenisUsulan::UbahRiwayat, $tabel, (string) $record->getKey(), $data));
    }

    public static function hapus(string $tabel): Action
    {
        return Action::make('usulkan_hapus_'.$tabel)->label('Usulkan hapus')->icon(Heroicon::OutlinedTrash)->color('danger')->size('sm')
            ->requiresConfirmation()->modalHeading('Usulkan penghapusan data ini?')
            ->schema([
                Textarea::make('alasan')->label('Alasan penghapusan')->maxLength(500),
                Toggle::make('simpan_draf')->label('Simpan sebagai draf (jangan kirim dulu)'),
            ])
            ->action(fn (array $data, Model $record) => self::kirim(JenisUsulan::HapusRiwayat, $tabel, (string) $record->getKey(), $data));
    }

    /** @param  array<string, mixed>  $data */
    public static function kirim(JenisUsulan $jenis, string $tabel, ?string $targetId, array $data): void
    {
        try {
            $usulan = app(KirimUsulanSaya::class)->handle(auth()->user(), self::pegawaiSaya(), $jenis, $tabel, $targetId, $data);
        } catch (ValidationException $e) {
            Notification::make()->danger()->title('Usulan tidak dapat dikirim')
                ->body(collect($e->errors())->flatten()->implode("\n"))->send();

            return;
        } catch (AuthorizationException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title(
            $usulan->status->value === 'draf'
                ? 'Usulan disimpan sebagai draf.'
                : 'Usulan terkirim dan menunggu verifikasi admin kepegawaian.'
        )->send();
    }
}
