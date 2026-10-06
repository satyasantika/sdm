<?php

namespace App\Providers;

use App\Berkas\TautanEksternal;
use App\Contracts\PenyimpananBerkas;
use App\Enums\Peran;
use App\Models\Aktivitas;
use App\Models\BarisImporGagal;
use App\Models\Ekspor;
use App\Models\Golongan;
use App\Models\Impor;
use App\Models\JabatanFungsional;
use App\Models\Konfigurasi;
use App\Models\Pegawai;
use App\Models\Prodi;
use App\Models\RiwayatJabatanFungsional;
use App\Models\RiwayatJabatanStruktural;
use App\Models\RiwayatKgb;
use App\Models\RiwayatPangkat;
use App\Models\RiwayatPendidikan;
use App\Models\StatusKepegawaian;
use App\Models\TokenAkses;
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

        Gate::policy(Aktivitas::class, AktivitasPolicy::class);
        Gate::before(fn ($user) => $user->hasRole(Peran::SuperAdmin->value) ? true : null);

        Sanctum::usePersonalAccessTokenModel(TokenAkses::class);

        DB::prohibitDestructiveCommands(app()->isProduction());
    }
}
