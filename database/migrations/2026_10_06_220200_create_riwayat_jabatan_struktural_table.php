<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_jabatan_struktural', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai');
            $table->foreignUuid('jenis_jabatan_struktural_id')->constrained('jenis_jabatan_struktural');
            $table->foreignUuid('unit_kerja_id')->nullable()->constrained('unit_kerja');
            $table->date('tmt_mulai');
            $table->date('tmt_selesai')->nullable()->index();
            $table->string('nomor_sk', 100);
            $table->date('tanggal_sk')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_jabatan_struktural');
    }
};
