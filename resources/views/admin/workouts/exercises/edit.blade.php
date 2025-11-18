@extends('layouts.master')

@section('content')
<form method="POST" action="{{ route('workout-exercises.update', [$workout->id, $workoutExercise->id]) }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Edit Exercise in "{{ $workout->name }}"
                    </div>
                    <div class="prism-toggle">
                        <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-sm btn-primary-light">
                            <i class="ri-arrow-left-line"></i> Back to Workout
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Exercise <span class="text-danger">*</span></label>
                            <select class="form-select @error('exercise_id') is-invalid @enderror" name="exercise_id" required>
                                <option selected="" disabled="">Select Exercise</option>
                                @foreach($exercises as $exercise)
                                    <option value="{{ $exercise->id }}" {{ old('exercise_id', $workoutExercise->exercise_id) == $exercise->id ? 'selected' : '' }}>
                                        {{ $exercise->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('exercise_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Order</label>
                            <input type="number" class="form-control @error('order') is-invalid @enderror" name="order" placeholder="Exercise order" min="1" value="{{ old('order', $workoutExercise->order) }}">
                            @error('order')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sets</label>
                            <input type="number" class="form-control @error('sets') is-invalid @enderror" name="sets" placeholder="Number of sets" min="1" value="{{ old('sets', $workoutExercise->sets) }}">
                            @error('sets')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Reps</label>
                            <input type="number" class="form-control @error('reps') is-invalid @enderror" name="reps" placeholder="Number of reps" min="1" value="{{ old('reps', $workoutExercise->reps) }}">
                            @error('reps')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Weight (lbs)</label>
                            <input type="number" step="0.5" class="form-control @error('weight') is-invalid @enderror" name="weight" placeholder="Weight in lbs" min="0" value="{{ old('weight', round($workoutExercise->weight * 2.20462, 2)) }}">
                            @error('weight')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Duration (seconds)</label>
                            <input type="number" class="form-control @error('duration') is-invalid @enderror" name="duration" placeholder="Duration in seconds" min="1" value="{{ old('duration', $workoutExercise->duration) }}">
                            @error('duration')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rest Interval (seconds)</label>
                            <input type="number" class="form-control @error('rest_interval') is-invalid @enderror" name="rest_interval" placeholder="Rest between sets" min="0" value="{{ old('rest_interval', $workoutExercise->rest_interval) }}">
                            @error('rest_interval')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tempo</label>
                            <input type="text" class="form-control @error('tempo') is-invalid @enderror" name="tempo" placeholder="e.g., 3-1-2-1" value="{{ old('tempo', $workoutExercise->tempo) }}">
                            <small class="text-muted">Format: eccentric-pause-concentric-pause (e.g., 3-1-2-1)</small>
                            @error('tempo')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3" placeholder="Exercise notes or instructions">{{ old('notes', $workoutExercise->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select @error('is_active') is-invalid @enderror" name="is_active" required>
                                <option selected="" disabled="">Select status</option>
                                <option value="1" {{ old('is_active', $workoutExercise->is_active) == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $workoutExercise->is_active) == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Exercise</button>
                    <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Exercise Sets Section -->
@if($workoutExercise->exerciseSets->count() > 0)
<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Exercise Sets ({{ $workoutExercise->exerciseSets->count() }})
                </div>
                <div class="prism-toggle">
                    <button type="button" class="btn btn-sm btn-primary-light" data-bs-toggle="modal" data-bs-target="#addSetModal">
                        Add Set
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Set #</th>
                                <th>Reps</th>
                                <th>Weight (lbs)</th>
                                <th>Duration (s)</th>
                                <th>Rest (s)</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workoutExercise->exerciseSets as $set)
                                <tr>
                                    <td>{{ $set->set_number }}</td>
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
                                        <button type="button" class="btn btn-sm btn-success me-1" onclick="editSet('{{ $set->id }}')">
                                            <i class="ri-edit-2-line"></i>
                                        </button>
                                        <form action="{{ route('workout-exercise-sets.destroy', [$workout->id, $workoutExercise->id, $set->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

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
                            <input type="number" class="form-control" name="reps" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Weight (lbs)</label>
                            <input type="number" step="0.5" class="form-control" name="weight" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (s)</label>
                            <input type="number" class="form-control" name="duration" min="1">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Rest Time (s)</label>
                            <input type="number" class="form-control" name="rest_time" min="0">
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
@endsection