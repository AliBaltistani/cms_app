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
        <h1 class="page-title fw-semibold fs-18 mb-0">User Locations Management</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">User Locations</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.user-locations.create') }}" class="btn btn-primary btn-wave waves-effect waves-light">
            <i class="ri-add-line align-middle me-1"></i>Add New Location
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
                        <h3 class="fw-semibold mb-1">{{ $stats['total'] ?? 0 }}</h3>
                        <span class="d-block text-muted">Total Locations</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
                            <i class="ri-map-pin-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['complete'] ?? 0 }}</h3>
                        <span class="d-block text-muted">Complete Addresses</span>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['countries'] ?? 0 }}</h3>
                        <span class="d-block text-muted">Countries</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-info-transparent">
                            <i class="ri-global-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['cities'] ?? 0 }}</h3>
                        <span class="d-block text-muted">Cities</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-warning-transparent">
                            <i class="ri-building-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Locations Table -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    User Locations List
                </div>
                <div class="d-flex">
                    <!-- Country Filter -->
                    <div class="me-3">
                        <select class="form-select" id="countryFilter">
                            <option value="">All Countries</option>
                            @foreach($countries as $country)
                                <option value="{{ $country }}">{{ $country }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- User Role Filter -->
                    <div class="me-3">
                        <select class="form-select" id="roleFilter">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="trainer">Trainer</option>
                            <option value="client">Client</option>
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
                    <table id="userLocationsTable" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Country</th>
                                <th>State</th>
                                <th>City</th>
                                <th>Address</th>
                                <th>Zipcode</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Bulk Actions -->
                <div class="mt-3" id="bulkActions" style="display: none;">
                    <button type="button" class="btn btn-danger btn-sm" id="bulkDelete">
                        <i class="ri-delete-bin-line me-1"></i>Delete Selected
                    </button>
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
    var table = $('#userLocationsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('admin.user-locations.index') }}",
            data: function(d) {
                d.country = $('#countryFilter').val();
                d.role = $('#roleFilter').val();
            }
        },
        columns: [
            { 
                data: 'id', 
                name: 'id',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<input type="checkbox" class="row-checkbox" value="' + data + '">';
                }
            },
            { data: 'id', name: 'id' },
            { 
                data: 'user', 
                name: 'user.name',
                render: function(data, type, row) {
                    return '<div class="d-flex align-items-center">' +
                           '<div class="avatar avatar-sm me-2">' +
                           '<img src="' + (data.profile_image || '/assets/images/faces/default-avatar.png') + '" alt="' + data.name + '" class="rounded-circle">' +
                           '</div>' +
                           '<div>' +
                           '<div class="fw-semibold">' + data.name + '</div>' +
                           '<div class="text-muted small">' + data.email + '</div>' +
                           '</div>' +
                           '</div>';
                }
            },
            { 
                data: 'user.role', 
                name: 'user.role',
                render: function(data, type, row) {
                    var badgeClass = data === 'admin' ? 'bg-danger' : 
                                   data === 'trainer' ? 'bg-success' : 'bg-primary';
                    return '<span class="badge ' + badgeClass + '">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                }
            },
            { data: 'country', name: 'country' },
            { data: 'state', name: 'state' },
            { data: 'city', name: 'city' },
            { 
                data: 'address', 
                name: 'address',
                render: function(data, type, row) {
                    return data ? (data.length > 30 ? data.substring(0, 30) + '...' : data) : '-';
                }
            },
            { data: 'zipcode', name: 'zipcode' },
            { 
                data: 'created_at', 
                name: 'created_at',
                render: function(data, type, row) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<div class="btn-group" role="group">' +
                           '<a href="/admin/user-locations/' + row.id + '" class="btn btn-sm btn-info" title="View">' +
                           '<i class="ri-eye-line"></i>' +
                           '</a>' +
                           '<a href="/admin/user-locations/' + row.id + '/edit" class="btn btn-sm btn-warning" title="Edit">' +
                           '<i class="ri-edit-line"></i>' +
                           '</a>' +
                           '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' + row.id + '" title="Delete">' +
                           '<i class="ri-delete-bin-line"></i>' +
                           '</button>' +
                           '</div>';
                }
            }
        ],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="ri-file-excel-line me-1"></i>Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="ri-file-pdf-line me-1"></i>PDF',
                className: 'btn btn-danger btn-sm'
            }
        ],
        order: [[1, 'desc']]
    });

    // Filter handlers
    $('#countryFilter, #roleFilter').change(function() {
        table.draw();
    });

    // Select all checkbox
    $('#selectAll').change(function() {
        $('.row-checkbox').prop('checked', this.checked);
        toggleBulkActions();
    });

    // Individual checkbox change
    $(document).on('change', '.row-checkbox', function() {
        toggleBulkActions();
    });

    // Toggle bulk actions visibility
    function toggleBulkActions() {
        var checkedCount = $('.row-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#bulkActions').show();
        } else {
            $('#bulkActions').hide();
        }
    }

    // Bulk delete
    $('#bulkDelete').click(function() {
        var selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select locations to delete.');
            return;
        }

        if (confirm('Are you sure you want to delete ' + selectedIds.length + ' selected location(s)?')) {
            $.ajax({
                url: "{{ route('admin.user-locations.bulk-delete') }}",
                type: 'POST',
                data: {
                    ids: selectedIds,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        table.draw();
                        $('#selectAll').prop('checked', false);
                        toggleBulkActions();
                        alert('Locations deleted successfully.');
                    } else {
                        alert('Error deleting locations: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error deleting locations. Please try again.');
                }
            });
        }
    });

    // Individual delete
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this location?')) {
            $.ajax({
                url: '/admin/user-locations/' + id,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        table.draw();
                        alert('Location deleted successfully.');
                    } else {
                        alert('Error deleting location: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error deleting location. Please try again.');
                }
            });
        }
    });
});
</script>
@endsection