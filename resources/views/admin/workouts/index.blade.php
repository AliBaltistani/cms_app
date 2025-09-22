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
        <h1 class="page-title fw-semibold fs-18 mb-0">Workout Management</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Workouts</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('workouts.create')}}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
            <i class="ri-add-line me-1"></i> Create New Workout
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
                            <i class="ti ti-barbell fs-16"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">Total Workouts</p>
                                <h4 class="fw-semibold mt-1" id="totalWorkouts">0</h4>
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
                                <p class="text-muted mb-0">Active Workouts</p>
                                <h4 class="fw-semibold mt-1" id="activeWorkouts">0</h4>
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
                            <i class="ti ti-video fs-16"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">Total Videos</p>
                                <h4 class="fw-semibold mt-1" id="totalVideos">0</h4>
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
                            <i class="ti ti-currency-dollar fs-16"></i>
                        </span>
                    </div>
                    <div class="flex-fill ms-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <p class="text-muted mb-0">Paid Workouts</p>
                                <h4 class="fw-semibold mt-1" id="paidWorkouts">0</h4>
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
                    Workouts List
                </div>
                <!-- <div class="d-flex">
                    <div class="me-3">
                        <input class="form-control form-control-sm" type="text" placeholder="Search workouts..." id="searchInput">
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
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Filter by Price</h6></li>
                            <li><a class="dropdown-item filter-price" href="#" data-price="">All Workouts</a></li>
                            <li><a class="dropdown-item filter-price" href="#" data-price="free">Free Workouts</a></li>
                            <li><a class="dropdown-item filter-price" href="#" data-price="paid">Paid Workouts</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Filter by Videos</h6></li>
                            <li><a class="dropdown-item filter-videos" href="#" data-videos="">All Workouts</a></li>
                            <li><a class="dropdown-item filter-videos" href="#" data-videos="with">With Videos</a></li>
                            <li><a class="dropdown-item filter-videos" href="#" data-videos="without">Without Videos</a></li>
                        </ul>
                    </div>
                </div> -->
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="workoutsTable" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Workout Name</th>
                                <th>Trainer</th>
                                <th>Duration</th>
                                <th>Videos</th>
                                <th>Price</th>
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

<!-- Video Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabel">Workout Videos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="videosList">
                    <!-- Videos will be loaded here -->
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
    console.log('Table element found:', $('#workoutsTable').length > 0);
    
    // Load statistics
    loadStatistics();
    
    // Initialize DataTable
    try {
        var table = $('#workoutsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/admin/workouts',
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function(d) {
                    d.search_value = d.search.value;
                    return d;
                },
                error: function(xhr, error, thrown) {
                    console.log('DataTables AJAX Error:', error, thrown);
                    console.log('Response:', xhr.responseText);
                }
            },
            columnDefs: [
                {
                    targets: [6, 8], // is_active, actions columns
                    createdCell: function (td, cellData, rowData, row, col) {
                        $(td).html(cellData);
                    }
                }
            ],
            columns: [
                { data: 'id', name: 'id', width: '5%' },
                { 
                    data: 'name', 
                    name: 'name',
                    render: function(data, type, row) {
                        // Merge thumbnail with workout name
                        let thumbnailHtml = '';
                        if (row.thumbnail) {
                            thumbnailHtml = '<div class="d-flex align-items-center">' +
                                          '<div class="me-3">' + row.thumbnail + '</div>' +
                                          '<div>';
                        } else {
                            thumbnailHtml = '<div>';
                        }
                        
                        let nameHtml = '<div class="fw-semibold">' + data + '</div>';
                        if (row.description) {
                            nameHtml += '<small class="text-muted">' + row.description.substring(0, 50) + '...</small>';
                        }
                        
                        return thumbnailHtml + nameHtml + (row.thumbnail ? '</div></div>' : '</div>');
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
                    data: 'formatted_duration', 
                    name: 'duration',
                    render: function(data, type, row) {
                        return '<span class="badge bg-info-transparent">' + data + '</span>';
                    }
                },
                { 
                    data: 'total_videos', 
                    name: 'total_videos',
                    render: function(data, type, row) {
                        if (data > 0) {
                            return '<a href="#" onclick="showVideos(' + row.id + ')" class="badge bg-primary-transparent">' + data + ' videos</a>';
                        }
                        return '<span class="badge bg-secondary-transparent">No videos</span>';
                    }
                },
                { 
                    data: 'formatted_price', 
                    name: 'price',
                    render: function(data, type, row) {
                        if (row.price == 0) {
                            return '<span class="badge bg-success-transparent">Free</span>';
                        }
                        return '<span class="badge bg-warning-transparent">' + data + '</span>';
                    }
                },
                { 
                    data: 'is_active', 
                    name: 'is_active',
                    render: function(data, type, row) {
                        // Data is already formatted HTML from controller
                        return data;
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
                    width: '15%',
                    render: function(data, type, row) {
                        // Data is already formatted HTML from controller
                        return data;
                    }
                }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true,
            language: {
                search: "",
                searchPlaceholder: "Search workouts...",
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
        console.log('DataTable initialization failed:', error);
        $('#workoutsTable').html('<tr><td colspan="9" class="text-center text-danger">Failed to initialize data table: ' + error.message + '</td></tr>');
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

    $('.filter-price').on('click', function(e) {
        e.preventDefault();
        var price = $(this).data('price');
        $('#priceFilter').val(price);
        table.ajax.reload();
    });

    $('.filter-videos').on('click', function(e) {
        e.preventDefault();
        var videos = $(this).data('videos');
        $('#videosFilter').val(videos);
        table.ajax.reload();
    });

    // Hidden filter inputs
    $('body').append('<input type="hidden" id="statusFilter">');
    $('body').append('<input type="hidden" id="priceFilter">');
    $('body').append('<input type="hidden" id="videosFilter">');
});

// Load statistics
function loadStatistics() {
    $.ajax({
        url: '/admin/workouts/stats',
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#totalWorkouts').text(response.data.total_workouts);
                $('#activeWorkouts').text(response.data.active_workouts);
                $('#totalVideos').text(response.data.total_videos);
                $('#paidWorkouts').text(response.data.paid_workouts);
            }
        },
        error: function(xhr) {
            console.error('Failed to load statistics');
        }
    });
}

// Show videos modal
function showVideos(workoutId) {
    $.ajax({
        url: '/admin/workouts/' + workoutId + '/videos-list',
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                let videosHtml = '';
                if (response.data.length > 0) {
                    response.data.forEach(function(video) {
                        videosHtml += `
                            <div class="video-item mb-3 p-3 border rounded">
                                <div class="row">
                                    <div class="col-md-3">
                                        <img src="${video.thumbnail || '/images/default-video.jpg'}" 
                                             alt="${video.title}" class="img-fluid rounded">
                                    </div>
                                    <div class="col-md-9">
                                        <h6>${video.title}</h6>
                                        <p class="text-muted mb-1">Duration: ${video.duration || 'N/A'}</p>
                                        <p class="text-muted mb-1">Order: ${video.order_index}</p>
                                        <p class="mb-0">${video.description || 'No description available'}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    videosHtml = '<p class="text-center text-muted">No videos found for this workout.</p>';
                }
                
                $('#videosContent').html(videosHtml);
                $('#videosModal').modal('show');
            } else {
                alert('Failed to load videos: ' + response.message);
            }
        },
        error: function() {
            alert('Error loading videos');
        }
    });
}

// Action Functions
function toggleStatus(workoutId) {
    if (confirm('Are you sure you want to change the status of this workout?')) {
        $.ajax({
            url: '/admin/workouts/' + workoutId + '/toggle-status',
            type: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.message);
                    $('#workoutsTable').DataTable().ajax.reload();
                    loadStatistics(); // Reload stats
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Error: Failed to toggle workout status');
            }
        });
    }
}

function deleteWorkout(workoutId) {
    if (confirm('Are you sure you want to delete this workout? This action cannot be undone.')) {
        $.ajax({
            url: '/admin/workouts/' + workoutId,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#workoutsTable').DataTable().ajax.reload();
                    alert('Workout deleted successfully');
                } else {
                    alert('Failed to delete workout: ' + response.message);
                }
            },
            error: function() {
                alert('Error deleting workout');
            }
        });
    }
}
</script>
@endsection