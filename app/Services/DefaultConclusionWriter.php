<?php

namespace App\Services;

class DefaultConclusionWriter implements ConclusionWriter
{
    public function write(int $totalAthletes, array $athleteScores, ?float $averageScore): string
    {
        $validScores = array_filter(array_column($athleteScores, 'overall_score'));
        $scoredCount = count($validScores);

        $bestAthlete = !empty($athleteScores) ? $athleteScores[0]['athlete'] : null;
        $bestScore = !empty($athleteScores) ? $athleteScores[0]['overall_score'] : null;

        $parts = [];
        $parts[] = "From a total of {$totalAthletes} athletes tested";

        if ($averageScore !== null) {
            $parts[] = "the overall average performance score is {$averageScore}%";
        }

        if ($bestAthlete && $bestScore !== null) {
            $parts[] = "The highest scoring athlete is {$bestAthlete->name} with a score of {$bestScore}%";
        }

        if ($scoredCount < $totalAthletes) {
            $incomplete = $totalAthletes - $scoredCount;
            $parts[] = "{$incomplete} athletes have incomplete data";
        }

        return implode('. ', $parts) . '.';
    }
}
