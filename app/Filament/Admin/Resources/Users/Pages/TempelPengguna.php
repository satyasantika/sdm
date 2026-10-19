<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Actions\Pengguna\PratinjauTempelPengguna;
use App\Actions\Pengguna\SimpanPenggunaImpor;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Impor massal pengguna dengan menempel (copy-paste) dari Excel, dengan pratinjau sebelum disimpan. */
class TempelPengguna extends Page
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.admin.pages.tempel-pengguna';

    protected static ?string $title = 'Impor pengguna dari Excel (tempel)';

    public string $teks = '';

    /** @var array{galat: ?string, baris: list<array<string, mixed>>, valid: int, tidak_valid: int}|null */
    public ?array $pratinjau = null;

    public static function canAccess(array $parameters = []): bool
    {
        return (bool) auth()->user()?->can('pengguna.kelola');
    }

    public function pratinjau(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->pratinjau = app(PratinjauTempelPengguna::class)->handle($this->teks);
    }

    public function ulangi(): void
    {
        $this->pratinjau = null;
    }

    /** Baris diperiksa ulang dari teks (bukan dari state pratinjau di peramban) sebelum disimpan. */
    public function impor(): void
    {
        abort_unless(static::canAccess(), 403);

        $hasil = app(PratinjauTempelPengguna::class)->handle($this->teks);
        $this->pratinjau = $hasil;

        if ($hasil['galat'] !== null || $hasil['valid'] === 0) {
            Notification::make()->title('Tidak ada baris valid untuk diimpor')->danger()->send();

            return;
        }

        // §1b: tanpa kunci cache. Tiap baris: transaksi + lockForUpdate pada akun yang sudah ada;
        // indeks UNIQUE users.email menjaga bila dua impor menambah email baru yang sama (baris kedua dihitung gagal).
        $berhasil = 0;
        $gagal = 0;

        foreach ($hasil['baris'] as $baris) {
            if (! $baris['valid']) {
                continue;
            }

            try {
                DB::transaction(function () use ($baris): void {
                    $pengguna = User::query()->where('email', $baris['data']['email'])->lockForUpdate()->first() ?? new User;

                    app(SimpanPenggunaImpor::class)->handle($pengguna, $baris['data']);
                });
                $berhasil++;
            } catch (Throwable $e) {
                report($e);
                $gagal++;
            }
        }

        activity()->causedBy(auth()->user())->event('impor-pengguna-tempel')
            ->withProperties(['berhasil' => $berhasil, 'gagal' => $gagal, 'dilewati' => $hasil['tidak_valid']])
            ->log('Impor pengguna dari data tempel');

        Notification::make()
            ->title("Impor selesai: {$berhasil} berhasil".($gagal > 0 ? ", {$gagal} gagal" : '').($hasil['tidak_valid'] > 0 ? ", {$hasil['tidak_valid']} dilewati (tidak valid)" : ''))
            ->success()->send();

        $this->teks = '';
        $this->pratinjau = null;
    }
}
