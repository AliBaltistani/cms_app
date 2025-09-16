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
                                        
                                        <!-- Video URL Field -->
                        <div class="col-md-12 mb-3" id="videoUrlField">
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
                        
                        <!-- Video File Upload Field -->
                        <div class="col-md-12 mb-3" id="videoFileField" style="display: none;">
                            <label class="form-label">Upload Video File</label>
                            <input type="file" class="form-control @error('video_file') is-invalid @enderror" name="video_file" id="videoFile" accept="video/*">
                            <small class="text-muted">Supported formats: MP4, AVI, MOV, WMV, FLV, WebM, MKV (Max: 100MB)</small>
                            @error('video_file')
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
    const urlField = document.getElementById('videoUrl');
    const fileField = document.getElementById('videoFile');
    const urlFieldContainer = document.getElementById('videoUrlField');
    const fileFieldContainer = document.getElementById('videoFileField');
    
    // Hide all help texts
    helps.forEach(help => help.style.display = 'none');
    
    // Show/hide appropriate fields based on video type
    if (videoType === 'file') {
        // Show file upload field, hide URL field
        urlFieldContainer.style.display = 'none';
        fileFieldContainer.style.display = 'block';
        
        // Remove required from URL field, add to file field
        urlField.removeAttribute('required');
        fileField.setAttribute('required', 'required');
        
        // Show file help text
        const fileHelpElement = document.getElementById('fileHelp');
        if (fileHelpElement) {
            fileHelpElement.style.display = 'inline';
        }
    } else {
        // Show URL field, hide file upload field
        urlFieldContainer.style.display = 'block';
        fileFieldContainer.style.display = 'none';
        
        // Add required to URL field, remove from file field
        urlField.setAttribute('required', 'required');
        fileField.removeAttribute('required');
        
        // Show relevant help text and update placeholder
        if (videoType) {
            const helpElement = document.getElementById(videoType + 'Help');
            if (helpElement) {
                helpElement.style.display = 'inline';
            }
        }
        
        // Update URL field placeholder
        if (videoType === 'youtube') {
            urlField.placeholder = 'https://www.youtube.com/watch?v=VIDEO_ID';
        } else if (videoType === 'vimeo') {
            urlField.placeholder = 'https://vimeo.com/VIDEO_ID';
        } else if (videoType === 'url') {
            urlField.placeholder = 'https://example.com/video.mp4';
        } else {
            urlField.placeholder = 'Enter video URL';
        }
    }
});

// Trigger change event on page load if there's an old value
document.addEventListener('DOMContentLoaded', function() {
    const videoTypeSelect = document.getElementById('videoType');
    if (videoTypeSelect.value) {
        videoTypeSelect.dispatchEvent(new Event('change'));
    }
    
    // Handle file size validation
    const fileInput = document.getElementById('videoFile');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 100 * 1024 * 1024; // 100MB in bytes
                if (file.size > maxSize) {
                    alert('File size exceeds 100MB limit. Please choose a smaller file.');
                    this.value = '';
                    return;
                }
                
                // Show file name and size
                const fileName = file.name;
                const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                console.log(`Selected file: ${fileName} (${fileSize} MB)`);
            }
        });
    }
});
</script>
@endsection