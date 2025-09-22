@extends('layouts.master')

@section('styles')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
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
    let usersTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("admin.users.index") }}',
            data: function(d) {
                d.role = $('#roleFilter').val();
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { 
                data: 'id', 
                name: 'id', 
                visible: false,
                searchable: false
            },
            { 
                data: 'name', 
                name: 'name', 
                width: '15%',
                render: function(data, type, row) {
                    let profileImage = '';
                    if (row.profile_image) {
                        profileImage = `<img src="${row.profile_image}" alt="Profile" class="avatar avatar-sm avatar-rounded me-2">`;
                    } else {
                        profileImage = `<span class="avatar avatar-sm avatar-rounded bg-primary-transparent me-2">
                                            <i class="ri-user-line"></i>
                                        </span>`;
                    }
                    return `<div class="d-flex align-items-center">
                                ${profileImage}
                                <span class="fw-medium">${data}</span>
                            </div>`;
                }
            },
            { data: 'email', name: 'email', width: '15%' },
            { data: 'phone', name: 'phone', width: '12%' },
            { 
                data: 'role', 
                name: 'role', 
                width: '12%',
                render: function(data, type, row) {
                    let badgeClass = 'bg-secondary';
                    if (data === 'Admin') badgeClass = 'bg-danger';
                    else if (data === 'Trainer') badgeClass = 'bg-success';
                    else if (data === 'Client') badgeClass = 'bg-info';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            { 
                data: 'status', 
                name: 'status', 
                width: '12%',
                render: function(data, type, row) {
                    if (data === 'Active') {
                        return '<span class="badge bg-success-transparent">Active</span>';
                    }
                    return '<span class="badge bg-danger-transparent">Inactive</span>';
                }
            },
            { data: 'created_at', name: 'created_at', width: '12%' },
            { 
                data: 'id', 
                name: 'actions', 
                orderable: false, 
                searchable: false,
                width: '22%',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex justify-content-end">
                            <div class="btn-group" role="group" style="align-self: end;">
                                <button type="button" class="btn btn-sm btn-info btn-wave" onclick="viewUser(${data})" title="View">
                                    <i class="ri-eye-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-success btn-wave" onclick="editUser(${data})" title="Edit">
                                    <i class="ri-edit-2-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-warning btn-wave" onclick="toggleUserStatus(${data})" title="Toggle Status">
                                    <i class="ri-toggle-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger btn-wave" onclick="deleteUser(${data})" title="Delete">
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
            emptyTable: 'No users found',
            zeroRecords: 'No matching users found'
        }
    });

    // Filter change handlers
    $('#roleFilter, #statusFilter').change(function() {
        usersTable.ajax.reload();
    });

    // Create User Modal Wizard
    let createCurrentStep = 0;
    const createTotalSteps = 4;
    const createSteps = ['create-basic-info', 'create-profile-details', 'create-trainer-info', 'create-finish-step'];
    
    // Initialize create wizard
    updateCreateWizardButtons();
    updateCreateProgressBar();
    
    // Create wizard navigation
    $('#createNextBtn').click(function() {
        if (validateCreateCurrentStep()) {
            if (createCurrentStep < createTotalSteps - 1) {
                createCurrentStep++;
                showCreateStep(createCurrentStep);
                updateCreateWizardButtons();
                updateCreateProgressBar();
                updateCreateSummary();
            }
        }
    });
    
    $('#createPrevBtn').click(function() {
        if (createCurrentStep > 0) {
            createCurrentStep--;
            showCreateStep(createCurrentStep);
            updateCreateWizardButtons();
            updateCreateProgressBar();
        }
    });
    
    $('#createFirstBtn').click(function() {
        createCurrentStep = 0;
        showCreateStep(createCurrentStep);
        updateCreateWizardButtons();
        updateCreateProgressBar();
    });
    
    function showCreateStep(step) {
        $('#createProgresswizard .tab-pane').removeClass('show active');
        $('#createProgresswizard .nav-link').removeClass('active');
        
        $(`#${createSteps[step]}`).addClass('show active');
        $(`#createProgresswizard .nav-link[href="#${createSteps[step]}"]`).addClass('active');
    }
    
    function updateCreateWizardButtons() {
        // First button
        if (createCurrentStep === 0) {
            $('#createFirstBtn').addClass('disabled');
        } else {
            $('#createFirstBtn').removeClass('disabled');
        }
        
        // Previous button
        if (createCurrentStep === 0) {
            $('#createPrevBtn').addClass('disabled');
        } else {
            $('#createPrevBtn').removeClass('disabled');
        }
        
        // Next button
        if (createCurrentStep === createTotalSteps - 1) {
            $('#createNextBtn').hide();
            $('#createFinishBtn').show();
        } else {
            $('#createNextBtn').show();
            $('#createFinishBtn').hide();
        }
    }
    
    function updateCreateProgressBar() {
        const progress = ((createCurrentStep + 1) / createTotalSteps) * 100;
        $('#createBar .progress-bar').css('width', progress + '%');
    }
    
    function validateCreateCurrentStep() {
        const currentStepElement = $(`#${createSteps[createCurrentStep]}`);
        const requiredFields = currentStepElement.find('[required]');
        let isValid = true;
        
        requiredFields.each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        return isValid;
    }
    
    function updateCreateSummary() {
        if (createCurrentStep === createTotalSteps - 1) {
            $('#createSummaryName').text($('#create_name').val() || '-');
            $('#createSummaryEmail').text($('#create_email').val() || '-');
            $('#createSummaryPhone').text($('#create_phone').val() || 'Not provided');
            $('#createSummaryRole').text($('#create_role option:selected').text() || '-');
            $('#createSummaryImage').text($('#create_profile_image')[0].files.length > 0 ? 'Uploaded' : 'Not uploaded');
        }
    }
    
    // Show/hide trainer fields based on role selection
    $('#create_role').change(function() {
        if ($(this).val() === 'trainer') {
            $('#createTrainerFields').slideDown();
            $('#createNonTrainerMessage').slideUp();
            $('.trainer-required').show();
            $('#create_designation, #create_experience, #create_about').attr('required', true);
        } else {
            $('#createTrainerFields').slideUp();
            $('#createNonTrainerMessage').slideDown();
            $('.trainer-required').hide();
            $('#create_designation, #create_experience, #create_about, #create_training_philosophy').attr('required', false);
        }
    });
    
    // Character counters for create form textareas
    $('#create_about').on('input', function() {
        const length = $(this).val().length;
        $('#createAboutCounter').text(length);
        if (length > 1000) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    $('#create_training_philosophy').on('input', function() {
        const length = $(this).val().length;
        $('#createPhilosophyCounter').text(length);
        if (length > 1000) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Create form profile image preview
    $('#create_profile_image').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#createImagePreview').attr('src', e.target.result).removeClass('d-none');
                $('#createAvatarPlaceholder').addClass('d-none');
                $('#createDeleteImageBtn').removeClass('d-none');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Delete create form profile image
    $('#createDeleteImageBtn').click(function() {
        $('#create_profile_image').val('');
        $('#createImagePreview').addClass('d-none');
        $('#createAvatarPlaceholder').removeClass('d-none');
        $(this).addClass('d-none');
    });

    // Create user form submission
    $('#createUserForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let submitBtn = $('#createFinishBtn');
        let spinner = submitBtn.find('.spinner-border');
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Show loading state
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        $.ajax({
            url: '{{ route("admin.users.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#createUserModal').modal('hide');
                    resetCreateForm();
                    usersTable.ajax.reload();
                    
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
                    
                    // Navigate to the step with errors
                    if (errors.name || errors.email || errors.phone || errors.role || errors.password) {
                        createCurrentStep = 0;
                        showCreateStep(createCurrentStep);
                        updateCreateWizardButtons();
                        updateCreateProgressBar();
                    } else if (errors.profile_image) {
                        createCurrentStep = 1;
                        showCreateStep(createCurrentStep);
                        updateCreateWizardButtons();
                        updateCreateProgressBar();
                    } else if (errors.designation || errors.experience || errors.about || errors.training_philosophy) {
                        createCurrentStep = 2;
                        showCreateStep(createCurrentStep);
                        updateCreateWizardButtons();
                        updateCreateProgressBar();
                    }
                } else {
                    showAlert('error', 'Failed to create user. Please try again.');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });
    
    function resetCreateForm() {
        $('#createUserForm')[0].reset();
        $('#createTrainerFields').hide();
        $('#createNonTrainerMessage').show();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Reset wizard
        createCurrentStep = 0;
        showCreateStep(createCurrentStep);
        updateCreateWizardButtons();
        updateCreateProgressBar();
        
        // Reset image preview
        $('#createImagePreview').addClass('d-none');
        $('#createAvatarPlaceholder').removeClass('d-none');
        $('#createDeleteImageBtn').addClass('d-none');
        
        // Reset character counters
        $('#createAboutCounter').text('0');
        $('#createPhilosophyCounter').text('0');
    }

    // Reset form when modal is hidden
    $('#createUserModal').on('hidden.bs.modal', function() {
        resetCreateForm();
    });
});

// View user function
function viewUser(userId) {
    window.location.href = `/admin/users/${userId}`;
}

// Edit user function
function editUser(userId) {
    window.location.href = `/admin/users/${userId}/edit`;
}

// Toggle user status function
function toggleUserStatus(userId) {
    if (confirm('Are you sure you want to toggle this user\'s status?')) {
        $.ajax({
            url: `/admin/users/${userId}/toggle-status`,
            method: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#usersTable').DataTable().ajax.reload();
                    showAlert('success', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Failed to update user status.');
            }
        });
    }
}

// Delete user function
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        $.ajax({
            url: `/admin/users/${userId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#usersTable').DataTable().ajax.reload();
                    showAlert('success', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Failed to delete user.');
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

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">User Management</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Users</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <button type="button" class="btn btn-primary btn-wave waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="ri-add-line align-middle me-1"></i>Add New User
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
                        <h3 class="fw-semibold mb-1">{{ $stats['total_users'] }}</h3>
                        <span class="d-block text-muted">Total Users</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
                            <i class="ri-user-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['total_clients'] }}</h3>
                        <span class="d-block text-muted">Total Clients</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-info-transparent">
                            <i class="ri-user-heart-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['active_users'] }}</h3>
                        <span class="d-block text-muted">Active Users</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-warning-transparent">
                            <i class="ri-user-follow-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Users Management
                </div>
                <div class="d-flex gap-2">
                    <!-- Role Filter -->
                    <select id="roleFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="trainer">Trainer</option>
                        <option value="client">Client</option>
                    </select>
                    <!-- Status Filter -->
                    <select id="statusFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
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
                    <table id="usersTable" class="table table-bordered text-nowrap w-100 table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Profile</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
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

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createUserForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div id="createProgresswizard">
                        <ul class="nav nav-tabs nav-justified flex-sm-row flex-column mb-4 tab-style-8 scaleX p-0" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link icon-btn d-flex align-items-center justify-content-sm-center gap-1 active" data-bs-toggle="tab" href="#create-basic-info" aria-selected="true" role="tab">
                                    <i class="ri-user-line me-1"></i><span>Basic Info</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link icon-btn d-flex align-items-center justify-content-sm-center gap-1" data-bs-toggle="tab" href="#create-profile-details" aria-selected="false" tabindex="-1" role="tab">
                                    <i class="ri-image-line me-1"></i><span>Profile & Image</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link icon-btn d-flex align-items-center justify-content-sm-center gap-1" data-bs-toggle="tab" href="#create-trainer-info" aria-selected="false" tabindex="-1" role="tab">
                                    <i class="ri-user-star-line me-1"></i><span>Trainer Info</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link icon-btn d-flex align-items-center justify-content-sm-center gap-1" data-bs-toggle="tab" href="#create-finish-step" aria-selected="false" tabindex="-1" role="tab">
                                    <i class="ri-check-line me-1"></i><span>Review & Create</span>
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div id="createBar" class="progress mb-3" style="height: 7px;">
                                <div class="bar progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 25%;"></div>
                            </div>
                            
                            <!-- Step 1: Basic Information -->
                            <div class="tab-pane show active" id="create-basic-info" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="create_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="create_name" name="name" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="create_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="create_email" name="email" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="create_phone" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="create_phone" name="phone">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="create_role" class="form-label">Role <span class="text-danger">*</span></label>
                                            <select class="form-select" id="create_role" name="role" required>
                                                <option value="">Select Role</option>
                                                <option value="client">Client</option>
                                                <option value="trainer">Trainer</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="create_password" class="form-label">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="create_password" name="password" required>
                                            <div class="invalid-feedback"></div>
                                            <small class="text-muted">Minimum 8 characters</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="create_password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="create_password_confirmation" name="password_confirmation" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 2: Profile & Image -->
                            <div class="tab-pane" id="create-profile-details" role="tabpanel">
                                <div class="text-center mb-4">
                                    <div class="image-upload-container mb-3">
                                        <div class="avatar avatar-xxl avatar-rounded bg-primary-transparent" id="createAvatarPlaceholder">
                                            <i class="ri-user-line fs-1"></i>
                                        </div>
                                        <img src="" alt="Profile Image" class="profile-image-preview d-none" id="createImagePreview">
                                        <div class="image-upload-overlay" onclick="document.getElementById('create_profile_image').click()">
                                            <i class="ri-camera-line text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <input type="file" class="d-none" id="create_profile_image" name="profile_image" accept="image/*">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('create_profile_image').click()">
                                            <i class="ri-upload-2-line me-1"></i>Upload Image
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger d-none" id="createDeleteImageBtn">
                                            <i class="ri-delete-bin-line me-1"></i>Remove Image
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-2">Max file size: 2MB. Supported formats: JPEG, PNG, JPG, GIF</small>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="text-center">
                                    <div class="alert alert-info">
                                        <i class="ri-information-line me-2"></i>
                                        <strong>Optional:</strong> You can upload a profile image or skip this step and add it later.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 3: Trainer Information -->
                            <div class="tab-pane" id="create-trainer-info" role="tabpanel">
                                <div id="createTrainerFields" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="create_designation" class="form-label">Designation <span class="text-danger trainer-required">*</span></label>
                                                <input type="text" class="form-control" id="create_designation" name="designation">
                                                <div class="invalid-feedback"></div>
                                                <small class="text-muted">e.g., Certified Personal Trainer, Fitness Coach</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="create_experience" class="form-label">Experience (Years) <span class="text-danger trainer-required">*</span></label>
                                                <select class="form-select" id="create_experience" name="experience">
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
                                    </div>
                                    <div class="mb-3">
                                        <label for="create_about" class="form-label">About <span class="text-danger trainer-required">*</span></label>
                                        <textarea class="form-control" id="create_about" name="about" rows="4" maxlength="1000"></textarea>
                                        <div class="invalid-feedback"></div>
                                        <div class="character-counter">
                                            <span id="createAboutCounter">0</span>/1000 characters
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="create_training_philosophy" class="form-label">Training Philosophy</label>
                                        <textarea class="form-control" id="create_training_philosophy" name="training_philosophy" rows="4" maxlength="1000"></textarea>
                                        <div class="invalid-feedback"></div>
                                        <div class="character-counter">
                                            <span id="createPhilosophyCounter">0</span>/1000 characters
                                        </div>
                                    </div>
                                </div>
                                <div id="createNonTrainerMessage" style="display: block;">
                                    <div class="text-center p-4">
                                        <span class="avatar avatar-xl avatar-rounded bg-info-transparent">
                                            <i class="ri-information-line fs-1"></i>
                                        </span>
                                        <h5 class="mt-3">Trainer Information Not Required</h5>
                                        <p class="text-muted">This section is only applicable for users with the 'Trainer' role. You can skip this step.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 4: Review & Create -->
                            <div class="tab-pane" id="create-finish-step" role="tabpanel">
                                <div class="row d-flex justify-content-center">
                                    <div class="col-lg-10">
                                        <div class="text-center p-4">
                                            <span class="avatar avatar-xl avatar-rounded bg-success-transparent">
                                                <i class="ri-check-line fs-1"></i>
                                            </span>
                                            <h3 class="mt-2">Ready to Create User</h3>
                                            <p class="text-muted">Please review all the information before creating the user account. You can go back to any previous step to make modifications.</p>
                                            
                                            <!-- Summary Card -->
                                            <div class="card mt-4">
                                                <div class="card-body text-start">
                                                    <h6 class="card-title">User Summary</h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p><strong>Name:</strong> <span id="createSummaryName">-</span></p>
                                                            <p><strong>Email:</strong> <span id="createSummaryEmail">-</span></p>
                                                            <p><strong>Phone:</strong> <span id="createSummaryPhone">Not provided</span></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Role:</strong> <span id="createSummaryRole">-</span></p>
                                                            <p><strong>Profile Image:</strong> <span id="createSummaryImage">Not uploaded</span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Wizard Navigation -->
                            <div class="d-flex wizard justify-content-between mt-3 flex-wrap gap-2">
                                <div class="first">
                                    <a href="javascript:void(0);" class="btn btn-light disabled" id="createFirstBtn">
                                        First
                                    </a>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <div class="previous me-2">
                                        <a href="javascript:void(0);" class="btn icon-btn btn-primary disabled" id="createPrevBtn">
                                            <i class="bx bx-left-arrow-alt me-2"></i>Back To Previous
                                        </a>
                                    </div>
                                    <div class="next">
                                        <a href="javascript:void(0);" class="btn icon-btn btn-secondary" id="createNextBtn">
                                            Next Step<i class="bx bx-right-arrow-alt ms-2"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="last">
                                    <button type="submit" class="btn btn-success" id="createFinishBtn" style="display: none;">
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        <i class="ri-user-add-line me-1"></i>Create User
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection