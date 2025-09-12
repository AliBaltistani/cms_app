<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'video_url' => 'required|string',
            'video_type' => 'required|in:url,file,youtube,vimeo',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'duration' => 'nullable|integer|min:1',
            'order' => 'nullable|integer|min:0',
            'is_preview' => 'boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Video title is required.',
            'video_url.required' => 'Video URL is required.',
            'video_type.in' => 'Video type must be one of: url, file, youtube, vimeo.',
            'thumbnail.image' => 'Thumbnail must be a valid image file.',
            'duration.min' => 'Duration must be at least 1 second.',
        ];
    }
}