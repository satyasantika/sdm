<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usulan_perubahan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai');
            $table->foreignUuid('diajukan_oleh')->constrained('users');
            $table->string('jenis', 20);
            $table->string('target_tabel', 50);
            $table->uuid('target_id')->nullable();
            $table->text('data_lama')->nullable();
            $table->text('data_baru')->nullable();
            $table->timestamp('target_updated_at')->nullable();
            $table->text('snapshot_tautan')->nullable();
            $table->string('alasan', 500)->nullable();
            $table->string('status', 20)->default('draf')->index();
            $table->string('catatan_verifikator', 500)->nullable();
            $table->foreignUuid('diverifikasi_oleh')->nullable()->constrained('users');
            $table->dateTime('diajukan_at')->nullable();
            $table->dateTime('diverifikasi_at')->nullable();
            $table->dateTime('diterapkan_at')->nullable();
            $table->string('kunci_aktif', 120)->nullable()->unique();
            $table->timestamps();

            $table->index(['pegawai_id', 'target_tabel', 'target_id']);
        });

        Schema::create('riwayat_status_usulan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('usulan_perubahan_id')->index()->constrained('usulan_perubahan')->cascadeOnDelete();
            $table->string('dari_status', 20)->nullable();
            $table->string('ke_status', 20);
            $table->foreignUuid('oleh_user_id')->constrained('users');
            $table->string('catatan', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_usulan');
        Schema::dropIfExists('usulan_perubahan');
    }
};
