<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jabatan_fungsional', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 40)->unique();
            $table->string('nama', 100);
            $table->string('kelompok', 10)->index();
            $table->string('rumpun', 100)->nullable();
            $table->smallInteger('urutan');
            $table->decimal('angka_kredit_minimal', 8, 2)->nullable();
            $table->unsignedSmallInteger('masa_kerja_minimal_bulan')->nullable();
            $table->foreignUuid('golongan_minimal_id')->nullable()->constrained('golongan')->nullOnDelete();
            $table->boolean('is_puncak')->default(false);
            $table->string('dasar_hukum', 255)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kelompok', 'rumpun', 'urutan']);
        });

        Schema::create('jenis_jabatan_struktural', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 40)->unique();
            $table->string('nama', 120);
            $table->string('kategori', 20);
            $table->smallInteger('urutan')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_jabatan_struktural');
        Schema::dropIfExists('jabatan_fungsional');
    }
};
