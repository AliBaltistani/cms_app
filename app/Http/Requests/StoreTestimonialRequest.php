<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * StoreTestimonialRequest
 * 
 * Validates testimonial creation requests from clients
 */
class StoreTestimonialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Only clients can create testimonials for trainers
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        
        // Check if user is authenticated and is a client
        if (!$user || $user->role !== 'client') {
            return false;
        }
        
        // Check if the trainer exists and is actually a trainer
        $trainerId = $this->route('id');
        $trainer = User::find($trainerId);
        
        return $trainer && $trainer->role === 'trainer';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2'
            ],
            'date' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'rate' => [
                'required',
                'integer',
                'min:1',
                'max:5'
            ],
            'comments' => [
                'required',
                'string',
                'min:10',
                'max:1000'
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
            'name.required' => 'Name is required.',
            'name.min' => 'Name must be at least 2 characters.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'date.required' => 'Date is required.',
            'date.date' => 'Date must be a valid date.',
            'date.before_or_equal' => 'Date cannot be in the future.',
            'rate.required' => 'Rating is required.',
            'rate.integer' => 'Rating must be a number.',
            'rate.min' => 'Rating must be at least 1.',
            'rate.max' => 'Rating cannot exceed 5.',
            'comments.required' => 'Comments are required.',
            'comments.min' => 'Comments must be at least 10 characters.',
            'comments.max' => 'Comments cannot exceed 1000 characters.'
        ];
    }
}
