<?php

namespace App\Http\Requests;

use App\Enums\ScoringDirection;
use Illuminate\Foundation\Http\FormRequest;

class StoreIndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_branch_id' => 'required|exists:sport_branches,id',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'category' => 'nullable|string|max:100',
            'scoring_direction' => 'required|in:' . implode(',', array_column(ScoringDirection::cases(), 'value')),
            'evaluation' => 'nullable|string',
            'evaluation_threshold' => 'nullable|integer|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'sport_branch_id.required' => 'Sport Branch selection is required.',
            'sport_branch_id.exists' => 'Selected Sport Branch is invalid.',
            'name.required' => 'Indicator name is required.',
            'unit.required' => 'Measurement unit is required.',
            'scoring_direction.required' => 'Scoring direction selection is required.',
            'scoring_direction.in' => 'Selected scoring direction is invalid.',
        ];
    }
}
