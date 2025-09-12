<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $workoutId = $this->route('workout')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workouts', 'name')->ignore($workoutId)
            ],
            'duration' => 'required|integer|min:1|max:1440',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ];
    }
}