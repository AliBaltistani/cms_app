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
        <h1 class="page-title fw-semibold fs-18 mb-0">Goals Management</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Goals</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('trainer.goals.create')}}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
            <i class="ri-add-line me-1"></i> Create New Goal
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
                            <i class="ti ti-target fs-16"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">Total Goals</p>
                                <h4 class="fw-semibold mt-1">{{ $stats['total_goals'] }}</h4>
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
                                <p class="text-muted mb-0">Active Goals</p>
                                <h4 class="fw-semibold mt-1">{{ $stats['active_goals'] }}</h4>
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
                            <i class="ti ti-pause-circle fs-16"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">Inactive Goals</p>
                                <h4 class="fw-semibold mt-1">{{ $stats['inactive_goals'] }}</h4>
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
                                <p class="text-muted mb-0">Goals with Users</p>
                                <h4 class="fw-semibold mt-1">{{ $stats['goals_with_users'] }}</h4>
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
                    Goals List
                </div>
                <!-- <div class="d-flex">
                    <div class="me-3">
                        <input class="form-control form-control-sm" type="text" placeholder="Search goals..." id="searchInput">
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ri-filter-3-line me-1"></i> Filters
                        </button>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header">Filter by Status</h6></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="">All Status</a></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="1">Active</a></li>
                            <li><a class="dropdown-item filter-status" href="#" data-status="0">Inactive</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Filter by User</h6></li>
                            <li><a class="dropdown-item filter-user" href="#" data-user="">All Users</a></li>
                            <li><a class="dropdown-item filter-user" href="#" data-user="assigned">With Users</a></li>
                            <li><a class="dropdown-item filter-user" href="#" data-user="unassigned">Unassigned</a></li>
                        </ul>
                    </div>
                </div> -->
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="goalsTable" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Goal Name</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Updated Date</th>
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
    console.log('Table element found:', $('#goalsTable').length > 0);
    
    // Initialize DataTable
    try {
        var table = $('#goalsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("trainer.goals.index") }}',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: function(d) {
                    d.status_filter = $('#statusFilter').val();
                    d.user_filter = $('#userFilter').val();
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX Error:', error, thrown);
                    console.error('Response:', xhr.responseText);
                }
            },
            columns: [
                { data: 'id', name: 'id', width: '5%' },
                { 
                    data: 'name', 
                    name: 'name',
                    render: function(data, type, row) {
                        return '<div class="fw-semibold">' + data + '</div>';
                    }
                },
                { 
                    data: 'user', 
                    name: 'user',
                    render: function(data, type, row) {
                        return data || '<span class="text-muted">Unassigned</span>';
                    }
                },
                { 
                    data: 'status', 
                    name: 'status',
                    render: function(data, type, row) {
                        if (data == 1) {
                            return '<span class="badge bg-success-transparent">Active</span>';
                        } else {
                            return '<span class="badge bg-danger-transparent">Inactive</span>';
                        }
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
                    data: 'updated_at', 
                    name: 'updated_at',
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
                searchPlaceholder: "Search goals...",
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
        $('#goalsTable').html('<tr><td colspan="7" class="text-center text-danger">Failed to initialize data table: ' + error.message + '</td></tr>');
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

    $('.filter-user').on('click', function(e) {
        e.preventDefault();
        var user = $(this).data('user');
        $('#userFilter').val(user);
        table.ajax.reload();
    });

    // Hidden filter inputs
    $('body').append('<input type="hidden" id="statusFilter">');
    $('body').append('<input type="hidden" id="userFilter">');
});

// Action Functions
function toggleStatus(goalId) {
    if (confirm('Are you sure you want to change the status of this goal?')) {
        $.ajax({
            url: '{{ url('trainer/goals') }}' + '/' + goalId + '/toggle-status',
            type: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    $('#goalsTable').DataTable().ajax.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error: Failed to toggle goal status');
            }
        });
    }
}

function deleteGoal(goalId) {
    if (confirm('Are you sure you want to delete this goal? This action cannot be undone!')) {
        $.ajax({
            url: '{{ url('trainer/goals') }}' + '/' + goalId,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    $('#goalsTable').DataTable().ajax.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error: Failed to delete goal');
            }
        });
    }
}
</script>
@endsection