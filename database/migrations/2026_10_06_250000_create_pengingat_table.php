<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengingat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai');
            $table->string('jenis', 30)->index();
            $table->string('referensi_tabel', 50)->nullable();
            $table->uuid('referensi_id')->nullable();
            $table->date('tanggal_jatuh_tempo')->index();
            $table->string('status', 20)->default('aktif')->index();
            $table->json('tahap_terkirim')->nullable();
            $table->dateTime('terakhir_dikirim_at')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['pegawai_id', 'jenis', 'referensi_tabel', 'referensi_id', 'tanggal_jatuh_tempo'], 'pengingat_unik');
            $table->index(['status', 'tanggal_jatuh_tempo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengingat');
    }
};
