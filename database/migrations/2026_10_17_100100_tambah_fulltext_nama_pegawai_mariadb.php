<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi 2026_10_06_200000_create_pegawai_table hanya membuat indeks FULLTEXT bila driver === 'mysql', sehingga pada
 * driver 'mariadb' (Laravel 13) indeks itu terlewat. Migrasi lama sudah dirilis dan tidak diubah; di sini indeks
 * ditambahkan bila belum ada (idempotent: aman di basis data yang sudah memilikinya). Lihat FASE-B-RENCANA, temuan lintas sistem.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) || $this->sudahAda()) {
            return;
        }

        Schema::table('pegawai', fn (Blueprint $table) => $table->fullText('nama'));
    }

    public function down(): void
    {
        // Sengaja kosong: indeks ini bagian dari skema yang diharapkan migrasi pegawai pada MariaDB/MySQL.
    }

    private function sudahAda(): bool
    {
        foreach (Schema::getIndexes('pegawai') as $indeks) {
            if ($indeks['type'] === 'fulltext' && $indeks['columns'] === ['nama']) {
                return true;
            }
        }

        return false;
    }
};
