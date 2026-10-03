<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_branches', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
        });

        Schema::table('athletes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
        });

        // Backfill existing records with the first admin user
        $adminId = DB::table('users')->where('role', 'admin')->value('id') ?? DB::table('users')->min('id');
        if ($adminId) {
            DB::table('sport_branches')->whereNull('user_id')->update(['user_id' => $adminId]);
            DB::table('athletes')->whereNull('user_id')->update(['user_id' => $adminId]);
        }

        Schema::table('sport_branches', function (Blueprint $table) {
            $table->dropUnique('sport_branches_name_unique');
            $table->unique(['name', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('sport_branches', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
