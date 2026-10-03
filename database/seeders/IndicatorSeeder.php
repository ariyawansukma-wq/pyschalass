<?php

namespace Database\Seeders;

use App\Enums\ScoringDirection;
use App\Models\Indicator;
use App\Models\SportBranch;
use Illuminate\Database\Seeder;

class IndicatorSeeder extends Seeder
{
    public function run(): void
    {
        $indicators = [
            'Athletics' => [
                ['name' => '100m Sprint', 'unit' => 'seconds', 'category' => 'Speed', 'scoring_direction' => ScoringDirection::LOWER_IS_BETTER],
                ['name' => 'Long Jump', 'unit' => 'cm', 'category' => 'Explosive Power', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
                ['name' => 'Javelin Throw', 'unit' => 'm', 'category' => 'Strength', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
                ['name' => '400m Run', 'unit' => 'seconds', 'category' => 'Endurance', 'scoring_direction' => ScoringDirection::LOWER_IS_BETTER],
            ],
            'Swimming' => [
                ['name' => '50m Freestyle', 'unit' => 'seconds', 'category' => 'Speed', 'scoring_direction' => ScoringDirection::LOWER_IS_BETTER],
                ['name' => '100m Breaststroke', 'unit' => 'seconds', 'category' => 'Speed', 'scoring_direction' => ScoringDirection::LOWER_IS_BETTER],
                ['name' => '200m Butterfly', 'unit' => 'seconds', 'category' => 'Endurance', 'scoring_direction' => ScoringDirection::LOWER_IS_BETTER],
            ],
            'Football' => [
                ['name' => '30m Sprint', 'unit' => 'seconds', 'category' => 'Speed', 'scoring_direction' => ScoringDirection::LOWER_IS_BETTER],
                ['name' => 'Shuttle Run', 'unit' => 'seconds', 'category' => 'Agility', 'scoring_direction' => ScoringDirection::LOWER_IS_BETTER],
                ['name' => 'Vertical Jump', 'unit' => 'cm', 'category' => 'Explosive Power', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
                ['name' => 'Push-up', 'unit' => 'reps', 'category' => 'Strength', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
            ],
            'Badminton' => [
                ['name' => '100m Sprint', 'unit' => 'seconds', 'category' => 'Speed', 'scoring_direction' => ScoringDirection::LOWER_IS_BETTER],
                ['name' => 'Sit-up', 'unit' => 'reps', 'category' => 'Muscular Strength', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
                ['name' => 'Vertical Jump', 'unit' => 'cm', 'category' => 'Explosive Power', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
            ],
            'Volleyball' => [
                ['name' => '30m Sprint', 'unit' => 'seconds', 'category' => 'Speed', 'scoring_direction' => ScoringDirection::LOWER_IS_BETTER],
                ['name' => 'Vertical Jump', 'unit' => 'cm', 'category' => 'Explosive Power', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
                ['name' => 'Push-up', 'unit' => 'reps', 'category' => 'Strength', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
                ['name' => 'Sit-up', 'unit' => 'reps', 'category' => 'Muscular Strength', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
                ['name' => '12-Minute Run', 'unit' => 'm', 'category' => 'Endurance', 'scoring_direction' => ScoringDirection::HIGHER_IS_BETTER],
            ],
        ];

        foreach ($indicators as $sportName => $sportIndicators) {
            $sportBranch = SportBranch::where('name', $sportName)->first();
            if (!$sportBranch) continue;

            foreach ($sportIndicators as $indicator) {
                Indicator::create([
                    'sport_branch_id' => $sportBranch->id,
                    ...$indicator,
                ]);
            }
        }
    }
}
