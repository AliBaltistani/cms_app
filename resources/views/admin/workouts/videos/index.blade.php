@extends('layouts.master')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Videos for "{{ $workout->name }}"
                </div>
                <div class="prism-toggle">
                    <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-sm btn-secondary me-2">
                        <i class="ri-arrow-left-line"></i> Back to Workout
                    </a>
                    <a href="{{ route('workout-videos.create', $workout->id) }}" class="btn btn-sm btn-primary">
                        <i class="ri-add-line"></i> Add Video
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($videos->count() > 0)
                    <div class="table-responsive">
                        <table class="table text-nowrap table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">Order</th>
                                    <th scope="col">Title</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Duration</th>
                                    <th scope="col">Preview</th>
                                    <th scope="col">Created</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($videos as $video)
                                <tr>
                                    <td>{{ $video->order }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($video->thumbnail)
                                                <img src="{{ Storage::url($video->thumbnail) }}" alt="Thumbnail" class="rounded me-2" width="40" height="30" style="object-fit: cover;">
                                            @else
                                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 30px;">
                                                    <i class="ri-video-line text-muted"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <strong>{{ $video->title }}</strong>
                                                @if($video->description)
                                                    <br><small class="text-muted">{{ Str::limit($video->description, 50) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $video->video_type === 'youtube' ? 'danger' : ($video->video_type === 'vimeo' ? 'info' : ($video->video_type === 'file' ? 'success' : 'primary')) }}-transparent">
                                            {{ ucfirst($video->video_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($video->duration)
                                            {{ gmdate('H:i:s', $video->duration) }}
                                        @else
                                            <span class="text-muted">Unknown</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($video->is_preview)
                                            <span class="badge bg-warning-transparent">Preview</span>
                                        @else
                                            <span class="text-muted">No</span>
                                        @endif
                                    </td>
                                    <td>{{ $video->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('workout-videos.edit', [$workout->id, $video->id]) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ri-edit-line"></i>
                                            </a>
                                            <form method="POST" action="{{ route('workout-videos.destroy', [$workout->id, $video->id]) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this video?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($videos->count() > 1)
                        <div class="mt-3">
                            <a href="{{ route('workout-videos.reorder-form', $workout->id) }}" class="btn btn-outline-secondary">
                                <i class="ri-drag-move-line"></i> Reorder Videos
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="ri-video-line display-4 text-muted"></i>
                        <h5 class="mt-3 text-muted">No Videos Added</h5>
                        <p class="text-muted">This workout doesn't have any videos yet.</p>
                        <a href="{{ route('workout-videos.create', $workout->id) }}" class="btn btn-primary">
                            <i class="ri-add-line"></i> Add First Video
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection