<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifikasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai');
            $table->foreignUuid('jenis_sertifikasi_id')->constrained('jenis_sertifikasi');
            $table->string('nama', 200);
            $table->string('nomor_sertifikat', 100)->nullable();
            $table->string('nomor_registrasi', 100)->nullable();
            $table->string('bidang', 150)->nullable();
            $table->string('penerbit', 150)->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->date('tanggal_terbit')->nullable();
            $table->date('tanggal_kedaluwarsa')->nullable()->index();
            $table->string('status_berlaku', 20)->default('berlaku');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikasi');
    }
};
