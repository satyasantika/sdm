<?php

namespace App\Providers;

use App\Enums\Peran;
use App\Models\TokenAkses;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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
        //
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

        Gate::before(fn ($user) => $user->hasRole(Peran::SuperAdmin->value) ? true : null);

        Sanctum::usePersonalAccessTokenModel(TokenAkses::class);

        DB::prohibitDestructiveCommands(app()->isProduction());
    }
}
