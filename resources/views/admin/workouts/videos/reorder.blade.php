@extends('layouts.master')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Reorder Videos for "{{ $workout->name }}"
                </div>
                <div class="prism-toggle">
                    <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-sm btn-secondary">
                        <i class="ri-arrow-left-line"></i> Back to Workout
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($videos->count() > 0)
                    <div class="alert alert-info">
                        <i class="ri-information-line"></i>
                        Drag and drop the videos below to reorder them. Click "Save Order" when you're done.
                    </div>
                    
                    <form method="POST" action="{{ route('workout-videos.reorder', $workout->id) }}" id="reorderForm">
                        @csrf
                        @method('PATCH')
                        
                        <div id="sortable-videos" class="list-group">
                            @foreach($videos as $video)
                            <div class="list-group-item d-flex align-items-center" data-video-id="{{ $video->id }}">
                                <div class="drag-handle me-3" style="cursor: move;">
                                    <i class="ri-drag-move-2-line text-muted"></i>
                                </div>
                                
                                @if($video->thumbnail)
                                    <img src="{{ Storage::url($video->thumbnail) }}" alt="Thumbnail" class="rounded me-3" width="60" height="40" style="object-fit: cover;">
                                @else
                                    <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 40px;">
                                        <i class="ri-video-line text-muted"></i>
                                    </div>
                                @endif
                                
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $video->title }}</h6>
                                    <div class="d-flex align-items-center text-muted small">
                                        <span class="badge bg-{{ $video->video_type === 'youtube' ? 'danger' : ($video->video_type === 'vimeo' ? 'info' : ($video->video_type === 'file' ? 'success' : 'primary')) }}-transparent me-2">
                                            {{ ucfirst($video->video_type) }}
                                        </span>
                                        @if($video->duration)
                                            <span class="me-2">{{ gmdate('H:i:s', $video->duration) }}</span>
                                        @endif
                                        @if($video->is_preview)
                                            <span class="badge bg-warning-transparent">Preview</span>
                                        @endif
                                    </div>
                                    @if($video->description)
                                        <p class="mb-0 text-muted small">{{ Str::limit($video->description, 100) }}</p>
                                    @endif
                                </div>
                                
                                <div class="order-number badge bg-primary-transparent">
                                    #<span class="order-display">{{ $video->order }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" id="saveOrderBtn">
                                <i class="ri-save-line"></i> Save Order
                            </button>
                            <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                        
                        <!-- Hidden input to store video order -->
                        <input type="hidden" name="video_ids" id="videoIds" value="">
                    </form>
                @else
                    <div class="text-center py-5">
                        <i class="ri-video-line display-4 text-muted"></i>
                        <h5 class="mt-3 text-muted">No Videos to Reorder</h5>
                        <p class="text-muted">This workout doesn't have any videos yet.</p>
                        <a href="{{ route('workout-videos.create', $workout->id) }}" class="btn btn-primary">
                            <i class="ri-add-line"></i> Add Videos
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Include SortableJS for drag and drop functionality -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sortableContainer = document.getElementById('sortable-videos');
    const videoIdsInput = document.getElementById('videoIds');
    const saveOrderBtn = document.getElementById('saveOrderBtn');
    
    if (sortableContainer) {
        // Initialize Sortable
        const sortable = Sortable.create(sortableContainer, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                updateOrderNumbers();
                updateVideoIds();
            }
        });
        
        // Function to update order numbers display
        function updateOrderNumbers() {
            const items = sortableContainer.querySelectorAll('.list-group-item');
            items.forEach((item, index) => {
                const orderDisplay = item.querySelector('.order-display');
                if (orderDisplay) {
                    orderDisplay.textContent = index + 1;
                }
            });
        }
        
        // Function to update hidden input with video IDs in new order
        function updateVideoIds() {
            const items = sortableContainer.querySelectorAll('.list-group-item');
            const videoIds = Array.from(items).map(item => item.dataset.videoId);
            videoIdsInput.value = JSON.stringify(videoIds);
        }
        
        // Initialize video IDs on page load
        updateVideoIds();
        
        // Form submission handler
        document.getElementById('reorderForm').addEventListener('submit', function(e) {
            // Convert JSON string to array for form submission
            const videoIds = JSON.parse(videoIdsInput.value);
            
            // Remove the JSON input and add individual inputs for each video ID
            videoIdsInput.remove();
            
            videoIds.forEach((videoId, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'video_ids[]';
                input.value = videoId;
                this.appendChild(input);
            });
        });
    }
});
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
}

.sortable-chosen {
    background-color: #f8f9fa;
}

.sortable-drag {
    background-color: #ffffff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.list-group-item {
    border: 1px solid #dee2e6;
    margin-bottom: 0.5rem;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.drag-handle:hover {
    color: #0d6efd !important;
}

.order-number {
    font-size: 0.875rem;
    min-width: 40px;
}
</style>
@endsection