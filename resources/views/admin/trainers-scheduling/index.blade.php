@extends('layouts.master')

@section('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <style>
        .scheduling-status {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
        }
        .status-complete {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-partial {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-incomplete {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .availability-badge {
            font-size: 0.75rem;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            margin: 0.125rem;
            display: inline-block;
        }
        .day-available {
            background-color: #dcfce7;
            color: #166534;
        }
        .day-unavailable {
            background-color: #f3f4f6;
            color: #6b7280;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
    </style>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Trainers Scheduling Management</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#!">Scheduling</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Trainers Overview</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.bookings.scheduling-menu') }}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
            <i class="ri-settings-3-line me-1"></i> Scheduling Settings
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
                            <span class="avatar avatar-md avatar-rounded bg-warning">
                                <i class="ti ti-users fs-16"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <p class="text-muted mb-0">Total Trainers</p>
                                    <h4 class="fw-semibold mt-1">{{ $stats['total_trainers'] }}</h4>
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
                                <i class="ti ti-check fs-16"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <p class="text-muted mb-0">Complete Setup</p>
                                    <h4 class="fw-semibold mt-1">{{ $stats['complete_setup'] }}</h4>
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
                                <i class="ti ti-clock fs-16"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <p class="text-muted mb-0">Partial Setup</p>
                                    <h4 class="fw-semibold mt-1">{{ $stats['partial_setup'] }}</h4>
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
                            <span class="avatar avatar-md avatar-rounded bg-danger">
                                <i class="ti ti-x-circle fs-16"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <p class="text-muted mb-0">No Setup</p>
                                    <h4 class="fw-semibold mt-1">{{ $stats['no_setup'] }}</h4>
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
                        Trainers Scheduling Management
                    </div>
                    <!-- <div class="d-flex">
                        <div class="me-3">
                            <input class="form-control" type="text" placeholder="Search Here..." id="searchInput">
                        </div>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="btn btn-primary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                Sort By<i class="ri-arrow-down-s-line align-middle ms-1 d-inline-block"></i>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a class="dropdown-item" href="javascript:void(0);">New to Old</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);">Old to New</a></li>
                            </ul>
                        </div>
                    </div> -->
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row ">
                        {{-- <div class="col-md-3">
                            <select class="form-select" id="setupStatusFilter">
                                <option value="">All Setup Status</option>
                                <option value="complete">Complete Setup</option>
                                <option value="partial">Partial Setup</option>
                                <option value="none">No Setup</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="availabilityFilter">
                                <option value="">All Availability</option>
                                <option value="available">Available</option>
                                <option value="busy">Busy</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div> --}}
                        <!-- <div class="col-md-3">
                            <button class="btn btn-success btn-sm" id="exportBtn">
                                <i class="ri-download-2-line me-1"></i>Export
                            </button>
                        </div> -->
                    </div>

                    <!-- Success/Error Messages -->
                    <div id="alert-container"></div>

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table id="trainers-scheduling-table" class="table text-nowrap table-striped table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Trainer</th>
                                    <th>Email</th>
                                    <th>Weekly Availability</th>
                                    <th>Blocked Times</th>
                                    <th>Session Capacity</th>
                                    <th>Booking Approval</th>
                                    <th>Setup Status</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trainers as $trainer)
                                <tr>
                                    <td>{{ $trainer->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($trainer->profile_image)
                                                <img src="{{ asset('storage/' . $trainer->profile_image) }}" alt="{{ $trainer->name }}" class="rounded-circle me-2" width="32" height="32">
                                            @else
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                    <span class="text-white fw-bold">{{ substr($trainer->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <strong>{{ $trainer->name }}</strong>
                                                @if($trainer->phone)
                                                    <br><small class="text-muted">{{ $trainer->phone }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $trainer->email }}</td>
                                    <td>
                                        <div class="availability-container">
                                            @if($trainer->availabilities->count() > 0)
                                                @php
                                                    $daysOfWeek = [
                                                        0 => 'Sunday',
                                                        1 => 'Monday', 
                                                        2 => 'Tuesday',
                                                        3 => 'Wednesday',
                                                        4 => 'Thursday',
                                                        5 => 'Friday',
                                                        6 => 'Saturday'
                                                    ];
                                                @endphp
                                                @foreach([1, 2, 3, 4, 5, 6, 0] as $dayIndex)
                                                    @php
                                                        $dayAvailability = $trainer->availabilities->where('day_of_week', $dayIndex)->first();
                                                        $dayName = $daysOfWeek[$dayIndex];
                                                    @endphp
                                                    <div class="availability-day mb-1">
                                                        <strong>{{ $dayName }}</strong>
                                                        @if($dayAvailability)
                                                            <div class="time-slots">
                                                                @if($dayAvailability->morning_available)
                                                                    <small class="text-success">
                                                                        Morning: {{ \Carbon\Carbon::parse($dayAvailability->morning_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($dayAvailability->morning_end)->format('g:i A') }}
                                                                    </small>
                                                                @endif
                                                                @if($dayAvailability->evening_available)
                                                                    <br><small class="text-success">
                                                                        Evening: {{ \Carbon\Carbon::parse($dayAvailability->evening_start)->format('g:i A') }} - {{ \Carbon\Carbon::parse($dayAvailability->evening_end)->format('g:i A') }}
                                                                    </small>
                                                                @endif
                                                                @if(!$dayAvailability->morning_available && !$dayAvailability->evening_available)
                                                                    <small class="text-muted">Not Available</small>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <small class="text-muted">Not Available</small>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Not Set</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="block-time-container">
                                            @if($trainer->blockedTimes && $trainer->blockedTimes->count() > 0)
                                                @foreach($trainer->blockedTimes->take(3) as $blockedTime)
                                                    <div class="blocked-time-item mb-1">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="time-badge badge bg-warning-transparent">
                                                                {{ \Carbon\Carbon::parse($blockedTime->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($blockedTime->end_time)->format('g:i A') }}
                                                            </span>
                                                            <small class="text-muted">{{ $blockedTime->date ? \Carbon\Carbon::parse($blockedTime->date)->format('M d') : 'Recurring' }}</small>
                                                        </div>
                                                        @if($blockedTime->reason)
                                                            <small class="text-muted d-block">{{ Str::limit($blockedTime->reason, 30) }}</small>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                @if($trainer->blockedTimes->count() > 3)
                                                    <small class="text-muted">+{{ $trainer->blockedTimes->count() - 3 }} more</small>
                                                @endif
                                            @else
                                                <div class="text-center p-2">
                                                    <i class="ri-time-line text-muted fs-20"></i>
                                                    <br><small class="text-muted">No blocked times</small>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="capacity-container">
                                            @if($trainer->sessionCapacity)
                                                <div class="capacity-section">
                                                    <div class="capacity-header mb-2">
                                                        <strong class="text-primary">Daily Capacity</strong>
                                                    </div>
                                                    <div class="capacity-item mb-1">
                                                        <span class="capacity-label">Maximum Sessions:</span>
                                                        <span class="capacity-value">{{ $trainer->sessionCapacity->max_daily_sessions ?? 'Not set' }}</span>
                                                    </div>
                                                    
                                                    <div class="capacity-header mb-2 mt-3">
                                                        <strong class="text-info">Weekly Capacity</strong>
                                                    </div>
                                                    <div class="capacity-item mb-1">
                                                        <span class="capacity-label">Maximum Sessions:</span>
                                                        <span class="capacity-value">{{ $trainer->sessionCapacity->max_weekly_sessions ?? 'Not set' }}</span>
                                                    </div>
                                                    
                                                    <div class="capacity-header mb-2 mt-3">
                                                        <strong class="text-secondary">Session Details</strong>
                                                    </div>
                                                    <div class="capacity-item mb-1">
                                                        <span class="capacity-label">Duration:</span>
                                                        <span class="capacity-value">{{ $trainer->sessionCapacity->session_duration_minutes ?? 60 }} min</span>
                                                    </div>
                                                    <div class="capacity-item mb-1">
                                                        <span class="capacity-label">Break Time:</span>
                                                        <span class="capacity-value">{{ $trainer->sessionCapacity->break_between_sessions_minutes ?? 15 }} min</span>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center p-2">
                                                    <i class="ri-calendar-line text-muted fs-20"></i>
                                                    <br><small class="text-muted">Capacity not set</small>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="booking-approval-container">
                                            @if($trainer->bookingSettings)
                                                <div class="approval-section">
                                                    <div class="approval-header mb-2">
                                                        <strong class="text-success">Booking Approval</strong>
                                                    </div>
                                                    
                                                    <div class="approval-item mb-2">
                                                        <div class="d-flex align-items-center">
                                                            <i class="ri-user-settings-line text-primary me-2"></i>
                                                            <span class="approval-label">Client Self-Booking:</span>
                                                        </div>
                                                        <div class="mt-1">
                                                            @if($trainer->bookingSettings->auto_accept_bookings)
                                                                <span class="badge bg-success-transparent">
                                                                    <i class="ri-check-line me-1"></i>Enabled
                                                                </span>
                                                                <div class="small text-muted mt-1">
                                                                    Allow clients to book directly through the app
                                                                </div>
                                                            @else
                                                                <span class="badge bg-warning-transparent">
                                                                    <i class="ri-close-line me-1"></i>Disabled
                                                                </span>
                                                                <div class="small text-muted mt-1">
                                                                    Require trainer approval for client bookings
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center p-2">
                                                    <i class="ri-settings-3-line text-muted fs-20"></i>
                                                    <br><small class="text-muted">Settings not configured</small>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="scheduling-status {{ $trainer->setup_status_class }}">
                                            {{ $trainer->setup_status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($trainer->last_scheduling_update)
                                            {{ $trainer->last_scheduling_update->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-success" onclick="window.location.href='{{ route('admin.bookings.scheduling-menu') }}?trainer_id={{ $trainer->id }}'" title="View Details">
                                                    <i class="ri-eye-line"></i>
                                                </button>
                                                <!-- <button type="button" class="btn btn-sm btn-info" onclick="sendReminder('{{ $trainer->id }}')" title="Send Setup Reminder">
                                                    <i class="ri-mail-line"></i>
                                                </button> -->
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#trainers-scheduling-table').DataTable({
                responsive: true,
                order: [[0, 'desc']],
                pageLength: 25,
                dom: 'Bfrtip',
                buttons: [
                    
                ],
                language: {
                    search: "Search trainers:",
                    lengthMenu: "Show _MENU_ trainers per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ trainers",
                    infoEmpty: "No trainers found",
                    infoFiltered: "(filtered from _MAX_ total trainers)"
                }
            });

            // Setup Status Filter
            $('#setup-filter').on('change', function() {
                var value = $(this).val();
                if (value === '') {
                    table.column(7).search('').draw();
                } else {
                    table.column(7).search(value).draw();
                }
            });

            // Approval Filter
            $('#approval-filter').on('change', function() {
                var value = $(this).val();
                if (value === '') {
                    table.column(6).search('').draw();
                } else if (value === '1') {
                    table.column(6).search('Auto Approval').draw();
                } else {
                    table.column(6).search('Manual Approval').draw();
                }
            });

            // Export button handlers
            $('#export-excel').on('click', function() {
                table.button('.buttons-excel').trigger();
            });

            $('#export-pdf').on('click', function() {
                table.button('.buttons-pdf').trigger();
            });
        });

        // Send setup reminder function
        function sendReminder(trainerId) {
            if (confirm('Send setup reminder to this trainer?')) {
                $.ajax({
                    url: '/admin/trainers-scheduling/send-reminder',
                    method: 'POST',
                    data: {
                        trainer_id: trainerId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        showAlert('success', 'Setup reminder sent successfully!');
                    },
                    error: function(xhr) {
                        showAlert('danger', 'Failed to send reminder. Please try again.');
                    }
                });
            }
        }

        // Show alert function
        function showAlert(type, message) {
            var alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $('#alert-container').html(alertHtml);
            
            // Auto hide after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    </script>
@endsection