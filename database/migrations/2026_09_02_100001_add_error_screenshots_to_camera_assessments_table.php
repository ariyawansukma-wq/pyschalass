<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom error_screenshots ke camera_assessments.
     *
     * Menyimpan array screenshot otomatis saat atlet melakukan kesalahan postur
     * selama assessment. Format: JSON array of {timestamp, dataUrl (base64 JPEG), reason}.
     * nullable — tidak semua tes menghasilkan screenshot kesalahan.
     */
    public function up(): void
    {
        Schema::table('camera_assessments', function (Blueprint $table) {
            $table->json('error_screenshots')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('camera_assessments', function (Blueprint $table) {
            $table->dropColumn('error_screenshots');
        });
    }
};
