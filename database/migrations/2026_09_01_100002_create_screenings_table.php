<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel skrining tumbuh kembang anak (dari iskad-app).
     *
     * Diisi oleh kader posyandu/TK melalui modul Screening.
     * user_id nullable + nullOnDelete: data historis tetap ada jika user dihapus.
     */
    public function up(): void
    {
        Schema::create('screenings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('nama_anak');
            $table->unsignedSmallInteger('umur_bulan');

            // Skor per komponen (0–4 tiap item)
            $table->unsignedTinyInteger('skor_statis');
            $table->unsignedTinyInteger('skor_tandem');
            $table->unsignedTinyInteger('skor_lompat');
            $table->unsignedTinyInteger('skor_sit_to_stand');
            $table->unsignedTinyInteger('skor_vestibular');

            $table->unsignedSmallInteger('total_skor');
            $table->string('kategori', 50); // NORMAL, RISIKO RINGAN, RISIKO BERAT, dll.

            $table->json('chart_data')->nullable(); // Data radar/grafik untuk laporan

            $table->timestamps();

            // Index untuk query riwayat per kader
            $table->index(['user_id', 'created_at']);
            // Index untuk pencarian nama anak
            $table->index('nama_anak');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screenings');
    }
};
