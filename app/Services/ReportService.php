<?php

namespace App\Services;

use App\Models\Anthropometry;
use App\Models\Athlete;
use App\Models\Folder;
use App\Models\Session;
use App\Models\Trial;
use App\Models\Indicator;
use App\Models\Institution;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Throwable;

class ReportService
{
    private ScoringService $engine;
    private ChartRenderer $chartRenderer;
    private ConclusionWriter $conclusionWriter;

    public function __construct(
        ?ChartRenderer $chartRenderer = null,
        ?ConclusionWriter $conclusionWriter = null,
    ) {
        $this->engine = new ScoringService();
        $this->chartRenderer = $chartRenderer ?? new QuickChartRenderer();
        $this->conclusionWriter = $conclusionWriter ?? new DefaultConclusionWriter();
    }

    public static function flushReportCache(): void
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

    /**
     * Generate individual athlete report data (Cached in Redis for 1 hour).
     */
    public function generateIndividualReportData(Athlete $athlete, ?Folder $folder): AssessmentReport
    {
        $cacheKey = 'report_ind_' . $athlete->id . '_f_' . ($folder ? $folder->id : 'all');

        $data = self::getCacheStore()->remember($cacheKey, 3600, function () use ($athlete, $folder) {
            return $this->computeIndividualReportData($athlete, $folder)->toArray();
        });

        return new AssessmentReport($data);
    }

    /**
     * Compute individual athlete report data.
     */
    public function computeIndividualReportData(Athlete $athlete, ?Folder $folder): AssessmentReport
    {
        $trialSessionIds = Trial::where('athlete_id', $athlete->id)->distinct()->pluck('session_id');
        $anthroSessionIds = Anthropometry::where('athlete_id', $athlete->id)->distinct()->pluck('session_id');
        $sessionIds = $trialSessionIds->merge($anthroSessionIds)->unique();

        $query = Session::whereIn('id', $sessionIds);
        if ($folder) {
            $query->where('folder_id', $folder->id);
        }
        $sessions = $query->orderBy('date_time')->get();

        $indicators = collect([]);
        if ($athlete->sportBranch) {
            $indicators = Indicator::where('sport_branch_id', $athlete->sport_branch_id)
                ->withTrashed()
                ->where(function($query) use ($athlete) {
                    $query->whereNull('deleted_at')
                          ->orWhereIn('id', function($q) use ($athlete) {
                              $q->select('indicator_id')
                                ->from('trials')
                                ->where('athlete_id', $athlete->id);
                          })
                          ->orWhereIn('id', function($q) use ($athlete) {
                              $q->select('indicator_id')
                                ->from('personal_records')
                                ->where('athlete_id', $athlete->id);
                          });
                })
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }
        $athletes = collect([$athlete]);

        $sessionScores = [];
        $allRadarData  = [];

        foreach ($sessions as $session) {
            $scores        = $this->engine->scoreSession($session, $athletes, $indicators);
            $athleteResult = $scores[$athlete->id] ?? null;

            $indData = [];
            foreach ($indicators as $indicator) {
                $r = $athleteResult['indicators'][$indicator->id] ?? ['value' => null, 'score' => null];
                $indData[$indicator->id] = $r;
            }

            $sessionScores[$session->id] = [
                'session'    => $session,
                'overall'    => $athleteResult['overall'] ?? null,
                'indicators' => $indData,
            ];

            if ($athleteResult) {
                $allRadarData[$session->id] = array_map(
                    fn ($ind) => $ind['score'] ?? 0,
                    $indData
                );
            }
        }

        $allAnthros = Anthropometry::whereIn('session_id', $sessions->pluck('id'))
            ->where('athlete_id', $athlete->id)
            ->get()
            ->keyBy('session_id');

        $anthropometry = [];
        foreach ($sessions as $session) {
            $anthropometry[$session->id] = $allAnthros->get($session->id);
        }

        $dob          = $athlete->date_of_birth;
        $firstSession = $sessions->first();
        $ageAtTest    = null;
        if ($dob && $firstSession) {
            $testDate  = $firstSession->date_time;
            $years     = (int) $dob->diffInYears($testDate);
            $months    = (int) ($dob->diffInMonths($testDate) % 12);
            $ageAtTest = [
                'years'     => $years,
                'months'    => $months,
                'formatted' => "{$years}y {$months}m",
            ];
        }

        $latestHeight = $athlete->height;
        $latestWeight = $athlete->weight;

        foreach ($sessions->reverse() as $session) {
            $aRec = $anthropometry[$session->id] ?? null;
            if ($aRec && ($aRec->height || $aRec->weight)) {
                if ($aRec->height) $latestHeight = $aRec->height;
                if ($aRec->weight) $latestWeight = $aRec->weight;
                break;
            }
        }

        $bmi = null;
        if ($latestHeight && $latestWeight && $latestHeight > 0) {
            $hM  = $latestHeight / 100;
            $bmi = round($latestWeight / ($hM * $hM), 1);
        } elseif ($athlete->bmi !== null) {
            $bmi = $athlete->bmi;
        }

        $bmiCategory = null;
        $bmiColor    = '#68758A';
        if ($bmi !== null) {
            if ($bmi < 18.5)      { $bmiCategory = 'Underweight';            $bmiColor = '#5B8DEF'; }
            elseif ($bmi < 23)    { $bmiCategory = 'Healthy Weight';         $bmiColor = '#06A77D'; }
            elseif ($bmi < 25)    { $bmiCategory = 'At Risk of Overweight';  $bmiColor = '#F4A100'; }
            else                  { $bmiCategory = 'Overweight';             $bmiColor = '#E63946'; }
        }

        $trends     = [];
        $sessionIds = $sessions->pluck('id')->toArray();
        foreach ($indicators as $indicator) {
            $prevScore = null;
            $trends[$indicator->id] = [];
            foreach ($sessionIds as $sid) {
                $score = $sessionScores[$sid]['indicators'][$indicator->id]['score'] ?? null;
                $trend = null;
                if ($prevScore !== null && $score !== null) {
                    $diff = $score - $prevScore;
                    if (abs($diff) < 1) $trend = 'same';
                    elseif ($diff > 0)  $trend = 'up';
                    else                $trend = 'down';
                }
                $trends[$indicator->id][$sid] = $trend;
                $prevScore = $score;
            }
        }

        $bestOverall = null;
        foreach ($sessionScores as $ss) {
            if ($ss['overall'] !== null && ($bestOverall === null || $ss['overall'] > $bestOverall)) {
                $bestOverall = $ss['overall'];
            }
        }

        return new AssessmentReport([
            'athlete'        => $athlete,
            'folder'         => $folder,
            'sessions'       => $sessions,
            'indicators'     => $indicators,
            'session_scores' => $sessionScores,
            'anthropometry'  => $anthropometry,
            'age_at_test'    => $ageAtTest,
            'latest_height'  => $latestHeight,
            'latest_weight'  => $latestWeight,
            'bmi'            => $bmi,
            'bmi_category'   => $bmiCategory,
            'bmi_color'      => $bmiColor,
            'trends'         => $trends,
            'overall_score'  => $bestOverall,
            'radar_data'     => $allRadarData,
        ]);
    }

    /**
     * Generate final (batch) report data (Cached in Redis for 1 hour when athletes is null).
     */
    public function generateFinalReportData(?Folder $folder, $athletes = null): AssessmentReport
    {
        if ($athletes === null) {
            $cacheKey = 'report_final_f_' . ($folder ? $folder->id : 'all');

            $data = self::getCacheStore()->remember($cacheKey, 3600, function () use ($folder) {
                return $this->computeFinalReportData($folder)->toArray();
            });

            return new AssessmentReport($data);
        }

        return $this->computeFinalReportData($folder, $athletes);
    }

    /**
     * Compute final (batch) report data.
     */
    public function computeFinalReportData(?Folder $folder, $athletes = null): AssessmentReport
    {
        if ($athletes === null) {
            $athletes = $folder
                ? $folder->athletes()->with(['sportBranch.indicators'])->get()
                : Athlete::with(['sportBranch.indicators'])->get();
        }

        $sessions = $folder
            ? $folder->sessions()->orderBy('date_time')->get()
            : Session::orderBy('date_time')->get();

        $branchIds = $athletes->pluck('sport_branch_id')->filter()->unique();
        $athleteIds = $athletes->pluck('id')->unique();
        $indicators = Indicator::whereIn('sport_branch_id', $branchIds)
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

        $allScores = $this->engine->scoreFolder($folder, $athletes, $indicators);

        // Pre-fetch valid anthropometries for batch calculations
        $allAnthros = Anthropometry::whereIn('session_id', $sessions->pluck('id'))
            ->whereIn('athlete_id', $athletes->pluck('id'))
            ->whereNotNull('height')
            ->whereNotNull('weight')
            ->get()
            ->groupBy('athlete_id');

        $athleteScores = [];
        $totalScore    = 0;
        $validCount    = 0;

        foreach ($athletes as $athlete) {
            $bestOverall   = null;
            $bestSessionId = null;

            foreach ($sessions as $session) {
                $sessionScores = $allScores[$session->id] ?? [];
                $athleteResult = $sessionScores[$athlete->id] ?? null;
                $overall       = $athleteResult ? $athleteResult['overall'] : null;

                if ($overall !== null && ($bestOverall === null || $overall > $bestOverall)) {
                    $bestOverall   = $overall;
                    $bestSessionId = $session->id;
                }
            }

            // Get latest anthropometry height & weight for this athlete across sessions
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
                $hM  = $latestHeight / 100;
                $bmi = round($latestWeight / ($hM * $hM), 1);
            } elseif ($athlete->bmi !== null) {
                $bmi = $athlete->bmi;
            }

            $bmiCat = null;
            if ($bmi !== null) {
                if ($bmi < 18.5)      $bmiCat = 'Underweight';
                elseif ($bmi < 23)    $bmiCat = 'Healthy Weight';
                elseif ($bmi < 25)    $bmiCat = 'At Risk of Overweight';
                else                  $bmiCat = 'Overweight';
            }

            $dob = $athlete->date_of_birth;
            $age = null;
            if ($dob) {
                $age = $dob->age;
            }

            $athleteScores[] = [
                'athlete'       => $athlete,
                'overall_score' => $bestOverall,
                'bmi_category'  => $bmiCat,
                'height'        => $latestHeight,
                'weight'        => $latestWeight,
                'bmi'           => $bmi,
                'age'           => $age,
            ];

            if ($bestOverall !== null) {
                $totalScore += $bestOverall;
                $validCount++;
            }
        }

        usort($athleteScores, fn ($a, $b) => ($b['overall_score'] ?? 0) <=> ($a['overall_score'] ?? 0));

        $averageScore = $validCount > 0 ? round($totalScore / $validCount, 2) : null;

        $maleScores = [];
        $femaleScores = [];
        $maleCount = 0;
        $femaleCount = 0;
        foreach ($athleteScores as $as) {
            $gender = $as['athlete']->gender;
            if ($gender === 'M') {
                $maleCount++;
                if ($as['overall_score'] !== null) {
                    $maleScores[] = $as['overall_score'];
                }
            } elseif ($gender === 'F') {
                $femaleCount++;
                if ($as['overall_score'] !== null) {
                    $femaleScores[] = $as['overall_score'];
                }
            }
        }
        $maleAvg = !empty($maleScores) ? round(array_sum($maleScores) / count($maleScores), 2) : null;
        $femaleAvg = !empty($femaleScores) ? round(array_sum($femaleScores) / count($femaleScores), 2) : null;

        $conclusion = $this->conclusionWriter->write($athletes->count(), $athleteScores, $averageScore);

        // Per sport branch aggregation
        $sportBranches = $athletes->pluck('sportBranch')->filter()->unique('id');
        $perBranch     = [];

        $indicatorsByBranch = $indicators->groupBy('sport_branch_id');

        foreach ($sportBranches as $branch) {
            $branchAthletes = array_filter($athleteScores, fn ($as) => $as['athlete']->sport_branch_id === $branch->id);
            $branchScores   = array_filter(array_column($branchAthletes, 'overall_score'));
            $best           = !empty($branchAthletes) ? reset($branchAthletes) : null;

            $branchIndicators = $indicatorsByBranch->get($branch->id, collect());
            $perIndicator     = [];
            foreach ($branchIndicators as $indicator) {
                $sum = 0; $cnt = 0;
                foreach ($athletes->where('sport_branch_id', $branch->id) as $ba) {
                    foreach ($sessions as $session) {
                        $score = $allScores[$session->id][$ba->id]['indicators'][$indicator->id]['score'] ?? null;
                        if ($score !== null) { $sum += $score; $cnt++; }
                    }
                }
                $perIndicator[] = [
                    'indicator' => $indicator,
                    'avg_score' => $cnt > 0 ? round($sum / $cnt, 2) : null,
                    'count'     => $cnt,
                ];
            }

            $perBranch[] = [
                'branch'        => $branch,
                'athlete_count' => count($branchAthletes),
                'avg_score'     => !empty($branchScores) ? round(array_sum($branchScores) / count($branchScores), 2) : null,
                'best_athlete'  => $best ? $best['athlete'] : null,
                'best_score'    => $best ? $best['overall_score'] : null,
                'per_indicator' => $perIndicator,
                'athletes'      => array_values($branchAthletes),
            ];
        }

        // Generate individual reports for each athlete identical to computeIndividualReportData
        $individualReports = [];
        foreach ($athletes as $athlete) {
            $individualReports[$athlete->id] = $this->computeIndividualReportData($athlete, $folder);
        }

        return new AssessmentReport([
            'folder'             => $folder,
            'athlete_scores'     => $athleteScores,
            'average_score'      => $averageScore,
            'conclusion'         => $conclusion,
            'total_athletes'     => $athletes->count(),
            'total_sessions'     => $sessions->count(),
            'per_branch'         => $perBranch,
            'individual_reports' => $individualReports,
            'gender_stats'       => [
                'male_count'   => $maleCount,
                'female_count' => $femaleCount,
                'male_avg'     => $maleAvg,
                'female_avg'   => $femaleAvg,
            ],
        ]);
    }

    public function formatIndonesianDate($date): string
    {
        if (!$date) return '';
        $d = Carbon::parse($date);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $d->day . ' ' . $months[$d->month] . ' ' . $d->year;
    }

    public function decorateInstitutionData(AssessmentReport $report, Institution $institution): void
    {
        $sigDate = $institution->signature_date ?: now();
        $institution->signature_date_formatted = $this->formatIndonesianDate($sigDate);
        $institution->signer_name = $institution->signer_name ?: '';
        $institution->signer_title = $institution->signer_title ?: '';
        $institution->signature_city = $institution->signature_city ?: '';
        $report->set('institution', $institution);
    }
}