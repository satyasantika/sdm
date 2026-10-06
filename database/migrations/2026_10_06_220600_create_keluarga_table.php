<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keluarga', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai');
            $table->string('hubungan', 10);
            $table->string('nama', 150);
            $table->text('nik')->nullable();
            $table->string('tempat_lahir', 80)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->char('jenis_kelamin', 1)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->boolean('status_tunjangan')->default(false);
            $table->date('tanggal_nikah')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keluarga');
    }
};
