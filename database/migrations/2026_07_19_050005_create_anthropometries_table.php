<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anthropometries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->nullable()->constrained('test_sessions')->nullOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('height', 5, 1)->nullable(); // cm
            $table->decimal('weight', 5, 1)->nullable(); // kg
            $table->decimal('bmi', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anthropometries');
    }
};
