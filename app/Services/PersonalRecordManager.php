<?php

namespace App\Services;

use App\Models\Trial;
use App\Models\Indicator;
use App\Models\PersonalRecord;
use App\Enums\ScoringDirection;

class PersonalRecordManager
{
    /**
     * Synchronize personal records when a trial is created or updated.
     */
    public function syncTrial(Trial $trial): void
    {
        if (!$trial->is_valid || $trial->value === null) {
            $this->recalculate($trial->athlete_id, $trial->indicator_id);
            return;
        }

        $indicator = Indicator::find($trial->indicator_id);
        if (!$indicator) {
            return;
        }

        $existingRecord = PersonalRecord::where('athlete_id', $trial->athlete_id)
            ->where('indicator_id', $trial->indicator_id)
            ->first();

        if (!$existingRecord) {
            // First record fast path
            $session = $trial->session;
            PersonalRecord::create([
                'athlete_id'   => $trial->athlete_id,
                'indicator_id' => $trial->indicator_id,
                'folder_id'   => $session?->folder_id,
                'best_value'  => $trial->value,
                'achieved_at' => $session?->date_time ?? now(),
            ]);
            return;
        }

        $direction = ScoringDirection::from($indicator->scoring_direction);
        $isBetter = $direction === ScoringDirection::HIGHER_IS_BETTER
            ? $trial->value > $existingRecord->best_value
            : $trial->value < $existingRecord->best_value;

        if ($isBetter) {
            $session = $trial->session;
            $existingRecord->update([
                'folder_id'   => $session?->folder_id,
                'best_value'  => $trial->value,
                'achieved_at' => $session?->date_time ?? now(),
            ]);
        } else {
            // If the trial value changed, and it was previously the best value, recalculate
            if ($trial->isDirty('value')) {
                $originalValue = $trial->getOriginal('value');
                if ($originalValue !== null && (float)$originalValue === (float)$existingRecord->best_value) {
                    $this->recalculate($trial->athlete_id, $trial->indicator_id);
                }
            }
        }
    }

    /**
     * Synchronize personal records when a trial is deleted.
     */
    public function deleteTrial(Trial $trial): void
    {
        $this->recalculate($trial->athlete_id, $trial->indicator_id);
    }

    /**
     * Recalculate personal record from scratch across all valid trials of the athlete.
     */
    public function recalculate(int $athleteId, int $indicatorId): void
    {
        $indicator = Indicator::find($indicatorId);
        if (!$indicator) {
            return;
        }

        $trials = Trial::where('athlete_id', $athleteId)
            ->where('indicator_id', $indicatorId)
            ->where('is_valid', true)
            ->whereNotNull('value')
            ->get();

        if ($trials->isEmpty()) {
            PersonalRecord::where('athlete_id', $athleteId)
                ->where('indicator_id', $indicatorId)
                ->delete();
            return;
        }

        $direction = ScoringDirection::from($indicator->scoring_direction);
        
        $bestTrial = $direction === ScoringDirection::HIGHER_IS_BETTER
            ? $trials->sortByDesc('value')->first()
            : $trials->sortBy('value')->first();

        if ($bestTrial) {
            $session = $bestTrial->session;
            PersonalRecord::updateOrCreate(
                [
                    'athlete_id'   => $athleteId,
                    'indicator_id' => $indicatorId,
                ],
                [
                    'folder_id'   => $session?->folder_id,
                    'best_value'  => $bestTrial->value,
                    'achieved_at' => $session?->date_time ?? now(),
                ]
            );
        }
    }
}
