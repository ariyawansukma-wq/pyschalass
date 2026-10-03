<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreAthleteRequest;
use App\Http\Requests\UpdateAthleteRequest;
use App\Models\Anthropometry;
use App\Models\Athlete;
use App\Models\Folder;
use App\Models\Indicator;
use App\Models\Institution;
use App\Models\Session;
use App\Models\SportBranch;
use App\Models\Trial;
use App\Models\GeneratedReport;
use App\Services\AthleteAssessmentRecorder;
use App\Services\ScoringService;
use App\Services\ReportService;
use App\Exports\AthleteExport;
use App\Exports\FolderExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class AthleteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 10), 5), 100);
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $query = QueryBuilder::for(Athlete::class)
            ->with(['sportBranch:id,name', 'folders:id,name', 'latestAnthropometry'])
            ->allowedSorts('name', 'athlete_number', 'gender', 'date_of_birth', 'bmi', 'sport_branch_id', 'created_at')
            ->defaultSort('-created_at');

        if ($isOfficer) {
            $query->where('user_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('athlete_number', 'like', "%{$search}%");
            });
        }

        if ($sportBranchId = $request->input('sport_branch_id')) {
            $query->where('sport_branch_id', $sportBranchId);
        }

        if ($folderId = $request->input('folder_id')) {
            $query->whereHas('folders', fn($q) => $q->where('folders.id', $folderId));
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        $athletes = $query->paginate($perPage)->onEachSide(1)->withQueryString();

        $folders = Folder::select('id', 'name')->orderBy('name')->get();

        $sportBranches = $isOfficer
            ? SportBranch::where('user_id', $user->id)->select('id', 'name')->orderBy('name')->get()
            : SportBranch::select('id', 'name')->orderBy('name')->get();

        $institutions = Institution::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Athletes/Index', [
            'athletes' => $athletes,
            'folders' => $folders,
            'sportBranches' => $sportBranches,
            'institutions' => $institutions,
            'filters' => $request->only(['search', 'sport_branch_id', 'folder_id', 'gender']),
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $folders = Folder::orderBy('name')->get();
        
        $sportBranchesQuery = SportBranch::with(['indicators', 'benchmarks'])->orderBy('name');
        if ($isOfficer) {
            $sportBranchesQuery->where('user_id', $user->id);
        }
        $sportBranches = $sportBranchesQuery->get();
        
        $athletesQuery = Athlete::with('sportBranch')->orderBy('name');
        if ($isOfficer) {
            $athletesQuery->where('user_id', $user->id);
        }
        $existingAthletes = $athletesQuery->get();

        return Inertia::render('Athletes/Create', compact('sportBranches', 'folders', 'existingAthletes'));
    }

    public function store(StoreAthleteRequest $request, AthleteAssessmentRecorder $recorder)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $folderId = $validated['folder_id'] ?? null;
        $folderIds = $folderId ? [$folderId] : [];

        if (!empty($validated['existing_athlete_id'])) {
            $athlete = Athlete::findOrFail($validated['existing_athlete_id']);
            if ($isOfficer && $athlete->user_id !== $user->id) {
                abort(403, 'Unauthorized access to this athlete.');
            }
            if (!empty($folderIds)) {
                $athlete->folders()->syncWithoutDetaching($folderIds);
            }
        } else {
            if ($request->hasFile('photo')) {
                $validated['photo_path'] = $request->file('photo')->store('athletes/photos', 'public');
            }

            if (!empty($validated['height']) && !empty($validated['weight'])) {
                $heightM = $validated['height'] / 100;
                $bmi = round($validated['weight'] / ($heightM * $heightM), 2);
                $validated['bmi'] = min(999.99, $bmi);
            } else {
                $validated['bmi'] = null;
            }

            $validated['user_id'] = $user->id ?? auth()->id();
            unset($validated['folder_id']);
            $athlete = Athlete::create($validated);
            $athlete->folders()->sync($folderIds);
        }

        $recorder->record($athlete, (array) $request->input('sessions', []), $folderId);

        return redirect()->route('athletes.index')
            ->with('success', 'Athlete created successfully.');
    }

    public function edit(Request $request, Athlete $athlete)
    {
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;
        if ($isOfficer && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this athlete.');
        }

        $athlete->load(['sportBranch', 'folders']);

        $athleteIndicators = Indicator::where('sport_branch_id', $athlete->sport_branch_id)
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
            ->get();

        if ($athlete->sportBranch) {
            $athlete->sportBranch->setRelation('indicators', $athleteIndicators);
        }

        $getSessionIds = function() use ($request, $athlete) {
            $targetFolderId = $request->input('folder_id');
            $query = Trial::where('athlete_id', $athlete->id);
            
            if ($targetFolderId) {
                $query->whereIn('session_id', function($q) use ($targetFolderId) {
                    $q->select('id')->from('test_sessions')->where('folder_id', $targetFolderId);
                });
            }
            $trialSessionIds = $query->distinct()->pluck('session_id');

            $queryAnthro = Anthropometry::where('athlete_id', $athlete->id);
            if ($targetFolderId) {
                $queryAnthro->whereIn('session_id', function($q) use ($targetFolderId) {
                    $q->select('id')->from('test_sessions')->where('folder_id', $targetFolderId);
                });
            }
            $anthroSessionIds = $queryAnthro->distinct()->pluck('session_id');

            return $trialSessionIds->merge($anthroSessionIds)->unique();
        };

        $existingSessions = Session::whereIn('id', $getSessionIds())
            ->orderBy('date_time')
            ->get();

        $sportBranchesQuery = SportBranch::with(['indicators', 'benchmarks'])->orderBy('name');
        if ($isOfficer) {
            $sportBranchesQuery->where('user_id', $user->id);
        }
        $sportBranches = $sportBranchesQuery->get();
        
        $athleteBranch = $sportBranches->firstWhere('id', $athlete->sport_branch_id);
        if ($athleteBranch) {
            $athleteBranch->setRelation('indicators', $athleteIndicators);
        }

        return Inertia::render('Athletes/Edit', [
            'athlete' => $athlete,
            'folders' => fn() => Folder::orderBy('name')->get(),
            'sportBranches' => fn() => $sportBranches,
            'existingSessions' => $existingSessions,
            'existingTrials' => function() use ($athlete, $getSessionIds) {
                return Trial::where('athlete_id', $athlete->id)
                    ->whereIn('session_id', $getSessionIds())
                    ->get()
                    ->groupBy(['session_id', 'indicator_id']);
            },
            'existingAnthro' => function() use ($athlete, $getSessionIds) {
                return Anthropometry::where('athlete_id', $athlete->id)
                    ->whereIn('session_id', $getSessionIds())
                    ->get()
                    ->keyBy('session_id');
            },
            'institutions' => fn() => Institution::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAthleteRequest $request, Athlete $athlete, AthleteAssessmentRecorder $recorder)
    {
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;
        if ($isOfficer && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this athlete.');
        }

        $validated = $request->validated();
        $folderId  = $validated['folder_id'] ?? null;

        if ($request->hasFile('photo')) {
            if ($athlete->photo_path) {
                Storage::disk('public')->delete($athlete->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('athletes/photos', 'public');
        }

        if (!empty($validated['height']) && !empty($validated['weight'])) {
            $heightM = $validated['height'] / 100;
            $bmi = round($validated['weight'] / ($heightM * $heightM), 2);
            $validated['bmi'] = min(999.99, $bmi);
        } else {
            $validated['bmi'] = null;
        }

        unset($validated['folder_id'], $validated['original_folder_id']);
        $athlete->update($validated);

        if ($folderId) {
            $athlete->folders()->syncWithoutDetaching([$folderId]);
        }

        // Both Admin and Officer can record test session data
        $recorder->record($athlete, (array) $request->input('sessions', []), $folderId);

        return redirect()->route('athletes.index')
            ->with('success', 'Athlete updated successfully.');
    }

    public function destroy(Athlete $athlete)
    {
        Gate::authorize('delete-athletes');
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this athlete.');
        }

        if ($athlete->photo_path) {
            Storage::disk('public')->delete($athlete->photo_path);
        }

        $athlete->delete();

        return redirect()->route('athletes.index')
            ->with('success', 'Athlete deleted successfully.');
    }

    public function destroyAll()
    {
        Gate::authorize('delete-athletes');
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $athletes = $isOfficer
            ? Athlete::where('user_id', $user->id)->get()
            : Athlete::all();

        foreach ($athletes as $athlete) {
            if ($athlete->photo_path) {
                Storage::disk('public')->delete($athlete->photo_path);
            }
            $athlete->delete();
        }

        return redirect()->route('athletes.index')
            ->with('success', 'All athletes and associated data deleted successfully.');
    }

    public function saveTrial(Request $request, Athlete $athlete)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this athlete.');
        }

        if ($request->has('value') && $request->input('value') !== null) {
            $request->merge([
                'value' => str_replace(',', '.', trim((string) $request->input('value')))
            ]);
        }

        $request->validate([
            'session_id' => 'required|exists:test_sessions,id',
            'indicator_id' => 'required|exists:indicators,id',
            'value' => 'nullable|numeric',
        ]);

        $recorder = new AthleteAssessmentRecorder();
        $trial = $recorder->recordTrial(
            $athlete,
            (int) $request->input('session_id'),
            (int) $request->input('indicator_id'),
            $request->filled('value') ? (float) $request->input('value') : null
        );

        if (!$trial) {
            return response()->json(['status' => 'deleted']);
        }

        return response()->json(['status' => 'success', 'trial' => $trial]);
    }

    public function saveAnthropometry(Request $request, Athlete $athlete)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this athlete.');
        }

        $dataToMerge = [];
        if ($request->has('height') && $request->input('height') !== null) {
            $dataToMerge['height'] = str_replace(',', '.', trim((string) $request->input('height')));
        }
        if ($request->has('weight') && $request->input('weight') !== null) {
            $dataToMerge['weight'] = str_replace(',', '.', trim((string) $request->input('weight')));
        }
        if (!empty($dataToMerge)) {
            $request->merge($dataToMerge);
        }

        $request->validate([
            'session_id' => 'required|exists:test_sessions,id',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
        ]);

        $recorder = new AthleteAssessmentRecorder();
        $anthro = $recorder->recordAnthropometry(
            $athlete,
            (int) $request->input('session_id'),
            $request->filled('height') ? (float) $request->input('height') : null,
            $request->filled('weight') ? (float) $request->input('weight') : null
        );

        return response()->json(['status' => 'success', 'anthropometry' => $anthro]);
    }

    public function comparison(Athlete $athlete)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this athlete.');
        }

        $athlete->load('sportBranch');

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

        // Get all sessions across any folder involving this athlete
        $sessionIdsFromTrials = Trial::where('athlete_id', $athlete->id)->distinct()->pluck('session_id');
        $sessionIdsFromAnthro = Anthropometry::where('athlete_id', $athlete->id)->distinct()->pluck('session_id');
        $sessionIds = $sessionIdsFromTrials->merge($sessionIdsFromAnthro)->unique();

        $sessions = Session::whereIn('id', $sessionIds)
            ->with('folder')
            ->orderBy('date_time')
            ->get();

        $allTrials = Trial::where('athlete_id', $athlete->id)
            ->whereIn('session_id', $sessionIds)
            ->where('is_valid', true)
            ->whereNotNull('value')
            ->get()
            ->groupBy(['session_id', 'indicator_id']);

        // Calculate scores per session per indicator
        $engine = new ScoringService();
        $matrix = [];       // $matrix[$indicatorId][$sessionId] = score
        $rawValues = [];    // $rawValues[$indicatorId][$sessionId] = raw value
        $overallPerSession = []; // $overallPerSession[$sessionId] = overall score

        foreach ($sessions as $session) {
            $sessionTrials = $allTrials->get($session->id, collect());
            $scores = [];
            foreach ($indicators as $indicator) {
                $indicatorTrials = $sessionTrials->get($indicator->id, collect())->pluck('value');
                $raw = $engine->finalResultForAthlete($athlete, $indicator, $session, $indicatorTrials);
                $score = $engine->scoreForAthlete($athlete, $indicator, $session, $indicatorTrials);

                $rawValues[$indicator->id][$session->id] = $raw;
                $matrix[$indicator->id][$session->id] = $score;

                if ($score !== null) {
                    $scores[] = $score;
                }
            }
            $overallPerSession[$session->id] = !empty($scores)
                ? round(array_sum($scores) / count($scores), 2)
                : null;
        }

        // Best per indicator across all sessions
        $bestPerIndicator = [];
        foreach ($indicators as $indicator) {
            $scores = array_filter($matrix[$indicator->id] ?? [], fn($v) => $v !== null);
            if (!empty($scores)) {
                $bestPerIndicator[$indicator->id] = max($scores);
            }
        }

        return Inertia::render('Athletes/Comparison', [
            'athlete' => $athlete,
            'indicators' => $indicators,
            'sessions' => $sessions,
            'matrix' => $matrix,
            'rawValues' => $rawValues,
            'overallPerSession' => $overallPerSession,
            'bestPerIndicator' => $bestPerIndicator,
        ]);
    }

    public function exportPdf(Request $request, Athlete $athlete)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this athlete.');
        }

        $athlete->load(['sportBranch.indicators', 'personalRecords.indicator', 'folders']);

        $folder = null;
        if ($request->filled('folder_id')) {
            $folder = Folder::find($request->input('folder_id'));
        }
        if (!$folder) {
            $folder = $athlete->folders->first();
        }

        $reportService = new ReportService();
        $report = $reportService->generateIndividualReportData($athlete, $folder);

        if ($request->filled('institution_id')) {
            $inst = Institution::with('logos')->find($request->input('institution_id'));
            if ($inst) {
                $reportService->decorateInstitutionData($report, $inst);
            }
        }

        if (!$report->get('institution')) {
            $report->set('institution', Institution::with('logos')->first());
        }

        $data = $report->toArray();
        return Inertia::render('Reports/IndividualPreview', compact('data'));
    }

    public function exportExcel(Athlete $athlete)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this athlete.');
        }

        $athlete->load(['sportBranch', 'personalRecords.indicator', 'folders']);

        // Get all sessions for this athlete across any folder
        $sessionIdsFromTrials = Trial::where('athlete_id', $athlete->id)->distinct()->pluck('session_id');
        $sessionIdsFromAnthro = Anthropometry::where('athlete_id', $athlete->id)->distinct()->pluck('session_id');
        $sessionIds = $sessionIdsFromTrials->merge($sessionIdsFromAnthro)->unique();
        $sessions = Session::whereIn('id', $sessionIds)
            ->with('folder')
            ->orderBy('date_time', 'desc')
            ->get();

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

        // Preload all trials for this athlete and sessions in exactly 1 query!
        $allTrials = Trial::where('athlete_id', $athlete->id)
            ->whereIn('session_id', $sessionIds)
            ->where('is_valid', true)
            ->whereNotNull('value')
            ->get()
            ->groupBy(['session_id', 'indicator_id']);

        // Calculate scores
        $engine = new ScoringService();
        $scoresPerSession = [];
        $overallPerSession = [];

        foreach ($sessions as $session) {
            $sessionTrials = $allTrials->get($session->id, collect());
            $scores = [];
            foreach ($indicators as $indicator) {
                $indicatorTrials = $sessionTrials->get($indicator->id, collect())->pluck('value');
                $score = $engine->scoreForAthlete($athlete, $indicator, $session, $indicatorTrials);
                $scoresPerSession[$session->id][$indicator->id] = $score;
                if ($score !== null) $scores[] = $score;
            }
            $overallPerSession[$session->id] = !empty($scores) ? round(array_sum($scores) / count($scores), 2) : null;
        }

        $filename = 'Athlete-' . ($athlete->athlete_number ?: $athlete->id) . '-' . now()->format('Y-m-d') . '.xlsx';
        $exportInstance = new AthleteExport($athlete, $sessions, $indicators, $scoresPerSession, $overallPerSession);

        try {
            $relativeFilePath = 'exports/excel/' . uniqid() . '_' . $filename;
            Excel::store($exportInstance, $relativeFilePath, 'public');
            $fullPath = Storage::disk('public')->path($relativeFilePath);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;

            GeneratedReport::create([
                'user_id' => $user ? $user->id : null,
                'folder_id' => $athlete->folders()->first()?->id,
                'title' => 'Athlete Assessment Excel - ' . $athlete->name,
                'file_type' => 'excel',
                'file_name' => $filename,
                'file_path' => $relativeFilePath,
                'file_size' => $fileSize,
            ]);
        } catch (\Exception $e) {
            // Ignore archive save error and proceed with download
        }

        return Excel::download(
            $exportInstance,
            $filename
        );
    }

    public function exportDirectoryPdf(Request $request)
    {
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $query = Athlete::with(['sportBranch.indicators', 'folders']);

        if ($isOfficer) {
            $query->where('user_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('athlete_number', 'like', "%{$search}%");
            });
        }

        if ($sportBranchId = $request->input('sport_branch_id')) {
            $query->where('sport_branch_id', $sportBranchId);
        }

        if ($folderId = $request->input('folder_id')) {
            $query->whereHas('folders', fn($q) => $q->where('folders.id', $folderId));
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        $athletes = $query->orderBy('name')->get();

        $institution = null;
        if ($request->filled('institution_id')) {
            $institution = Institution::with('logos')->find($request->input('institution_id'));
        }
        if (!$institution) {
            $institution = Institution::with('logos')->first();
        }

        $reportService = new ReportService();
        $report = $reportService->generateFinalReportData(null, $athletes);
        
        if ($institution) {
            $reportService->decorateInstitutionData($report, $institution);
        }

        $coverSub = 'Filtered Athlete Data Export';
        if ($sportBranchId) {
            $cabor = SportBranch::find($sportBranchId);
            if ($cabor) {
                $coverSub = 'Sport Branch: ' . $cabor->name;
            }
        }

        $report->set('cover_title', 'Physical Test Directory Report');
        $report->set('cover_sub', $coverSub);

        $data = $report->toArray();
        $showOverallSummaryPerAthlete = false;

        return Inertia::render('Reports/FolderPreview', compact('data', 'showOverallSummaryPerAthlete'));
    }

    public function exportDirectoryExcel(Request $request)
    {
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $query = Athlete::with(['sportBranch', 'folders']);

        if ($isOfficer) {
            $query->where('user_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('athlete_number', 'like', "%{$search}%");
            });
        }

        if ($sportBranchId = $request->input('sport_branch_id')) {
            $query->where('sport_branch_id', $sportBranchId);
        }

        if ($folderId = $request->input('folder_id')) {
            $query->whereHas('folders', fn($q) => $q->where('folders.id', $folderId));
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        $athletes = $query->orderBy('name')->get();

        $filename = 'Athletes_Export_' . now()->format('Y-m-d') . '.xlsx';
        $exportInstance = new FolderExport(null, $athletes);

        try {
            $relativeFilePath = 'exports/excel/' . uniqid() . '_' . $filename;
            Excel::store($exportInstance, $relativeFilePath, 'public');
            $fullPath = Storage::disk('public')->path($relativeFilePath);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;

            $exportCenter = new \App\Services\ExportCenterService();
            $exportCenter->archiveFile(
                $user,
                $folderId ?: null,
                'Athlete Directory Excel Export',
                'excel',
                $filename,
                $relativeFilePath,
                $fileSize
            );
        } catch (\Exception $e) {
            // Ignore archive save error and proceed with download
        }

        return Excel::download(
            $exportInstance,
            $filename
        );
    }
}
