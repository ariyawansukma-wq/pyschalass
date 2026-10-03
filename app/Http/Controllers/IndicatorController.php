<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreIndicatorRequest;
use App\Http\Requests\UpdateIndicatorRequest;
use App\Models\Indicator;
use App\Models\Benchmark;
use App\Models\SportBranch;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class IndicatorController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('sport-branches.index');
    }

    public function create(Request $request)
    {
        return redirect()->route('sport-branches.index');
    }

    public function store(StoreIndicatorRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $branch = SportBranch::findOrFail($validated['sport_branch_id']);
        if ($user && $user->role === UserRole::Officer->value && $branch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this sport branch.');
        }
        
        $maxSortOrder = Indicator::where('sport_branch_id', $validated['sport_branch_id'])->max('sort_order');
        $validated['sort_order'] = ($maxSortOrder !== null) ? $maxSortOrder + 1 : 0;

        $indicator = Indicator::create($validated);

        return redirect()->route('sport-branches.show', [$indicator->sport_branch_id, 'tab' => 'indicators'])
            ->with('success', 'Indicator created successfully.');
    }

    public function edit(Indicator $indicator)
    {
        return redirect()->route('sport-branches.index');
    }

    public function update(UpdateIndicatorRequest $request, Indicator $indicator)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $indicator->sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this indicator.');
        }

        $indicator->update($request->validated());

        return redirect()->route('sport-branches.show', [$indicator->sport_branch_id, 'tab' => 'indicators'])
            ->with('success', 'Indicator updated successfully.');
    }

    public function destroy(Indicator $indicator)
    {
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $indicator->sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this indicator.');
        }

        $sportBranchId = $indicator->sport_branch_id;
        $indicator->delete();

        return redirect()->route('sport-branches.show', [$sportBranchId, 'tab' => 'indicators'])
            ->with('success', 'Indicator archived successfully.');
    }

    public function restore(Request $request, $id)
    {
        $indicator = Indicator::onlyTrashed()->with('sportBranch')->findOrFail($id);
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $indicator->sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this indicator.');
        }

        $indicator->restore();

        return redirect()->route('sport-branches.show', [$indicator->sport_branch_id, 'tab' => 'indicators'])
            ->with('success', 'Indicator restored successfully.');
    }

    public function forceDelete(Request $request, $id)
    {
        $indicator = Indicator::onlyTrashed()->with('sportBranch')->findOrFail($id);
        $user = auth()->user();
        if ($user && $user->role === UserRole::Officer->value && $indicator->sportBranch->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this indicator.');
        }

        $sportBranchId = $indicator->sport_branch_id;
        $indicator->forceDelete();

        return redirect()->route('sport-branches.show', [$sportBranchId, 'tab' => 'indicators'])
            ->with('success', 'Indicator permanently deleted.');
    }
}
