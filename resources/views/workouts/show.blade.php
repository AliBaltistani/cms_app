@extends('layouts.master')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Workout Details
                </div>
                <div class="prism-toggle">
                    <a href="{{route('workouts.index')}}" class="btn btn-sm btn-primary-light me-2">Back</a>
                    <a href="{{ route('workouts.edit', $workout->id) }}" class="btn btn-sm btn-success">Edit</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Workout Basic Info -->
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <h3>{{ $workout->name }}</h3>
                                @if($workout->description)
                                    <p class="text-muted">{{ $workout->description }}</p>
                                @endif
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Duration</label>
                                <div class="fw-bold">{{ $workout->formatted_duration }}</div>
                            </div>
                            
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <div>
                                    {!! $workout->is_active ? '<span class="badge bg-success-transparent">Active</span>' : '<span class="badge bg-light text-dark">Inactive</span>' !!}
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Workout Thumbnail -->
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Thumbnail</label>
                            <div class="border rounded p-3 text-center">
                                @if($workout->thumbnail)
                                    <img src="{{ Storage::url($workout->thumbnail) }}" alt="{{ $workout->name }}" class="img-fluid rounded" style="max-width: 100%; max-height: 200px;">
                                @else
                                    <i class="ri-image-line" style="font-size: 3rem; color: #ccc;"></i>
                                    <div class="text-muted mt-2">No thumbnail</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Videos Section -->
        <div class="card custom-card mt-4">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Workout Videos ({{ $workout->videos->count() }})
                </div>
                <div class="prism-toggle">
                    <a href="{{ route('workout-videos.create', $workout->id) }}" class="btn btn-sm btn-primary-light">Add Video</a>
                </div>
            </div>
            <div class="card-body">
                @if($workout->videos->count() > 0)
                    <div class="row">
                        @foreach($workout->videos as $video)
                            <div class="col-md-6 mb-4">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-1">{{ $video->title }}</h6>
                                            <span class="badge bg-primary-transparent">{{ $video->order }}</span>
                                        </div>
                                        
                                        @if($video->description)
                                            <p class="card-text text-muted small mb-2">{{ Str::limit($video->description, 100) }}</p>
                                        @endif
                                        
                                        <div class="row g-2 mb-3">
                                            <div class="col-4">
                                                <small class="text-muted">Duration:</small>
                                                <div class="fw-bold">{{ $video->formatted_duration }}</div>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted">Type:</small>
                                                <div>
                                                    <span class="badge bg-info-transparent">{{ ucfirst($video->video_type) }}</span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                @if($video->is_preview)
                                                    <span class="badge bg-warning-transparent">Preview</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Video Thumbnail/Preview -->
                                        <div class="mb-3">
                                            @if($video->thumbnail)
                                                <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" class="img-fluid rounded" style="width: 100%; height: 150px; object-fit: cover;">
                                            @else
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 150px;">
                                                    <i class="ri-video-line" style="font-size: 2rem; color: #ccc;"></i>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Video URL -->
                                        <div class="mb-3">
                                            <small class="text-muted">Video URL:</small>
                                            <div class="small">
                                                <a href="{{ $video->video_url }}" target="_blank" rel="noopener" class="text-decoration-none">
                                                    {{ Str::limit($video->video_url, 40) }}
                                                    <i class="ri-external-link-line ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="btn-group w-100" role="group">
                                            <a href="{{ route('workout-videos.edit', [$workout->id, $video->id]) }}" class="btn btn-sm btn-success">
                                                <i class="ri-edit-2-line"></i> Edit
                                            </a>
                                            <form action="{{ route('workout-videos.destroy', [$workout->id, $video->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this video?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="ri-delete-bin-5-line"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="ri-video-line" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="mt-3 text-muted">No Videos Added</h5>
                        <p class="text-muted">This workout doesn't have any videos yet.</p>
                        <a href="{{ route('workout-videos.create', $workout->id) }}" class="btn btn-primary">Add First Video</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection