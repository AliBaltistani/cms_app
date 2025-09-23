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
                                <label class="form-label">Price</label>
                                <div class="fw-bold">
                                    <span class="badge bg-success-transparent">{{ $workout->formatted_price }}</span>
                                </div>
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
                                        
                                        <!-- Video Player -->
                                        <div class="mb-3">
                                            @if($video->video_type === 'youtube')
                                                <!-- YouTube Video -->
                                                <div class="ratio ratio-16x9">
                                                    <iframe src="{{ $video->embed_url }}" 
                                                            title="{{ $video->title }}" 
                                                            frameborder="0" 
                                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                            allowfullscreen
                                                            class="rounded">
                                                    </iframe>
                                                </div>
                                            @elseif($video->video_type === 'vimeo')
                                                <!-- Vimeo Video -->
                                                <div class="ratio ratio-16x9">
                                                    <iframe src="{{ $video->embed_url }}" 
                                                            title="{{ $video->title }}" 
                                                            frameborder="0" 
                                                            allow="autoplay; fullscreen; picture-in-picture" 
                                                            allowfullscreen
                                                            class="rounded">
                                                    </iframe>
                                                </div>
                                            @elseif($video->video_type === 'file' || $video->video_type === 'url')
                                                <!-- Local File or Direct URL Video -->
                                                <video controls class="w-100 rounded" style="max-height: 300px;">
                                                    <source src="{{ $video->video_file_url }}" type="video/mp4">
                                                    <source src="{{ $video->video_file_url }}" type="video/webm">
                                                    <source src="{{ $video->video_file_url }}" type="video/ogg">
                                                    Your browser does not support the video tag.
                                                    <p>Your browser doesn't support HTML5 video. 
                                                       <a href="{{ $video->video_file_url }}">Download the video</a> instead.
                                                    </p>
                                                </video>
                                            @else
                                                <!-- Fallback for unknown video types -->
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                                    <div class="text-center">
                                                        <i class="ri-video-line" style="font-size: 2rem; color: #ccc;"></i>
                                                        <div class="mt-2 text-muted">Video Preview Not Available</div>
                                                        <a href="{{ $video->video_url }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                            <i class="ri-external-link-line"></i> Open Video
                                                        </a>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            <!-- Video Thumbnail Overlay (Optional) -->
                                            @if($video->thumbnail && ($video->video_type === 'file' || $video->video_type === 'url'))
                                                <div class="mt-2">
                                                    <small class="text-muted">Thumbnail:</small>
                                                    <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" class="img-thumbnail" style="max-width: 100px; max-height: 60px;">
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Video URL -->
                                        <div class="mb-3">
                                            <small class="text-muted">Video URL:</small>
                                            <div class="small">
                                                <a href="{{ $video->video_file_url }}" target="_blank" rel="noopener" class="text-decoration-none">
                                                    {{ Str::limit($video->video_file_url, 40) }}
                                                    <i class="ri-external-link-line ms-1"></i>
                                                </a>
                                            </div>
                                            @if($video->video_type === 'file')
                                                <div class="mt-1">
                                                    <span class="badge bg-success-transparent">Local File</span>
                                                </div>
                                            @endif
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
        
        <!-- Exercises Section -->
        <div class="card custom-card mt-4">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Workout Exercises ({{ $workout->workoutExercises->count() }})
                </div>
                <div class="prism-toggle">
                    <a href="{{ route('workout-exercises.create', $workout->id) }}" class="btn btn-sm btn-primary-light">Add Exercise</a>
                </div>
            </div>
            <div class="card-body">
                @if($workout->workoutExercises->count() > 0)
                    <div class="exercises-container">
                        @foreach($workout->workoutExercises as $workoutExercise)
                            <div class="exercise-card mb-4 border rounded p-3">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="exercise-header">
                                        <h6 class="mb-1">
                                            <span class="badge bg-primary-transparent me-2">{{ $workoutExercise->order }}</span>
                                            {{ $workoutExercise->exercise->name ?? 'Exercise #' . $workoutExercise->id }}
                                        </h6>
                                        @if($workoutExercise->notes)
                                            <p class="text-muted small mb-0">{{ $workoutExercise->notes }}</p>
                                        @endif
                                    </div>
                                    <div class="exercise-actions">
                                        <a href="{{ route('workout-exercises.edit', [$workout->id, $workoutExercise->id]) }}" class="btn btn-sm btn-success me-1">
                                            <i class="ri-edit-2-line"></i>
                                        </a>
                                        <form action="{{ route('workout-exercises.destroy', [$workout->id, $workoutExercise->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this exercise?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Exercise Details -->
                                <div class="row g-3 mb-3">
                                    @if($workoutExercise->sets)
                                        <div class="col-md-2">
                                            <small class="text-muted">Sets:</small>
                                            <div class="fw-bold">{{ $workoutExercise->sets }}</div>
                                        </div>
                                    @endif
                                    @if($workoutExercise->reps)
                                        <div class="col-md-2">
                                            <small class="text-muted">Reps:</small>
                                            <div class="fw-bold">{{ $workoutExercise->reps }}</div>
                                        </div>
                                    @endif
                                    @if($workoutExercise->weight)
                                        <div class="col-md-2">
                                            <small class="text-muted">Weight:</small>
                                            <div class="fw-bold">{{ $workoutExercise->weight }} kg</div>
                                        </div>
                                    @endif
                                    @if($workoutExercise->duration)
                                        <div class="col-md-2">
                                            <small class="text-muted">Duration:</small>
                                            <div class="fw-bold">{{ $workoutExercise->duration }}s</div>
                                        </div>
                                    @endif
                                    @if($workoutExercise->rest_interval)
                                        <div class="col-md-2">
                                            <small class="text-muted">Rest:</small>
                                            <div class="fw-bold">{{ $workoutExercise->rest_interval }}s</div>
                                        </div>
                                    @endif
                                    @if($workoutExercise->tempo)
                                        <div class="col-md-2">
                                            <small class="text-muted">Tempo:</small>
                                            <div class="fw-bold">{{ $workoutExercise->tempo }}</div>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Exercise Sets -->
                                @if($workoutExercise->exerciseSets->count() > 0)
                                    <div class="sets-section">
                                        <h6 class="mb-2">Sets Details:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Set</th>
                                                        <th>Reps</th>
                                                        <th>Weight (kg)</th>
                                                        <th>Duration (s)</th>
                                                        <th>Rest (s)</th>
                                                        <th>Notes</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($workoutExercise->exerciseSets as $set)
                                                        <tr>
                                                            <td>{{ $set->set_number }}</td>
                                                            <td>{{ $set->reps ?? '-' }}</td>
                                                            <td>{{ $set->weight ?? '-' }}</td>
                                                            <td>{{ $set->duration ?? '-' }}</td>
                                                            <td>{{ $set->rest_time ?? '-' }}</td>
                                                            <td>{{ $set->notes ?? '-' }}</td>
                                                            <td>
                                                                @if($set->is_completed)
                                                                    <span class="badge bg-success-transparent">Completed</span>
                                                                @else
                                                                    <span class="badge bg-warning-transparent">Pending</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Exercise Status -->
                                <div class="mt-2">
                                    @if($workoutExercise->is_active)
                                        <span class="badge bg-success-transparent">Active</span>
                                    @else
                                        <span class="badge bg-light text-dark">Inactive</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="ri-fitness-line" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="mt-3 text-muted">No Exercises Added</h5>
                        <p class="text-muted">This workout doesn't have any exercises yet.</p>
                        <a href="{{ route('workout-exercises.create', $workout->id) }}" class="btn btn-primary">Add First Exercise</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.exercise-card {
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.exercise-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.exercise-header h6 {
    color: #495057;
    font-weight: 600;
}

.exercises-container .table th {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
}

.exercises-container .table td {
    font-size: 0.875rem;
    vertical-align: middle;
}
</style>

@endsection