<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Allow authenticated users to update workout videos
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'video_type' => 'required|in:url,file,youtube,vimeo',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'duration' => 'nullable|integer|min:1',
            'order' => 'nullable|integer|min:0',
            'is_preview' => 'boolean',
            'metadata' => 'nullable|array',
        ];

        // Conditional validation based on video type
        if ($this->video_type === 'file') {
            $rules['video_file'] = 'nullable|file|mimes:mp4,avi,mov,wmv,flv,webm,mkv|max:102400'; // Max 100MB
            $rules['video_url'] = 'nullable|string|max:255';
        } else {
            $rules['video_url'] = 'required|string|max:500';
            $rules['video_file'] = 'nullable';
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Video title is required.',
            'video_url.required' => 'Video URL is required.',
            'video_file.file' => 'Please select a valid video file.',
            'video_file.mimes' => 'Video file must be one of: mp4, avi, mov, wmv, flv, webm, mkv.',
            'video_file.max' => 'Video file size cannot exceed 100MB.',
            'video_type.in' => 'Video type must be one of: url, file, youtube, vimeo.',
            'thumbnail.image' => 'Thumbnail must be a valid image file.',
            'thumbnail.mimes' => 'Thumbnail must be jpeg, png, jpg, gif, or webp format.',
            'thumbnail.max' => 'Thumbnail size cannot exceed 2MB.',
            'duration.min' => 'Duration must be at least 1 second.',
        ];
    }
}