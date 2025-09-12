@extends('layouts.master')

@section('content')
<form method="POST" action="{{ route('workouts.update', $workout->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
  <div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Edit Workout
                                    </div>
                                    <div class="prism-toggle">
                                        <a href="{{route('workouts.index')}}" class="btn btn-sm btn-primary-light me-2">Back to List</a>
                                        <a href="{{route('workouts.show', $workout->id)}}" class="btn btn-sm btn-info-light">View Details</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text"  class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Enter workout name" aria-label="Name" value="{{ old('name', $workout->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Duration (minutes)</label>
                                            <input type="number"  class="form-control @error('duration') is-invalid @enderror" name="duration" placeholder="Enter duration in minutes" min="1" max="1440" value="{{ old('duration', $workout->duration) }}" required>
                                            @error('duration')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Enter workout description">{{ old('description', $workout->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                     
                                        
                                       <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                                    <select class="form-select @error('is_active') is-invalid @enderror" name="is_active" required>
                                                        <option selected="" disabled >Select status</option>
                                                        <option value="1" {{ old('is_active', $workout->is_active) == '1' ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ old('is_active', $workout->is_active) == '0' ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                    @error('is_active')
                                                        <div class="invalid-feedback">
                                                            {{ $message }}
                                                        </div>
                                                    @enderror
                                                </div>
                                                
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Thumbnail</label>
                                            <input type="file"  class="form-control @error('thumbnail') is-invalid @enderror" name="thumbnail" accept="image/*">
                                            <small class="text-muted">Upload workout thumbnail (jpeg, png, jpg, gif, webp - max 2MB)</small>
                                            @if($workout->thumbnail)
                                                <div class="mt-2">
                                                    <img src="{{ Storage::url($workout->thumbnail) }}" alt="Current thumbnail" class="rounded" width="100" height="60" style="object-fit: cover;">
                                                    <small class="text-muted d-block">Current thumbnail</small>
                                                </div>
                                            @endif
                                            @error('thumbnail')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary">Update Workout</button>
                                            <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
</form>
@endsection