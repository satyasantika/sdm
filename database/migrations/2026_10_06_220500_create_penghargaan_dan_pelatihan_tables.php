<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penghargaan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai');
            $table->string('kategori', 30)->default('penghargaan')->index();
            $table->string('nama', 200);
            $table->string('pemberi', 150)->nullable();
            $table->string('tingkat', 20);
            $table->date('tanggal')->nullable();
            $table->string('nomor_sk', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pelatihan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai');
            $table->string('nama', 200);
            $table->string('jenis', 30);
            $table->string('penyelenggara', 150)->nullable();
            $table->string('tingkat', 20)->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->unsignedSmallInteger('jumlah_jam')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelatihan');
        Schema::dropIfExists('penghargaan');
    }
};
