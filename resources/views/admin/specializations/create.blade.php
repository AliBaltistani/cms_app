@extends('layouts.master')

@section('styles')
<!-- Form Validation CSS -->
<style>
.is-invalid {
    border-color: #dc3545;
}
.invalid-feedback {
    display: block;
}
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Create New Specialization</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.specializations.index') }}">Specializations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.specializations.index') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
            <i class="ri-arrow-left-line align-middle me-1"></i>Back to List
        </a>
    </div>
</div>

<!-- Create Form -->
<div class="row">
    <div class="col-xl-8 col-lg-10 col-md-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-add-line me-2"></i>Specialization Information
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.specializations.store') }}" method="POST" id="createSpecializationForm">
                    @csrf
                    
                    <!-- Name Field -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="name" class="form-label">Specialization Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="Enter specialization name (e.g., Weight Loss, Muscle Building)"
                                   maxlength="100"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum 100 characters. This will be displayed to trainers when selecting specializations.</div>
                        </div>
                    </div>

                    <!-- Description Field -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4" 
                                      placeholder="Enter a detailed description of this specialization (optional)"
                                      maxlength="1000">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum 1000 characters. Provide details about what this specialization covers.</div>
                        </div>
                    </div>

                    <!-- Status Field -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Only active specializations will be available for trainers to select.</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.specializations.index') }}" class="btn btn-light btn-wave">
                                    <i class="ri-close-line me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-wave" id="submitBtn">
                                    <i class="ri-save-line me-1"></i>Create Specialization
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Help Card -->
    <div class="col-xl-4 col-lg-2 col-md-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-information-line me-2"></i>Help & Guidelines
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading"><i class="ri-lightbulb-line me-1"></i>Tips for Creating Specializations</h6>
                    <hr>
                    <ul class="mb-0 ps-3">
                        <li><strong>Name:</strong> Use clear, descriptive names that trainers will easily understand</li>
                        <li><strong>Description:</strong> Provide detailed information about what this specialization covers</li>
                        <li><strong>Status:</strong> Set to "Active" to make it available for trainer selection</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning" role="alert">
                    <h6 class="alert-heading"><i class="ri-alert-line me-1"></i>Important Notes</h6>
                    <hr>
                    <ul class="mb-0 ps-3">
                        <li>Specialization names must be unique</li>
                        <li>Only active specializations appear in trainer forms</li>
                        <li>You can edit specializations later if needed</li>
                        <li>Inactive specializations won't be available for new trainer assignments</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <h6 class="fw-semibold">Common Specializations:</h6>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="badge bg-light text-dark">Weight Loss</span>
                        <span class="badge bg-light text-dark">Muscle Building</span>
                        <span class="badge bg-light text-dark">Cardio Training</span>
                        <span class="badge bg-light text-dark">Strength Training</span>
                        <span class="badge bg-light text-dark">Yoga</span>
                        <span class="badge bg-light text-dark">Pilates</span>
                        <span class="badge bg-light text-dark">CrossFit</span>
                        <span class="badge bg-light text-dark">Sports Conditioning</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $('#createSpecializationForm').on('submit', function(e) {
        var isValid = true;
        
        // Clear previous validation states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Validate name
        var name = $('#name').val().trim();
        if (name === '') {
            $('#name').addClass('is-invalid');
            $('#name').after('<div class="invalid-feedback">Specialization name is required.</div>');
            isValid = false;
        } else if (name.length > 100) {
            $('#name').addClass('is-invalid');
            $('#name').after('<div class="invalid-feedback">Specialization name cannot exceed 100 characters.</div>');
            isValid = false;
        }
        
        // Validate description length
        var description = $('#description').val().trim();
        if (description.length > 1000) {
            $('#description').addClass('is-invalid');
            $('#description').after('<div class="invalid-feedback">Description cannot exceed 1000 characters.</div>');
            isValid = false;
        }
        
        // Validate status
        var status = $('#status').val();
        if (status === '') {
            $('#status').addClass('is-invalid');
            $('#status').after('<div class="invalid-feedback">Please select a status.</div>');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 500);
        } else {
            // Show loading state
            $('#submitBtn').prop('disabled', true).html('<i class="ri-loader-2-line me-1 spinner-border spinner-border-sm"></i>Creating...');
        }
    });
    
    // Character counter for name
    $('#name').on('input', function() {
        var length = $(this).val().length;
        var maxLength = 100;
        var remaining = maxLength - length;
        
        // Update or create character counter
        var counter = $(this).siblings('.char-counter');
        if (counter.length === 0) {
            $(this).after('<div class="form-text char-counter"></div>');
            counter = $(this).siblings('.char-counter');
        }
        
        counter.text(remaining + ' characters remaining');
        
        if (remaining < 10) {
            counter.addClass('text-warning');
        } else {
            counter.removeClass('text-warning');
        }
        
        if (remaining < 0) {
            counter.addClass('text-danger').removeClass('text-warning');
        } else {
            counter.removeClass('text-danger');
        }
    });
    
    // Character counter for description
    $('#description').on('input', function() {
        var length = $(this).val().length;
        var maxLength = 1000;
        var remaining = maxLength - length;
        
        // Update or create character counter
        var counter = $(this).siblings('.char-counter');
        if (counter.length === 0) {
            $(this).after('<div class="form-text char-counter"></div>');
            counter = $(this).siblings('.char-counter');
        }
        
        counter.text(remaining + ' characters remaining');
        
        if (remaining < 50) {
            counter.addClass('text-warning');
        } else {
            counter.removeClass('text-warning');
        }
        
        if (remaining < 0) {
            counter.addClass('text-danger').removeClass('text-warning');
        } else {
            counter.removeClass('text-danger');
        }
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
});
</script>
@endsection