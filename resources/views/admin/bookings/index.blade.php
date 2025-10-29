@extends('layouts.master')


@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Booking Management</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Bookings</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <!-- <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
                <i class="ri-add-line fw-semibold align-middle me-1"></i> Create Booking
            </a> -->
            <a href="{{ route('admin.bookings.google-calendar') }}" class="btn btn-success btn-wave waves-effect waves-light me-2">
                <i class="ri-calendar-line fw-semibold align-middle me-1"></i> Google Calendar Booking
            </a>
            <a href="{{ route('admin.bookings.dashboard') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
                <i class="ri-dashboard-line fw-semibold align-middle me-1"></i> Dashboard
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        All Bookings
                    </div>
                    <div class="d-flex">
                        <div class="me-3">
                            <a href="{{ route('admin.bookings.export', request()->query()) }}" class="btn btn-success btn-sm">
                                <i class="ri-download-line me-1"></i> Export
                            </a>
                        </div>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-2-fill"></i>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a class="dropdown-item" href="javascript:void(0);" onclick="bulkAction('confirmed')">Bulk Confirm</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0);" onclick="bulkAction('cancelled')">Bulk Cancel</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-xl-12">
                            <form method="GET" action="{{ route('admin.bookings.index') }}" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="">All Status</option>
                                        @foreach($statuses as $key => $status)
                                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Trainer</label>
                                    <select name="trainer_id" class="form-select">
                                        <option value="">All Trainers</option>
                                        @foreach($trainers as $trainer)
                                            <option value="{{ $trainer->id }}" {{ request('trainer_id') == $trainer->id ? 'selected' : '' }}>{{ $trainer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Client</label>
                                    <select name="client_id" class="form-select">
                                        <option value="">All Clients</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Bookings Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap w-100" id="bookingsTable">
                            <thead>
                                <tr>
                                    <th>
                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                    </th>
                                    <th>ID</th>
                                    <th>Trainer</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Google Calendar</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $booking)
                                    <tr>
                                        <td>
                                            <input class="form-check-input booking-checkbox" type="checkbox" value="{{ $booking->id }}">
                                        </td>
                                        <td>{{ $booking->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    @if($booking->trainer->profile_image)
                                                        <img src="{{ asset('storage/' . $booking->trainer->profile_image) }}" alt="trainer" class="avatar-img rounded-circle">
                                                    @else
                                                        <div class="avatar-img rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold" style="width: 32px; height: 32px;">
                                                            {{ strtoupper(substr($booking->trainer->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $booking->trainer->name }}</span>
                                                    <br><small class="text-muted">{{ $booking->trainer->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    @if($booking->client->profile_image)
                                                        <img src="{{ asset('storage/' . $booking->client->profile_image) }}" alt="client" class="avatar-img rounded-circle">
                                                    @else
                                                        <div class="avatar-img rounded-circle bg-success d-flex align-items-center justify-content-center text-white fw-bold" style="width: 32px; height: 32px;">
                                                            {{ strtoupper(substr($booking->client->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="fw-semibold">{{ $booking->client->name }}</span>
                                                    <br><small class="text-muted">{{ $booking->client->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $booking->date->format('M d, Y') }}</span>
                                            <br><small class="text-muted">{{ $booking->date->format('l') }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $booking->start_time->format('h:i A') }}</span>
                                            <br><small class="text-muted">to {{ $booking->end_time->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($booking->status == 'pending')
                                                <span class="badge bg-warning-transparent">Pending</span>
                                            @elseif($booking->status == 'confirmed')
                                                <span class="badge bg-success-transparent">Confirmed</span>
                                            @else
                                                <span class="badge bg-danger-transparent">Cancelled</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($booking->google_event_id)
                                                <div class="d-flex align-items-center">
                                                    <i class="ri-google-line text-primary me-1"></i>
                                                    <span class="badge bg-success-transparent">Synced</span>
                                                </div>
                                                @if($booking->meet_link)
                                                    <small class="text-muted d-block">
                                                        <i class="ri-video-line me-1"></i>Meet Ready
                                                    </small>
                                                @endif
                                            @else
                                                <div class="d-flex align-items-center">
                                                    <i class="ri-calendar-line text-muted me-1"></i>
                                                    <span class="badge bg-secondary-transparent">Not Synced</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $booking->created_at->format('M d, Y') }}</span>
                                            <br><small class="text-muted">{{ $booking->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            <div class="hstack gap-2 fs-15">
                                                <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-icon btn-sm btn-info-transparent rounded-pill" title="View Details">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <a href="{{ route('admin.bookings.google-calendar.edit', $booking->id) }}" class="btn btn-icon btn-sm btn-primary-transparent rounded-pill" title="Edit Booking">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                @if($booking->google_event_id)
                                                    <button class="btn btn-icon btn-sm btn-success-transparent rounded-pill" onclick="syncToGoogleCalendar('{{ $booking->id }}')" title="Sync to Google Calendar">
                                                        <i class="ri-refresh-line"></i>
                                                    </button>
                                                    @if($booking->meet_link)
                                                        <a href="{{ $booking->meet_link }}" target="_blank" class="btn btn-icon btn-sm btn-warning-transparent rounded-pill" title="Join Google Meet">
                                                            <i class="ri-video-line"></i>
                                                        </a>
                                                    @endif
                                                @else
                                                    <button class="btn btn-icon btn-sm btn-secondary-transparent rounded-pill" onclick="createGoogleCalendarEvent('{{ $booking->id }}')" title="Create Google Calendar Event">
                                                        <i class="ri-google-line"></i>
                                                    </button>
                                                @endif
                                                <button class="btn btn-icon btn-sm btn-danger-transparent rounded-pill" onclick="deleteBooking('{{ $booking->id }}')" title="Delete Booking">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="ri-calendar-line fs-1 text-muted mb-2"></i>
                                                <h6 class="fw-semibold mb-1">No Bookings Found</h6>
                                                <p class="text-muted mb-0">There are no bookings matching your criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($bookings->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <p class="text-muted mb-0">
                                    Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of {{ $bookings->total() }} results
                                </p>
                            </div>
                            <div>
                                {{ $bookings->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="deleteModalLabel">Delete Booking</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this booking? This action will:</p>
                    <ul>
                        <li>Permanently delete the booking from the system</li>
                        <li>Remove the Google Calendar event (if exists)</li>
                        <li>Cancel any Google Meet links</li>
                        <li>This action cannot be undone</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="ri-delete-bin-line me-1"></i> Delete Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Action Modal -->
    <div class="modal fade" id="bulkActionModal" tabindex="-1" aria-labelledby="bulkActionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="bulkActionModalLabel">Bulk Action</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="bulkActionText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="bulkActionForm" method="POST" action="{{ route('admin.bookings.bulk-update') }}" style="display: inline;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="booking_ids" id="bulkBookingIds">
                        <input type="hidden" name="status" id="bulkStatus">
                        <button type="submit" class="btn btn-primary" id="bulkActionBtn">Confirm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#bookingsTable').DataTable({
                responsive: true,
                ordering: false,
                paging: false,
                searching: false,
                info: false
            });

            // Check all functionality
            $('#checkAll').change(function() {
                $('.booking-checkbox').prop('checked', $(this).prop('checked'));
            });

            $('.booking-checkbox').change(function() {
                if ($('.booking-checkbox:checked').length === $('.booking-checkbox').length) {
                    $('#checkAll').prop('checked', true);
                } else {
                    $('#checkAll').prop('checked', false);
                }
            });
        });

        function deleteBooking(id) {
            $('#deleteForm').attr('action', '/admin/bookings/google-calendar/' + id);
            $('#deleteModal').modal('show');
        }

        function bulkAction(status) {
            const checkedBoxes = $('.booking-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Please select at least one booking.');
                return;
            }

            const bookingIds = [];
            checkedBoxes.each(function() {
                bookingIds.push($(this).val());
            });

            $('#bulkBookingIds').val(JSON.stringify(bookingIds));
            $('#bulkStatus').val(status);
            
            const actionText = status === 'confirmed' ? 'confirm' : 'cancel';
            $('#bulkActionText').text(`Are you sure you want to ${actionText} ${bookingIds.length} selected booking(s)?`);
            $('#bulkActionBtn').text(status === 'confirmed' ? 'Confirm Bookings' : 'Cancel Bookings');
            
            $('#bulkActionModal').modal('show');
        }

        function syncToGoogleCalendar(bookingId) {
            if (confirm('Are you sure you want to sync this booking to Google Calendar?')) {
                // Show loading state
                const button = event.target.closest('button');
                const originalHtml = button.innerHTML;
                button.innerHTML = '<i class="ri-loader-2-line"></i>';
                button.disabled = true;

                fetch(`/admin/bookings/${bookingId}/sync-google-calendar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking synced to Google Calendar successfully!');
                        location.reload();
                    } else {
                        alert('Error syncing to Google Calendar: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error syncing to Google Calendar. Please try again.');
                })
                .finally(() => {
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                });
            }
        }

        function createGoogleCalendarEvent(bookingId) {
            if (confirm('Are you sure you want to create a Google Calendar event for this booking?')) {
                // Show loading state
                const button = event.target.closest('button');
                const originalHtml = button.innerHTML;
                button.innerHTML = '<i class="ri-loader-2-line"></i>';
                button.disabled = true;

                fetch(`/admin/bookings/${bookingId}/sync-google-calendar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Google Calendar event created successfully!');
                        location.reload();
                    } else {
                        alert('Error creating Google Calendar event: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error creating Google Calendar event. Please try again.');
                })
                .finally(() => {
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                });
            }
        }
    </script>
@endsection