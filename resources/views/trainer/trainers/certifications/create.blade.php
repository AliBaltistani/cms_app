@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Add Certification</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('trainers.index') }}">Trainers</a></li>
            <li class="breadcrumb-item"><a href="{{ route('trainers.show', $trainer->id) }}">{{ $trainer->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add Certification</li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

<!-- Display Success Messages -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-circle-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Display Error Messages -->
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Start::row-1 -->
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Add New Certification
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('trainers.certifications.store', $trainer->id) }}" enctype="multipart/form-data" id="certificationForm">
                    @csrf
                    <div class="row gy-3">
                        <div class="col-xl-12">
                            <label for="certificate-name" class="form-label">Certificate Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('certificate_name') is-invalid @enderror" id="certificate-name" name="certificate_name" value="{{ old('certificate_name') }}" placeholder="e.g., Certified Personal Trainer (CPT)" required>
                            @error('certificate_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">Enter the full name of your certification</small>
                            </div>
                        </div>
                        
                        <div class="col-xl-12">
                            <label for="certificate-doc" class="form-label">Certificate Document</label>
                            <input type="file" class="form-control @error('doc') is-invalid @enderror" id="certificate-doc" name="doc" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            @error('doc')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">Upload your certificate document (PDF, Image, or Word document). Maximum file size: 5MB</small>
                            </div>
                        </div>
                        
                        <div class="col-xl-12">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="fw-semibold mb-2"><i class="ri-information-line me-1"></i>Certification Guidelines</h6>
                                <ul class="mb-0 text-muted fs-13">
                                    <li>Provide the exact name as it appears on your certificate</li>
                                    <li>Upload a clear, readable copy of your certificate</li>
                                    <li>Accepted formats: PDF, JPG, PNG, DOC, DOCX</li>
                                    <li>File size should not exceed 5MB</li>
                                    <li>Ensure the document shows your name and certification details</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i>Add Certification
                        </button>
                        <a href="{{ route('trainers.show', $trainer->id) }}" class="btn btn-light ms-2">
                            <i class="ri-arrow-left-line me-1"></i>Back to Profile
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Existing Certifications -->
        @if($trainer->certifications->count() > 0)
        <div class="card custom-card mt-4">
            <div class="card-header">
                <div class="card-title">
                    Your Existing Certifications ({{ $trainer->certifications->count() }})
                </div>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    @foreach($trainer->certifications as $certification)
                    <div class="col-xl-6">
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="flex-fill">
                                    <h6 class="fw-semibold mb-1">{{ $certification->certificate_name }}</h6>
                                    <p class="text-muted fs-12 mb-2">Added: {{ \Carbon\Carbon::parse($certification->created_at)->format('M d, Y') }}</p>
                                    @if($certification->doc)
                                        <a href="{{ asset('storage/' . $certification->doc) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="ri-download-line me-1"></i>View Document
                                        </a>
                                    @else
                                        <span class="badge bg-secondary-transparent">No document uploaded</span>
                                    @endif
                                </div>
                                <div class="ms-2">
                                    <span class="avatar avatar-sm avatar-rounded bg-success-transparent">
                                        <i class="ri-award-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
<!--End::row-1 -->

@endsection

@section('scripts')
<script>
/**
 * Handle file upload validation and preview
 */
document.getElementById('certificate-doc').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        console.log('File selected:', file.name);
        
        // Validate file type
        const allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid file type (PDF, Image, or Word document)');
            this.value = '';
            return;
        }
        
        // Validate file size (5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            alert('File size must be less than 5MB');
            this.value = '';
            return;
        }
        
        // Update form text with file info
        const formText = this.parentNode.querySelector('.form-text small');
        if (formText) {
            formText.innerHTML = `Selected: <strong>${file.name}</strong> (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            formText.classList.remove('text-muted');
            formText.classList.add('text-success');
        }
    }
});

/**
 * Form validation before submit
 */
document.getElementById('certificationForm').addEventListener('submit', function(e) {
    const certificateName = document.getElementById('certificate-name').value.trim();
    
    if (certificateName.length < 3) {
        e.preventDefault();
        alert('Certificate name must be at least 3 characters long.');
        document.getElementById('certificate-name').focus();
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="ri-loader-2-line me-1 spinner-border spinner-border-sm"></i>Adding...';
    submitBtn.disabled = true;
    
    // Re-enable button after 10 seconds as fallback
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 10000);
});

/**
 * Character count for certificate name
 */
document.addEventListener('DOMContentLoaded', function() {
    const certificateNameInput = document.getElementById('certificate-name');
    
    if (certificateNameInput) {
        certificateNameInput.addEventListener('input', function() {
            const currentLength = this.value.length;
            const formText = this.parentNode.querySelector('.form-text small');
            if (formText && !formText.classList.contains('text-success')) {
                formText.textContent = `${currentLength}/255 characters`;
                if (currentLength > 255) {
                    formText.classList.add('text-danger');
                    formText.classList.remove('text-muted');
                } else {
                    formText.classList.remove('text-danger');
                    formText.classList.add('text-muted');
                }
            }
        });
    }
});
</script>
@endsection