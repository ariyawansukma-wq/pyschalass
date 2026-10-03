<?php

namespace App\Services;

use App\Enums\ScoringDirection;
use Illuminate\Support\Collection;

class ScoringEngine
{
    /**
     * Compute score from raw result and benchmark threshold.
     *
     * @param float $result
     * @param float $benchmarkValue
     * @param ScoringDirection $scoringDirection
     * @return float|null
     */
    public function computeScore(float $result, float $benchmarkValue, ScoringDirection $scoringDirection): ?float
    {
        if ($benchmarkValue == 0) {
            return null;
        }

        if ($scoringDirection === ScoringDirection::HIGHER_IS_BETTER) {
            $score = ($result / $benchmarkValue) * 100;
        } else {
            if ($result <= 0) {
                // Lower is better, so zero or negative values represent maximum performance (100%)
                $score = 100;
            } else {
                $score = ($benchmarkValue / $result) * 100;
            }
        }

        return round(max(0, min($score, 100)), 2);
    }

    /**
     * Compute overall average score from a list of scores.
     *
     * @param Collection|array $scores
     * @return float|null
     */
    public function computeOverall(Collection|array $scores): ?float
    {
        $scoreCol = is_array($scores) ? collect($scores) : $scores;
        $validScores = $scoreCol->filter(fn ($s) => $s !== null);

        if ($validScores->isEmpty()) {
            return null;
        }

        return round($validScores->avg(), 2);
    }

    /**
     * Resolve the final test result from multiple trial values.
     *
     * @param Collection|array $values
     * @param string $calculationMethod
     * @param ScoringDirection $direction
     * @return float|null
     */
    public function resolveFinalResult(Collection|array $values, string $calculationMethod, ScoringDirection $direction): ?float
    {
        $valCol = is_array($values) ? collect($values) : $values;
        $trials = $valCol->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);

        if ($trials->isEmpty()) {
            return null;
        }

        return match ($calculationMethod) {
            'best' => $direction === ScoringDirection::HIGHER_IS_BETTER
                ? $trials->max()
                : $trials->min(),
            'average' => round($trials->avg(), 2),
            'last' => $trials->last(),
            default => $trials->first(),
        };
    }
}
