<?php

namespace App\Providers;

use App\Enums\Peran;
use App\Models\Aktivitas;
use App\Models\BarisImporGagal;
use App\Models\Ekspor;
use App\Models\Golongan;
use App\Models\Impor;
use App\Models\JabatanFungsional;
use App\Models\Konfigurasi;
use App\Models\Prodi;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
