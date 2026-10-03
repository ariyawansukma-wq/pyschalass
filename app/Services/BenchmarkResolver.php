<?php

namespace App\Services;

use App\Models\Benchmark;
use App\Models\SportBranch;
use Illuminate\Support\Collection;

class BenchmarkResolver
{
    /**
     * Find the best matching benchmark set for an athlete given their gender and age.
     *
     * @param Collection|array $benchmarks
     * @param string $gender 'M' or 'F'
     * @param int $ageYears
     * @return Benchmark|null
     */
    public function resolve(Collection|array $benchmarks, string $gender, int $ageYears): ?Benchmark
    {
        $bms = is_array($benchmarks) ? collect($benchmarks) : $benchmarks;
        if ($bms->isEmpty()) {
            return null;
        }

        // 1. Filter gender (matching athlete gender 'M'/'F')
        $genderMatches = $bms->filter(function ($b) use ($gender) {
            return strtolower($b->gender) === strtolower($gender);
        });

        if ($genderMatches->isEmpty()) {
            $genderMatches = $bms;
        }

        // 2. Exact age range match (age_min <= ageYears <= age_max)
        $exactAgeMatch = $genderMatches->first(function ($b) use ($ageYears) {
            return $ageYears >= $b->age_min && $ageYears <= $b->age_max;
        });

        if ($exactAgeMatch) {
            return $exactAgeMatch;
        }

        // 3. Fallback for age exceeding max age in DB (e.g. Senior age 26+)
        $fallbackHighestAgeGroup = $genderMatches
            ->filter(function ($b) use ($ageYears) {
                return $ageYears >= $b->age_min;
            })
            ->sortByDesc('age_max')
            ->first();

        if ($fallbackHighestAgeGroup) {
            return $fallbackHighestAgeGroup;
        }

        // 4. Ultimate fallback to the last benchmark group
        return $genderMatches->sortByDesc('age_max')->first() ?: $bms->first();
    }
}
