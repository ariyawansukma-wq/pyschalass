<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah field untuk role kader (dari iskad-app):
     *   instansi — nama posyandu / TK / lembaga kader
     *   no_hp    — nomor handphone kader
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('instansi', 255)->nullable()->after('role');
            $table->string('no_hp', 20)->nullable()->after('instansi');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['instansi', 'no_hp']);
        });
    }
};
