<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\Indicator;
use App\Models\Session;
use App\Models\Trial;
use App\Models\Benchmark;
use App\Models\Folder;
use App\Enums\ScoringDirection;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ScoringService
{
    private ScoringEngine $engine;
    private BenchmarkResolver $resolver;
    private static array $staticBenchmarkCache = [];
    private static array $staticIndicatorCache = [];
    private array $benchmarkCache = [];
    private array $indicatorCache = [];

    public function __construct(?ScoringEngine $engine = null, ?BenchmarkResolver $resolver = null)
    {
        $this->engine = $engine ?? new ScoringEngine();
        $this->resolver = $resolver ?? new BenchmarkResolver();
    }

    public function scoreAthleteSession(Athlete $athlete, Session $session, Collection $indicators, ?Collection $preloadedTrials = null): array
    {
        $sessionTrials = $preloadedTrials ?? Trial::where('session_id', $session->id)
            ->where('athlete_id', $athlete->id)
            ->where('is_valid', true)
            ->whereNotNull('value')
            ->get();

        $trialsByIndicator = $sessionTrials->groupBy('indicator_id');
        $raws = [];
        $scores = [];
        $validScores = [];

        $age = $athlete->date_of_birth ? Carbon::parse($athlete->date_of_birth)->age : 0;
        $matchedBenchmark = $this->matchBenchmark($athlete, $indicators->first() ?? new Indicator());

        foreach ($indicators as $indicator) {
            $indicatorTrials = $trialsByIndicator->get($indicator->id, collect());
            $values = $indicatorTrials->pluck('value')->map(fn ($v) => (float) $v);
            $direction = ScoringDirection::from($indicator->scoring_direction);

            $bestValue = $this->resolveFinalResult($values, $indicator, $direction);
            $raws[$indicator->id] = $bestValue;

            $score = null;
            if ($bestValue !== null) {
                $bm = $this->matchBenchmark($athlete, $indicator);
                if ($bm && isset($bm->values[$indicator->id])) {
                    $benchmarkValue = (float) $bm->values[$indicator->id];
                    $score = $this->engine->computeScore($bestValue, $benchmarkValue, $direction);
                }
            }

            $scores[$indicator->id] = $score;
            if ($score !== null) {
                $validScores[] = $score;
            }
        }

        return [
            'raws'    => $raws,
            'scores'  => $scores,
            'overall' => $this->engine->computeOverall($validScores),
        ];
    }

    public function scoreForAthlete(Athlete $athlete, Indicator $indicator, Session $session, ?Collection $preloadedTrials = null): ?float
    {
        $bestValue = $this->finalResultForAthlete($athlete, $indicator, $session, $preloadedTrials);
        if ($bestValue === null) {
            return null;
        }

        $benchmark = $this->matchBenchmark($athlete, $indicator);
        if (!$benchmark) {
            return null;
        }

        $benchmarkValue = isset($benchmark->values[$indicator->id]) ? (float) $benchmark->values[$indicator->id] : 0;
        return $this->engine->computeScore($bestValue, $benchmarkValue, ScoringDirection::from($indicator->scoring_direction));
    }

    public function overallForAthlete(Athlete $athlete, Session $session): ?float
    {
        $trials = Trial::where('session_id', $session->id)
            ->where('athlete_id', $athlete->id)
            ->where('is_valid', true)
            ->whereNotNull('value')
            ->get();

        if ($trials->isEmpty()) {
            return null;
        }

        $scores = [];
        foreach ($trials->groupBy('indicator_id') as $indicatorId => $indicatorTrials) {
            $indicator = $this->getIndicator($indicatorId);
            if (!$indicator) {
                continue;
            }

            $direction = ScoringDirection::from($indicator->scoring_direction);
            $bestValue = $this->resolveFinalResult($indicatorTrials->pluck('value'), $indicator, $direction);

            if ($bestValue === null) {
                continue;
            }

            $benchmark = $this->matchBenchmark($athlete, $indicator);
            if (!$benchmark) {
                continue;
            }

            $benchmarkValue = isset($benchmark->values[$indicator->id]) ? (float) $benchmark->values[$indicator->id] : 0;
            $score = $this->engine->computeScore($bestValue, $benchmarkValue, $direction);
            if ($score !== null) {
                $scores[] = $score;
            }
        }

        return $this->engine->computeOverall($scores);
    }

    public function finalResultForAthlete(Athlete $athlete, Indicator $indicator, Session $session, ?Collection $preloadedTrials = null): ?float
    {
        $trials = $preloadedTrials ?? Trial::where('session_id', $session->id)
            ->where('athlete_id', $athlete->id)
            ->where('indicator_id', $indicator->id)
            ->where('is_valid', true)
            ->whereNotNull('value')
            ->pluck('value')
            ->map(fn ($v) => (float) $v);

        return $this->resolveFinalResult($trials, $indicator, ScoringDirection::from($indicator->scoring_direction));
    }

    public function resolveFinalResult(Collection $values, Indicator $indicator, ScoringDirection $direction): ?float
    {
        return $this->engine->resolveFinalResult($values, $indicator->calculation_method, $direction);
    }

    public function scoreSession(Session $session, Collection $athletes, Collection $indicators, ?Collection $preloadedTrials = null): array
    {
        $this->preloadBenchmarks($athletes, $indicators);

        $sessionTrials = $preloadedTrials ?? Trial::where('session_id', $session->id)
            ->where('is_valid', true)
            ->whereNotNull('value')
            ->get();

        $allTrials = $sessionTrials->groupBy(['athlete_id', 'indicator_id']);

        $results = [];

        foreach ($athletes as $athlete) {
            $athleteTrials = $allTrials->get($athlete->id, collect());
            $scores = [];
            $perIndicator = [];

            foreach ($indicators as $indicator) {
                $indicatorTrials = $athleteTrials->get($indicator->id, collect());
                $direction = ScoringDirection::from($indicator->scoring_direction);

                $values = $indicatorTrials->pluck('value')->map(fn ($v) => (float) $v);
                $bestValue = $this->resolveFinalResult($values, $indicator, $direction);

                $score = null;
                $benchmarkObj = null;
                if ($bestValue !== null) {
                    $benchmarkObj = $this->matchBenchmark($athlete, $indicator);
                    if ($benchmarkObj) {
                        $benchmarkValue = isset($benchmarkObj->values[$indicator->id]) ? (float) $benchmarkObj->values[$indicator->id] : 0;
                        $score = $this->engine->computeScore($bestValue, $benchmarkValue, $direction);
                    }
                }

                $perIndicator[$indicator->id] = [
                    'value'     => $bestValue,
                    'score'     => $score,
                    'benchmark' => $benchmarkObj,
                ];

                if ($score !== null) {
                    $scores[] = $score;
                }
            }

            $results[$athlete->id] = [
                'overall'    => $this->engine->computeOverall($scores),
                'indicators' => $perIndicator,
            ];
        }

        return $results;
    }

    public function scoreFolder(?Folder $folder, Collection $athletes, Collection $indicators): array
    {
        $sessions = $folder
            ? $folder->sessions()->orderBy('date_time')->get()
            : Session::orderBy('date_time')->get();

        $sessionIds = $sessions->pluck('id');

        $allTrials = Trial::whereIn('session_id', $sessionIds)
            ->where('is_valid', true)
            ->whereNotNull('value')
            ->get()
            ->groupBy('session_id');

        $results = [];
        foreach ($sessions as $session) {
            $sessionTrials = $allTrials->get($session->id, collect());
            $results[$session->id] = $this->scoreSession($session, $athletes, $indicators, $sessionTrials);
        }

        return $results;
    }

    public function progressForAthlete(Athlete $athlete, Indicator $indicator, ?Folder $folder): ?array
    {
        $sessions = $folder
            ? $folder->sessions()->orderBy('date_time')->get()
            : Session::where(function ($q) use ($athlete) {
                $q->whereHas('trials', fn ($t) => $t->where('athlete_id', $athlete->id));
            })->orderBy('date_time')->get();

        if ($sessions->count() < 2) {
            return null;
        }

        $firstSession = $sessions->first();
        $lastSession  = $sessions->last();

        $firstResult = $this->finalResultForAthlete($athlete, $indicator, $firstSession);
        $lastResult  = $this->finalResultForAthlete($athlete, $indicator, $lastSession);

        if ($firstResult === null || $lastResult === null) {
            return null;
        }

        $absoluteChange = $lastResult - $firstResult;

        $relativeChange = $firstResult == 0
            ? null
            : (($lastResult - $firstResult) / $firstResult) * 100;

        $firstScore = $this->scoreForAthlete($athlete, $indicator, $firstSession);
        $lastScore  = $this->scoreForAthlete($athlete, $indicator, $lastSession);

        $scoreChange = ($firstScore !== null && $lastScore !== null)
            ? $lastScore - $firstScore
            : null;

        return [
            'first_value'     => $firstResult,
            'last_value'      => $lastResult,
            'absolute_change' => round($absoluteChange, 2),
            'relative_change' => $relativeChange !== null ? round($relativeChange, 2) : null,
            'score_change'    => $scoreChange !== null ? round($scoreChange, 2) : null,
        ];
    }

    public function flushCaches(): void
    {
        self::$staticBenchmarkCache = [];
        self::$staticIndicatorCache = [];
        $this->benchmarkCache = [];
        $this->indicatorCache = [];
    }

    private function preloadBenchmarks(Collection $athletes, Collection $indicators): void
    {
        $sportBranchIds = $athletes->pluck('sport_branch_id')->filter()->unique();
        $missingIds = $sportBranchIds->diff(array_keys(self::$staticBenchmarkCache));
        if ($missingIds->isNotEmpty()) {
            $benchmarks = Benchmark::whereIn('sport_branch_id', $missingIds)->get()->groupBy('sport_branch_id');
            foreach ($missingIds as $sbId) {
                self::$staticBenchmarkCache[$sbId] = $benchmarks->get($sbId, collect());
            }
        }
        foreach ($sportBranchIds as $sbId) {
            $this->benchmarkCache[$sbId] = self::$staticBenchmarkCache[$sbId] ?? collect();
        }
        foreach ($indicators as $ind) {
            self::$staticIndicatorCache[$ind->id] = $ind;
            $this->indicatorCache[$ind->id] = $ind;
        }
    }

    private function matchBenchmark(Athlete $athlete, Indicator $indicator): ?Benchmark
    {
        $sbId = $athlete->sport_branch_id;
        $benchmarks = $this->benchmarkCache[$sbId] ?? self::$staticBenchmarkCache[$sbId] ?? null;
        if ($benchmarks === null) {
            $benchmarks = Benchmark::where('sport_branch_id', $sbId)->get();
            self::$staticBenchmarkCache[$sbId] = $benchmarks;
            $this->benchmarkCache[$sbId] = $benchmarks;
        }

        $age = $athlete->date_of_birth ? Carbon::parse($athlete->date_of_birth)->age : 0;
        return $this->resolver->resolve($benchmarks, $athlete->gender, $age);
    }

    private function getIndicator(int $id): ?Indicator
    {
        if (isset($this->indicatorCache[$id])) {
            return $this->indicatorCache[$id];
        }
        if (isset(self::$staticIndicatorCache[$id])) {
            return self::$staticIndicatorCache[$id];
        }

        $indicator = Indicator::find($id);
        if ($indicator) {
            self::$staticIndicatorCache[$id] = $indicator;
            $this->indicatorCache[$id] = $indicator;
        }

        return $indicator;
    }
}
