<?php

namespace Database\Factories;

use App\Models\Athlete;
use App\Models\SportBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

class AthleteFactory extends Factory
{
    protected $model = Athlete::class;

    public function definition(): array
    {
        return [
            'athlete_number' => fake()->unique()->numerify('ATL-####'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(['M', 'F']),
            'date_of_birth' => fake()->dateTimeBetween('-20 years', '-5 years'),
            'sport_branch_id' => SportBranch::factory(),
            'photo_path' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
