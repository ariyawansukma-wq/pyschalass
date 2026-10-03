<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreBenchmarkRequest;
use App\Http\Requests\UpdateBenchmarkRequest;
use App\Models\Benchmark;
use App\Models\Indicator;
use App\Models\SportBranch;
use App\Exports\BenchmarkTemplateExport;
use App\Imports\BenchmarksImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class BenchmarkController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $branchQuery = SportBranch::with(['indicators'])->orderBy('name');
        if ($user && $user->role === UserRole::Officer->value) {
            $branchQuery->where('user_id', $user->id);
        }
        $sportBranches = $branchQuery->get();

        $selectedBranch = null;
        $benchmarks = collect();
        $indicators = collect();

        $branchId = $request->input('sport_branch_id', $sportBranches->first()?->id);
        if ($branchId) {
            $selectedBranch = $sportBranches->firstWhere('id', $branchId);
            if ($selectedBranch) {
                $benchmarks = Benchmark::where('sport_branch_id', $selectedBranch->id)
                    ->orderBy('age_min')
                    ->get();
                $indicators = Indicator::where('sport_branch_id', $selectedBranch->id)
                    ->orderBy('id', 'asc')
                    ->get();
            }
        }

        return Inertia::render('Benchmarks/Index', [
            'sportBranches' => $sportBranches,
            'selectedBranch' => $selectedBranch,
            'benchmarks' => $benchmarks,
            'indicators' => $indicators,
            'filters' => ['sport_branch_id' => $branchId],
        ]);
    }

    public function create(Request $request)
    {
        return redirect()->route('benchmarks.index');
    }

    public function store(StoreBenchmarkRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $branch = SportBranch::findOrFail($validated['sport_branch_id']);
        if ($user && $user->role === UserRole::Officer->value && $branch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this sport branch.');
        }

        $values = [];
        if ($request->has('values')) {
            $values = array_filter($request->input('values', []), fn($v) => $v !== '' && $v !== null);
        }
        $validated['values'] = $values;

        $benchmark = Benchmark::create($validated);

        return redirect()->back()->with('success', 'Benchmark created successfully.');
    }

    public function edit(Benchmark $benchmark)
    {
        return redirect()->route('benchmarks.index');
    }

    public function update(UpdateBenchmarkRequest $request, Benchmark $benchmark)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $branch = SportBranch::findOrFail($validated['sport_branch_id'] ?? $benchmark->sport_branch_id);
        if ($user && $user->role === UserRole::Officer->value && $branch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this sport branch.');
        }

        $values = [];
        if ($request->has('values')) {
            $values = array_filter($request->input('values', []), fn($v) => $v !== '' && $v !== null);
        }
        $validated['values'] = $values;

        $benchmark->update($validated);

        return redirect()->back()->with('success', 'Benchmark updated successfully.');
    }

    public function destroy(Benchmark $benchmark)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $benchmark->sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this benchmark.');
        }

        $benchmark->delete();

        return redirect()->back()->with('success', 'Benchmark deleted successfully.');
    }

    public function downloadTemplate(Request $request)
    {
        $branchId = $request->query('sport_branch_id') ? (int) $request->query('sport_branch_id') : null;
        $user = auth()->user();
        if ($branchId && $user && $user->role === UserRole::Officer->value) {
            $branch = SportBranch::findOrFail($branchId);
            if ($branch->user_id !== $user->id) {
                abort(403, 'Unauthorized.');
            }
        }

        return Excel::download(
            new BenchmarkTemplateExport($branchId),
            'Template_Import_Benchmark.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'sport_branch_id' => 'nullable|exists:sport_branches,id',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $user = auth()->user();
        $fallbackBranchId = $request->filled('sport_branch_id') ? (int) $request->sport_branch_id : null;
        if ($fallbackBranchId && $user && $user->role === UserRole::Officer->value) {
            $branch = SportBranch::findOrFail($fallbackBranchId);
            if ($branch->user_id !== $user->id) {
                abort(403, 'Unauthorized.');
            }
        }

        try {
            Excel::import(
                new BenchmarksImport($fallbackBranchId),
                $request->file('file')
            );

            return redirect()->back()->with('success', 'Benchmark standards & indicators successfully imported!');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
