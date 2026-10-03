<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $dataToMerge = [];
        if ($this->has('height') && $this->input('height') !== null) {
            $dataToMerge['height'] = str_replace(',', '.', trim((string)$this->input('height')));
        }
        if ($this->has('weight') && $this->input('weight') !== null) {
            $dataToMerge['weight'] = str_replace(',', '.', trim((string)$this->input('weight')));
        }
        if (!empty($dataToMerge)) {
            $this->merge($dataToMerge);
        }

        if ($this->has('sessions')) {
            $sessions = $this->input('sessions');
            if (is_array($sessions)) {
                foreach ($sessions as $sKey => $sVal) {
                    if (isset($sVal['height']) && $sVal['height'] !== null) {
                        $sessions[$sKey]['height'] = str_replace(',', '.', trim((string)$sVal['height']));
                    }
                    if (isset($sVal['weight']) && $sVal['weight'] !== null) {
                        $sessions[$sKey]['weight'] = str_replace(',', '.', trim((string)$sVal['weight']));
                    }
                    if (isset($sVal['indicators']) && is_array($sVal['indicators'])) {
                        foreach ($sVal['indicators'] as $iKey => $iVal) {
                            if ($iVal !== null && $iVal !== '') {
                                $sessions[$sKey]['indicators'][$iKey] = str_replace(',', '.', trim((string)$iVal));
                            }
                        }
                    }
                }
                $this->merge(['sessions' => $sessions]);
            }
        }
    }

    public function rules(): array
    {
        $isExisting = $this->filled('existing_athlete_id');

        return [
            'existing_athlete_id' => 'nullable|exists:athletes,id',
            'athlete_number' => 'nullable|string|max:50|unique:athletes,athlete_number',
            'name' => $isExisting ? 'nullable|string|max:255' : 'required|string|max:255',
            'gender' => $isExisting ? 'nullable|in:M,F' : 'required|in:M,F',
            'date_of_birth' => $isExisting ? 'nullable|date|before:today' : 'required|date|before:today',
            'event_number' => 'nullable|string|max:100',
            'sport_branch_id' => 'required|exists:sport_branches,id',
            'folder_id' => 'required|exists:folders,id',
            'photo' => 'nullable|image|max:2048',
            'height' => 'nullable|numeric|min:30|max:300',
            'weight' => 'nullable|numeric|min:5|max:500',
            'bmi' => 'nullable|numeric',
            'sessions' => 'nullable|array',
            'sessions.*.name' => 'required|string|max:255',
            'sessions.*.date' => 'required|date',
            'sessions.*.color' => 'nullable|string|max:7',
            'sessions.*.height' => 'nullable|numeric|min:30|max:300',
            'sessions.*.weight' => 'nullable|numeric|min:5|max:500',
            'sessions.*.indicators' => 'nullable|array',
            'sessions.*.indicators.*' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        $messages = [
            'athlete_number.unique' => 'Athlete Registration Number has already been taken.',
            'name.required' => 'Athlete Full Name is required.',
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Gender is invalid.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'sport_branch_id.required' => 'Sport Branch selection is required.',
            'sport_branch_id.exists' => 'Selected Sport Branch is invalid.',
            'folder_id.required' => 'Data Folder selection is required.',
            'folder_id.exists' => 'Selected Data Folder is invalid.',
            'photo.image' => 'Profile photo must be an image file.',
            'photo.max' => 'Profile photo maximum size is 2MB.',
        ];

        $sessions = $this->input('sessions', []);
        if (is_array($sessions)) {
            foreach ($sessions as $key => $sData) {
                $sessionName = !empty($sData['name']) ? $sData['name'] : "Test {$key}";
                $messages["sessions.{$key}.height.min"] = "The height in session \"{$sessionName}\" must be at least :min cm.";
                $messages["sessions.{$key}.height.max"] = "The height in session \"{$sessionName}\" must not exceed :max cm.";
                $messages["sessions.{$key}.height.numeric"] = "The height in session \"{$sessionName}\" must be a number.";
                $messages["sessions.{$key}.weight.min"] = "The weight in session \"{$sessionName}\" must be at least :min kg.";
                $messages["sessions.{$key}.weight.max"] = "The weight in session \"{$sessionName}\" must not exceed :max kg.";
                $messages["sessions.{$key}.weight.numeric"] = "The weight in session \"{$sessionName}\" must be a number.";
                $messages["sessions.{$key}.name.required"] = "The name for session \"{$sessionName}\" is required.";
                $messages["sessions.{$key}.date.required"] = "The date for session \"{$sessionName}\" is required.";
            }
        }

        return $messages;
    }
}
