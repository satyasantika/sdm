<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bkd', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->constrained('pegawai');
            $table->foreignUuid('semester_id')->index()->constrained('semester');
            $table->decimal('sks_pendidikan', 5, 2)->nullable();
            $table->decimal('sks_penelitian', 5, 2)->nullable();
            $table->decimal('sks_pengabdian', 5, 2)->nullable();
            $table->decimal('sks_penunjang', 5, 2)->nullable();
            $table->decimal('total_sks', 5, 2)->nullable();
            $table->string('kewajiban_khusus', 30)->nullable();
            $table->string('kesimpulan', 20)->default('belum_dinilai')->index();
            $table->string('sumber', 20)->default('sister_impor');
            $table->foreignUuid('import_id')->nullable()->constrained('imports')->nullOnDelete();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['pegawai_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkd');
    }
};
