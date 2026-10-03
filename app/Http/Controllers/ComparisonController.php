<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Indicator;
use App\Models\Institution;
use App\Models\Session;
use App\Models\Trial;
use App\Models\Anthropometry;
use App\Services\ScoringService;
use App\Exports\ComparisonExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ComparisonController extends Controller
{
    public function index(Request $request)
    {
        $selectedAthleteIds = array_filter(array_map('intval', (array) $request->query('athlete_ids', [])));
        $basis              = $request->query('basis', 'best');

        $comparedAthletes  = collect();
        $indicators        = collect();
        $columns           = [];
        $comparisonMatrix  = [];
        $rawMatrix         = [];
        $overallPerColumn  = [];
        $bestPerIndicator  = [];
        $institution       = Institution::with('logos')->first();

        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        if (!empty($selectedAthleteIds)) {
            $athletesQuery = Athlete::with('sportBranch.indicators')
                ->whereIn('id', $selectedAthleteIds);

            if ($isOfficer) {
                $athletesQuery->where('user_id', $user->id);
            }

            $comparedAthletes = $athletesQuery->get();

            if ($comparedAthletes->isNotEmpty()) {
                $branchIds = $comparedAthletes->pluck('sport_branch_id')->unique();
                $indicators = Indicator::whereIn('sport_branch_id', $branchIds)
                    ->orderBy('name')
                    ->get();

                $engine = new ScoringService();

                $allTrials = Trial::whereIn('athlete_id', $selectedAthleteIds)
                    ->where('is_valid', true)
                    ->whereNotNull('value')
                    ->get()
                    ->groupBy(['athlete_id', 'session_id', 'indicator_id']);

                // Preload all session mappings across all compared athletes in 2 bulk queries
                $allTrialSessions = Trial::whereIn('athlete_id', $selectedAthleteIds)
                    ->select('athlete_id', 'session_id')
                    ->distinct()
                    ->get()
                    ->groupBy('athlete_id');

                $allAnthroSessions = Anthropometry::whereIn('athlete_id', $selectedAthleteIds)
                    ->select('athlete_id', 'session_id')
                    ->distinct()
                    ->get()
                    ->groupBy('athlete_id');

                $allSessionIds = $allTrialSessions->flatten()->pluck('session_id')
                    ->merge($allAnthroSessions->flatten()->pluck('session_id'))
                    ->unique();

                $allLoadedSessions = Session::whereIn('id', $allSessionIds)
                    ->with('folder')
                    ->orderBy('date_time')
                    ->get()
                    ->keyBy('id');

                foreach ($comparedAthletes as $athlete) {
                    $athleteTrials = $allTrials->get($athlete->id, collect());
                    $athTrialSess = $allTrialSessions->get($athlete->id, collect())->pluck('session_id');
                    $athAnthroSess = $allAnthroSessions->get($athlete->id, collect())->pluck('session_id');
                    $sessionIds = $athTrialSess->merge($athAnthroSess)->unique();

                    $athleteSessions = $sessionIds->map(fn($id) => $allLoadedSessions->get($id))->filter()->sortBy('date_time');

                    if ($athleteSessions->isEmpty()) {
                        $colKey = "ath_{$athlete->id}_none";
                        $columns[] = [
                            'key'     => $colKey,
                            'athlete' => $athlete,
                            'session' => null,
                        ];
                        $overallPerColumn[$colKey] = null;
                        foreach ($indicators as $ind) {
                            $comparisonMatrix[$ind->id][$colKey] = null;
                            $rawMatrix[$ind->id][$colKey]        = null;
                        }
                    } else {
                        $sessionData = [];
                        $bestSessionId = null;
                        $bestScore = -1.0;

                        foreach ($athleteSessions as $session) {
                            $sessionTrials = $athleteTrials->get($session->id, collect());
                            $scored = $engine->scoreAthleteSession($athlete, $session, $indicators, $sessionTrials);

                            $sessionData[$session->id] = [
                                'session' => $session,
                                'raws'    => $scored['raws'],
                                'scores'  => $scored['scores'],
                                'overall' => $scored['overall'],
                            ];

                            if ($scored['overall'] !== null && $scored['overall'] >= $bestScore) {
                                $bestScore = $scored['overall'];
                                $bestSessionId = $session->id;
                            }
                        }

                        if ($basis === 'latest') {
                            $latestSession = $athleteSessions->last();
                            $bestSessionId = $latestSession ? $latestSession->id : null;
                        }

                        if ($bestSessionId === null) {
                            $colKey = "ath_{$athlete->id}";
                            $columns[] = [
                                'key'     => $colKey,
                                'athlete' => $athlete,
                                'session' => null,
                            ];
                            $overallPerColumn[$colKey] = null;
                            foreach ($indicators as $indicator) {
                                $rawMatrix[$indicator->id][$colKey]        = null;
                                $comparisonMatrix[$indicator->id][$colKey] = null;
                            }
                        } else {
                            $chosenData = $sessionData[$bestSessionId];
                            $chosenSession = $chosenData['session'];
                            $colKey = "ath_{$athlete->id}";

                            $columns[] = [
                                'key'     => $colKey,
                                'athlete' => $athlete,
                                'session' => $chosenSession,
                            ];

                            $overallPerColumn[$colKey] = $chosenData['overall'];
                            foreach ($indicators as $indicator) {
                                $rawMatrix[$indicator->id][$colKey]        = $chosenData['raws'][$indicator->id] ?? null;
                                $comparisonMatrix[$indicator->id][$colKey] = $chosenData['scores'][$indicator->id] ?? null;
                            }
                        }
                    }
                }

                foreach ($indicators as $indicator) {
                    $scores = array_filter($comparisonMatrix[$indicator->id] ?? [], fn($v) => $v !== null);
                    if (!empty($scores)) {
                        $bestPerIndicator[$indicator->id] = max($scores);
                    }
                }
            }
        }

        if ($request->has('preview_pdf') && $comparedAthletes->isNotEmpty()) {
            return Inertia::render('Comparison/PreviewPdf', compact(
                'selectedAthleteIds', 'comparedAthletes', 'indicators', 'columns',
                'comparisonMatrix', 'rawMatrix', 'overallPerColumn', 'bestPerIndicator',
                'institution', 'basis'
            ));
        }

        if ($request->has('export_excel') && $comparedAthletes->isNotEmpty()) {
            $filename = 'Performance-Comparison-' . now()->format('Y-m-d') . '.xlsx';
            return Excel::download(
                new ComparisonExport($comparedAthletes, $indicators, $columns, $comparisonMatrix, $rawMatrix, $overallPerColumn),
                $filename
            );
        }

        $allAthletesQuery = Athlete::orderBy('name');
        if ($isOfficer) {
            $allAthletesQuery->where('user_id', $user->id);
        }
        $allAthletes = $allAthletesQuery->get(['id', 'name', 'athlete_number']);

        return Inertia::render('Comparison/Index', compact(
            'allAthletes', 'selectedAthleteIds', 'comparedAthletes', 'indicators', 'columns',
            'comparisonMatrix', 'rawMatrix', 'overallPerColumn', 'bestPerIndicator',
            'institution', 'basis'
        ));
    }
}
