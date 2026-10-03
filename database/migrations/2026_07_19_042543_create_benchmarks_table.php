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
        Schema::create('benchmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('gender', ['M', 'F']);
            $table->string('label', 255);
            $table->unsignedTinyInteger('age_min');
            $table->unsignedTinyInteger('age_max');
            $table->json('values')->nullable();
            $table->timestamps();

            // Unique constraint: no duplicate benchmark for same sport+gender+age range
            $table->unique(['sport_branch_id', 'gender', 'age_min', 'age_max'], 'benchmarks_sb_gender_age_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benchmarks');
    }
};
