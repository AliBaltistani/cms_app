<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * UpdateTrainerProfileRequest
 * 
 * Validates trainer profile update requests
 */
class UpdateTrainerProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Only trainers can update their own profile
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        
        // Check if user is authenticated and is a trainer
        if (!$user || $user->role !== 'trainer') {
            return false;
        }
        
        // Check if the trainer is updating their own profile
        $trainerId = $this->route('id');
        return $user->id == $trainerId;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'designation' => [
                'nullable',
                'string',
                'max:255'
            ],
            'experience' => [
                'nullable',
                Rule::in([
                    'less_than_1_year',
                    '1_year',
                    '2_years',
                    '3_years',
                    '4_years',
                    '5_years',
                    '6_years',
                    '7_years',
                    '8_years',
                    '9_years',
                    '10_years',
                    'more_than_10_years'
                ])
            ],
            'about' => [
                'nullable',
                'string',
                'max:2000'
            ],
            'training_philosophy' => [
                'nullable',
                'string',
                'max:2000'
            ]
        ];
    }
    
    /**
     * Get custom error messages for validation rules.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'designation.max' => 'Designation cannot exceed 255 characters.',
            'experience.in' => 'Please select a valid experience level.',
            'about.max' => 'About section cannot exceed 2000 characters.',
            'training_philosophy.max' => 'Training philosophy cannot exceed 2000 characters.'
        ];
    }
}
