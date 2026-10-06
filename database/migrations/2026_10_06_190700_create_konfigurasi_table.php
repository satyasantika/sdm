<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfigurasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kunci', 60)->unique();
            $table->text('nilai');
            $table->string('tipe', 10)->default('string');
            $table->string('keterangan', 255)->nullable();
            $table->foreignUuid('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi');
    }
};
