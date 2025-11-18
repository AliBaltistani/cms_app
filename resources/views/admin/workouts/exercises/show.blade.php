@extends('layouts.master')

@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Exercise Details
                </div>
                <div class="prism-toggle">
                    <a href="{{ route('workout-exercises.index', $workout->id) }}" class="btn btn-sm btn-primary-light me-2">
                        <i class="ri-arrow-left-line"></i> Back to Exercises
                    </a>
                    <a href="{{ route('workout-exercises.edit', [$workout->id, $workoutExercise->id]) }}" class="btn btn-sm btn-success">
                        <i class="ri-edit-2-line"></i> Edit
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Exercise Basic Info -->
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <h3>
                                    <span class="badge bg-primary-transparent me-2">{{ $workoutExercise->order }}</span>
                                    {{ $workoutExercise->exercise->name ?? 'Exercise #' . $workoutExercise->id }}
                                </h3>
                                <p class="text-muted">In workout: <strong>{{ $workout->name }}</strong></p>
                                @if($workoutExercise->notes)
                                    <div class="mt-3">
                                        <h6>Notes:</h6>
                                        <p class="text-muted">{{ $workoutExercise->notes }}</p>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Sets</label>
                                <div class="fw-bold">{{ $workoutExercise->sets ?? 'Not specified' }}</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Reps</label>
                                <div class="fw-bold">{{ $workoutExercise->reps ?? 'Not specified' }}</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Weight (lbs)</label>
                                <div class="fw-bold">
                                    {{ $workoutExercise->formatted_weight ?? 'Not specified' }}
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Duration</label>
                                <div class="fw-bold">
                                    {{ $workoutExercise->duration ? $workoutExercise->duration . ' seconds' : 'Not specified' }}
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Rest Interval</label>
                                <div class="fw-bold">
                                    {{ $workoutExercise->rest_interval ? $workoutExercise->rest_interval . ' seconds' : 'Not specified' }}
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tempo</label>
                                <div class="fw-bold">{{ $workoutExercise->tempo ?? 'Not specified' }}</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div>
                                    @if($workoutExercise->is_active)
                                        <span class="badge bg-success-transparent">Active</span>
                                    @else
                                        <span class="badge bg-light text-dark">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Created</label>
                                <div class="fw-bold">{{ $workoutExercise->created_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Exercise Image/Info -->
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Exercise Info</label>
                            <div class="border rounded p-3 text-center">
                                @if($workoutExercise->exercise && $workoutExercise->exercise->image)
                                    <img src="{{ Storage::url($workoutExercise->exercise->image) }}" alt="{{ $workoutExercise->exercise->name }}" class="img-fluid rounded" style="max-width: 100%; max-height: 200px;">
                                @else
                                    <i class="ri-fitness-line" style="font-size: 3rem; color: #ccc;"></i>
                                    <div class="text-muted mt-2">No exercise image</div>
                                @endif
                                
                                @if($workoutExercise->exercise)
                                    <div class="mt-3">
                                        <h6>{{ $workoutExercise->exercise->name }}</h6>
                                        @if($workoutExercise->exercise->description)
                                            <p class="text-muted small">{{ Str::limit($workoutExercise->exercise->description, 100) }}</p>
                                        @endif
                                        @if($workoutExercise->exercise->muscle_group)
                                            <span class="badge bg-info-transparent">{{ $workoutExercise->exercise->muscle_group }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Exercise Sets Section -->
        <div class="card custom-card mt-4">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Exercise Sets ({{ $workoutExercise->exerciseSets->count() }})
                </div>
                <div class="prism-toggle">
                    <button type="button" class="btn btn-sm btn-primary-light" data-bs-toggle="modal" data-bs-target="#addSetModal">
                        <i class="ri-add-line"></i> Add Set
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($workoutExercise->exerciseSets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Set #</th>
                                    <th>Reps</th>
                                    <th>Weight (lbs)</th>
                                    <th>Duration (s)</th>
                                    <th>Rest Time (s)</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workoutExercise->exerciseSets as $set)
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary-transparent">{{ $set->set_number }}</span>
                                        </td>
                                        <td>{{ $set->reps ?? '-' }}</td>
                                        <td>{{ $set->formatted_weight ?? '-' }}</td>
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
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-success" onclick="editSet('{{ $set->id }}')" title="Edit">
                                                    <i class="ri-edit-2-line"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-{{ $set->is_completed ? 'warning' : 'success' }}" onclick="toggleSetStatus('{{ $set->id }}')" title="{{ $set->is_completed ? 'Mark as Pending' : 'Mark as Completed' }}">
                                                    <i class="ri-{{ $set->is_completed ? 'time' : 'check' }}-line"></i>
                                                </button>
                                                <form action="{{ route('workout-exercise-sets.destroy', [$workout->id, $workoutExercise->id, $set->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
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
                @else
                    <div class="text-center py-4">
                        <i class="ri-list-check-line" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="mt-3 text-muted">No Sets Added</h5>
                        <p class="text-muted">This exercise doesn't have any sets yet.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSetModal">
                            Add First Set
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Set Modal -->
<div class="modal fade" id="addSetModal" tabindex="-1" aria-labelledby="addSetModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('workout-exercise-sets.store', [$workout->id, $workoutExercise->id]) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addSetModalLabel">Add New Set</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Set Number</label>
                            <input type="number" class="form-control" name="set_number" value="{{ $workoutExercise->exerciseSets->count() + 1 }}" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reps</label>
                            <input type="number" class="form-control" name="reps" value="{{ $workoutExercise->reps }}" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Weight (lbs)</label>
                            <input type="number" step="0.5" class="form-control" name="weight" value="{{ $workoutExercise->weight }}" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (s)</label>
                            <input type="number" class="form-control" name="duration" value="{{ $workoutExercise->duration }}" min="1">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Rest Time (s)</label>
                            <input type="number" class="form-control" name="rest_time" value="{{ $workoutExercise->rest_interval }}" min="0">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Set</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Set Modal -->
<div class="modal fade" id="editSetModal" tabindex="-1" aria-labelledby="editSetModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editSetForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editSetModalLabel">Edit Set</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Set Number</label>
                            <input type="number" class="form-control" name="set_number" id="edit_set_number" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reps</label>
                            <input type="number" class="form-control" name="reps" id="edit_reps" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Weight (lbs)</label>
                            <input type="number" step="0.5" class="form-control" name="weight" id="edit_weight" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (s)</label>
                            <input type="number" class="form-control" name="duration" id="edit_duration" min="1">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Rest Time (s)</label>
                            <input type="number" class="form-control" name="rest_time" id="edit_rest_time" min="0">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit_notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Set</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editSet(setId) {
    // You would typically fetch set data via AJAX here
    // For now, we'll show the modal
    const editModal = new bootstrap.Modal(document.getElementById('editSetModal'));
    document.getElementById('editSetForm').action = `{{ route('workout-exercise-sets.update', [$workout->id, $workoutExercise->id, ':setId']) }}`.replace(':setId', setId);
    editModal.show();
}

function toggleSetStatus(setId) {
    fetch(`{{ route('workout-exercise-sets.toggle-status', [$workout->id, $workoutExercise->id, ':setId']) }}`.replace(':setId', setId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update set status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating set status');
    });
}
</script>
@endsection