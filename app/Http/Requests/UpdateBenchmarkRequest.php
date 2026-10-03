<?php

namespace App\Http\Requests;

use App\Models\Benchmark;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBenchmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_branch_id' => 'required|exists:sport_branches,id',
            'gender' => 'required|in:M,F',
            'label' => 'required|string|max:255',
            'age_min' => 'required|integer|min:5|max:100',
            'age_max' => 'required|integer|min:5|max:100|gte:age_min',
        ];
    }

    public function messages(): array
    {
        return [
            'sport_branch_id.required' => 'Sport Branch selection is required.',
            'sport_branch_id.exists' => 'Selected Sport Branch is invalid.',
            'gender.required' => 'Gender selection is required.',
            'label.required' => 'Label is required.',
            'age_min.required' => 'Minimum age is required.',
            'age_min.min' => 'Minimum age must be at least 5 years.',
            'age_max.required' => 'Maximum age is required.',
            'age_max.gte' => 'Maximum age must be greater than or equal to minimum age.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $benchmark = $this->route('benchmark');

                $overlap = Benchmark::where('sport_branch_id', $this->sport_branch_id)
                    ->where('gender', $this->gender)
                    ->where('id', '!=', $benchmark->id)
                    ->where(function ($query) {
                        $query->whereBetween('age_min', [$this->age_min, $this->age_max])
                            ->orWhereBetween('age_max', [$this->age_min, $this->age_max])
                            ->orWhere(function ($q) {
                                $q->where('age_min', '<=', $this->age_min)
                                  ->where('age_max', '>=', $this->age_max);
                            });
                    })
                    ->first();

                if ($overlap) {
                    $validator->errors()->add(
                        'age_min',
                        "Age range overlaps with existing benchmark '{$overlap->label}' (ages {$overlap->age_min}-{$overlap->age_max})."
                    );
                }
            },
        ];
    }
}
