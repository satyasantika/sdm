<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Indeks tambahan untuk kueri daftar, dasbor, dan laporan (F10.2). Migrasi lama tidak diubah. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_pendidikan', fn (Blueprint $t) => $t->index(['pegawai_id', 'is_pendidikan_tertinggi'], 'riwayat_pendidikan_pegawai_tertinggi'));
        Schema::table('sertifikasi', fn (Blueprint $t) => $t->index(['pegawai_id', 'jenis_sertifikasi_id'], 'sertifikasi_pegawai_jenis'));
        Schema::table('bkd', fn (Blueprint $t) => $t->index(['semester_id', 'kesimpulan'], 'bkd_semester_kesimpulan'));
        Schema::table('dokumen_pegawai', fn (Blueprint $t) => $t->index(['pegawai_id', 'jenis_dokumen_id'], 'dokumen_pegawai_pegawai_jenis'));
        Schema::table('riwayat_jabatan_struktural', fn (Blueprint $t) => $t->index(['pegawai_id', 'tmt_selesai'], 'rjs_pegawai_selesai'));
        Schema::table('riwayat_pangkat', fn (Blueprint $t) => $t->index(['pegawai_id', 'is_terkini'], 'riwayat_pangkat_pegawai_terkini'));
    }

    public function down(): void
    {
        Schema::table('riwayat_pendidikan', fn (Blueprint $t) => $t->dropIndex('riwayat_pendidikan_pegawai_tertinggi'));
        Schema::table('sertifikasi', fn (Blueprint $t) => $t->dropIndex('sertifikasi_pegawai_jenis'));
        Schema::table('bkd', fn (Blueprint $t) => $t->dropIndex('bkd_semester_kesimpulan'));
        Schema::table('dokumen_pegawai', fn (Blueprint $t) => $t->dropIndex('dokumen_pegawai_pegawai_jenis'));
        Schema::table('riwayat_jabatan_struktural', fn (Blueprint $t) => $t->dropIndex('rjs_pegawai_selesai'));
        Schema::table('riwayat_pangkat', fn (Blueprint $t) => $t->dropIndex('riwayat_pangkat_pegawai_terkini'));
    }
};
