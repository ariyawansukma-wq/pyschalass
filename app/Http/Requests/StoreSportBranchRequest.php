<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSportBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sport_branches')->where(fn ($query) => $query->where('user_id', $userId)),
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
