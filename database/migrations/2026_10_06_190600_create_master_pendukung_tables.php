<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenjang_pendidikan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 10)->unique();
            $table->string('nama', 60);
            $table->smallInteger('urutan');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('jenis_sertifikasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 40)->unique();
            $table->string('nama', 120);
            $table->boolean('is_serdos')->default(false);
            $table->boolean('punya_masa_berlaku')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('jenis_dokumen', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 40)->unique();
            $table->string('nama', 120);
            $table->boolean('punya_masa_berlaku')->default(false);
            $table->boolean('is_identitas')->default(false);
            $table->string('wajib_untuk', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('semester', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 5)->unique();
            $table->string('tahun_akademik', 9);
            $table->string('jenis', 10);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_aktif')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester');
        Schema::dropIfExists('jenis_dokumen');
        Schema::dropIfExists('jenis_sertifikasi');
        Schema::dropIfExists('jenjang_pendidikan');
    }
};
