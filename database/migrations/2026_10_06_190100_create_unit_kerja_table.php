<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_kerja', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 20)->unique();
            $table->string('nama', 150);
            $table->string('jenis', 30)->index();
            $table->foreignUuid('induk_id')->nullable()->constrained('unit_kerja')->nullOnDelete();
            $table->foreignUuid('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->string('kode_eksternal', 50)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_kerja');
    }
};
