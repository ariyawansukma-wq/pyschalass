<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Folder;
use App\Models\Institution;
use App\Services\DashboardService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class FolderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 10), 5), 100);
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $query = QueryBuilder::for(Folder::class)
            ->allowedSorts('name', 'created_at')
            ->defaultSort('-created_at');

        if ($isOfficer) {
            $query->whereHas('athletes', fn($q) => $q->where('user_id', $user->id))
                ->withCount(['athletes' => fn($q) => $q->where('user_id', $user->id)]);
        } else {
            $query->withCount('athletes');
        }

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $folders = $query->paginate($perPage)->onEachSide(1)->withQueryString();

        return Inertia::render('Folders/Index', [
            'folders' => $folders,
            'filters' => $request->only(['search']),
            'institutions' => Institution::select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function show(Folder $folder, DashboardService $dashboardService, Request $request)
    {
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        if ($isOfficer) {
            $hasData = $folder->athletes()->where('user_id', $user->id)->exists();
            if (!$hasData) {
                abort(403, 'Unauthorized access: You do not have athlete data in this folder.');
            }
        }

        $kpi = $dashboardService->getKpiData($folder);
        $performanceBySport = $dashboardService->getPerformanceBySport($folder);
        $performanceByGender = $dashboardService->getPerformanceByGender($folder);
        $athletesBySport = $dashboardService->getAthletesBySport($folder);
        $performanceByAgeGroup = $dashboardService->getPerformanceByAgeGroup($folder);

        $sessions = $folder->sessions()
            ->withCount('trials')
            ->orderBy('date_time', 'desc')
            ->get();

        $athleteScores = $dashboardService->getAthleteScores($folder);

        // Assign global rank to each athlete based on overall score sorting
        $allRanked = collect($athleteScores)->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        });

        // Server-side search filtering
        if ($search = $request->input('search')) {
            $searchLower = strtolower(trim($search));
            $allRanked = $allRanked->filter(function ($item) use ($searchLower) {
                $athlete = $item['athlete'] ?? [];
                $name = strtolower(is_array($athlete) ? ($athlete['name'] ?? '') : ($athlete->name ?? ''));
                $num = strtolower(is_array($athlete) ? ($athlete['athlete_number'] ?? '') : ($athlete->athlete_number ?? ''));
                $branchName = is_array($athlete)
                    ? ($athlete['sport_branch']['name'] ?? '')
                    : ($athlete->sportBranch->name ?? '');
                $branch = strtolower($branchName);
                return str_contains($name, $searchLower)
                    || str_contains($num, $searchLower)
                    || str_contains($branch, $searchLower);
            });
        }

        // Server-side pagination
        $page = (int) $request->input('page', 1);
        $perPage = min(max((int) $request->input('per_page', 10), 5), 100);
        $total = $allRanked->count();
        $sliced = $allRanked->slice(($page - 1) * $perPage, $perPage)->values();

        $paginatedAthleteScores = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            $total,
            $perPage,
            $page,
            [
                'path' => url()->current(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('Folders/Show', [
            'folder' => $folder,
            'kpi' => $kpi,
            'performanceBySport' => $performanceBySport,
            'performanceByGender' => $performanceByGender,
            'athletesBySport' => $athletesBySport,
            'performanceByAgeGroup' => $performanceByAgeGroup,
            'sessions' => $sessions,
            'athleteScores' => $paginatedAthleteScores,
            'filters' => $request->only(['search', 'per_page']),
            'institutions' => Institution::select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function exportPdf(Request $request, Folder $folder)
    {
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        if ($isOfficer) {
            $hasData = $folder->athletes()->where('user_id', $user->id)->exists();
            if (!$hasData) {
                abort(403, 'Unauthorized access: You do not have athlete data in this folder.');
            }
        }

        $athletes = $isOfficer
            ? $folder->athletes()->where('user_id', $user->id)->get()
            : null;

        $reportService = new ReportService();
        $report = $reportService->generateFinalReportData($folder, $athletes);

        if ($request->filled('institution_id')) {
            $institution = Institution::with('logos')->find($request->input('institution_id'));
            if ($institution) {
                $reportService->decorateInstitutionData($report, $institution);
            }
        }

        if (!$report->get('institution')) {
            $institution = Institution::with('logos')->first();
            if ($institution) {
                $report->set('institution', $institution);
                $reportService->decorateInstitutionData($report, $institution);
            }
        }

        $report->set('folder', $folder);
        $report->set('cover_title', 'Physical Test Final Report');
        $report->set('cover_sub', 'Folder: ' . $folder->name);

        $data = $report->toArray();
        return Inertia::render('Reports/FolderPreview', compact('data'));
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-folders');

        $request->validate([
            'name' => 'required|string|max:255|unique:folders,name',
        ]);

        Folder::create([
            'name' => $request->name,
        ]);

        return redirect()->route('folders.index')
            ->with('success', 'Folder created successfully.');
    }

    public function apiStore(Request $request)
    {
        Gate::authorize('manage-folders');

        $request->validate([
            'name' => 'required|string|max:255|unique:folders,name',
        ]);

        $folder = Folder::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'folder' => $folder,
        ]);
    }

    public function destroy(Folder $folder)
    {
        Gate::authorize('manage-folders');

        $folder->delete();

        return redirect()->route('folders.index')
            ->with('success', 'Folder deleted successfully.');
    }
}
