<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreSportBranchRequest;
use App\Http\Requests\UpdateSportBranchRequest;
use App\Models\SportBranch;
use App\Models\Indicator;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class SportBranchController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 10), 5), 100);
        $user = auth()->user();

        $query = QueryBuilder::for(SportBranch::class)
            ->with(['indicators' => fn($q) => $q->orderBy('sort_order', 'asc')->orderBy('id', 'asc')])
            ->withCount(['athletes', 'indicators'])
            ->allowedSorts('name', 'description', 'created_at')
            ->defaultSort('id');

        if ($user && $user->role === UserRole::Officer->value) {
            $query->where('user_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sportBranches = $query->paginate($perPage)->onEachSide(1)->withQueryString();

        $institutions = Institution::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SportBranches/Index', [
            'sportBranches' => $sportBranches,
            'institutions' => $institutions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Request $request, SportBranch $sportBranch)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this sport branch.');
        }

        $indicators = $sportBranch->indicators;
        $archivedIndicators = $sportBranch->indicators()->onlyTrashed()->get();

        session(['last_sport_branch_id' => $sportBranch->id]);

        return Inertia::render('SportBranches/Show', compact('sportBranch', 'indicators', 'archivedIndicators'));
    }

    public function create()
    {
        return redirect()->route('sport-branches.index');
    }

    public function store(StoreSportBranchRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        SportBranch::create($data);

        return redirect()->route('sport-branches.index')
            ->with('success', 'Sport branch created successfully.');
    }

    public function edit(SportBranch $sportBranch)
    {
        return redirect()->route('sport-branches.index');
    }

    public function update(UpdateSportBranchRequest $request, SportBranch $sportBranch)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this sport branch.');
        }

        $sportBranch->update($request->validated());

        return redirect()->route('sport-branches.index')
            ->with('success', 'Sport branch updated successfully.');
    }

    public function duplicate(SportBranch $sportBranch)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this sport branch.');
        }

        return DB::transaction(function () use ($sportBranch, $user) {
            $newSportBranch = $sportBranch->replicate();
            $newSportBranch->user_id = $user->id ?? auth()->id();
            
            $suffix = ' - copy';
            $baseName = substr($sportBranch->name, 0, 100 - strlen($suffix)) . $suffix;
            $newName = $baseName;
            $counter = 1;
            while (SportBranch::where('name', $newName)->where('user_id', $newSportBranch->user_id)->exists()) {
                $extraSuffix = ' (' . $counter . ')';
                $newName = substr($baseName, 0, 100 - strlen($extraSuffix)) . $extraSuffix;
                $counter++;
            }
            $newSportBranch->name = $newName;
            $newSportBranch->save();

            $indicatorIdMap = [];
            foreach ($sportBranch->indicators as $indicator) {
                $newIndicator = $indicator->replicate();
                $newIndicator->sport_branch_id = $newSportBranch->id;
                $newIndicator->save();
                $indicatorIdMap[$indicator->id] = $newIndicator->id;
            }

            foreach ($sportBranch->benchmarks as $benchmark) {
                $newBenchmark = $benchmark->replicate();
                $newBenchmark->sport_branch_id = $newSportBranch->id;
                
                $newValues = [];
                if (is_array($benchmark->values)) {
                    foreach ($benchmark->values as $oldIndicatorId => $val) {
                        if (isset($indicatorIdMap[$oldIndicatorId])) {
                            $newValues[$indicatorIdMap[$oldIndicatorId]] = $val;
                        } else {
                            $newValues[$oldIndicatorId] = $val;
                        }
                    }
                }
                $newBenchmark->values = $newValues;
                $newBenchmark->save();
            }

            return redirect()->route('sport-branches.index')
                ->with('success', 'Sport branch and its indicators duplicated successfully.');
        });
    }

    public function destroy(SportBranch $sportBranch)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this sport branch.');
        }

        $sportBranch->delete();

        return redirect()->route('sport-branches.index')
            ->with('success', 'Sport branch deleted successfully.');
    }

    public function getDetails($id)
    {
        $branch = SportBranch::with(['indicators', 'benchmarks'])->findOrFail($id);
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $branch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this sport branch.');
        }
        
        return response()->json($branch);
    }

    public function reorderIndicators(Request $request, SportBranch $sportBranch)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this sport branch.');
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:indicators,id',
        ]);

        foreach ($request->ids as $index => $id) {
            Indicator::where('id', $id)->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Indicator sequence updated successfully.');
    }
}
