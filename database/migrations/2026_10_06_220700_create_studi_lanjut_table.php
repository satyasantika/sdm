<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studi_lanjut', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai');
            $table->foreignUuid('jenjang_pendidikan_id')->constrained('jenjang_pendidikan');
            $table->string('jenis', 20);
            $table->string('nama_pt', 150);
            $table->string('negara', 60)->default('Indonesia');
            $table->string('nama_prodi', 150)->nullable();
            $table->string('sumber_biaya', 100)->nullable();
            $table->string('nomor_sk', 100)->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai_rencana')->nullable()->index();
            $table->date('tanggal_selesai_aktual')->nullable();
            $table->string('status', 20)->default('berjalan');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studi_lanjut');
    }
};
