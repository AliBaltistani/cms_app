@extends('layouts.master')

@section('styles')
<!-- DataTables CSS from CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Nutrition Plans Management</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nutrition Plans</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('admin.nutrition-plans.create')}}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
            <i class="ri-add-line me-1"></i> Create New Plan
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <span class="avatar avatar-md avatar-rounded bg-primary">
                            <i class="ti ti-clipboard-list fs-16"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">Total Plans</p>
                                <h4 class="fw-semibold mt-1">{{ $stats['total_plans'] }}</h4>
                            </div>
                        </div>
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
                        <span class="avatar avatar-md avatar-rounded bg-success">
                            <i class="ti ti-check-circle fs-16"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">Active Plans</p>
                                <h4 class="fw-semibold mt-1">{{ $stats['active_plans'] }}</h4>
                            </div>
                        </div>
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
                        <span class="avatar avatar-md avatar-rounded bg-warning">
                            <i class="ti ti-world fs-16"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">Global Plans</p>
                                <h4 class="fw-semibold mt-1">{{ $stats['global_plans'] }}</h4>
                            </div>
                        </div>
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
                        <span class="avatar avatar-md avatar-rounded bg-info">
                            <i class="ti ti-users fs-16"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">Assigned Plans</p>
                                <h4 class="fw-semibold mt-1">{{ $stats['plans_with_clients'] }}</h4>
                            </div>
                        </div>
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
                    Nutrition Plans List
                </div>
                <div class="d-flex">
                    <div class="me-3">
                        <input class="form-control form-control-sm" type="text" placeholder="Search plans..." id="searchInput">
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ri-filter-3-line me-1"></i> Filters
                        </button>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">Filter by Status</h6></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="">All Status</a></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="active">Active</a></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="inactive">Inactive</a></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="draft">Draft</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Filter by Type</h6></li>
                            <li><a class="dropdown-item filter-global" href="#" data-global="">All Plans</a></li>
                            <li><a class="dropdown-item filter-global" href="#" data-global="1">Global Plans</a></li>
                            <li><a class="dropdown-item filter-global" href="#" data-global="0">Trainer Plans</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="nutritionPlansTable" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Plan Name</th>
                                <th>Trainer</th>
                                <th>Client</th>
                                <th>Goal Type</th>
                                <th>Meals</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Restrictions</th>
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

@section('scripts')
<!-- DataTables JS from CDN -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>

<!-- Sweet Alert -->
<script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

<script>
$(document).ready(function() {
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    console.log('DataTables available:', typeof $.fn.DataTable !== 'undefined');
    console.log('Table element found:', $('#nutritionPlansTable').length > 0);
    
    // Initialize DataTable
    try {
        var table = $('#nutritionPlansTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.nutrition-plans.index") }}',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: function(d) {
                d.status_filter = $('#statusFilter').val();
                d.trainer_filter = $('#trainerFilter').val();
                d.client_filter = $('#clientFilter').val();
                d.global_filter = $('#globalFilter').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX Error:', error, thrown);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'id', name: 'id', width: '5%' },
            { 
                data: 'plan_name', 
                name: 'plan_name',
                render: function(data, type, row) {
                    var badge = row.is_global ? '<span class="badge bg-warning-transparent ms-2">Global</span>' : '';
                    return '<div class="fw-semibold">' + data + badge + '</div>';
                }
            },
            { 
                data: 'trainer', 
                name: 'trainer',
                render: function(data, type, row) {
                    return data || '<span class="text-muted">Admin</span>';
                }
            },
            { 
                data: 'client', 
                name: 'client',
                render: function(data, type, row) {
                    return data || '<span class="text-muted">Unassigned</span>';
                }
            },
            { 
                data: 'goal_type', 
                name: 'goal_type',
                render: function(data, type, row) {
                    if (!data) return '<span class="text-muted">N/A</span>';
                    var badgeClass = {
                        'weight_loss': 'bg-danger-transparent',
                        'weight_gain': 'bg-success-transparent',
                        'maintenance': 'bg-info-transparent',
                        'muscle_gain': 'bg-warning-transparent'
                    };
                    return '<span class="badge ' + (badgeClass[row.goal_type] || 'bg-secondary-transparent') + '">' + data + '</span>';
                }
            },
            { 
                data: 'meals_count', 
                name: 'meals_count',
                render: function(data, type, row) {
                    return '<span class="badge bg-primary-transparent">' + data + ' meals</span>';
                }
            },
            { data: 'duration', name: 'duration' },
            { 
                data: 'status', 
                name: 'status',
                render: function(data, type, row) {
                    var badgeClass = {
                        'active': 'bg-success-transparent',
                        'inactive': 'bg-danger-transparent',
                        'draft': 'bg-warning-transparent'
                    };
                    return '<span class="badge ' + (badgeClass[data] || 'bg-secondary-transparent') + '">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                }
            },
            { 
                data: 'restrictions_summary', 
                name: 'restrictions_summary',
                render: function(data, type, row) {
                    if (data === 'None' || data === 'No dietary restrictions') {
                        return '<span class="text-muted">None</span>';
                    }
                    return '<span class="text-truncate" style="max-width: 150px;" title="' + data + '">' + data + '</span>';
                }
            },
            { 
                data: 'created_at', 
                name: 'created_at',
                render: function(data, type, row) {
                    return '<small class="text-muted">' + data + '</small>';
                }
            },
            { 
                data: 'actions', 
                name: 'actions', 
                orderable: false, 
                searchable: false,
                width: '15%'
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            search: "",
            searchPlaceholder: "Search plans...",
            paginate: {
                next: '<i class="ri-arrow-right-s-line"></i>',
                previous: '<i class="ri-arrow-left-s-line"></i>'
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        drawCallback: function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
    
    console.log('DataTable initialized successfully');
    
    } catch (error) {
        console.error('DataTable initialization failed:', error);
        $('#nutritionPlansTable').html('<tr><td colspan="11" class="text-center text-danger">Failed to initialize data table: ' + error.message + '</td></tr>');
    }

    // Search functionality
    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Filter functionality
    $('.filter-status').on('click', function(e) {
        e.preventDefault();
        var status = $(this).data('status');
        $('#statusFilter').val(status);
        table.ajax.reload();
    });

    $('.filter-global').on('click', function(e) {
        e.preventDefault();
        var global = $(this).data('global');
        $('#globalFilter').val(global);
        table.ajax.reload();
    });

    // Hidden filter inputs
    $('body').append('<input type="hidden" id="statusFilter">');
    $('body').append('<input type="hidden" id="trainerFilter">');
    $('body').append('<input type="hidden" id="clientFilter">');
    $('body').append('<input type="hidden" id="globalFilter">');
});

// Action Functions
function toggleStatus(planId) {
    if (confirm('Are you sure you want to change the status of this nutrition plan?')) {
        $.ajax({
            url: '/admin/nutrition-plans/' + planId + '/toggle-status',
            type: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    $('#nutritionPlansTable').DataTable().ajax.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error: Failed to toggle plan status');
            }
        });
    }
}

function duplicatePlan(planId) {
    if (confirm('This will create a copy of the nutrition plan. Continue?')) {
        $.ajax({
            url: '/admin/nutrition-plans/' + planId + '/duplicate',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    $('#nutritionPlansTable').DataTable().ajax.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error: Failed to duplicate plan');
            }
        });
    }
}

function deletePlan(planId) {
    if (confirm('Are you sure you want to delete this nutrition plan? This action cannot be undone!')) {
        $.ajax({
            url: '/admin/nutrition-plans/' + planId,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    $('#nutritionPlansTable').DataTable().ajax.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error: Failed to delete plan');
            }
        });
    }
}
</script>
@endsection