<?php

namespace Database\Seeders;

use App\Models\Anthropometry;
use App\Models\Athlete;
use App\Models\Folder;
use App\Models\Indicator;
use App\Models\Session;
use App\Models\SportBranch;
use App\Models\Trial;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComparisonTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get or create Football branch
        $branch = SportBranch::firstOrCreate(
            ['name' => 'Football'],
            ['description' => 'Football / soccer including field players and goalkeepers']
        );

        // 2. Ensure indicators exist for Football
        $indicatorsData = [
            ['name' => '30m Sprint', 'unit' => 'seconds', 'category' => 'Speed', 'scoring_direction' => 'LOWER_IS_BETTER'],
            ['name' => 'Shuttle Run', 'unit' => 'seconds', 'category' => 'Agility', 'scoring_direction' => 'LOWER_IS_BETTER'],
            ['name' => 'Vertical Jump', 'unit' => 'cm', 'category' => 'Explosive Power', 'scoring_direction' => 'HIGHER_IS_BETTER'],
            ['name' => 'Push-up', 'unit' => 'reps', 'category' => 'Strength', 'scoring_direction' => 'HIGHER_IS_BETTER'],
        ];

        $indicators = [];
        foreach ($indicatorsData as $indData) {
            $indicators[$indData['name']] = Indicator::firstOrCreate(
                [
                    'sport_branch_id' => $branch->id,
                    'name' => $indData['name']
                ],
                $indData
            );
        }

        // 3. Ensure Male U-21 Benchmark for Football exists (usually seeded by BenchmarkSeeder)
        $benchmarkExists = DB::table('benchmarks')
            ->where('sport_branch_id', $branch->id)
            ->where('gender', 'M')
            ->where('age_min', 18)
            ->where('age_max', 21)
            ->exists();

        if (!$benchmarkExists) {
            $benchmarkValues = [];
            $benchmarkValues[$indicators['30m Sprint']->id] = 4.2;
            $benchmarkValues[$indicators['Shuttle Run']->id] = 10.8;
            $benchmarkValues[$indicators['Vertical Jump']->id] = 56.0;
            $benchmarkValues[$indicators['Push-up']->id] = 42.0;

            try {
                DB::table('benchmarks')->insert([
                    'sport_branch_id' => $branch->id,
                    'gender' => 'M',
                    'label' => 'U-21',
                    'age_min' => 18,
                    'age_max' => 21,
                    'values' => json_encode($benchmarkValues),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Ignore key constraints if already exists globally
            }
        }

        // 4. Create Athlete A & Athlete B
        $athleteA = Athlete::firstOrCreate(
            ['name' => 'Athlete A Comparison Test'],
            [
                'athlete_number' => 'ATH-COMP-A',
                'gender' => 'M',
                'date_of_birth' => Carbon::now()->subYears(20)->format('Y-m-d'),
                'sport_branch_id' => $branch->id,
                'height' => 175.0,
                'weight' => 70.0,
            ]
        );

        $athleteB = Athlete::firstOrCreate(
            ['name' => 'Athlete B Comparison Test'],
            [
                'athlete_number' => 'ATH-COMP-B',
                'gender' => 'M',
                'date_of_birth' => Carbon::now()->subYears(20)->format('Y-m-d'),
                'sport_branch_id' => $branch->id,
                'height' => 180.0,
                'weight' => 75.0,
            ]
        );

        // 5. Create comparison test folder
        $folder = Folder::firstOrCreate(['name' => 'Comparison Validation Folder']);

        // Sync athletes to folder
        $athleteA->folders()->syncWithoutDetaching([$folder->id]);
        $athleteB->folders()->syncWithoutDetaching([$folder->id]);

        // 6. Create 3 test sessions
        $session1 = Session::firstOrCreate(
            [
                'folder_id' => $folder->id,
                'name' => 'Comparison Baseline Test',
            ],
            [
                'color' => '#3B82F6',
                'location' => 'Training Pitch A',
                'date_time' => '2026-01-01 09:00:00',
            ]
        );

        $session2 = Session::firstOrCreate(
            [
                'folder_id' => $folder->id,
                'name' => 'Comparison Intermediate Test',
            ],
            [
                'color' => '#10B981',
                'location' => 'Training Pitch A',
                'date_time' => '2026-02-01 09:00:00',
            ]
        );

        $session3 = Session::firstOrCreate(
            [
                'folder_id' => $folder->id,
                'name' => 'Comparison Final Test',
            ],
            [
                'color' => '#F59E0B',
                'location' => 'Training Pitch A',
                'date_time' => '2026-03-01 09:00:00',
            ]
        );

        // 7. Seed trials for Athlete A
        // Session 1: ~60% average
        $this->seedAthleteTrials($athleteA->id, $session1->id, [
            $indicators['30m Sprint']->id => 7.0, // 4.2 / 7.0 = 60%
            $indicators['Shuttle Run']->id => 18.0, // 10.8 / 18.0 = 60%
            $indicators['Vertical Jump']->id => 33.6, // 33.6 / 56 = 60%
            $indicators['Push-up']->id => 25.2, // 25.2 / 42 = 60%
        ]);
        Anthropometry::updateOrCreate(
            ['session_id' => $session1->id, 'athlete_id' => $athleteA->id],
            ['height' => 175.0, 'weight' => 70.0, 'bmi' => 22.86]
        );

        // Session 2: ~85% average (BEST)
        $this->seedAthleteTrials($athleteA->id, $session2->id, [
            $indicators['30m Sprint']->id => 4.94, // 4.2 / 4.94 = 85%
            $indicators['Shuttle Run']->id => 12.7, // 10.8 / 12.7 = 85%
            $indicators['Vertical Jump']->id => 47.6, // 47.6 / 56 = 85%
            $indicators['Push-up']->id => 35.7, // 35.7 / 42 = 85%
        ]);
        Anthropometry::updateOrCreate(
            ['session_id' => $session2->id, 'athlete_id' => $athleteA->id],
            ['height' => 175.0, 'weight' => 71.0, 'bmi' => 23.18]
        );

        // Session 3: ~70% average (LATEST)
        $this->seedAthleteTrials($athleteA->id, $session3->id, [
            $indicators['30m Sprint']->id => 6.0, // 4.2 / 6.0 = 70%
            $indicators['Shuttle Run']->id => 15.4, // 10.8 / 15.4 = 70%
            $indicators['Vertical Jump']->id => 39.2, // 39.2 / 56 = 70%
            $indicators['Push-up']->id => 29.4, // 29.4 / 42 = 70%
        ]);
        Anthropometry::updateOrCreate(
            ['session_id' => $session3->id, 'athlete_id' => $athleteA->id],
            ['height' => 175.0, 'weight' => 71.0, 'bmi' => 23.18]
        );

        // 8. Seed trials for Athlete B
        // Session 1: ~55% average
        $this->seedAthleteTrials($athleteB->id, $session1->id, [
            $indicators['30m Sprint']->id => 7.64, // 4.2 / 7.64 = 55%
            $indicators['Shuttle Run']->id => 19.6, // 10.8 / 19.6 = 55%
            $indicators['Vertical Jump']->id => 30.8, // 30.8 / 56 = 55%
            $indicators['Push-up']->id => 23.1, // 23.1 / 42 = 55%
        ]);
        Anthropometry::updateOrCreate(
            ['session_id' => $session1->id, 'athlete_id' => $athleteB->id],
            ['height' => 180.0, 'weight' => 75.0, 'bmi' => 23.15]
        );

        // Session 2: ~75% average
        $this->seedAthleteTrials($athleteB->id, $session2->id, [
            $indicators['30m Sprint']->id => 5.6, // 4.2 / 5.6 = 75%
            $indicators['Shuttle Run']->id => 14.4, // 10.8 / 14.4 = 75%
            $indicators['Vertical Jump']->id => 42.0, // 42 / 56 = 75%
            $indicators['Push-up']->id => 31.5, // 31.5 / 42 = 75%
        ]);
        Anthropometry::updateOrCreate(
            ['session_id' => $session2->id, 'athlete_id' => $athleteB->id],
            ['height' => 180.0, 'weight' => 75.0, 'bmi' => 23.15]
        );

        // Session 3: ~90% average (BEST & LATEST)
        $this->seedAthleteTrials($athleteB->id, $session3->id, [
            $indicators['30m Sprint']->id => 4.67, // 4.2 / 4.67 = 90%
            $indicators['Shuttle Run']->id => 12.0, // 10.8 / 12 = 90%
            $indicators['Vertical Jump']->id => 50.4, // 50.4 / 56 = 90%
            $indicators['Push-up']->id => 37.8, // 37.8 / 42 = 90%
        ]);
        Anthropometry::updateOrCreate(
            ['session_id' => $session3->id, 'athlete_id' => $athleteB->id],
            ['height' => 180.0, 'weight' => 74.0, 'bmi' => 22.84]
        );
    }

    private function seedAthleteTrials(int $athleteId, int $sessionId, array $trialsMap): void
    {
        foreach ($trialsMap as $indicatorId => $value) {
            // Seed two trials per indicator
            for ($trialNum = 1; $trialNum <= 2; $trialNum++) {
                // Add a small jitter (±1%) for trials, so the average remains close to the expected value
                $jitter = 1 + (rand(-10, 10) / 1000);
                Trial::updateOrCreate(
                    [
                        'session_id' => $sessionId,
                        'athlete_id' => $athleteId,
                        'indicator_id' => $indicatorId,
                        'trial_number' => $trialNum,
                    ],
                    [
                        'value' => round($value * $jitter, 2),
                        'is_valid' => true,
                    ]
                );
            }
        }
    }
}
