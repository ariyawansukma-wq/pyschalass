<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\SportBranch;
use App\Models\Institution;
use App\Models\Session;
use App\Models\Folder;
use App\Models\Athlete;
use Illuminate\Http\Request;

class Select2Controller extends Controller
{
    public function sportBranches(Request $request)
    {
        $user = auth()->user();
        $query = SportBranch::orderBy('name');

        if ($user && $user->role === UserRole::Officer->value) {
            $query->where('user_id', $user->id);
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $results = $query->limit(10)->get(['id', 'name', 'description']);

        return response()->json([
            'results' => $results->map(fn($r) => [
                'id' => $r->id,
                'text' => $r->name . ($r->description ? ' (' . $r->description . ')' : '')
            ])->toArray(),
        ]);
    }

    public function institutions(Request $request)
    {
        $query = Institution::orderBy('name');

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $results = $query->limit(10)->get(['id', 'name']);

        return response()->json([
            'results' => $results->map(fn($r) => ['id' => $r->id, 'text' => $r->name])->toArray(),
        ]);
    }

    public function folders(Request $request)
    {
        $query = Folder::orderBy('name');

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($athleteId = $request->input('athlete_id')) {
            $query->whereHas('athletes', function($q) use ($athleteId) {
                $q->where('athletes.id', $athleteId);
            });
        }

        $results = $query->limit(10)->get(['id', 'name']);

        return response()->json([
            'results' => $results->map(fn($r) => ['id' => $r->id, 'text' => $r->name])->toArray(),
        ]);
    }

    public function athletes(Request $request)
    {
        $user = auth()->user();
        $query = Athlete::with('sportBranch:id,name')->select(['id', 'name', 'athlete_number', 'sport_branch_id', 'user_id'])->orderBy('name');

        if ($user && $user->role === UserRole::Officer->value) {
            $query->where('user_id', $user->id);
        }

        if ($search = $request->input('search') ?: $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('athlete_number', 'like', "%{$search}%")
                  ->orWhereHas('sportBranch', function($sbQ) use ($search) {
                      $sbQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($branchId = $request->input('sport_branch_id')) {
            $query->where('sport_branch_id', $branchId);
        }

        $results = $query->limit(10)->get();

        return response()->json([
            'results' => $results->map(fn($r) => [
                'id'   => $r->id,
                'text' => $r->name . ($r->athlete_number ? " ({$r->athlete_number})" : '') . ($r->sportBranch ? " · {$r->sportBranch->name}" : ''),
            ])->toArray(),
        ]);
    }

    public function sessions(Request $request)
    {
        $query = Session::orderBy('date_time', 'desc');

        if ($folderId = $request->input('folder_id')) {
            $query->where('folder_id', $folderId);
        }

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $results = $query->limit(10)->get(['id', 'name', 'date_time']);

        return response()->json([
            'results' => $results->map(fn($r) => [
                'id' => $r->id,
                'text' => $r->name . ' - ' . $r->date_time->format('d/m/Y H:i'),
            ])->toArray(),
        ]);
    }
}
