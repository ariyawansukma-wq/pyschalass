<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Models\Institution;
use App\Models\InstitutionLogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class InstitutionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 10), 5), 100);

        $query = QueryBuilder::for(Institution::class)
            ->with(['logos', 'signatories', 'letterheadTemplates'])
            ->withCount('signatories', 'letterheadTemplates')
            ->allowedSorts('name', 'address', 'created_at')
            ->defaultSort('-created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $institutions = $query->paginate($perPage)->onEachSide(1)->withQueryString();

        return Inertia::render('Institutions/Index', [
            'institutions' => $institutions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return redirect()->route('institutions.index');
    }

    public function store(StoreInstitutionRequest $request)
    {
        $validated = $request->validated();

        $institution = Institution::create($validated);

        if ($request->hasFile('logos')) {
            foreach ($request->file('logos') as $index => $file) {
                $path = $file->store('institutions/logos', 'public');
                $institution->logos()->create([
                    'logo_path'  => $path,
                    'position'   => 'left',
                    'height_px'  => 52,
                    'sort_order' => $index + 1,
                ]);
            }
        }

        return redirect()->route('institutions.index')
            ->with('success', 'Institution created successfully.');
    }

    public function show(Institution $institution)
    {
        return redirect()->route('institutions.index');
    }

    public function edit(Institution $institution)
    {
        return redirect()->route('institutions.index');
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution)
    {
        $validated = $request->validated();

        $institution->update($validated);

        return redirect()->route('institutions.index')
            ->with('success', 'Institution updated successfully.');
    }

    public function storeLogo(Request $request, Institution $institution)
    {
        $request->validate([
            'logos' => 'required|image|max:2048',
            'height_px' => 'nullable|integer|min:20|max:150',
        ]);

        $path    = $request->file('logos')->store('institutions/logos', 'public');
        $height  = $request->input('height_px', 52);
        $maxSort = $institution->logos()->max('sort_order') ?? 0;

        $institution->logos()->create([
            'logo_path'  => $path,
            'position'   => 'left',
            'height_px'  => $height,
            'sort_order' => $maxSort + 1,
        ]);

        return redirect()->back()->with('success', 'Logo added successfully.');
    }

    public function destroyLogo(Institution $institution, InstitutionLogo $logo)
    {
        if ($logo->logo_path) {
            Storage::disk('public')->delete($logo->logo_path);
        }
        $logo->delete();

        return redirect()->back()->with('success', 'Logo deleted successfully.');
    }

    public function updateLogoHeight(Request $request, Institution $institution, InstitutionLogo $logo)
    {
        $request->validate([
            'height_px' => 'required|integer|min:20|max:150',
        ]);

        $logo->update(['height_px' => $request->height_px]);

        return redirect()->back()->with('success', 'Logo size updated successfully.');
    }

    public function destroy(Institution $institution)
    {
        foreach ($institution->logos as $logo) {
            if ($logo->logo_path) {
                Storage::disk('public')->delete($logo->logo_path);
            }
        }

        $institution->delete();

        return redirect()->route('institutions.index')
            ->with('success', 'Institution deleted successfully.');
    }
}
