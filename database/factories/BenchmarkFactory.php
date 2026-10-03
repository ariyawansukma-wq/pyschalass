<?php

namespace Database\Factories;

use App\Models\Benchmark;
use App\Models\SportBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BenchmarkFactory extends Factory
{
    protected $model = Benchmark::class;

    public function definition(): array
    {
        $ageMin = fake()->numberBetween(5, 15);
        $ageMax = $ageMin + fake()->numberBetween(1, 5);

        return [
            'sport_branch_id' => SportBranch::factory(),
            'gender' => fake()->randomElement(['M', 'F']),
            'label' => fake()->randomElement([
                'Boys 10-12 Years', 'Girls 10-12 Years',
                'Boys 13-15 Years', 'Girls 13-15 Years',
                'Boys 16-18 Years', 'Girls 16-18 Years',
            ]),
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'values' => null,
        ];
    }
}
