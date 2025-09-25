@extends('layouts.master')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Exercises for "{{ $workout->name }}"
                </div>
                <div class="prism-toggle">
                    <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-sm btn-primary-light me-2">
                        <i class="ri-arrow-left-line"></i> Back to Workout
                    </a>
                    <a href="{{ route('workout-exercises.create', $workout->id) }}" class="btn btn-sm btn-primary">
                        <i class="ri-add-line"></i> Add Exercise
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($exercises->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th>Order</th>
                                    <th>Exercise</th>
                                    <th>Sets</th>
                                    <th>Reps</th>
                                    <th>Weight</th>
                                    <th>Duration</th>
                                    <th>Rest</th>
                                    <th>Tempo</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-exercises">
                                @foreach($exercises as $exercise)
                                    <tr data-id="{{ $exercise->id }}">
                                        <td>
                                            <span class="badge bg-primary-transparent">{{ $exercise->order }}</span>
                                            <i class="ri-drag-move-2-line ms-2 text-muted" style="cursor: move;"></i>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $exercise->exercise->name ?? 'Exercise #' . $exercise->id }}</div>
                                            @if($exercise->notes)
                                                <small class="text-muted">{{ Str::limit($exercise->notes, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $exercise->sets ?? '-' }}</td>
                                        <td>{{ $exercise->reps ?? '-' }}</td>
                                        <td>{{ $exercise->weight ? $exercise->weight . ' kg' : '-' }}</td>
                                        <td>{{ $exercise->duration ? $exercise->duration . 's' : '-' }}</td>
                                        <td>{{ $exercise->rest_interval ? $exercise->rest_interval . 's' : '-' }}</td>
                                        <td>{{ $exercise->tempo ?? '-' }}</td>
                                        <td>
                                            @if($exercise->is_active)
                                                <span class="badge bg-success-transparent">Active</span>
                                            @else
                                                <span class="badge bg-light text-dark">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('workout-exercises.show', [$workout->id, $exercise->id]) }}" class="btn btn-sm btn-info" title="View">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <a href="{{ route('workout-exercises.edit', [$workout->id, $exercise->id]) }}" class="btn btn-sm btn-success" title="Edit">
                                                    <i class="ri-edit-2-line"></i>
                                                </a>
                                                <form action="{{ route('workout-exercises.destroy', [$workout->id, $exercise->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this exercise?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="ri-delete-bin-5-line"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($exercises->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $exercises->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="ri-fitness-line" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="mt-3 text-muted">No Exercises Found</h5>
                        <p class="text-muted">This workout doesn't have any exercises yet.</p>
                        <a href="{{ route('workout-exercises.create', $workout->id) }}" class="btn btn-primary">
                            <i class="ri-add-line"></i> Add First Exercise
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Workout Summary Card -->
<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Workout Summary</div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="mb-1">{{ $exercises->count() }}</h4>
                            <p class="text-muted mb-0">Total Exercises</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="mb-1">{{ $exercises->where('is_active', 1)->count() }}</h4>
                            <p class="text-muted mb-0">Active Exercises</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="mb-1">{{ $exercises->sum('sets') ?: 0 }}</h4>
                            <p class="text-muted mb-0">Total Sets</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="mb-1">{{ $workout->formatted_duration }}</h4>
                            <p class="text-muted mb-0">Workout Duration</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sortable for exercise reordering
    const sortableElement = document.getElementById('sortable-exercises');
    if (sortableElement) {
        const sortable = Sortable.create(sortableElement, {
            handle: '.ri-drag-move-2-line',
            animation: 150,
            onEnd: function(evt) {
                const exerciseIds = Array.from(sortableElement.children).map(row => row.dataset.id);
                
                // Send AJAX request to update order
                fetch(`{{ route('workout-exercises.reorder', $workout->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        exercise_ids: exerciseIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update order badges
                        sortableElement.querySelectorAll('tr').forEach((row, index) => {
                            const badge = row.querySelector('.badge');
                            if (badge) {
                                badge.textContent = index + 1;
                            }
                        });
                        
                        // Show success message
                        showToast('Exercise order updated successfully!', 'success');
                    } else {
                        showToast('Failed to update exercise order', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while updating exercise order', 'error');
                });
            }
        });
    }
});

function showToast(message, type = 'info') {
    // Simple toast notification (you can replace with your preferred toast library)
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}
</script>
@endsection