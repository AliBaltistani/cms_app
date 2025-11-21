@extends('layouts.master')

@section('styles')
    <!-- Select2 CSS -->
    {{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> --}}
    {{-- <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" /> --}}
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Create New Program</h1>
            <p class="mb-0 text-muted">Create a new workout program template</p>
        </div>
        <a href="{{ route('trainer.programs.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Programs
        </a>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Program Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('trainer.programs.store') }}" method="POST" id="programForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Program Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (weeks) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('duration') is-invalid @enderror" 
                                           id="duration" name="duration" value="{{ old('duration', 4) }}" min="1" max="52" required>
                                    @error('duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 d-none">
                                <div class="mb-3">
                                    <label class="form-label">Trainer</label>
                                    <input type="text" class="form-control" value="{{ Auth::user()->name }}" disabled>
                                    <input type="hidden" id="trainer_id" name="trainer_id" value="{{ Auth::id() }}">
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="client_id" class="form-label">Client (Optional)</label>
                                    <select class="form-control @error('client_id') is-invalid @enderror" 
                                            id="client_id" name="client_id">
                                        <option value="">Select Client (Leave empty for template)</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                {{ $client->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4" 
                                      placeholder="Describe the program goals, target audience, and key features...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Program
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Program Guidelines</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Program Creation Tips</h6>
                        <ul class="mb-0 small">
                            <li>Choose a descriptive name that reflects the program's focus</li>
                            <li>Duration should match the intended program length</li>
                            <li>Leave client empty to create a reusable template</li>
                            <li>You can assign specific clients later</li>
                            <li>After creation, use the Program Builder to add weeks, days, and exercises</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Next Steps</h6>
                        <p class="mb-0 small">
                            After creating the program, you'll be redirected to the Program Builder where you can:
                        </p>
                        <ul class="mb-0 small">
                            <li>Add weeks to your program</li>
                            <li>Create days within each week</li>
                            <li>Build circuits for each day</li>
                            <li>Add exercises from the workout library</li>
                            <li>Configure sets, reps, and rest intervals</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Program Structure</h6>
                </div>
                <div class="card-body">
                    <div class="hierarchy-preview">
                        <div class="hierarchy-item">
                            <i class="fas fa-clipboard-list text-primary"></i>
                            <strong>Program</strong>
                            <div class="hierarchy-children">
                                <div class="hierarchy-item">
                                    <i class="fas fa-calendar-week text-success"></i>
                                    Week 1, Week 2...
                                    <div class="hierarchy-children">
                                        <div class="hierarchy-item">
                                            <i class="fas fa-calendar-day text-info"></i>
                                            Day 1, Day 2...
                                            <div class="hierarchy-children">
                                                <div class="hierarchy-item">
                                                    <i class="fas fa-circle text-warning"></i>
                                                    Circuit 1, Circuit 2...
                                                    <div class="hierarchy-children">
                                                        <div class="hierarchy-item">
                                                            <i class="fas fa-dumbbell text-danger"></i>
                                                            Exercises & Sets
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hierarchy-preview {
    font-size: 0.875rem;
}

.hierarchy-item {
    margin: 0.5rem 0;
    padding-left: 1rem;
    position: relative;
}

.hierarchy-item i {
    margin-right: 0.5rem;
}

.hierarchy-children {
    margin-left: 1rem;
    border-left: 2px solid #e3e6f0;
    padding-left: 1rem;
}

.hierarchy-children .hierarchy-item:before {
    content: '';
    position: absolute;
    left: -1rem;
    top: 0.75rem;
    width: 0.75rem;
    height: 2px;
    background-color: #e3e6f0;
}
</style>
@endsection

@section('scripts')
    <!-- Select2 JS -->
    {{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}

    <script>
        $(document).ready(function() {
            // Initialize Select2
            // $('#trainer_id, #client_id').select2({
            //     theme: 'bootstrap-5',
            //     placeholder: function() {
            //         return $(this).data('placeholder');
            //     }
            // });

            // Form validation
            $('#programForm').on('submit', function(e) {
                let isValid = true;
                
                // Check required fields
                if (!$('#name').val().trim()) {
                    $('#name').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#name').removeClass('is-invalid');
                }
                
                if (!$('#duration').val() || $('#duration').val() < 1) {
                    $('#duration').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#duration').removeClass('is-invalid');
                }
                
                
                
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            });

            // Real-time validation
            $('#name, #duration').on('input change', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
@endsection