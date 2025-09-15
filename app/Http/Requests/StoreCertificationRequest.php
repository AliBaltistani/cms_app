<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * StoreCertificationRequest
 * 
 * Validates certification creation requests for trainers
 */
class StoreCertificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Only trainers can add certifications to their profile
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        
        // Check if user is authenticated and is a trainer
        if (!$user || $user->role !== 'trainer') {
            return false;
        }
        
        // Check if the trainer is adding certification to their own profile
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
            'certificate_name' => [
                'required',
                'string',
                'max:255',
                'min:3'
            ],
            'doc' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
                'max:5120' // 5MB max file size
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
            'certificate_name.required' => 'Certificate name is required.',
            'certificate_name.min' => 'Certificate name must be at least 3 characters.',
            'certificate_name.max' => 'Certificate name cannot exceed 255 characters.',
            'doc.file' => 'Document must be a valid file.',
            'doc.mimes' => 'Document must be a PDF, image, or Word document.',
            'doc.max' => 'Document size cannot exceed 5MB.'
        ];
    }
}
