<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 18)->nullable()->index()->after('password');
            $table->string('nidn', 10)->nullable()->index()->after('nip');
            $table->string('no_hp', 20)->nullable()->after('nidn');
            $table->boolean('is_aktif')->default(true)->after('no_hp');
            $table->timestamp('last_login_at')->nullable()->after('is_aktif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['nip']);
            $table->dropIndex(['nidn']);
            $table->dropColumn(['nip', 'nidn', 'no_hp', 'is_aktif', 'last_login_at']);
        });
    }
};
