@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-video me-2" style="color: rgb(255, 106, 0);"></i>Program Videos
            </h1>
            <p class="mb-0 text-muted">Manage videos for "{{ $program->name }}"</p>
        </div>
        <div>
            <a href="{{ route('program-builder.show', $program->id) }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Program Builder
            </a>
            <a href="{{ route('program-videos.create', $program->id) }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Video
            </a>
        </div>
    </div>

    <!-- Videos List -->
    @if($videos->count() > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Videos ({{ $videos->count() }})</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Order</th>
                            <th>Title</th>
                            <th style="width: 120px;">Type</th>
                            <th style="width: 100px;">Duration</th>
                            <th style="width: 80px;">Preview</th>
                            <th style="width: 120px;">Created</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($videos as $video)
                        <tr>
                            <td class="text-center">
                                <span class="badge bg-primary">{{ $video->order }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($video->thumbnail)
                                        <img src="{{ Storage::url($video->thumbnail) }}" alt="Thumbnail" class="rounded me-2" width="40" height="40" style="object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-video text-muted"></i>
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
                                    <small class="text-muted">{{ $video->formatted_duration }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($video->is_preview)
                                    <span class="badge bg-warning">Yes</span>
                                @else
                                    <span class="text-muted">No</span>
                                @endif
                            </td>
                            <td>{{ $video->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('program-videos.edit', [$program->id, $video->id]) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('program-videos.destroy', [$program->id, $video->id]) }}" class="d-inline" onsubmit="return confirm('Delete this video?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
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
            <div class="mt-3 border-top pt-3">
                <a href="{{ route('program-videos.reorder-form', $program->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrows-alt me-2"></i>Reorder Videos
                </a>
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="card shadow">
        <div class="card-body text-center py-5">
            <i class="fas fa-video display-4 text-muted"></i>
            <h5 class="mt-3 text-muted">No Videos Added</h5>
            <p class="text-muted mb-3">This program doesn't have any videos yet.</p>
            <a href="{{ route('program-videos.create', $program->id) }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add First Video
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
