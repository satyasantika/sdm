<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golongan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jenis', 10)->index();
            $table->string('kode', 10);
            $table->string('pangkat', 60)->nullable();
            $table->smallInteger('urutan')->index();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jenis', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golongan');
    }
};
