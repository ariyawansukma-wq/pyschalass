<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Folder;
use App\Models\Institution;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function individualPreview(Request $request, Athlete $athlete)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $athlete->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this athlete.');
        }

        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'folder_id'      => 'nullable|exists:folders,id',
        ]);

        ReportService::flushReportCache();

        $institution = Institution::with('logos')->findOrFail($request->institution_id);
        $folder      = $request->folder_id ? Folder::findOrFail($request->folder_id) : null;

        $reportService = new ReportService();
        $report = $reportService->generateIndividualReportData($athlete, $folder);
        $reportService->decorateInstitutionData($report, $institution);

        $data = $report->toArray();
        return Inertia::render('Reports/IndividualPreview', compact('data'));
    }

    public function finalPreview(Request $request)
    {
        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'folder_id'      => 'required|exists:folders,id',
        ]);

        ReportService::flushReportCache();

        $institution = Institution::with('logos')->findOrFail($request->institution_id);
        $folder      = $request->folder_id ? Folder::findOrFail($request->folder_id) : null;

        $reportService = new ReportService();
        $report = $reportService->generateFinalReportData($folder);
        $reportService->decorateInstitutionData($report, $institution);

        $data = $report->toArray();
        return Inertia::render('Reports/FinalPreview', compact('data'));
    }

    public function finalReportForm(Request $request)
    {
        $folders      = Folder::withCount('athletes')->orderBy('name')->get();
        $institutions = Institution::orderBy('name')->get();

        return Inertia::render('FinalReport/Form', compact('folders', 'institutions'));
    }

    public function generateFinalReport(Request $request)
    {
        $request->validate([
            'folder_id'      => 'required|exists:folders,id',
            'institution_id' => 'required|exists:institutions,id',
            'cover_title'    => 'nullable|string|max:255',
            'cover_sub'      => 'nullable|string|max:255',
            'foreword'     => 'nullable|string',
            'conclusion'   => 'nullable|string',
        ]);

        ReportService::flushReportCache();

        $folder      = $request->folder_id ? Folder::find($request->folder_id) : null;
        $institution = Institution::with('logos')->findOrFail($request->institution_id);

        $reportService = new ReportService();
        $report = $reportService->generateFinalReportData($folder);
        $reportService->decorateInstitutionData($report, $institution);
        $report->set('folder', $folder);
        $report->set('cover_title', $request->cover_title ?: 'Physical Test Final Report');
        $report->set('cover_sub', $request->cover_sub   ?: ($folder ? 'Folder: ' . $folder->name : 'All Athlete Data'));
        $report->set('foreword', $request->foreword);
        $report->set('conclusion', $request->conclusion);

        $data = $report->toArray();
        return Inertia::render('Reports/FinalPreview', compact('data'));
    }
}
