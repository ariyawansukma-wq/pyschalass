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
        $users = [
            [
                'username' => 'admin',
                'name'     => 'Administrator',
                'email'    => 'admin@indexfitlab.com',
                'role'     => UserRole::Admin->value,
            ],
            [
                'username' => 'officer',
                'name'     => 'Officer',
                'email'    => 'officer@indexfitlab.com',
                'role'     => UserRole::Officer->value,
            ],
            [
                'username' => 'kader1',
                'name'     => 'Kader 1',
                'email'    => 'kader1@indexfitlab.com',
                'role'     => UserRole::Kader->value,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['username' => $user['username']],
                array_merge($user, [
                    'password'   => Hash::make('password'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

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
