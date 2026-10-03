<?php

namespace Database\Seeders;

use App\Models\Anthropometry;
use App\Models\Athlete;
use App\Models\Benchmark;
use App\Models\Folder;
use App\Models\Indicator;
use App\Models\Session;
use App\Models\Trial;
use Illuminate\Database\Seeder;

class SessionSeeder extends Seeder
{
    public function run(): void
    {
        // Create folders
        $folderA = Folder::create(['name' => 'Pelatda 2026 — Batch 1']);
        $folderB = Folder::create(['name' => 'Pelatda 2026 — Batch 2']);
        $folderC = Folder::create(['name' => 'Selection Trial June 2026']);

        // Create sessions across folders
        $sessions = [
            // Folder A: 3 test sessions (progression over time)
            Session::create([
                'folder_id' => $folderA->id,
                'name' => 'Baseline Test',
                'color' => '#FF6B35',
                'location' => 'Main Stadium, Bandung',
                'date_time' => '2026-03-10 08:00:00',
            ]),
            Session::create([
                'folder_id' => $folderA->id,
                'name' => 'Mid-Term Test',
                'color' => '#06A77D',
                'location' => 'Main Stadium, Bandung',
                'date_time' => '2026-05-15 08:00:00',
            ]),
            Session::create([
                'folder_id' => $folderA->id,
                'name' => 'Final Test',
                'color' => '#5B8DEF',
                'location' => 'Main Stadium, Bandung',
                'date_time' => '2026-07-20 08:00:00',
            ]),

            // Folder B: 2 test sessions
            Session::create([
                'folder_id' => $folderB->id,
                'name' => 'Pre-Selection Test',
                'color' => '#F4A100',
                'location' => 'Sport Center, Jakarta',
                'date_time' => '2026-06-01 08:00:00',
            ]),
            Session::create([
                'folder_id' => $folderB->id,
                'name' => 'Post-Selection Test',
                'color' => '#E63946',
                'location' => 'Sport Center, Jakarta',
                'date_time' => '2026-06-28 08:00:00',
            ]),

            // Folder C: 1 session
            Session::create([
                'folder_id' => $folderC->id,
                'name' => 'Regional Selection',
                'color' => '#8B5CF6',
                'location' => 'Gelora Bung Karno, Jakarta',
                'date_time' => '2026-07-05 08:00:00',
            ]),
        ];

        $athletes = Athlete::with('sportBranch')->get();

        foreach ($athletes as $athlete) {
            $branch = $athlete->sportBranch;
            if (!$branch) continue;

            $indicators = Indicator::where('sport_branch_id', $branch->id)->get();
            $benchmarks = Benchmark::where('sport_branch_id', $branch->id)
                ->where('gender', $athlete->gender)
                ->get();

            $age = $athlete->date_of_birth ? now()->diffInYears($athlete->date_of_birth) : 18;
            $bm = $benchmarks->first(fn ($b) => $age >= $b->age_min && $age <= $b->age_max);

            // Assign athletes to folders based on their ID
            $athleteFolderIds = [];
            if ($athlete->id % 3 !== 0) {
                $athleteFolderIds[] = $folderA->id; // ~67% in folder A
            }
            if ($athlete->id % 2 === 0) {
                $athleteFolderIds[] = $folderB->id; // ~50% in folder B
            }
            if ($athlete->id % 5 === 0) {
                $athleteFolderIds[] = $folderC->id; // ~20% in folder C
            }

            // Sync folder relationships via pivot table
            $athlete->folders()->sync($athleteFolderIds);

            foreach ($sessions as $sessionIdx => $session) {
                // Only seed if athlete belongs to this session's folder
                if (!in_array($session->folder_id, $athleteFolderIds)) continue;

                $this->seedAnthropometry($session, $athlete, $sessionIdx);

                foreach ($indicators as $indicator) {
                    $bmVal = $bm?->values[$indicator->id] ?? null;
                    $this->seedTrials($session, $athlete, $indicator, $bmVal, $sessionIdx);
                }
            }
        }
    }

    private function seedAnthropometry(Session $session, Athlete $athlete, int $sessionIdx): void
    {
        $h = $athlete->height ?? 170;
        $w = $athlete->weight ?? 65;

        // Simulate slight weight changes over time
        $delta = match($sessionIdx) {
            0 => 0,
            1 => rand(-3, 3) * 0.1,
            2 => rand(-5, 2) * 0.1,
            default => rand(-2, 2) * 0.1,
        };
        $wSession = round($w + $delta, 1);
        $bmi = round($wSession / (($h / 100) ** 2), 2);

        Anthropometry::create([
            'session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'height' => $h,
            'weight' => $wSession,
            'bmi' => $bmi,
        ]);
    }

    private function seedTrials(Session $session, Athlete $athlete, Indicator $indicator, ?float $bmVal, int $sessionIdx): void
    {
        // Create 2 trials per indicator per session
        for ($trialNum = 1; $trialNum <= 2; $trialNum++) {
            $value = $this->generateValue($indicator, $bmVal, $sessionIdx);

            Trial::create([
                'session_id' => $session->id,
                'athlete_id' => $athlete->id,
                'indicator_id' => $indicator->id,
                'trial_number' => $trialNum,
                'value' => $value,
                'is_valid' => true,
            ]);
        }
    }

    private function generateValue(Indicator $indicator, ?float $bm, int $sessionIdx): float
    {
        $isLower = $indicator->scoring_direction === 'LOWER_IS_BETTER';

        if (!$bm) {
            return $isLower ? round(rand(80, 150) / 10, 2) : round(rand(20, 80), 2);
        }

        // Performance improves over sessions (training effect)
        $factor = match($sessionIdx) {
            0 => ($isLower ? 1.08 : 0.85),  // Baseline: below benchmark
            1 => ($isLower ? 1.02 : 0.92),  // Mid-term: approaching benchmark
            2 => ($isLower ? 0.97 : 0.97),  // Final: at or above benchmark
            3 => ($isLower ? 1.05 : 0.88),  // Pre-selection
            4 => ($isLower ? 0.98 : 0.95),  // Post-selection
            5 => ($isLower ? 0.96 : 0.98),  // Regional selection
            default => ($isLower ? 1.0 : 1.0),
        };

        // Add some randomness (±8%)
        $jitter = 1 + (rand(-8, 8) / 100);
        $val = $bm * $factor * $jitter;

        if ($bm >= 100) return round($val, 0);
        if ($bm >= 10) return round($val, 1);
        return round($val, 2);
    }
}
