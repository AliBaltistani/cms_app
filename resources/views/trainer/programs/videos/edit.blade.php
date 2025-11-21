@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Edit Video</h1>
            <p class="mb-0 text-muted">Update "{{ $video->title }}" for "{{ $program->name }}"</p>
        </div>
        <a href="{{ route('trainer.program-videos.index', $program->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Videos
        </a>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Edit Video Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('trainer.program-videos.update', [$program->id, $video->id]) }}" method="POST" enctype="multipart/form-data" id="videoForm">
                        @csrf
                        @method('PUT')

                        <!-- Video Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Video Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" placeholder="Enter video title" value="{{ old('title', $video->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Video Type -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="video_type" class="form-label">Video Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('video_type') is-invalid @enderror" id="video_type" name="video_type" required>
                                    <option value="">Select video type</option>
                                    <option value="youtube" {{ old('video_type', $video->video_type) == 'youtube' ? 'selected' : '' }}>YouTube</option>
                                    <option value="vimeo" {{ old('video_type', $video->video_type) == 'vimeo' ? 'selected' : '' }}>Vimeo</option>
                                    <option value="url" {{ old('video_type', $video->video_type) == 'url' ? 'selected' : '' }}>Direct URL</option>
                                    <option value="file" {{ old('video_type', $video->video_type) == 'file' ? 'selected' : '' }}>Upload File</option>
                                </select>
                                @error('video_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="order" class="form-label">Order</label>
                                <input type="number" class="form-control @error('order') is-invalid @enderror" id="order" name="order" placeholder="Video order" min="0" value="{{ old('order', $video->order) }}">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Video URL Field -->
                        <div class="mb-3" id="videoUrlField">
                            <label for="video_url" class="form-label">Video URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control @error('video_url') is-invalid @enderror" id="video_url" name="video_url" placeholder="Enter video URL" value="{{ old('video_url', $video->video_url) }}" required>
                            <small class="form-text text-muted" id="urlHelp">
                                <span id="youtubeHelp" style="display: none;">YouTube URL (e.g., https://www.youtube.com/watch?v=VIDEO_ID)</span>
                                <span id="vimeoHelp" style="display: none;">Vimeo URL (e.g., https://vimeo.com/VIDEO_ID)</span>
                                <span id="urlHelpDirect" style="display: none;">Direct video URL (e.g., https://example.com/video.mp4)</span>
                                <span id="fileHelp" style="display: none;">Leave empty for file upload</span>
                            </small>
                            @error('video_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Video File Upload Field -->
                        <div class="mb-3" id="videoFileField" style="display: none;">
                            <label for="video_file" class="form-label">Upload Video File</label>
                            <input type="file" class="form-control @error('video_file') is-invalid @enderror" id="video_file" name="video_file" accept="video/*">
                            <small class="form-text text-muted">Supported formats: MP4, AVI, MOV, WMV, FLV, WebM, MKV (Max: 100MB)</small>
                            @if($video->video_type === 'file' && $video->video_url)
                                <div class="alert alert-info mt-2">
                                    <small><i class="fas fa-check-circle me-2"></i>Current file uploaded</small>
                                </div>
                            @endif
                            @error('video_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Enter video description">{{ old('description', $video->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Duration and Thumbnail -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="duration" class="form-label">Duration (seconds)</label>
                                <input type="number" class="form-control @error('duration') is-invalid @enderror" id="duration" name="duration" placeholder="Duration in seconds" min="1" value="{{ old('duration', $video->duration) }}">
                                <small class="form-text text-muted">Leave blank if unknown</small>
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="thumbnail" class="form-label">Thumbnail</label>
                                <input type="file" class="form-control @error('thumbnail') is-invalid @enderror" id="thumbnail" name="thumbnail" accept="image/*">
                                <small class="form-text text-muted">JPEG, PNG, GIF, WebP (max 2MB)</small>
                                @if($video->thumbnail)
                                    <div class="mt-2">
                                        <img src="{{ Storage::url($video->thumbnail) }}" alt="Current Thumbnail" class="rounded" width="80" height="60" style="object-fit: cover;">
                                    </div>
                                @endif
                                @error('thumbnail')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Preview Option -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_preview" value="1" id="is_preview" {{ old('is_preview', $video->is_preview) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_preview">
                                    This is a preview video
                                </label>
                                <small class="d-block text-muted">Preview videos are shown before users start the program</small>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Video
                            </button>
                            <a href="{{ route('trainer.program-videos.index', $program->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Video Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Type:</strong><br>
                        <span class="badge bg-{{ $video->video_type === 'youtube' ? 'danger' : ($video->video_type === 'vimeo' ? 'info' : ($video->video_type === 'file' ? 'success' : 'primary')) }}-transparent">
                            {{ ucfirst($video->video_type) }}
                        </span>
                    </div>

                    @if($video->duration)
                    <div class="mb-3">
                        <strong>Duration:</strong><br>
                        <small class="text-muted">{{ $video->formatted_duration }}</small>
                    </div>
                    @endif

                    <div class="mb-3">
                        <strong>Created:</strong><br>
                        <small class="text-muted">{{ $video->created_at->format('d/m/Y H:i') }}</small>
                    </div>

                    <div class="mb-3">
                        <strong>Updated:</strong><br>
                        <small class="text-muted">{{ $video->updated_at->format('d/m/Y H:i') }}</small>
                    </div>

                    <hr>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>Tips</h6>
                        <ul class="mb-0 small">
                            <li>Update title and description</li>
                            <li>Change video type or URL</li>
                            <li>Upload new thumbnail</li>
                            <li>Adjust playback order</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Danger Zone</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('trainer.program-videos.destroy', [$program->id, $video->id]) }}" onsubmit="return confirm('Are you sure you want to delete this video?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash me-2"></i>Delete Video
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const videoTypeSelect = document.getElementById('video_type');
    const videoUrlField = document.getElementById('videoUrlField');
    const videoFileField = document.getElementById('videoFileField');
    const videoUrlInput = document.getElementById('video_url');
    const videoFileInput = document.getElementById('video_file');

    function updateFieldVisibility() {
        const videoType = videoTypeSelect.value;
        
        // Hide all help texts
        document.querySelectorAll('[id$="Help"]').forEach(el => el.style.display = 'none');

        if (videoType === 'file') {
            videoUrlField.style.display = 'none';
            videoFileField.style.display = 'block';
            videoUrlInput.removeAttribute('required');
            videoFileInput.removeAttribute('required');
            document.getElementById('fileHelp').style.display = 'inline';
        } else {
            videoUrlField.style.display = 'block';
            videoFileField.style.display = 'none';
            videoUrlInput.setAttribute('required', 'required');
            videoFileInput.removeAttribute('required');

            if (videoType === 'youtube') {
                document.getElementById('youtubeHelp').style.display = 'inline';
            } else if (videoType === 'vimeo') {
                document.getElementById('vimeoHelp').style.display = 'inline';
            } else if (videoType === 'url') {
                document.getElementById('urlHelpDirect').style.display = 'inline';
            }
        }
    }

    videoTypeSelect.addEventListener('change', updateFieldVisibility);

    // Initialize on load
    updateFieldVisibility();

    // File size validation
    videoFileInput.addEventListener('change', function() {
        if (this.files[0]) {
            const fileSize = this.files[0].size / (1024 * 1024);
            if (fileSize > 100) {
                alert('File size exceeds 100MB limit!');
                this.value = '';
            }
        }
    });
});
</script>
@endsection
