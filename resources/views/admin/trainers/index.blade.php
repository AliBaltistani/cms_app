@extends('layouts.master')

@section('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Trainer Management</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Trainers</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <button type="button" class="btn btn-primary btn-wave waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createTrainerModal">
            <i class="ri-add-line align-middle me-1"></i>Add New Trainer
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['total_trainers'] }}</h3>
                        <span class="d-block text-muted">Total Trainers</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-success-transparent">
                            <i class="ri-user-star-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['active_trainers'] }}</h3>
                        <span class="d-block text-muted">Active Trainers</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
                            <i class="ri-user-follow-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['total_certifications'] }}</h3>
                        <span class="d-block text-muted">Total Certifications</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-info-transparent">
                            <i class="ri-award-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ number_format($stats['avg_rating'], 1) }}</h3>
                        <span class="d-block text-muted">Average Rating</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-warning-transparent">
                            <i class="ri-star-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trainers Table -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Trainers Management
                </div>
                <div class="d-flex gap-2">
                    <!-- Status Filter -->
                    <select id="statusFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <!-- Experience Filter -->
                    <select id="experienceFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">All Experience</option>
                        <option value="beginner">Beginner (0-2 years)</option>
                        <option value="intermediate">Intermediate (3-7 years)</option>
                        <option value="expert">Expert (8+ years)</option>
                    </select>
                </div>
            </div>
            
            <!-- Display Success/Error Messages -->
            @if (session('success') || session('error'))
                <div class="card-body pb-0">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success!</strong> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                </div>
            @endif

            <div class="card-body">
                <div class="table-responsive">
                    <table id="trainersTable" class="table table-bordered text-nowrap w-100 table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Designation</th>
                                <th>Experience</th>
                                <th>Status</th>
                                <!-- <th>Certifications</th> -->
                                <!-- <th>Rating</th> -->
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Trainer Modal -->
<div class="modal fade" id="createTrainerModal" tabindex="-1" aria-labelledby="createTrainerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTrainerModalLabel">Create New Trainer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createTrainerForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trainer_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="trainer_name" name="name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trainer_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="trainer_email" name="email" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trainer_phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="trainer_phone" name="phone">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trainer_designation" class="form-label">Designation <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="trainer_designation" name="designation" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trainer_experience" class="form-label">Experience (Years) <span class="text-danger">*</span></label>
                                <select class="form-select" id="trainer_experience" name="experience" required>
                                    <option value="">Select Experience Level</option>
                                    <option value="0">Less than 1 year</option>
                                    <option value="1">1 year</option>
                                    <option value="2">2 years</option>
                                    <option value="3">3 years</option>
                                    <option value="4">4 years</option>
                                    <option value="5">5 years</option>
                                    <option value="6">6 years</option>
                                    <option value="7">7 years</option>
                                    <option value="8">8 years</option>
                                    <option value="9">9 years</option>
                                    <option value="10">10 years</option>
                                    <option value="11">More than 10 years</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trainer_profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="trainer_profile_image" name="profile_image" accept="image/*">
                                <div class="invalid-feedback"></div>
                                <small class="text-muted">Max file size: 2MB. Supported formats: JPEG, PNG, JPG, GIF</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trainer_password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="trainer_password" name="password" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trainer_password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="trainer_password_confirmation" name="password_confirmation" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="trainer_about" class="form-label">About <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="trainer_about" name="about" rows="3" maxlength="1000" required></textarea>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Maximum 1000 characters</small>
                    </div>
                    <div class="mb-3">
                        <label for="trainer_training_philosophy" class="form-label">Training Philosophy</label>
                        <textarea class="form-control" id="trainer_training_philosophy" name="training_philosophy" rows="3" maxlength="1000"></textarea>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Maximum 1000 characters</small>
                    </div>
                    <div class="mb-3">
                        <label for="trainer_specializations" class="form-label">Specializations</label>
                        <select class="form-select" id="trainer_specializations" name="specializations">
                            <option value="" disabled selected>Select Specialization</option>
                            @php
                                $specializations = \App\Models\Specialization::where('status', 1)->orderBy('name')->get();
                            @endphp
                            @foreach($specializations as $specialization)
                                <option value="{{ $specialization->id }}">{{ $specialization->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Create Trainer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Trainer Modal -->
<div class="modal fade" id="viewTrainerModal" tabindex="-1" aria-labelledby="viewTrainerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTrainerModalLabel">Trainer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="trainerDetailsContent">
                <!-- Trainer details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Certification Modal -->
<div class="modal fade" id="addCertificationModal" tabindex="-1" aria-labelledby="addCertificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCertificationModalLabel">Add Certification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCertificationForm">
                @csrf
                <input type="hidden" id="certification_trainer_id" name="trainer_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="certification_name" class="form-label">Certification Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="certification_name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="issuing_organization" class="form-label">Issuing Organization <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="issuing_organization" name="issuing_organization" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="issue_date" class="form-label">Issue Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="issue_date" name="issue_date" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="credential_id" class="form-label">Credential ID</label>
                        <input type="text" class="form-control" id="credential_id" name="credential_id">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="credential_url" class="form-label">Credential URL</label>
                        <input type="url" class="form-control" id="credential_url" name="credential_url">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Add Certification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    let trainersTable = $('#trainersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("admin.trainers.index") }}',
            data: function(d) {
                d.status = $('#statusFilter').val();
                d.experience = $('#experienceFilter').val();
            }
        },
        columns: [
            { 
                data: 'name', 
                name: 'name', 
                width: '15%',
                render: function(data, type, row) {
                    let profileImage = '';
                    if (row.profile_image) {
                        profileImage = `<img src="${row.profile_image}" alt="Profile" class="avatar avatar-sm avatar-rounded me-2">`;
                    } else {
                        profileImage = `<span class="avatar avatar-sm avatar-rounded bg-success-transparent me-2">
                                            <i class="ri-user-star-line"></i>
                                        </span>`;
                    }
                    return `<div class="d-flex align-items-center">
                                ${profileImage}
                                <span class="fw-semibold">${data}</span>
                            </div>`;
                }
            },
            { data: 'email', name: 'email', width: '10%' },
            { data: 'phone', name: 'phone', width: '10%' },
            { data: 'designation', name: 'designation', width: '10%' },
            { data: 'experience', name: 'experience', width: '10%' },
            { 
                data: 'status', 
                name: 'status', 
                width: '10%',
                render: function(data, type, row) {
                    if (data === 'Active') {
                        return '<span class="badge bg-success-transparent">Active</span>';
                    }
                    return '<span class="badge bg-danger-transparent">Inactive</span>';
                }
            },
            // { data: 'certifications_count', name: 'certifications_count', width: '10%' },
            // { 
            //     data: 'average_rating', 
            //     name: 'average_rating', 
            //     width: '10%',
            //     render: function(data, type, row) {
            //         if (data > 0) {
            //             return `<span class="badge bg-warning">${data} ⭐</span>`;
            //         }
            //         return '<span class="text-muted">No ratings</span>';
            //     }
            // },
            { 
                data: 'id', 
                name: 'actions', 
                orderable: false, 
                searchable: false,
                width: '13%',
                render: function(data, type, row) {
                    return `
                     <div class="d-flex justify-content-end">
                        <div class="btn-group" role="group">
                            <a href="/admin/trainers/${data}" class="btn btn-sm btn-info btn-wave" title="View">
                                <i class="ri-eye-line"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-success btn-wave" onclick="editTrainer(${data})" title="Edit">
                                <i class="ri-edit-2-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-primary btn-wave" onclick="addCertification(${data})" title="Add Certification">
                                <i class="ri-award-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning btn-wave" onclick="toggleTrainerStatus(${data})" title="Toggle Status">
                                <i class="ri-toggle-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-wave" onclick="deleteTrainer(${data})" title="Delete">
                                <i class="ri-delete-bin-5-line"></i>
                            </button>
                        </div>
                    </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No trainers found',
            zeroRecords: 'No matching trainers found'
        }
    });

    // Filter change handlers
    $('#statusFilter, #experienceFilter').change(function() {
        trainersTable.ajax.reload();
    });

    // Create trainer form submission
    $('#createTrainerForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let submitBtn = $(this).find('button[type="submit"]');
        let spinner = submitBtn.find('.spinner-border');
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Show loading state
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        $.ajax({
            url: '{{ route("admin.trainers.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#createTrainerModal').modal('hide');
                    $('#createTrainerForm')[0].reset();
                    trainersTable.ajax.reload();
                    
                    // Show success message
                    showAlert('success', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        let input = $(`[name="${field}"]`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    showAlert('error', 'Failed to create trainer. Please try again.');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    // Add certification form submission
    $('#addCertificationForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = $(this).serialize();
        let trainerId = $('#certification_trainer_id').val();
        let submitBtn = $(this).find('button[type="submit"]');
        let spinner = submitBtn.find('.spinner-border');
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Show loading state
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        $.ajax({
            url: `/admin/trainers/${trainerId}/certifications`,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#addCertificationModal').modal('hide');
                    $('#addCertificationForm')[0].reset();
                    trainersTable.ajax.reload();
                    
                    // Show success message
                    showAlert('success', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        let input = $(`[name="${field}"]`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    showAlert('error', 'Failed to add certification. Please try again.');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    // Reset forms when modals are hidden
    $('#createTrainerModal, #addCertificationModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    });
});

// View trainer function
function viewTrainer(trainerId) {
    $.ajax({
        url: `/admin/trainers/${trainerId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let trainer = response.trainer;
                let stats = response.stats;
                
                let content = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            ${trainer.profile_image ? 
                                `<img src="${trainer.profile_image}" alt="Profile" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">` :
                                `<div class="avatar avatar-xxl avatar-rounded bg-success-transparent mb-3">
                                    <i class="ri-user-star-line fs-1"></i>
                                </div>`
                            }
                            <h5>${trainer.name}</h5>
                            <p class="text-muted">${trainer.email}</p>
                            <span class="badge bg-success">${trainer.designation}</span>
                        </div>
                        <div class="col-md-8">
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <strong>Phone:</strong> ${trainer.phone || 'N/A'}<br>
                                    <strong>Experience:</strong> ${trainer.experience} years<br>
                                    <strong>Status:</strong> ${trainer.email_verified_at ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'}<br>
                                    <strong>Joined:</strong> ${new Date(trainer.created_at).toLocaleDateString()}
                                </div>
                                <div class="col-sm-6">
                                    <strong>Certifications:</strong> ${stats.total_certifications}<br>
                                    <strong>Testimonials:</strong> ${stats.total_testimonials}<br>
                                    <strong>Average Rating:</strong> ${stats.average_rating > 0 ? stats.average_rating.toFixed(1) + ' ⭐' : 'No ratings'}<br>
                                    <strong>Total Likes:</strong> ${stats.total_likes}
                                </div>
                            </div>
                            
                            ${trainer.about ? `
                                <div class="mb-3">
                                    <strong>About:</strong><br>
                                    <p>${trainer.about}</p>
                                </div>
                            ` : ''}
                            
                            ${trainer.training_philosophy ? `
                                <div class="mb-3">
                                    <strong>Training Philosophy:</strong><br>
                                    <p>${trainer.training_philosophy}</p>
                                </div>
                            ` : ''}
                            
                            ${stats.recent_certifications && stats.recent_certifications.length > 0 ? `
                                <div class="mb-3">
                                    <strong>Recent Certifications:</strong>
                                    <ul class="list-group list-group-flush">
                                        ${stats.recent_certifications.map(cert => `
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>${cert.name}</strong><br>
                                                    <small class="text-muted">${cert.issuing_organization}</small>
                                                </div>
                                                <span class="badge bg-primary">${new Date(cert.issue_date).getFullYear()}</span>
                                            </li>
                                        `).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                $('#trainerDetailsContent').html(content);
                $('#viewTrainerModal').modal('show');
            }
        },
        error: function() {
            showAlert('error', 'Failed to load trainer details.');
        }
    });
}

// Edit trainer function
function editTrainer(trainerId) {
    window.location.href = `/admin/trainers/${trainerId}/edit`;
}

// Add certification function
function addCertification(trainerId) {
    $('#certification_trainer_id').val(trainerId);
    $('#addCertificationModal').modal('show');
}

// Toggle trainer status function
function toggleTrainerStatus(trainerId) {
    if (confirm('Are you sure you want to toggle this trainer\'s status?')) {
        $.ajax({
            url: `/admin/trainers/${trainerId}/toggle-status`,
            method: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#trainersTable').DataTable().ajax.reload();
                    showAlert('success', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Failed to update trainer status.');
            }
        });
    }
}

// Delete trainer function
function deleteTrainer(trainerId) {
    if (confirm('Are you sure you want to delete this trainer? This action cannot be undone.')) {
        $.ajax({
            url: `/admin/trainers/${trainerId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#trainersTable').DataTable().ajax.reload();
                    showAlert('success', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Failed to delete trainer.');
            }
        });
    }
}

// Show alert function
function showAlert(type, message) {
    let alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    let alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the page
    $('.page-header-breadcrumb').after(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection