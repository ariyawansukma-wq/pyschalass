<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Folder;
use App\Models\GeneratedReport;
use App\Services\ExportCenterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class GeneratedReportController extends Controller
{
    private ExportCenterService $exportCenter;

    public function __construct(?ExportCenterService $exportCenter = null)
    {
        $this->exportCenter = $exportCenter ?? new ExportCenterService();
    }

    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 10), 5), 100);
        $user = auth()->user();
        $isOfficer = $user && $user->role === UserRole::Officer->value;

        $query = QueryBuilder::for(GeneratedReport::class)
            ->with(['user:id,name', 'folder:id,name'])
            ->allowedSorts('title', 'file_type', 'file_size', 'created_at')
            ->defaultSort('-created_at');

        if ($isOfficer) {
            $query->where('user_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        if ($fileType = $request->input('file_type')) {
            $query->where('file_type', $fileType);
        }

        if ($folderId = $request->input('folder_id')) {
            $query->where('folder_id', $folderId);
        }

        $reports = $query->paginate($perPage)->onEachSide(1)->withQueryString();

        $statsQuery = GeneratedReport::query();
        if ($isOfficer) {
            $statsQuery->where('user_id', $user->id);
        }
        $totalFiles = (clone $statsQuery)->count();
        $totalBytes = (clone $statsQuery)->sum('file_size');

        $folders = Folder::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Exports/Index', [
            'reports' => $reports,
            'folders' => $folders,
            'stats' => [
                'total_files' => $totalFiles,
                'total_bytes' => $totalBytes,
                'max_files' => ExportCenterService::MAX_FILES_PER_USER,
                'max_quota' => ExportCenterService::MAX_FILES_PER_USER,
                'max_bytes' => ExportCenterService::MAX_QUOTA_PER_USER,
            ],
            'filters' => $request->only(['search', 'file_type', 'folder_id']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file_type' => 'required|in:pdf,excel',
            'file' => 'required|file|max:512000',// 500MB
            'folder_id' => 'nullable|exists:folders,id',
        ]);

        $report = $this->exportCenter->storeUploadedFile(
            $request->file('file'),
            $request->input('file_type'),
            $request->input('title'),
            $request->input('folder_id'),
            auth()->user()
        );

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    public function download(GeneratedReport $generatedReport)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $generatedReport->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this report file.');
        }

        if (!Storage::disk('public')->exists($generatedReport->file_path)) {
            abort(404, 'The exported file no longer exists in storage.');
        }

        return Storage::disk('public')->download($generatedReport->file_path, $generatedReport->file_name);
    }

    public function dispatchQueueJob(Request $request)
    {
        $this->exportCenter->dispatchBatchExport(
            auth()->user(),
            $request->input('folder_id'),
            $request->input('sport_branch_id'),
            $request->input('format', 'excel'),
            $request->input('institution_id'),
            $request->only(['search', 'gender'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Export job has been queued in background. You will find the file in Export Center when ready.',
        ]);
    }

    public function destroy(GeneratedReport $generatedReport)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $generatedReport->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this report file.');
        }

        if (Storage::disk('public')->exists($generatedReport->file_path)) {
            Storage::disk('public')->delete($generatedReport->file_path);
        }

        $generatedReport->delete();

        return redirect()->route('exports.index')
            ->with('success', 'Exported file record deleted successfully.');
    }
}

