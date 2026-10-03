<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->nullable()->constrained('test_sessions')->nullOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('indicator_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('trial_number')->default(1);
            $table->decimal('value', 10, 2)->nullable();
            $table->boolean('is_valid')->default(true);
            $table->timestamps();

            $table->unique(['session_id', 'athlete_id', 'indicator_id', 'trial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trials');
    }
};
