<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->string('athlete_number', 50)->nullable()->unique();
            $table->string('name', 255);
            $table->enum('gender', ['M', 'F']);
            $table->date('date_of_birth');
            $table->string('event_number', 100)->nullable();
            $table->foreignId('sport_branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athletes');
    }
};
