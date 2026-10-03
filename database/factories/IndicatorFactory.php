<?php

namespace Database\Factories;

use App\Enums\ScoringDirection;
use App\Models\Indicator;
use App\Models\SportBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndicatorFactory extends Factory
{
    protected $model = Indicator::class;

    public function definition(): array
    {
        return [
            'sport_branch_id' => SportBranch::factory(),
            'name' => fake()->unique()->randomElement([
                'Lari 100m', 'Lompat Jauh', 'Lempar Lembing', 'Lari 400m',
                'Renang 50m', 'Push-up', 'Sit-up', 'Vertical Jump',
            ]),
            'unit' => fake()->randomElement(['detik', 'cm', 'm', 'kg', 'kali']),
            'category' => fake()->optional()->randomElement(['Kecepatan', 'Kekuatan', 'Daya Tahan', 'Kelentukan']),
            'scoring_direction' => fake()->randomElement(ScoringDirection::cases())->value,
        ];
    }
}
