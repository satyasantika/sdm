<?php

namespace App\Filament\Forms;

use App\Actions\Berkas\GantiTautanBerkas;
use App\Actions\Berkas\SimpanTautanBerkas;
use App\Enums\JenisTautan;
use App\Models\Pegawai;
use App\Models\TautanBerkas;
use App\Models\User;
use App\Rules\TautanBerkasValid;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Komponen form tautan berkas (tanpa unggah). Nilai form: "tautan_{nama}" (URL) dan "tautan_{nama}_konfirmasi".
 * Action pemanggil memisahkan kunci ini lewat TautanBerkasField::pisahkan() lalu menyimpannya.
 */
class TautanBerkasField
{
    public static function kunciUrl(string $nama): string
    {
        return 'tautan_'.$nama;
    }

    public static function kunciKonfirmasi(string $nama): string
    {
        return 'tautan_'.$nama.'_konfirmasi';
    }

    public static function make(string $nama, JenisTautan|string $jenis = JenisTautan::Lainnya, ?string $label = null): Group
    {
        $jenis = $jenis instanceof JenisTautan ? $jenis : JenisTautan::from($jenis);
        $kunci = self::kunciUrl($nama);
        $label ??= 'Tautan '.$jenis->getLabel();

        $komponen = [
            TextInput::make($kunci)->label($label)->url()->maxLength(2048)
                ->placeholder('https://drive.google.com/file/d/…/view')
                ->rule(new TautanBerkasValid)
                ->live(onBlur: true)
                ->afterStateHydrated(function (TextInput $component, ?Model $record) use ($jenis): void {
                    if ($record && method_exists($record, 'tautan')) {
                        $component->state($record->tautan($jenis)?->url);
                    }
                })
                ->helperText($jenis->isSensitif()
                    ? 'Berkas sensitif: bagikan terbatas ke domain unsil.ac.id (bukan "Siapa saja yang memiliki link").'
                    : 'Salin tautan berkas dari Google Drive (akun @unsil.ac.id). Bukan unggah berkas.'),
        ];

        if ($jenis->isSensitif()) {
            $komponen[] = Checkbox::make(self::kunciKonfirmasi($nama))
                ->label('Berkas sudah saya bagikan terbatas (domain unsil.ac.id), bukan publik')
                ->accepted(fn (Get $get): bool => filled($get($kunci)))
                ->validationMessages(['accepted' => 'Konfirmasi berbagi terbatas wajib dicentang untuk berkas sensitif.'])
                ->dehydrated(true);
        }

        return Group::make($komponen);
    }

    /**
     * Memisahkan kunci tautan dari data form (agar tidak masuk ke model) dan mengembalikannya.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array{url: string, konfirmasi: bool}>
     */
    public static function pisahkan(array &$data): array
    {
        $hasil = [];

        foreach (array_keys($data) as $kunci) {
            if (! str_starts_with($kunci, 'tautan_') || str_ends_with($kunci, '_konfirmasi')) {
                continue;
            }

            $nama = substr($kunci, strlen('tautan_'));
            $hasil[$nama] = ['url' => (string) ($data[$kunci] ?? ''), 'konfirmasi' => (bool) ($data[self::kunciKonfirmasi($nama)] ?? false)];
            unset($data[$kunci], $data[self::kunciKonfirmasi($nama)]);
        }

        return $hasil;
    }

    /**
     * Menyimpan/mengganti tautan hasil pisahkan() pada record pemilik.
     *
     * @param  array{url?: string, konfirmasi?: bool}  $nilai
     */
    public static function simpan(Model $pemilik, string $nama, JenisTautan $jenis, array $nilai, ?Pegawai $pegawai, ?User $oleh): ?TautanBerkas
    {
        $url = trim($nilai['url'] ?? '');
        if ($url === '') {
            return null;
        }

        $ada = method_exists($pemilik, 'tautan') ? $pemilik->tautan($jenis) : null;
        if ($ada && $ada->url === $url) {
            return $ada;
        }

        return $ada
            ? app(GantiTautanBerkas::class)->handle($ada, $url, $oleh, (bool) ($nilai['konfirmasi'] ?? false))
            : app(SimpanTautanBerkas::class)->handle($pemilik, $jenis, $url, null, $pegawai, $oleh, (bool) ($nilai['konfirmasi'] ?? false));
    }

    public static function entri(string $nama, JenisTautan $jenis, ?string $label = null): ViewEntry
    {
        return ViewEntry::make('tautan_'.$nama)->label($label ?? $jenis->getLabel())
            ->view('filament.infolists.tautan-berkas')
            ->state(fn (Model $record): ?TautanBerkas => method_exists($record, 'tautan') ? $record->tautan($jenis) : null);
    }

    public static function kolom(string $nama, JenisTautan $jenis, ?string $label = null): TextColumn
    {
        return TextColumn::make('tautan_'.$nama)->label($label ?? 'Berkas')
            ->state(fn (Model $record): ?TautanBerkas => method_exists($record, 'tautan') ? $record->tautan($jenis) : null)
            ->formatStateUsing(fn (?TautanBerkas $state): HtmlString => new HtmlString(
                view('components.tautan-berkas', ['tautan' => $state])->render()
            ))
            ->html();
    }
}
