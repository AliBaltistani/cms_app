<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'duration' => 'required|integer|max:1440', // Max 24 hours
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Workout name is required.',
            'duration.required' => 'Duration is required.',
            // 'duration.min' => 'Duration must be at least 1 minute.',
            'thumbnail.image' => 'Thumbnail must be a valid image file.',
            'thumbnail.max' => 'Thumbnail size must not exceed 2MB.',
        ];
    }
}