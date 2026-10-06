<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jumlah_mahasiswa_prodi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prodi_id')->constrained('prodi');
            $table->foreignUuid('semester_id')->constrained('semester');
            $table->unsignedInteger('jumlah_mahasiswa_aktif');
            $table->string('sumber', 100)->nullable();
            $table->foreignUuid('diinput_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['prodi_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jumlah_mahasiswa_prodi');
    }
};
