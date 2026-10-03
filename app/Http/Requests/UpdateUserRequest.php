<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Tambahkan ini
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? $this->route('user');

        return [
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'instansi' => 'nullable|string|max:255',
            'no_hp'    => 'nullable|string|max:20',
            'max_devices' => 'nullable|integer|min:1|max:50',
            'active_until' => 'nullable|date|after:today',
            'password' => [
                'nullable',
                'string',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.unique' => 'Username is already taken.',
            'name.required' => 'Name is required.',
            'email.email' => 'Invalid email address format.',
            'email.unique' => 'Email address is already taken.',
            'role.required' => 'Role is required.',
            'role.enum' => 'Invalid role.',
            'max_devices.min' => 'Maximum allowed devices must be at least 1.',
            'max_devices.max' => 'Maximum allowed devices cannot exceed 50.',
            'active_until.after' => 'Active until date must be a date after today.',
        ];
    }
}