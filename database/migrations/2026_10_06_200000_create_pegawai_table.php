<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('jenis_pegawai', 10)->index();
            $table->string('gelar_depan', 50)->nullable();
            $table->string('nama', 150)->index();
            $table->string('gelar_belakang', 80)->nullable();
            $table->string('nip', 18)->nullable()->unique();
            $table->string('nidn', 10)->nullable()->unique();
            $table->string('nidk', 10)->nullable()->unique();
            $table->string('nuptk', 16)->nullable()->unique();
            $table->text('nik')->nullable();
            $table->char('nik_hash', 64)->nullable()->unique();
            $table->text('npwp')->nullable();
            $table->string('nama_bank', 60)->nullable();
            $table->text('nomor_rekening')->nullable();
            $table->string('tempat_lahir', 80)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->char('jenis_kelamin', 1)->nullable();
            $table->string('agama', 20)->nullable();
            $table->string('status_perkawinan', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('email_pribadi', 150)->nullable();
            $table->string('email_unsil', 150)->nullable()->unique();
            $table->foreignUuid('status_kepegawaian_id')->constrained('status_kepegawaian');
            $table->foreignUuid('prodi_id')->nullable()->index()->constrained('prodi');
            $table->foreignUuid('unit_kerja_id')->nullable()->constrained('unit_kerja');
            $table->foreignUuid('jabatan_fungsional_id')->nullable()->index()->constrained('jabatan_fungsional');
            $table->foreignUuid('golongan_id')->nullable()->constrained('golongan');
            $table->date('tmt_cpns')->nullable();
            $table->date('tmt_pns')->nullable();
            $table->date('tmt_masuk')->nullable();
            $table->string('status_aktif', 30)->default('aktif')->index();
            $table->date('tanggal_pensiun')->nullable()->index();
            $table->boolean('sesuai_kompetensi_inti_ps')->default(true);
            $table->string('kode_eksternal', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['jenis_pegawai', 'prodi_id', 'status_aktif']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('pegawai', fn (Blueprint $table) => $table->fullText('nama'));
        }

        Schema::create('riwayat_status_pegawai', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->index()->constrained('pegawai')->cascadeOnDelete();
            $table->string('dari_status', 30)->nullable();
            $table->string('ke_status', 30);
            $table->date('tmt');
            $table->string('nomor_sk', 100)->nullable();
            $table->string('catatan', 255)->nullable();
            $table->foreignUuid('oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_pegawai');
        Schema::dropIfExists('pegawai');
    }
};
