@extends('layouts.master')

@section('content')
<form method="POST" action="{{ route('workouts.store') }}" enctype="multipart/form-data">
                    @csrf
  <div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Workout Add New
                                    </div>
                                    <div class="prism-toggle">
                                        <a href="{{route('workouts.index')}}" class="btn btn-sm btn-primary-light"> <i class=" "></i> back</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text"  class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Enter workout name" aria-label="Name" value="{{ old('name') }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Duration (minutes)</label>
                                            <input type="number"  class="form-control @error('duration') is-invalid @enderror" name="duration" placeholder="Enter duration in minutes" min="1" max="1440" value="{{ old('duration') }}" required>
                                            @error('duration')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Price ($)</label>
                                            <input type="number" step="0.01" min="0" max="9999999.99" class="form-control @error('price') is-invalid @enderror" name="price" placeholder="Enter price (leave empty for free)" value="{{ old('price') }}">
                                            <small class="text-muted">Leave empty or enter 0 for free workout</small>
                                            @error('price')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Trainer <span class="text-danger">*</span></label>
                                            <select class="form-select @error('user_id') is-invalid @enderror" name="user_id" required>
                                                <option selected="" disabled="">Select Trainer</option>
                                                @foreach($trainers as $trainer)
                                                    <option value="{{ $trainer->id }}" {{ old('user_id') == $trainer->id ? 'selected' : '' }}>
                                                        {{ $trainer->name }} ({{ $trainer->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('user_id')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Enter workout description">{{ old('description') }}</textarea>
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
                                                        <option value="1" {{ old('is_active') == '1' ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
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
                                            @error('thumbnail')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        
                                        
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
</form>
@endsection