<?php

namespace App\Services;

interface ConclusionWriter
{
    public function write(int $totalAthletes, array $athleteScores, ?float $averageScore): string;
}
