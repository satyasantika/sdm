<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tautan_berkas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('pemilik_type', 100);
            $table->uuid('pemilik_id');
            $table->foreignUuid('pegawai_id')->nullable()->index()->constrained('pegawai')->nullOnDelete();
            $table->string('jenis', 30)->index();
            $table->string('label', 150)->nullable();
            $table->string('url', 2048);
            $table->string('penyedia', 20);
            $table->string('drive_file_id', 100)->nullable()->index();
            $table->boolean('is_sensitif')->default(false);
            $table->string('status_cek', 25)->default('belum')->index();
            $table->dateTime('dicek_pada')->nullable();
            $table->unsignedSmallInteger('kode_http_terakhir')->nullable();
            $table->foreignUuid('ditambahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pemilik_type', 'pemilik_id']);
            $table->index(['pegawai_id', 'is_sensitif', 'status_cek']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tautan_berkas');
    }
};
