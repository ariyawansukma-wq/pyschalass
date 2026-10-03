<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@indexfitlab.com',
                'role' => UserRole::Admin->value,
                'password' => Hash::make('password'),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['username' => 'officer'],
            [
                'name' => 'Officer',
                'email' => 'officer@indexfitlab.com',
                'role' => UserRole::Officer->value,
                'password' => Hash::make('password'),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->call([
            SportBranchSeeder::class,
            IndicatorSeeder::class,
            AthleteSeeder::class,
            BenchmarkSeeder::class,
            InstitutionSeeder::class,
            SessionSeeder::class,
            ComparisonTestSeeder::class,
        ]);
    }
}
