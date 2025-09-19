<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use App\Models\User;

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
            'user_id' => 'required|integer|exists:users,id',
            'is_active' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'price' => 'nullable|numeric|min:0|max:9999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Workout name is required.',
            'name.unique' => 'A workout with this name already exists.',
            'duration.required' => 'Duration is required.',
            'user_id.required' => 'Please select a trainer for this workout.',
            'user_id.exists' => 'The selected trainer is invalid.',
            'thumbnail.image' => 'Thumbnail must be a valid image file.',
            'thumbnail.max' => 'Thumbnail size must not exceed 2MB.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'price.max' => 'Price cannot exceed $9,999,999.99.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled('user_id')) {
                $user = User::find($this->user_id);
                if ($user && $user->role !== 'trainer') {
                    $validator->errors()->add('user_id', 'The selected user must be a trainer.');
                }
            }
        });
    }
}