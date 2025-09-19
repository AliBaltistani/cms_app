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
        <h1 class="page-title fw-semibold fs-18 mb-0">Specialization Management</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Specializations</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.specializations.create') }}" class="btn btn-primary btn-wave waves-effect waves-light">
            <i class="ri-add-line align-middle me-1"></i>Add New Specialization
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['total'] }}</h3>
                        <span class="d-block text-muted">Total Specializations</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
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
                        <h3 class="fw-semibold mb-1">{{ $stats['active'] }}</h3>
                        <span class="d-block text-muted">Active Specializations</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-success-transparent">
                            <i class="ri-check-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['inactive'] }}</h3>
                        <span class="d-block text-muted">Inactive Specializations</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-warning-transparent">
                            <i class="ri-close-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['with_trainers'] }}</h3>
                        <span class="d-block text-muted">With Trainers</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-info-transparent">
                            <i class="ri-user-star-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Specializations Table -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Specializations List
                </div>
                <div class="d-flex">
                    <!-- Status Filter -->
                    <div class="me-3">
                        <select class="form-select" id="statusFilter">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Status</option>
                            <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <!-- Export Buttons -->
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="exportExcel">
                            <i class="ri-file-excel-line me-1"></i>Excel
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="exportPdf">
                            <i class="ri-file-pdf-line me-1"></i>PDF
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="specializationsTable" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Trainers Count</th>
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
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#specializationsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('admin.specializations.index') }}",
            data: function(d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description', orderable: false },
            { data: 'status', name: 'status', orderable: false },
            { data: 'trainers_count', name: 'trainers_count' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="ri-file-excel-line"></i> Excel',
                className: 'btn btn-success btn-sm d-none',
                exportOptions: {
                    columns: [0, 1, 2, 4, 5] // Exclude status and actions columns
                }
            },
            {
                extend: 'pdf',
                text: '<i class="ri-file-pdf-line"></i> PDF',
                className: 'btn btn-danger btn-sm d-none',
                exportOptions: {
                    columns: [0, 1, 2, 4, 5] // Exclude status and actions columns
                }
            }
        ],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: "No specializations found",
            zeroRecords: "No matching specializations found"
        }
    });

    // Status filter change
    $('#statusFilter').on('change', function() {
        table.ajax.reload();
        
        // Update URL without page reload
        var url = new URL(window.location);
        url.searchParams.set('status', this.value);
        window.history.pushState({}, '', url);
    });

    // Export buttons
    $('#exportExcel').on('click', function() {
        table.button('.buttons-excel').trigger();
    });

    $('#exportPdf').on('click', function() {
        table.button('.buttons-pdf').trigger();
    });

    // Toggle Status
    $(document).on('click', '.toggle-status', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var status = $(this).data('status');
        var button = $(this);
        var originalHtml = button.html();
        
        // Validate required data
        if (!id || status === undefined) {
            showAlert('error', 'Invalid data. Please refresh the page and try again.');
            return;
        }
        
        $.ajax({
            url: "{{ route('admin.specializations.toggle-status', ':id') }}".replace(':id', id),
            type: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}',
                status: status
            },
            beforeSend: function() {
                button.prop('disabled', true);
                button.html('<i class="ri-loader-2-line ri-spin"></i>');
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showAlert('success', response.message);
                    
                    // Reload table to reflect changes
                    table.ajax.reload(null, false);
                } else {
                    showAlert('error', response.message || 'Failed to update status.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                
                var message = 'An error occurred while updating status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    message = 'Specialization not found.';
                } else if (xhr.status === 403) {
                    message = 'You do not have permission to perform this action.';
                } else if (xhr.status === 422) {
                    message = 'Invalid data provided.';
                } else if (xhr.status === 500) {
                    message = 'Server error occurred. Please try again later.';
                }
                
                showAlert('error', message);
            },
            complete: function() {
                button.prop('disabled', false);
                button.html(originalHtml);
            }
        });
    });

    // Delete Specialization
    $(document).on('click', '.delete-specialization', function() {
        var id = $(this).data('id');
        var button = $(this);
        
        if (confirm('Are you sure you want to delete this specialization? This action cannot be undone.')) {
            $.ajax({
                url: "{{ route('admin.specializations.destroy', ':id') }}".replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                beforeSend: function() {
                    button.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showAlert('success', response.message);
                        
                        // Reload table
                        table.ajax.reload(null, false);
                    } else {
                        showAlert('error', response.message);
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.message || 'An error occurred while deleting specialization.';
                    showAlert('error', message);
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        }
    });

    // Show Alert Function
    function showAlert(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var iconClass = type === 'success' ? 'ri-check-line' : 'ri-error-warning-line';
        
        var alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="${iconClass} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert at the top of content
        $('.page-header-breadcrumb').after(alertHtml);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endsection