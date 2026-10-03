<?php

namespace Database\Factories;

use App\Models\SportBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

class SportBranchFactory extends Factory
{
    protected $model = SportBranch::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word() . ' ' . fake()->unique()->randomNumber(2),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
