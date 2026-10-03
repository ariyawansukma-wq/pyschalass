<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('athlete_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('indicator_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->decimal('best_value', 10, 2);
            $table->date('achieved_at');
            $table->timestamps();

            $table->unique(['athlete_id', 'indicator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_records');
    }
};
