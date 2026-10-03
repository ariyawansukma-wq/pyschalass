<?php

namespace App\Services;

use App\Services\ScoringService;
use App\Models\Athlete;
use App\Models\Folder;
use App\Models\Indicator;
use App\Models\Session;
use Illuminate\Support\Collection;

class ComparisonService
{
    private ScoringService $engine;

    public function __construct()
    {
        $this->engine = new ScoringService();
    }

    /**
     * @param Folder|null $folder  Scope to a folder, or null for all data.
     */
    public function forActivity(?Folder $folder): array
    {
        $athletes = $folder
            ? $folder->athletes()->with(['sportBranch.indicators'])->get()
            : Athlete::with(['sportBranch.indicators'])->get();

        $sessions = $folder
            ? $folder->sessions()->orderBy('date_time')->get()
            : Session::orderBy('date_time')->get();

        $indicators = $this->resolveIndicators($athletes);
        $allScores  = $this->engine->scoreFolder($folder, $athletes, $indicators);

        $comparisonData   = [];
        $bestPerIndicator = [];

        foreach ($indicators as $indicator) {
            $indicatorScores = [];

            foreach ($athletes as $athlete) {
                $bestScore = null;

                foreach ($sessions as $session) {
                    $sessionScores = $allScores[$session->id] ?? [];
                    $athleteResult = $sessionScores[$athlete->id] ?? null;
                    $score         = $athleteResult ? ($athleteResult['indicators'][$indicator->id]['score'] ?? null) : null;

                    if ($score !== null && ($bestScore === null || $score > $bestScore)) {
                        $bestScore = $score;
                    }
                }

                $comparisonData[$indicator->id][$athlete->id] = $bestScore;

                if ($bestScore !== null) {
                    $indicatorScores[$athlete->id] = $bestScore;
                }
            }

            if (!empty($indicatorScores)) {
                $bestPerIndicator[$indicator->id] = max($indicatorScores);
            }
        }

        return [
            'athletes'         => $athletes,
            'indicators'       => $indicators,
            'comparisonData'   => $comparisonData,
            'bestPerIndicator' => $bestPerIndicator,
        ];
    }

    /**
     * @param Folder|null $folder
     */
    public function forActivityFiltered(?Folder $folder, ?int $sportBranchId = null, ?int $sessionId = null): array
    {
        $allAthletes = $folder
            ? $folder->athletes()->with(['sportBranch.indicators'])->get()
            : Athlete::with(['sportBranch.indicators'])->get();

        $allSessions = $folder
            ? $folder->sessions()->orderBy('date_time')->get()
            : Session::orderBy('date_time')->get();

        // Filter athletes by sport branch
        $athletes = $sportBranchId
            ? $allAthletes->where('sport_branch_id', $sportBranchId)->values()
            : $allAthletes;

        $indicators = $sportBranchId
            ? Indicator::where('sport_branch_id', $sportBranchId)
                ->withTrashed()
                ->where(function($query) use ($athletes) {
                    $query->whereNull('deleted_at')
                          ->orWhereIn('id', function($q) use ($athletes) {
                              $q->select('indicator_id')
                                ->from('trials')
                                ->whereIn('athlete_id', $athletes->pluck('id'));
                          })
                          ->orWhereIn('id', function($q) use ($athletes) {
                              $q->select('indicator_id')
                                ->from('personal_records')
                                ->whereIn('athlete_id', $athletes->pluck('id'));
                          });
                })
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get()
            : $this->resolveIndicators($athletes);

        // Filter sessions
        $sessions = $sessionId
            ? $allSessions->where('id', $sessionId)->values()
            : $allSessions;

        $allScores = $this->engine->scoreFolder($folder, $athletes, $indicators);

        $comparisonData   = [];
        $bestPerIndicator = [];

        foreach ($indicators as $indicator) {
            $indicatorScores = [];

            foreach ($athletes as $athlete) {
                $bestScore = null;

                foreach ($sessions as $session) {
                    $sessionScores = $allScores[$session->id] ?? [];
                    $athleteResult = $sessionScores[$athlete->id] ?? null;
                    $score         = $athleteResult ? ($athleteResult['indicators'][$indicator->id]['score'] ?? null) : null;

                    if ($score !== null && ($bestScore === null || $score > $bestScore)) {
                        $bestScore = $score;
                    }
                }

                $comparisonData[$indicator->id][$athlete->id] = $bestScore;

                if ($bestScore !== null) {
                    $indicatorScores[$athlete->id] = $bestScore;
                }
            }

            if (!empty($indicatorScores)) {
                $bestPerIndicator[$indicator->id] = max($indicatorScores);
            }
        }

        return [
            'athletes'         => $athletes,
            'indicators'       => $indicators,
            'sessions'         => $allSessions,
            'comparisonData'   => $comparisonData,
            'bestPerIndicator' => $bestPerIndicator,
        ];
    }

    private function resolveIndicators($athletes): Collection
    {
        $branchIds = $athletes->pluck('sport_branch_id')->filter()->unique();
        $athleteIds = $athletes->pluck('id')->unique();

        return Indicator::whereIn('sport_branch_id', $branchIds)
            ->withTrashed()
            ->where(function($query) use ($athleteIds) {
                $query->whereNull('deleted_at')
                      ->orWhereIn('id', function($q) use ($athleteIds) {
                          $q->select('indicator_id')
                            ->from('trials')
                            ->whereIn('athlete_id', $athleteIds);
                      })
                      ->orWhereIn('id', function($q) use ($athleteIds) {
                          $q->select('indicator_id')
                            ->from('personal_records')
                            ->whereIn('athlete_id', $athleteIds);
                      });
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }
}
