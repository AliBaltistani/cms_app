@extends('layouts.master')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Reorder Videos</h1>
            <p class="mb-0 text-muted">Drag to reorder videos for "{{ $program->name }}"</p>
        </div>
        <a href="{{ route('trainer.program-videos.index', $program->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Videos
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Reorder Videos</h6>
                </div>
                <div class="card-body">
                    <div id="videosList" class="list-group">
                        @foreach($videos as $video)
                        <div class="list-group-item list-group-item-action p-3 mb-2 video-item" draggable="true" data-video-id="{{ $video->id }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <i class="fas fa-grip-vertical text-muted me-3" style="cursor: grab;"></i>
                                    @if($video->thumbnail)
                                        <img src="{{ Storage::url($video->thumbnail) }}" alt="Thumbnail" class="rounded me-3" width="50" height="40" style="object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 40px;">
                                            <i class="fas fa-video text-muted"></i>
                                        </div>
                                    @endif
                                    <div class="flex-grow-1">
                                        <strong>{{ $video->title }}</strong>
                                        <br>
                                        <small class="text-muted">{{ ucfirst($video->video_type) }}</small>
                                    </div>
                                </div>
                                <span class="badge bg-primary badge-order">{{ $video->order }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="saveOrder">
                            <i class="fas fa-save me-2"></i>Save Order
                        </button>
                        <a href="{{ route('trainer.program-videos.index', $program->id) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Instructions</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>How to Reorder</h6>
                        <ol class="mb-0 small">
                            <li>Drag each video to your preferred position</li>
                            <li>The order will update automatically</li>
                            <li>Click "Save Order" to apply changes</li>
                            <li>Or click "Cancel" to discard changes</li>
                        </ol>
                    </div>

                    <div class="alert alert-secondary">
                        <h6 class="mb-2"><i class="fas fa-video me-2"></i>Total Videos</h6>
                        <p class="mb-0">{{ $videos->count() }} video(s)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.video-item {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
    background-color: #fff;
}

.video-item:hover {
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.video-item.dragging {
    opacity: 0.5;
    background-color: #e7f3ff;
    border-color: #0d6efd;
}

.video-item.drag-over {
    background-color: #e7f3ff;
    border-color: #0d6efd;
    border-top: 3px solid #0d6efd;
}

.badge-order {
    font-size: 1rem;
    padding: 0.5rem 0.75rem;
}

i[style*="cursor: grab"] {
    cursor: grab;
}

i[style*="cursor: grab"]:active {
    cursor: grabbing;
}
</style>

<script>
let draggedElement = null;

const videosList = document.getElementById('videosList');
const videoItems = document.querySelectorAll('.video-item');
const saveButton = document.getElementById('saveOrder');

// Add drag event listeners to all videos
videoItems.forEach(item => {
    item.addEventListener('dragstart', handleDragStart);
    item.addEventListener('dragend', handleDragEnd);
    item.addEventListener('dragover', handleDragOver);
    item.addEventListener('drop', handleDrop);
    item.addEventListener('dragleave', handleDragLeave);
});

function handleDragStart(e) {
    draggedElement = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    document.querySelectorAll('.video-item').forEach(item => {
        item.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    
    if (this !== draggedElement) {
        this.classList.add('drag-over');
    }
    return false;
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    
    if (draggedElement !== this) {
        // Swap elements
        videosList.insertBefore(draggedElement, this);
        updateOrderBadges();
    }
    
    return false;
}

function updateOrderBadges() {
    const items = document.querySelectorAll('.video-item');
    items.forEach((item, index) => {
        item.querySelector('.badge-order').textContent = index + 1;
    });
}

saveButton.addEventListener('click', function() {
    const videoIds = Array.from(document.querySelectorAll('.video-item'))
        .map(item => item.getAttribute('data-video-id'));

    fetch('{{ route("trainer.program-videos.reorder", $program->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ video_ids: videoIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '{{ route("trainer.program-videos.index", $program->id) }}';
        } else {
            alert('Error saving order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving order');
    });
});

// Initialize order badges
updateOrderBadges();
</script>
@endsection
