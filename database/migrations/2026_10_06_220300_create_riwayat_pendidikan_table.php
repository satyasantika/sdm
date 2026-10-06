<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_pendidikan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai');
            $table->foreignUuid('jenjang_pendidikan_id')->constrained('jenjang_pendidikan');
            $table->string('nama_pt', 150);
            $table->string('negara', 60)->default('Indonesia');
            $table->string('nama_prodi', 150)->nullable();
            $table->string('bidang_ilmu', 150)->nullable();
            $table->string('gelar', 30)->nullable();
            $table->unsignedSmallInteger('tahun_masuk')->nullable();
            $table->unsignedSmallInteger('tahun_lulus')->nullable();
            $table->string('nomor_ijazah', 100)->nullable();
            $table->decimal('ipk', 3, 2)->nullable();
            $table->string('judul_tugas_akhir', 500)->nullable();
            $table->boolean('is_pendidikan_tertinggi')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_pendidikan');
    }
};
