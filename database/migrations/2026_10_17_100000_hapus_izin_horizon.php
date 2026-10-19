<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Profil Ringan (STANDAR-TEKNIS §1b): Horizon dicabut, izin `horizon.*` tidak lagi dipakai.
 * Menghapus izin beserta keterkaitannya dengan peran/pengguna di data yang sudah ada. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tabel = config('permission.table_names');

        if (! Schema::hasTable($tabel['permissions'])) {
            return;
        }

        $id = DB::table($tabel['permissions'])->where('name', 'like', 'horizon.%')->pluck('id')->all();

        if ($id !== []) {
            DB::table($tabel['role_has_permissions'])->whereIn('permission_id', $id)->delete();
            DB::table($tabel['model_has_permissions'])->whereIn('permission_id', $id)->delete();
            DB::table($tabel['permissions'])->whereIn('id', $id)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Izin horizon.* sengaja tidak dipulihkan: Horizon sudah dicabut.
    }
};
