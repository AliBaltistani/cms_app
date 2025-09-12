@extends('layouts.master')

@section('content')
<form method="POST" action="{{ route('workout-videos.store', $workout->id) }}" enctype="multipart/form-data">
                    @csrf
  <div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Add Video to "{{ $workout->name }}"
                                    </div>
                                    <div class="prism-toggle">
                                        <a href="{{route('workouts.show', $workout->id)}}" class="btn btn-sm btn-primary-light">Back to Workout</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Video Title</label>
                                            <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" placeholder="Enter video title" value="{{ old('title') }}" required>
                                            @error('title')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Video Type</label>
                                            <select class="form-select @error('video_type') is-invalid @enderror" name="video_type" id="videoType" required>
                                                <option value="">Select video type</option>
                                                <option value="youtube" {{ old('video_type') == 'youtube' ? 'selected' : '' }}>YouTube</option>
                                                <option value="vimeo" {{ old('video_type') == 'vimeo' ? 'selected' : '' }}>Vimeo</option>
                                                <option value="url" {{ old('video_type') == 'url' ? 'selected' : '' }}>Direct URL</option>
                                                <option value="file" {{ old('video_type') == 'file' ? 'selected' : '' }}>Upload File</option>
                                            </select>
                                            @error('video_type')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Order</label>
                                            <input type="number" class="form-control @error('order') is-invalid @enderror" name="order" placeholder="Video order" min="0" value="{{ old('order', $workout->videos->count() + 1) }}">
                                            <small class="text-muted">Leave blank for auto-order</small>
                                            @error('order')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Video URL</label>
                                            <input type="url" class="form-control @error('video_url') is-invalid @enderror" name="video_url" id="videoUrl" placeholder="Enter video URL" value="{{ old('video_url') }}" required>
                                            <small class="text-muted" id="urlHelp">
                                                <span id="youtubeHelp" style="display: none;">Enter YouTube URL (e.g., https://www.youtube.com/watch?v=VIDEO_ID)</span>
                                                <span id="vimeoHelp" style="display: none;">Enter Vimeo URL (e.g., https://vimeo.com/VIDEO_ID)</span>
                                                <span id="urlHelp" style="display: none;">Enter direct video file URL (e.g., https://example.com/video.mp4)</span>
                                                <span id="fileHelp" style="display: none;">For file upload, enter a temporary filename or leave blank</span>
                                            </small>
                                            @error('video_url')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Enter video description">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Duration (seconds)</label>
                                            <input type="number" class="form-control @error('duration') is-invalid @enderror" name="duration" placeholder="Duration in seconds" min="1" value="{{ old('duration') }}">
                                            <small class="text-muted">Optional - leave blank if unknown</small>
                                            @error('duration')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Thumbnail</label>
                                            <input type="file" class="form-control @error('thumbnail') is-invalid @enderror" name="thumbnail" accept="image/*">
                                            <small class="text-muted">Upload video thumbnail (jpeg, png, jpg, gif, webp - max 2MB)</small>
                                            @error('thumbnail')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Video Options</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_preview" value="1" id="isPreview" {{ old('is_preview') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="isPreview">
                                                    This is a preview video
                                                </label>
                                            </div>
                                            <small class="text-muted">Preview videos are typically shown to users before they start the workout</small>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary">Add Video</button>
                                            <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
</form>

<script>
document.getElementById('videoType').addEventListener('change', function() {
    const videoType = this.value;
    const helps = document.querySelectorAll('[id$="Help"]');
    
    // Hide all help texts
    helps.forEach(help => help.style.display = 'none');
    
    // Show relevant help text
    if (videoType) {
        const helpElement = document.getElementById(videoType + 'Help');
        if (helpElement) {
            helpElement.style.display = 'inline';
        }
    }
    
    // Update URL field requirements and placeholder
    const urlField = document.getElementById('videoUrl');
    if (videoType === 'youtube') {
        urlField.placeholder = 'https://www.youtube.com/watch?v=VIDEO_ID';
    } else if (videoType === 'vimeo') {
        urlField.placeholder = 'https://vimeo.com/VIDEO_ID';
    } else if (videoType === 'url') {
        urlField.placeholder = 'https://example.com/video.mp4';
    } else if (videoType === 'file') {
        urlField.placeholder = 'temporary-filename (optional)';
        urlField.removeAttribute('required');
    } else {
        urlField.placeholder = 'Enter video URL';
        urlField.setAttribute('required', 'required');
    }
});

// Trigger change event on page load if there's an old value
document.addEventListener('DOMContentLoaded', function() {
    const videoTypeSelect = document.getElementById('videoType');
    if (videoTypeSelect.value) {
        videoTypeSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection