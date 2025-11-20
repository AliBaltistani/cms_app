@extends('layouts.master')

@section('styles')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Edit Program</h1>
            <p class="mb-0 text-muted">Update program information</p>
        </div>
        <div>
            <a href="{{ route('programs.show', $program->id) }}" class="btn btn-info me-2">
                <i class="fas fa-eye me-2"></i>View Program
            </a>
            <a href="{{ route('programs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Programs
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Program Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('programs.update', $program->id) }}" method="POST" id="programForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Program Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $program->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (weeks) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('duration') is-invalid @enderror" 
                                           id="duration" name="duration" value="{{ old('duration', $program->duration) }}" min="1" max="52" required>
                                    @error('duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($program->weeks->count() > 0)
                                        <div class="form-text text-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            This program has {{ $program->weeks->count() }} week(s). Reducing duration may affect existing weeks.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="trainer_id" class="form-label">Trainer <span class="text-danger">*</span></label>
                                    <select class="form-select @error('trainer_id') is-invalid @enderror" 
                                            id="trainer_id" name="trainer_id" required>
                                        <option value="">Select Trainer</option>
                                        @foreach($trainers as $trainer)
                                            <option value="{{ $trainer->id }}" {{ old('trainer_id', $program->trainer_id) == $trainer->id ? 'selected' : '' }}>
                                                {{ $trainer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('trainer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="client_id" class="form-label">Client (Optional)</label>
                                    <select class="form-select @error('client_id') is-invalid @enderror" 
                                            id="client_id" name="client_id">
                                        <option value="">Select Client (Leave empty for template)</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id', $program->client_id) == $client->id ? 'selected' : '' }}>
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
                                      placeholder="Describe the program goals, target audience, and key features...">{{ old('description', $program->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.history.back()">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Program
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Program Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary mb-0">{{ $program->weeks->count() }}</h4>
                                <small class="text-muted">Weeks</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success mb-0">{{ $program->weeks->sum(function($week) { return $week->days->count(); }) }}</h4>
                            <small class="text-muted">Days</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-info mb-0">{{ $program->weeks->sum(function($week) { return $week->days->sum(function($day) { return $day->circuits->count(); }); }) }}</h4>
                                <small class="text-muted">Circuits</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning mb-0">{{ $program->weeks->sum(function($week) { return $week->days->sum(function($day) { return $day->circuits->sum(function($circuit) { return $circuit->programExercises->count(); }); }); }) }}</h4>
                            <small class="text-muted">Exercises</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('program-builder.show', $program->id) }}" class="btn btn-success">
                            <i class="fas fa-hammer me-2"></i>Program Builder
                        </a>
                        <a href="{{ route('program-videos.index', $program->id) }}" class="btn btn-info">
                            <i class="fas fa-video me-2"></i>Manage Videos
                        </a>
                        <a href="{{ route('programs.show', $program->id) }}" class="btn btn-outline-info">
                            <i class="fas fa-eye me-2"></i>View Program
                        </a>
                        @if($program->client_id)
                            <button class="btn btn-warning" onclick="duplicateAsTemplate()">
                                <i class="fas fa-copy me-2"></i>Duplicate as Template
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Program Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Created:</strong><br>
                        <small class="text-muted">{{ $program->created_at->format('M d, Y \a\t g:i A') }}</small>
                    </div>
                    <div class="mb-2">
                        <strong>Last Updated:</strong><br>
                        <small class="text-muted">{{ $program->updated_at->format('M d, Y \a\t g:i A') }}</small>
                    </div>
                    <div class="mb-2">
                        <strong>Status:</strong><br>
                        @if($program->client_id)
                            <span class="badge bg-success">Assigned</span>
                        @else
                            <span class="badge bg-secondary">Template</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#trainer_id, #client_id').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                }
            });

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
                
                if (!$('#trainer_id').val()) {
                    $('#trainer_id').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#trainer_id').removeClass('is-invalid');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            });

            // Real-time validation
            $('#name, #duration, #trainer_id').on('input change', function() {
                $(this).removeClass('is-invalid');
            });
        });

        function duplicateAsTemplate() {
            Swal.fire({
                title: 'Duplicate as Template?',
                text: "This will create a copy of this program without the client assignment.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, duplicate it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/programs/{{ $program->id }}/duplicate`,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'View New Program'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = `/admin/programs/${response.program_id}/edit`;
                                    }
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'An error occurred while duplicating the program.',
                                'error'
                            );
                        }
                    });
                }
            });
        }
    </script>
@endsection