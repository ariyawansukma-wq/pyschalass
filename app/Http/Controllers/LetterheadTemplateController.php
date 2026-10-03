<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterheadTemplateRequest;
use App\Models\Institution;
use App\Models\LetterheadTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LetterheadTemplateController extends Controller
{
    public function store(StoreLetterheadTemplateRequest $request, Institution $institution)
    {
        $validated = $request->validated();

        if ($request->hasFile('header_image')) {
            $validated['header_image_path'] = $request->file('header_image')->store('letterheads/headers', 'public');
        }

        if ($request->hasFile('footer_image')) {
            $validated['footer_image_path'] = $request->file('footer_image')->store('letterheads/footers', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['is_active']) {
            $institution->letterheadTemplates()->update(['is_active' => false]);
        }

        $institution->letterheadTemplates()->create($validated);

        return back()->with('success', 'Template created successfully.');
    }

    public function update(StoreLetterheadTemplateRequest $request, Institution $institution, LetterheadTemplate $letterheadTemplate)
    {
        $validated = $request->validated();

        if ($request->hasFile('header_image')) {
            if ($letterheadTemplate->header_image_path) {
                Storage::disk('public')->delete($letterheadTemplate->header_image_path);
            }
            $validated['header_image_path'] = $request->file('header_image')->store('letterheads/headers', 'public');
        }

        if ($request->hasFile('footer_image')) {
            if ($letterheadTemplate->footer_image_path) {
                Storage::disk('public')->delete($letterheadTemplate->footer_image_path);
            }
            $validated['footer_image_path'] = $request->file('footer_image')->store('letterheads/footers', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['is_active']) {
            $institution->letterheadTemplates()
                ->where('id', '!=', $letterheadTemplate->id)
                ->update(['is_active' => false]);
        }

        $letterheadTemplate->update($validated);

        return back()->with('success', 'Template updated successfully.');
    }

    public function destroy(Institution $institution, LetterheadTemplate $letterheadTemplate)
    {
        if ($letterheadTemplate->header_image_path) {
            Storage::disk('public')->delete($letterheadTemplate->header_image_path);
        }
        if ($letterheadTemplate->footer_image_path) {
            Storage::disk('public')->delete($letterheadTemplate->footer_image_path);
        }

        $letterheadTemplate->delete();

        return back()->with('success', 'Template deleted successfully.');
    }
}
