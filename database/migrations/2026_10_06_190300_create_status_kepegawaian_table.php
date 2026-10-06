<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_kepegawaian', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100);
            $table->string('kelompok', 10)->index();
            $table->string('jenis_golongan', 10)->nullable();
            $table->boolean('berlaku_kenaikan_pangkat')->default(false);
            $table->boolean('berlaku_kgb')->default(false);
            $table->boolean('dihitung_dosen_tetap')->default(true);
            $table->smallInteger('urutan')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_kepegawaian');
    }
};
