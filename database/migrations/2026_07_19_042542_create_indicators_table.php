<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 255);
            $table->string('unit', 50);
            $table->string('calculation_method', 50)->default('best');
            $table->string('category', 100)->nullable();
            $table->string('scoring_direction'); // stores ScoringDirection enum value
            $table->text('evaluation')->nullable();
            $table->integer('evaluation_threshold')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
