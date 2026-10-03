<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('camera_assessments', function (Blueprint $table) {
            $table->id();

            // Trainer/officer yang melakukan assessment
            // nullable + nullOnDelete: data historis tetap ada jika user dihapus
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Atlet yang dinilai
            // nullable + nullOnDelete: data historis tetap ada jika atlet dihapus
            $table->foreignId('athlete_id')
                  ->nullable()
                  ->constrained('athletes')
                  ->nullOnDelete();

            // Jenis tes: 'Keseimbangan Statis', 'Push Up', 'Sit Up', dll.
            $table->string('test_type', 100);

            // Kategori: 'Balance', 'Strength', 'Endurance', dll.
            $table->string('category', 100)->nullable();

            // Nilai numerik hasil assessment (decimal 10,4 untuk presisi semua jenis tes:
            // repetisi (int), durasi detik (float 1 desimal), cm (float 1 desimal))
            $table->decimal('result_value', 10, 4)->nullable();

            // Representasi teks hasil: "12 rep", "8.3 detik", "+5.0 cm"
            $table->string('result_display', 100)->nullable();

            // Satuan: 'rep', 'detik', 'cm'
            $table->string('unit', 50)->nullable();

            // Durasi sesi assessment dalam detik
            $table->unsignedInteger('duration_sec')->nullable();

            // Apakah hasil merupakan estimasi (mis. Sit and Reach cm belum dikalibrasi)
            $table->boolean('is_estimated')->default(false);

            // Snapshot benchmark pada saat assessment dilakukan
            // Disimpan sebagai JSON agar tidak terpengaruh perubahan benchmark di masa depan
            // Contoh: {"value": 30, "unit": "detik"}
            $table->json('benchmark_snapshot')->nullable();

            // Persentase pencapaian vs benchmark snapshot (misal: 83.33, 100.00)
            // decimal(8,2) cukup untuk 0.00 – 100.00 dengan 2 desimal
            $table->decimal('achievement', 8, 2)->nullable();

            // Waktu assessment dilakukan (berbeda dari created_at — bisa diisi retroaktif)
            $table->dateTime('performed_at');

            // Catatan tambahan dari trainer
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index untuk query history per atlet (paling sering digunakan)
            $table->index(['athlete_id', 'performed_at']);

            // Index untuk query history per trainer
            $table->index(['user_id', 'performed_at']);

            // Index untuk filter per jenis tes
            $table->index('test_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camera_assessments');
    }
};
