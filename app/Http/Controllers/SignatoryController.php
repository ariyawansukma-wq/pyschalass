<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSignatoryRequest;
use App\Models\Institution;
use App\Models\Signatory;
use Illuminate\Http\Request;

class SignatoryController extends Controller
{
    public function store(StoreSignatoryRequest $request, Institution $institution)
    {
        $institution->signatories()->create($request->validated());

        return back()->with('success', 'Signatory created successfully.');
    }

    public function update(StoreSignatoryRequest $request, Institution $institution, Signatory $signatory)
    {
        $signatory->update($request->validated());

        return back()->with('success', 'Signatory updated successfully.');
    }

    public function destroy(Institution $institution, Signatory $signatory)
    {
        $signatory->delete();

        return back()->with('success', 'Signatory deleted successfully.');
    }
}
