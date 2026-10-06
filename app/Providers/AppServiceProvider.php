<?php

namespace App\Providers;

use App\Actions\Pengingat\SelesaikanPengingat;
use App\Berkas\TautanEksternal;
use App\Contracts\PenyimpananBerkas;
use App\Enums\JenisPengingat;
use App\Enums\Peran;
use App\Events\KonfigurasiKepegawaianDiubah;
use App\Models\Aktivitas;
use App\Models\BarisImporGagal;
use App\Models\DokumenPegawai;
use App\Models\Ekspor;
use App\Models\Golongan;
use App\Models\Impor;
use App\Models\JabatanFungsional;
use App\Models\Konfigurasi;
use App\Models\Pegawai;
use App\Models\Pelatihan;
use App\Models\Penghargaan;
use App\Models\Pengingat;
use App\Models\Prodi;
use App\Models\RiwayatJabatanFungsional;
use App\Models\RiwayatJabatanStruktural;
use App\Models\RiwayatKgb;
use App\Models\RiwayatPangkat;
use App\Models\RiwayatPendidikan;
use App\Models\Sertifikasi;
use App\Models\StatusKepegawaian;
use App\Models\StudiLanjut;
use App\Models\TokenAkses;
use App\Models\UsulanPerubahan;
use App\Observers\KonfigurasiObserver;
use App\Observers\MasterCacheObserver;
use App\Policies\AktivitasPolicy;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /** Alias morph berbahasa Indonesia (tidak ketat: model lain tetap memakai nama kelas). */
    private const PETA_MORPH = [
        'pegawai' => Pegawai::class,
        'riwayat_jabatan_fungsional' => RiwayatJabatanFungsional::class,
        'riwayat_pangkat' => RiwayatPangkat::class,
        'riwayat_kgb' => RiwayatKgb::class,
        'riwayat_jabatan_struktural' => RiwayatJabatanStruktural::class,
        'riwayat_pendidikan' => RiwayatPendidikan::class,
        'sertifikasi' => Sertifikasi::class,
        'penghargaan' => Penghargaan::class,
        'studi_lanjut' => StudiLanjut::class,
        'usulan_perubahan' => UsulanPerubahan::class,
        'dokumen_pegawai' => DokumenPegawai::class,
        'pengingat' => Pengingat::class,
        'pelatihan' => Pelatihan::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PenyimpananBerkas::class, fn () => match (config('berkas.mode')) {
            default => new TautanEksternal,
        });

        // Model impor/ekspor Filament memakai UUIDv7 (STANDAR-TEKNIS §4a butir 5).
        $this->app->bind(Import::class, Impor::class);
        $this->app->bind(FailedImportRow::class, BarisImporGagal::class);
        $this->app->bind(Export::class, Ekspor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
        Date::use(CarbonImmutable::class);

        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        Relation::morphMap(self::PETA_MORPH);

        RateLimiter::for('buka-tautan', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->getKey() ?: $request->ip()));

        Konfigurasi::observe(KonfigurasiObserver::class);
        foreach ([
            'prodi' => Prodi::class,
            'status-kepegawaian' => StatusKepegawaian::class,
            'golongan' => Golongan::class,
            'jabatan-fungsional' => JabatanFungsional::class,
        ] as $nama => $model) {
            $lupakan = fn () => MasterCacheObserver::lupakan($nama);
            $model::saved($lupakan);
            $model::deleted($lupakan);
            $model::restored($lupakan);
        }

        // BR-28: perubahan master syarat jabatan/status memicu hitung ulang pengingat dan pensiun.
        JabatanFungsional::saved(function (JabatanFungsional $jabatan): void {
            if ($jabatan->wasChanged(['masa_kerja_minimal_bulan', 'is_puncak', 'urutan', 'is_aktif'])) {
                KonfigurasiKepegawaianDiubah::dispatch(['jabatan_fungsional']);
            }
        });
        StatusKepegawaian::saved(function (StatusKepegawaian $status): void {
            if ($status->wasChanged(['berlaku_kenaikan_pangkat', 'berlaku_kgb', 'dihitung_dosen_tetap'])) {
                KonfigurasiKepegawaianDiubah::dispatch(['status_kepegawaian']);
            }
        });

        // Riwayat baru menutup pengingat lama untuk jenis terkait.
        RiwayatPangkat::created(fn (RiwayatPangkat $r) => app(SelesaikanPengingat::class)->untukPegawai($r->pegawai_id, [JenisPengingat::KenaikanPangkat, JenisPengingat::Kgb]));
        RiwayatKgb::created(fn (RiwayatKgb $r) => app(SelesaikanPengingat::class)->untukPegawai($r->pegawai_id, [JenisPengingat::Kgb]));
        RiwayatJabatanFungsional::created(fn (RiwayatJabatanFungsional $r) => app(SelesaikanPengingat::class)->untukPegawai($r->pegawai_id, [JenisPengingat::KenaikanJabfung]));

        Gate::policy(Aktivitas::class, AktivitasPolicy::class);
        Gate::before(fn ($user) => $user->hasRole(Peran::SuperAdmin->value) ? true : null);

        Sanctum::usePersonalAccessTokenModel(TokenAkses::class);

        DB::prohibitDestructiveCommands(app()->isProduction());
    }
}
