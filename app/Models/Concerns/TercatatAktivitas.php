<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Pencatatan audit standar untuk model domain. Kolom di $kolomSensitif tidak
 * pernah disimpan nilainya; hanya namanya yang dicatat di properties.kolom_sensitif_diubah.
 *
 * @mixin Model
 */
trait TercatatAktivitas
{
    use LogsActivity;

    /**
     * Kolom yang nilainya tidak boleh masuk log; model dapat menimpa method ini.
     *
     * @return list<string>
     */
    public static function kolomSensitif(): array
    {
        return ['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept(static::kolomSensitif());
    }

    protected static function bootTercatatAktivitas(): void
    {
        static::updated(function (Model $model): void {
            $diubah = array_values(array_intersect(array_keys($model->getChanges()), static::kolomSensitif()));

            if ($diubah === []) {
                return;
            }

            activity()
                ->performedOn($model)
                ->event('updated')
                ->withProperties(['kolom_sensitif_diubah' => $diubah])
                ->log('kolom sensitif diubah');
        });
    }
}
