<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel perpustakaan karya ilmiah (dari iskad-app).
     *
     * Dikelola oleh admin. Dapat diakses baca oleh semua role yang login.
     */
    public function up(): void
    {
        Schema::create('karya_ilmiahs', function (Blueprint $table) {
            $table->id();

            $table->string('judul');
            $table->string('jenis', 50); // Jurnal, Buku, HAKI, Modul, Lainnya
            $table->year('tahun');
            $table->text('deskripsi')->nullable();

            // File PDF yang diupload (path relatif ke storage/app/public/)
            $table->string('file_path')->nullable();

            // Link eksternal alternatif (Google Scholar, DOI, dll.)
            $table->string('link_eksternal')->nullable();

            $table->timestamps();

            $table->index('jenis');
            $table->index('tahun');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karya_ilmiahs');
    }
};
