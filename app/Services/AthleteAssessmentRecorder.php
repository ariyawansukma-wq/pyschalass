<?php

namespace App\Services;

use App\Models\Anthropometry;
use App\Models\Athlete;
use App\Models\Session;
use App\Models\Trial;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AthleteAssessmentRecorder
{
    /**
     * Record or update batch test sessions, anthropometry, and trial scores for an athlete.
     *
     * @param Athlete $athlete
     * @param array $sessionsData
     * @return void
     */
    public function record(Athlete $athlete, array $sessionsData, ?int $activeFolderId = null): void
    {
        if (empty($sessionsData)) {
            return;
        }

        DB::transaction(function () use ($athlete, $sessionsData, $activeFolderId) {
            $activeSessionIds = [];
            $defaultFolderId = $activeFolderId ?: $athlete->folders->first()?->id;

            foreach ($sessionsData as $sIndex => $sData) {
                if (empty($sData['name'])) {
                    continue;
                }

                $date = !empty($sData['date']) ? Carbon::parse($sData['date'])->toDateTimeString() : now()->toDateTimeString();
                $color = !empty($sData['color']) ? $sData['color'] : '#0B2545';
                $folderId = !empty($sData['folder_id']) ? $sData['folder_id'] : $defaultFolderId;

                // 1. Save or Update Session
                $session = Session::updateOrCreate(
                    [
                        'name' => $sData['name'],
                        'folder_id' => $folderId,
                    ],
                    [
                        'date_time' => $date,
                        'color' => $color,
                    ]
                );

                $activeSessionIds[] = $session->id;

                // 2. Save or Update Anthropometry (Height, Weight, BMI)
                $height = !empty($sData['height']) ? (float) $sData['height'] : null;
                $weight = !empty($sData['weight']) ? (float) $sData['weight'] : null;
                $bmi = null;

                if ($height && $weight) {
                    $heightM = $height / 100;
                    $bmi = round($weight / ($heightM * $heightM), 2);
                    $bmi = min(999.99, $bmi);
                }

                if ($height !== null || $weight !== null) {
                    Anthropometry::updateOrCreate(
                        [
                            'athlete_id' => $athlete->id,
                            'session_id' => $session->id,
                        ],
                        [
                            'height' => $height,
                            'weight' => $weight,
                            'bmi' => $bmi,
                        ]
                    );
                } else {
                    Anthropometry::where([
                        'athlete_id' => $athlete->id,
                        'session_id' => $session->id,
                    ])->delete();
                }

                // 3. Save or Update Indicator Trial Scores
                if (!empty($sData['indicators']) && is_array($sData['indicators'])) {
                    foreach ($sData['indicators'] as $indicatorId => $scoreVal) {
                        // Skip if indicatorId is empty, null, or not a valid positive integer
                        if ($indicatorId === '' || $indicatorId === null || !is_numeric($indicatorId) || (int) $indicatorId <= 0) {
                            continue;
                        }

                        if ($scoreVal === '' || $scoreVal === null) {
                            Trial::where([
                                'athlete_id' => $athlete->id,
                                'session_id' => $session->id,
                                'indicator_id' => $indicatorId,
                                'trial_number' => 1,
                            ])->delete();
                            continue;
                        }

                        Trial::updateOrCreate(
                            [
                                'athlete_id' => $athlete->id,
                                'session_id' => $session->id,
                                'indicator_id' => $indicatorId,
                                'trial_number' => 1,
                            ],
                            [
                                'value' => (float) $scoreVal,
                            ]
                        );
                    }
                }
            }

            // Get all folder IDs this athlete belongs to
            $athleteFolderIds = $athlete->folders->pluck('id');
            $folderSessionIds = Session::whereIn('folder_id', $athleteFolderIds)->pluck('id');

            if (!empty($activeSessionIds)) {
                Trial::where('athlete_id', $athlete->id)
                    ->whereIn('session_id', $folderSessionIds)
                    ->whereNotIn('session_id', $activeSessionIds)
                    ->delete();

                Anthropometry::where('athlete_id', $athlete->id)
                    ->whereIn('session_id', $folderSessionIds)
                    ->whereNotIn('session_id', $activeSessionIds)
                    ->delete();
            } else {
                Trial::where('athlete_id', $athlete->id)
                    ->whereIn('session_id', $folderSessionIds)
                    ->delete();

                Anthropometry::where('athlete_id', $athlete->id)
                    ->whereIn('session_id', $folderSessionIds)
                    ->delete();
            }
        });

        DashboardService::flushDashboardCache();
        ReportService::flushReportCache();
    }

    /**
     * Record or update a single indicator trial score.
     */
    public function recordTrial(Athlete $athlete, int $sessionId, int $indicatorId, ?float $value): ?Trial
    {
        $trial = DB::transaction(function () use ($athlete, $sessionId, $indicatorId, $value) {
            if ($value === null || $value === '') {
                Trial::where([
                    'session_id'   => $sessionId,
                    'athlete_id'   => $athlete->id,
                    'indicator_id' => $indicatorId,
                    'trial_number' => 1,
                ])->delete();
                return null;
            }

            return Trial::updateOrCreate(
                [
                    'session_id'   => $sessionId,
                    'athlete_id'   => $athlete->id,
                    'indicator_id' => $indicatorId,
                    'trial_number' => 1,
                ],
                [
                    'value'    => (float) $value,
                    'is_valid' => true,
                ]
            );
        });

        DashboardService::flushDashboardCache();
        ReportService::flushReportCache();

        return $trial;
    }

    /**
     * Record or update session anthropometry (Height, Weight, BMI).
     */
    public function recordAnthropometry(Athlete $athlete, int $sessionId, ?float $height, ?float $weight): ?Anthropometry
    {
        $anthro = DB::transaction(function () use ($athlete, $sessionId, $height, $weight) {
            if ($height === null && $weight === null) {
                Anthropometry::where([
                    'session_id' => $sessionId,
                    'athlete_id' => $athlete->id,
                ])->delete();
                return null;
            }

            $bmi = null;
            if ($height && $weight && $height > 0) {
                $heightM = $height / 100;
                $bmi = round($weight / ($heightM * $heightM), 2);
                $bmi = min(999.99, $bmi);
            }

            return Anthropometry::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'athlete_id' => $athlete->id,
                ],
                [
                    'height' => $height ?: null,
                    'weight' => $weight ?: null,
                    'bmi'    => $bmi,
                ]
            );
        });

        DashboardService::flushDashboardCache();
        ReportService::flushReportCache();

        return $anthro;
    }
}
