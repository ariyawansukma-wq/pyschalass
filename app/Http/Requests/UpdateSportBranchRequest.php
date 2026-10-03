<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSportBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sportBranch = $this->route('sport_branch');
        $sportBranchId = is_object($sportBranch) ? $sportBranch->id : $sportBranch;
        $userId = is_object($sportBranch) ? ($sportBranch->user_id ?? auth()->id()) : auth()->id();

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sport_branches')
                    ->where(fn ($query) => $query->where('user_id', $userId))
                    ->ignore($sportBranchId),
            ],
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Sport Branch name is required.',
            'name.unique' => 'Sport Branch name has already been taken.',
            'name.max' => 'Sport Branch name maximum length is 100 characters.',
        ];
    }
}
