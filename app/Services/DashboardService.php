<?php

namespace App\Services;

use App\Enums\ScoringDirection;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Benchmark;
use App\Models\Folder;
use App\Models\Indicator;
use App\Models\SportBranch;
use App\Models\Session;
use App\Models\Trial;
use App\Models\User;
use App\Services\ScoringService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardService
{
    private ScoringService $engine;
    private ?User $user;

    public function __construct(?User $user = null)
    {
        $this->engine = new ScoringService();
        $this->user = $user ?? auth()->user();
    }

    private function isOfficer(): bool
    {
        return $this->user && $this->user->role === UserRole::Officer->value;
    }

    public function getKpiData(?Folder $folder = null): array
    {
        $stats = $this->calculateAggregateStats($folder);

        $athleteQuery = Athlete::query();
        if ($folder) {
            $athleteQuery->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id));
        }

        if ($this->isOfficer()) {
            $athleteQuery->where('user_id', $this->user->id);
        }

        $counts = (clone $athleteQuery)
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN gender = 'M' THEN 1 ELSE 0 END) as male_count, SUM(CASE WHEN gender = 'F' THEN 1 ELSE 0 END) as female_count")
            ->first();

        $totalAthletes = (int) ($counts->total ?? 0);
        $maleCount = (int) ($counts->male_count ?? 0);
        $femaleCount = (int) ($counts->female_count ?? 0);

        if ($folder) {
            $branchQuery = SportBranch::whereHas('athletes', function ($q) use ($folder) {
                $q->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id));
                if ($this->isOfficer()) {
                    $q->where('athletes.user_id', $this->user->id);
                }
            });
            $totalSports = $branchQuery->count();
            $totalSessions = $folder->sessions()->count();
        } else {
            $branchQuery = SportBranch::query();
            if ($this->isOfficer()) {
                $branchQuery->where('user_id', $this->user->id);
            }
            $totalSports = $branchQuery->count();
            $totalSessions = Session::count();
        }

        return [
            'total_athletes' => $totalAthletes,
            'male_athletes' => $maleCount,
            'female_athletes' => $femaleCount,
            'male_avg_score' => $stats['byGender']['M'] ?? null,
            'female_avg_score' => $stats['byGender']['F'] ?? null,
            'average_score' => $stats['overall'] ?? null,
            'total_sports' => $totalSports,
            'total_folders' => $folder ? 1 : Folder::count(),
            'total_sessions' => $totalSessions,
        ];
    }

    public function getPerformanceBySport(?Folder $folder = null): array
    {
        $stats = $this->calculateAggregateStats($folder);

        if ($folder) {
            $branchQuery = SportBranch::whereHas('athletes', function ($q) use ($folder) {
                $q->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id));
                if ($this->isOfficer()) {
                    $q->where('athletes.user_id', $this->user->id);
                }
            });
            if ($this->isOfficer()) {
                $branchQuery->withCount(['athletes' => fn($q) => $q->where('user_id', $this->user->id)->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id))]);
            } else {
                $branchQuery->withCount(['athletes' => fn($q) => $q->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id))]);
            }
        } else {
            $branchQuery = SportBranch::query();
            if ($this->isOfficer()) {
                $branchQuery->where('user_id', $this->user->id)
                    ->withCount(['athletes' => fn($q) => $q->where('user_id', $this->user->id)]);
            } else {
                $branchQuery->withCount('athletes');
            }
        }

        return $branchQuery->get()
            ->map(fn ($branch) => [
                'name' => $branch->name,
                'athlete_count' => $branch->athletes_count,
                'average_score' => $stats['bySport'][$branch->id] ?? 0,
            ])
            ->values()
            ->toArray();
    }

    public function getPerformanceByGender(?Folder $folder = null): array
    {
        $stats = $this->calculateAggregateStats($folder);
        $genders = ['M' => 'Male (M)', 'F' => 'Female (F)'];

        return array_map(function ($value, $label) use ($stats, $folder) {
            $q = Athlete::where('gender', $value);
            if ($folder) {
                $q->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id));
            }
            if ($this->isOfficer()) {
                $q->where('user_id', $this->user->id);
            }

            return [
                'gender' => $label,
                'athlete_count' => $q->count(),
                'average_score' => $stats['byGender'][$value] ?? 0,
            ];
        }, array_keys($genders), $genders);
    }

    public function getAthletesBySport(?Folder $folder = null): array
    {
        if ($folder) {
            $branchQuery = SportBranch::whereHas('athletes', function ($q) use ($folder) {
                $q->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id));
                if ($this->isOfficer()) {
                    $q->where('athletes.user_id', $this->user->id);
                }
            });
            if ($this->isOfficer()) {
                $branchQuery->withCount(['athletes' => fn($q) => $q->where('user_id', $this->user->id)->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id))]);
            } else {
                $branchQuery->withCount(['athletes' => fn($q) => $q->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id))]);
            }
        } else {
            $branchQuery = SportBranch::query();
            if ($this->isOfficer()) {
                $branchQuery->where('user_id', $this->user->id)
                    ->withCount(['athletes' => fn($q) => $q->where('user_id', $this->user->id)]);
            } else {
                $branchQuery->withCount('athletes');
            }
        }

        return $branchQuery->get()
            ->map(fn ($branch) => [
                'name' => $branch->name,
                'athletes_count' => (int) $branch->athletes_count,
            ])
            ->values()
            ->toArray();
    }

    public function getPerformanceByAgeGroup(?Folder $folder = null): array
    {
        $stats = $this->calculateAggregateStats($folder);
        $byAgeGroup = $stats['byAgeGroup'] ?? [];

        $expectedGroups = ['<15 Tahun', '>16 Tahun'];
        $result = [];
        $seen = [];

        foreach ($byAgeGroup as $group => $score) {
            if ($group === null || strtolower(trim((string)$group)) === 'usia') {
                continue;
            }
            $result[] = [
                'age_group' => (string) $group,
                'average_score' => $score !== null ? $score : 0,
            ];
            $seen[$group] = true;
        }

        foreach ($expectedGroups as $eg) {
            if (!isset($seen[$eg])) {
                $result[] = [
                    'age_group' => $eg,
                    'average_score' => 0,
                ];
            }
        }

        return $result;
    }

    public static function flushDashboardCache(): void
    {
        try {
            self::getCacheStore()->flush();
        } catch (Throwable $e) {
            Cache::flush();
        }
    }

    private static ?bool $redisAvailable = null;

    private static function getCacheStore()
    {
        if (self::$redisAvailable === null) {
            try {
                $client = config('database.redis.client', 'phpredis');
                if ($client === 'phpredis' && !class_exists('Redis')) {
                    self::$redisAvailable = false;
                } elseif ($client === 'predis' && !class_exists('Predis\Client')) {
                    self::$redisAvailable = false;
                } else {
                    $store = Cache::store('redis');
                    $store->get('redis_connection_test');
                    self::$redisAvailable = true;
                }
            } catch (Throwable $e) {
                self::$redisAvailable = false;
            }
        }

        return self::$redisAvailable ? Cache::store('redis') : Cache::store();
    }

    public function getAthleteScores(?Folder $folder = null, ?Collection $customAthletes = null): array
    {
        if ($customAthletes !== null) {
            $computed = $this->computeScoresForAthletes($customAthletes, $folder);
            $athleteScores = $computed['athleteScores'];
        } else {
            $stats = $this->calculateAggregateStats($folder);
            $athleteScores = $stats['athleteScores'] ?? [];
        }

        // Sort by overall_score descending (null scores at the end)
        usort($athleteScores, function ($a, $b) {
            $scoreA = $a['overall_score'];
            $scoreB = $b['overall_score'];
            if ($scoreA === null && $scoreB === null) {
                return strcmp($a['athlete']['name'] ?? '', $b['athlete']['name'] ?? '');
            }
            if ($scoreA === null) return 1;
            if ($scoreB === null) return -1;
            return $scoreB <=> $scoreA;
        });

        return $athleteScores;
    }

    private function calculateAggregateStats(?Folder $folder = null): array
    {
        $cacheKey = 'dashboard_aggregate_stats_' . ($folder ? 'folder_' . $folder->id . '_' : '') . ($this->isOfficer() ? 'user_' . $this->user->id : 'admin');

        return self::getCacheStore()->remember($cacheKey, 3600, function () use ($folder) {
            $athletesQuery = Athlete::with('sportBranch');
            if ($folder) {
                $athletesQuery->whereHas('folders', fn($f) => $f->where('folders.id', $folder->id));
            }
            if ($this->isOfficer()) {
                $athletesQuery->where('athletes.user_id', $this->user->id);
            }
            $athletes = $athletesQuery->get();
            if ($athletes->isEmpty()) {
                return ['overall' => null, 'bySport' => [], 'byGender' => [], 'byAgeGroup' => [], 'athleteScores' => []];
            }

            return $this->computeScoresForAthletes($athletes, $folder);
        });
    }

    public function computeScoresForAthletes(Collection $athletes, ?Folder $folder = null): array
    {
        if ($athletes->isEmpty()) {
            return ['overall' => null, 'bySport' => [], 'byGender' => [], 'byAgeGroup' => [], 'athleteScores' => []];
        }

        $athleteIds = $athletes->pluck('id');
        $branchIds = $athletes->pluck('sport_branch_id')->filter()->unique();

        // Bulk fetch latest session IDs in 1 query
        $sessionsQuery = DB::table('trials')
            ->join('test_sessions', 'trials.session_id', '=', 'test_sessions.id')
            ->select('trials.athlete_id', 'trials.session_id')
            ->whereIn('trials.athlete_id', $athleteIds)
            ->where('trials.is_valid', true)
            ->whereNotNull('trials.value');

        if ($folder) {
            $sessionsQuery->where('test_sessions.folder_id', $folder->id);
        }

        $latestSessions = $sessionsQuery->orderBy('test_sessions.date_time', 'desc')
            ->get()
            ->unique('athlete_id')
            ->pluck('session_id', 'athlete_id');

        // Bulk preload all trials for these latest sessions in 1 query
        $allTrials = Trial::whereIn('athlete_id', $athleteIds)
            ->whereIn('session_id', $latestSessions->values())
            ->where('is_valid', true)
            ->whereNotNull('value')
            ->get()
            ->groupBy(['athlete_id', 'indicator_id']);

        // Bulk preload all indicators in 1 query
        $allIndicators = Indicator::whereIn('sport_branch_id', $branchIds)
            ->withTrashed()
            ->get()
            ->keyBy('id');

        // Bulk preload all benchmarks in 1 query
        $benchmarksByBranch = Benchmark::whereIn('sport_branch_id', $branchIds)
            ->get()
            ->groupBy('sport_branch_id');

        // Bulk preload all anthropometries in 1 query
        $allAnthros = \App\Models\Anthropometry::whereIn('athlete_id', $athleteIds)
            ->whereNotNull('height')
            ->whereNotNull('weight')
            ->get()
            ->groupBy('athlete_id');

        $resolver = new BenchmarkResolver();
        $scoringEngine = new ScoringEngine();

        $overallScores = [];
        $sportScores = [];
        $genderScores = [];
        $ageGroupScores = [];
        $athleteScores = [];

        foreach ($athletes as $athlete) {
            $sessionId = $latestSessions[$athlete->id] ?? null;
            $score = null;

            if ($sessionId && isset($allTrials[$athlete->id])) {
                $athleteTrials = $allTrials[$athlete->id];
                $scores = [];
                $age = $athlete->date_of_birth ? Carbon::parse($athlete->date_of_birth)->age : 0;
                $branchBms = $benchmarksByBranch->get($athlete->sport_branch_id, collect());
                $matchedBm = $resolver->resolve($branchBms, $athlete->gender, $age);

                foreach ($athleteTrials as $indicatorId => $indicatorTrials) {
                    $indicator = $allIndicators->get($indicatorId);
                    if (!$indicator) continue;

                    $direction = ScoringDirection::from($indicator->scoring_direction);
                    $bestValue = $scoringEngine->resolveFinalResult($indicatorTrials->pluck('value'), $indicator->calculation_method, $direction);
                    if ($bestValue === null) continue;

                    if (!$matchedBm || !isset($matchedBm->values[$indicator->id])) continue;

                    $benchmarkValue = (float) $matchedBm->values[$indicator->id];
                    $indScore = $scoringEngine->computeScore($bestValue, $benchmarkValue, $direction);
                    if ($indScore !== null) {
                        $scores[] = $indScore;
                    }
                }

                if (!empty($scores)) {
                    $score = $scoringEngine->computeOverall($scores);
                }
            }

            $age = $athlete->date_of_birth ? Carbon::parse($athlete->date_of_birth)->age : 0;
            $branchBms = $benchmarksByBranch->get($athlete->sport_branch_id, collect());
            $matchedBm = $resolver->resolve($branchBms, $athlete->gender, $age);

            $group = null;
            if ($matchedBm) {
                if ($matchedBm->age_max <= 15) {
                    $group = '<15 Tahun';
                } elseif ($matchedBm->age_min >= 16) {
                    $group = '>16 Tahun';
                } else {
                    $lbl = trim((string) $matchedBm->label);
                    if (!empty($lbl) && !in_array(strtolower($lbl), ['usia', 'age', 'standard', 'standard internal'])) {
                        $group = $lbl;
                    } else {
                        $group = "{$matchedBm->age_min}-{$matchedBm->age_max} Tahun";
                    }
                }
            }

            if (!$group) {
                $group = ($age > 0 && $age > 15) ? '>16 Tahun' : '<15 Tahun';
            }

            if ($score !== null) {
                $overallScores[] = $score;
                $sportScores[$athlete->sport_branch_id][] = $score;
                $genderScores[$athlete->gender][] = $score;
                $ageGroupScores[$group][] = $score;
            }

            // Compute latest anthropometry & BMI
            $latestHeight = $athlete->height;
            $latestWeight = $athlete->weight;
            $athleteAnthros = $allAnthros->get($athlete->id, collect());
            $lastAnthro = $athleteAnthros->sortByDesc('id')->first();
            if ($lastAnthro) {
                $latestHeight = $lastAnthro->height;
                $latestWeight = $lastAnthro->weight;
            }

            $bmi = null;
            if ($latestHeight && $latestWeight && $latestHeight > 0) {
                $hM = $latestHeight / 100;
                $bmi = round($latestWeight / ($hM * $hM), 1);
            } elseif ($athlete->bmi !== null) {
                $bmi = (float) $athlete->bmi;
            }

            $bmiCat = null;
            if ($bmi !== null) {
                if ($bmi < 18.5)      $bmiCat = 'Underweight';
                elseif ($bmi < 23)    $bmiCat = 'Healthy Weight';
                elseif ($bmi < 25)    $bmiCat = 'At Risk of Overweight';
                else                  $bmiCat = 'Overweight';
            }

            // Plain lightweight array to keep Redis cache footprint minimal (<30KB)
            $athleteScores[] = [
                'athlete' => [
                    'id' => $athlete->id,
                    'name' => $athlete->name,
                    'athlete_number' => $athlete->athlete_number,
                    'gender' => $athlete->gender,
                    'sport_branch' => $athlete->sportBranch ? [
                        'id' => $athlete->sportBranch->id,
                        'name' => $athlete->sportBranch->name,
                    ] : null,
                ],
                'overall_score' => $score,
                'bmi_category' => $bmiCat,
                'height' => $latestHeight,
                'weight' => $latestWeight,
                'bmi' => $bmi,
                'age' => $age > 0 ? $age : null,
            ];
        }

        $avg = fn (array $arr) => !empty($arr) ? round(array_sum($arr) / count($arr), 1) : null;

        return [
            'overall' => $avg($overallScores),
            'bySport' => array_map($avg, $sportScores),
            'byGender' => array_map($avg, $genderScores),
            'byAgeGroup' => array_map($avg, $ageGroupScores),
            'athleteScores' => $athleteScores,
        ];
    }
}
