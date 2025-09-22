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
    let traineesTable = $('#traineesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '{{ route("admin.trainees.index") }}',
            data: function(d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', width: '5%' },
            { 
                data: 'profile_image', 
                name: 'profile_image', 
                orderable: false, 
                searchable: false,
                width: '8%',
                render: function(data, type, row) {
                    if (data) {
                        return `<img src="${data}" alt="Profile" class="avatar avatar-sm avatar-rounded">`;
                    }
                    return `<span class="avatar avatar-sm avatar-rounded bg-info-transparent">
                                <i class="ri-user-line"></i>
                            </span>`;
                }
            },
            { data: 'name', name: 'name', width: '15%' },
            { data: 'email', name: 'email', width: '15%' },
            { data: 'phone', name: 'phone', width: '10%' },
            { 
                data: 'goals_count', 
                name: 'goals_count', 
                width: '8%',
                render: function(data, type, row) {
                    return `<span class="badge bg-primary-transparent">${data} Goals</span>`;
                }
            },
            { 
                data: 'testimonials_count', 
                name: 'testimonials_count', 
                width: '8%',
                render: function(data, type, row) {
                    return `<span class="badge bg-success-transparent">${data} Reviews</span>`;
                }
            },
            { 
                data: 'status', 
                name: 'status', 
                width: '8%',
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
                width: '15%',
                render: function(data, type, row) {
                    return `
                      <div class="d-flex justify-content-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-info btn-wave" onclick="viewTrainee(${data})" title="View">
                                <i class="ri-eye-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-success btn-wave" onclick="editTrainee(${data})" title="Edit">
                                <i class="ri-edit-2-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning btn-wave" onclick="toggleTraineeStatus(${data})" title="Toggle Status">
                                <i class="ri-toggle-line"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-wave" onclick="deleteTrainee(${data})" title="Delete">
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
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'No trainees found',
            zeroRecords: 'No matching trainees found'
        }
    });

    // Filter handlers
    $('#statusFilter').on('change', function() {
        traineesTable.ajax.reload();
    });

    // Refresh button
    $('#refreshBtn').on('click', function() {
        traineesTable.ajax.reload();
    });
});

// View trainee function
function viewTrainee(id) {
    window.location.href = `{{ route('admin.trainees.index') }}/${id}`;
}

// Edit trainee function
function editTrainee(id) {
    window.location.href = `{{ route('admin.trainees.index') }}/${id}/edit`;
}

// Toggle trainee status
function toggleTraineeStatus(id) {
    if (confirm('Are you sure you want to toggle this trainee\'s status?')) {
        $.ajax({
            url: `{{ route('admin.trainees.index') }}/${id}/toggle-status`,
            type: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#traineesTable').DataTable().ajax.reload();
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                let message = 'Failed to update status';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert('error', message);
            }
        });
    }
}

// Delete trainee function
function deleteTrainee(id) {
    if (confirm('Are you sure you want to delete this trainee? This action cannot be undone.')) {
        $.ajax({
            url: `{{ route('admin.trainees.index') }}/${id}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#traineesTable').DataTable().ajax.reload();
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                let message = 'Failed to delete trainee';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert('error', message);
            }
        });
    }
}

// Show alert function
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('#alertContainer').html(alertHtml);
    
    // Auto hide after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}
</script>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Trainees Management</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Trainees</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.trainees.create') }}" class="btn btn-primary btn-wave">
            <i class="ri-add-line fw-semibold align-middle me-1"></i> Add New Trainee
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Alert Container -->
<div id="alertContainer"></div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="mb-2">
                            <span class="d-block fs-12 text-muted mb-1">Total Trainees</span>
                            <h4 class="fw-semibold mb-0">{{ $stats['total_trainees'] }}</h4>
                        </div>
                    </div>
                    <div>
                        <span class="avatar avatar-md bg-primary-transparent">
                            <i class="ri-user-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="mb-2">
                            <span class="d-block fs-12 text-muted mb-1">Active Trainees</span>
                            <h4 class="fw-semibold mb-0">{{ $stats['active_trainees'] }}</h4>
                        </div>
                    </div>
                    <div>
                        <span class="avatar avatar-md bg-success-transparent">
                            <i class="ri-user-check-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="mb-2">
                            <span class="d-block fs-12 text-muted mb-1">Inactive Trainees</span>
                            <h4 class="fw-semibold mb-0">{{ $stats['inactive_trainees'] }}</h4>
                        </div>
                    </div>
                    <div>
                        <span class="avatar avatar-md bg-danger-transparent">
                            <i class="ri-user-unfollow-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="mb-2">
                            <span class="d-block fs-12 text-muted mb-1">With Goals</span>
                            <h4 class="fw-semibold mb-0">{{ $stats['trainees_with_goals'] }}</h4>
                        </div>
                    </div>
                    <div>
                        <span class="avatar avatar-md bg-info-transparent">
                            <i class="ri-target-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Trainees List
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div>
                        <select class="form-select" id="statusFilter">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Status</option>
                            <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <button class="btn btn-outline-light btn-wave" id="refreshBtn">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="traineesTable" class="table table-bordered text-nowrap w-100 table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Avatar</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Goals</th>
                                <th>Reviews</th>
                                <th>Status</th>
                                <th>Created</th>
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
@endsection