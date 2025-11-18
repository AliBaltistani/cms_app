@extends('layouts.master')

@section('content')
<form method="POST" action="{{ route('workout-exercises.store', $workout->id) }}">
    @csrf
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Add Exercise to "{{ $workout->name }}"
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
                                    <option value="{{ $exercise->id }}" {{ old('exercise_id') == $exercise->id ? 'selected' : '' }}>
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
                            <input type="number" class="form-control @error('order') is-invalid @enderror" name="order" placeholder="Exercise order" min="1" value="{{ old('order', $nextOrder) }}">
                            <small class="text-muted">Leave empty to add at the end</small>
                            @error('order')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sets</label>
                            <input type="number" class="form-control @error('sets') is-invalid @enderror" name="sets" placeholder="Number of sets" min="1" value="{{ old('sets') }}">
                            @error('sets')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Reps</label>
                            <input type="number" class="form-control @error('reps') is-invalid @enderror" name="reps" placeholder="Number of reps" min="1" value="{{ old('reps') }}">
                            @error('reps')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Weight (lbs)</label>
                            <input type="number" step="0.5" class="form-control @error('weight') is-invalid @enderror" name="weight" placeholder="Weight in lbs" min="0" value="{{ old('weight') }}">
                            @error('weight')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Duration (seconds)</label>
                            <input type="number" class="form-control @error('duration') is-invalid @enderror" name="duration" placeholder="Duration in seconds" min="1" value="{{ old('duration') }}">
                            @error('duration')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rest Interval (seconds)</label>
                            <input type="number" class="form-control @error('rest_interval') is-invalid @enderror" name="rest_interval" placeholder="Rest between sets" min="0" value="{{ old('rest_interval') }}">
                            @error('rest_interval')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tempo</label>
                            <input type="text" class="form-control @error('tempo') is-invalid @enderror" name="tempo" placeholder="e.g., 3-1-2-1" value="{{ old('tempo') }}">
                            <small class="text-muted">Format: eccentric-pause-concentric-pause (e.g., 3-1-2-1)</small>
                            @error('tempo')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3" placeholder="Exercise notes or instructions">{{ old('notes') }}</textarea>
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
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
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
                    <button type="submit" class="btn btn-primary">Add Exercise</button>
                    <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection