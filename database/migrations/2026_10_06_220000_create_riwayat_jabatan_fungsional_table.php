<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_jabatan_fungsional', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->constrained('pegawai');
            $table->foreignUuid('jabatan_fungsional_id')->constrained('jabatan_fungsional');
            $table->date('tmt');
            $table->string('nomor_sk', 100);
            $table->date('tanggal_sk')->nullable();
            $table->decimal('angka_kredit', 8, 2)->nullable();
            $table->boolean('is_terkini')->default(false)->index();
            $table->boolean('is_koreksi')->default(false);
            $table->string('sumber', 10)->default('admin');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pegawai_id', 'tmt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_jabatan_fungsional');
    }
};
