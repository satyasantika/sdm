<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_pangkat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->constrained('pegawai');
            $table->foreignUuid('golongan_id')->constrained('golongan');
            $table->date('tmt');
            $table->string('nomor_sk', 100);
            $table->date('tanggal_sk')->nullable();
            $table->string('jenis_kenaikan', 30);
            $table->unsignedTinyInteger('masa_kerja_tahun')->nullable();
            $table->unsignedTinyInteger('masa_kerja_bulan')->nullable();
            $table->boolean('is_terkini')->default(false);
            $table->string('sumber', 10)->default('admin');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pegawai_id', 'tmt']);
        });

        Schema::create('riwayat_kgb', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->constrained('pegawai');
            $table->foreignUuid('golongan_id')->nullable()->constrained('golongan');
            $table->date('tmt');
            $table->string('nomor_sk', 100);
            $table->date('tanggal_sk')->nullable();
            $table->decimal('gaji_pokok', 15, 2)->nullable();
            $table->unsignedTinyInteger('masa_kerja_tahun')->nullable();
            $table->unsignedTinyInteger('masa_kerja_bulan')->nullable();
            $table->boolean('is_terkini')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pegawai_id', 'tmt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kgb');
        Schema::dropIfExists('riwayat_pangkat');
    }
};
