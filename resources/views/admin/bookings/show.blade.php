@extends('layouts.master')

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Booking Details</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Booking #{{ $booking->id }}</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.bookings.google-calendar.edit', $booking->id) }}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
                <i class="ri-edit-line fw-semibold align-middle me-1"></i> Edit Booking
            </a>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
                <i class="ri-arrow-left-line fw-semibold align-middle me-1"></i> Back to Bookings
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Start::row-1 -->
    <div class="row">
        <!-- Booking Information -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Booking Information
                    </div>
                    <div class="ms-auto">
                        @if($booking->status == 'pending')
                            <span class="badge bg-warning-transparent fs-12">Pending Approval</span>
                        @elseif($booking->status == 'confirmed')
                            <span class="badge bg-success-transparent fs-12">Confirmed</span>
                        @else
                            <span class="badge bg-danger-transparent fs-12">Cancelled</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-muted">Booking ID:</td>
                                            <td>#{{ $booking->id }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Date:</td>
                                            <td>
                                                <span class="fw-semibold">{{ $booking->date->format('l, F d, Y') }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Time:</td>
                                            <td>
                                                <span class="fw-semibold">{{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }}</span>
                                                <small class="text-muted ms-2">({{ $booking->getDurationInMinutes() }} minutes)</small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Status:</td>
                                            <td>
                                                @if($booking->status == 'pending')
                                                    <span class="badge bg-warning-transparent">Pending Approval</span>
                                                @elseif($booking->status == 'confirmed')
                                                    <span class="badge bg-success-transparent">Confirmed</span>
                                                @else
                                                    <span class="badge bg-danger-transparent">Cancelled</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Created:</td>
                                            <td>{{ $booking->created_at->format('M d, Y h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Last Updated:</td>
                                            <td>{{ $booking->updated_at->format('M d, Y h:i A') }}</td>
                                        </tr>
                                        @if($booking->notes)
                                            <tr>
                                                <td class="fw-semibold text-muted">Notes:</td>
                                                <td>
                                                    <div class="p-3 bg-light rounded">
                                                        {{ $booking->notes }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Quick Actions
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($booking->status == 'pending')
                            <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="trainer_id" value="{{ $booking->trainer_id }}">
                                <input type="hidden" name="client_id" value="{{ $booking->client_id }}">
                                <input type="hidden" name="date" value="{{ $booking->date->format('Y-m-d') }}">
                                <input type="hidden" name="start_time" value="{{ $booking->start_time->format('H:i') }}">
                                <input type="hidden" name="end_time" value="{{ $booking->end_time->format('H:i') }}">
                                <input type="hidden" name="status" value="confirmed">
                                <input type="hidden" name="notes" value="{{ $booking->notes }}">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="ri-check-line me-1"></i> Confirm Booking
                                </button>
                            </form>
                            
                            <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="trainer_id" value="{{ $booking->trainer_id }}">
                                <input type="hidden" name="client_id" value="{{ $booking->client_id }}">
                                <input type="hidden" name="date" value="{{ $booking->date->format('Y-m-d') }}">
                                <input type="hidden" name="start_time" value="{{ $booking->start_time->format('H:i') }}">
                                <input type="hidden" name="end_time" value="{{ $booking->end_time->format('H:i') }}">
                                <input type="hidden" name="status" value="cancelled">
                                <input type="hidden" name="notes" value="{{ $booking->notes }}">
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="ri-close-line me-1"></i> Cancel Booking
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-primary w-100">
                            <i class="ri-edit-line me-1"></i> Edit Booking
                        </a>
                        
                        <button class="btn btn-danger w-100" onclick="deleteBooking('{{ $booking->id }}')">
                            <i class="ri-delete-bin-line me-1"></i> Delete Booking
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->

    <!-- Start::row-2 -->
    <div class="row">
        <!-- Trainer Information -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Trainer Information
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-lg me-3">
                            @if($booking->trainer->profile_image && file_exists(public_path('storage/' . $booking->trainer->profile_image)))
                                <img src="{{ asset('storage/' . $booking->trainer->profile_image) }}" 
                                     alt="trainer" class="avatar-img rounded-circle">
                            @else
                                <div class="avatar-img rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold fs-4">
                                    {{ strtoupper(substr($booking->trainer->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-1">{{ $booking->trainer->name }}</h6>
                            <p class="text-muted mb-0">{{ $booking->trainer->designation ?? 'Personal Trainer' }}</p>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm">
                            <tbody>
                                <tr>
                                    <td class="fw-semibold text-muted">Email:</td>
                                    <td>
                                        <a href="mailto:{{ $booking->trainer->email }}">{{ $booking->trainer->email }}</a>
                                    </td>
                                </tr>
                                @if($booking->trainer->phone)
                                    <tr>
                                        <td class="fw-semibold text-muted">Phone:</td>
                                        <td>
                                            <a href="tel:{{ $booking->trainer->phone }}">{{ $booking->trainer->phone }}</a>
                                        </td>
                                    </tr>
                                @endif
                                @if($booking->trainer->experience)
                                    <tr>
                                        <td class="fw-semibold text-muted">Experience:</td>
                                        <td>{{ $booking->trainer->experience }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Client Information -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Client Information
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-lg me-3">
                            @if($booking->client->profile_image && file_exists(public_path('storage/' . $booking->client->profile_image)))
                                <img src="{{ asset('storage/' . $booking->client->profile_image) }}" 
                                     alt="client" class="avatar-img rounded-circle">
                            @else
                                <div class="avatar-img rounded-circle bg-success d-flex align-items-center justify-content-center text-white fw-bold fs-4">
                                    {{ strtoupper(substr($booking->client->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-1">{{ $booking->client->name }}</h6>
                            <p class="text-muted mb-0">Client</p>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm">
                            <tbody>
                                <tr>
                                    <td class="fw-semibold text-muted">Email:</td>
                                    <td>
                                        <a href="mailto:{{ $booking->client->email }}">{{ $booking->client->email }}</a>
                                    </td>
                                </tr>
                                @if($booking->client->phone)
                                    <tr>
                                        <td class="fw-semibold text-muted">Phone:</td>
                                        <td>
                                            <a href="tel:{{ $booking->client->phone }}">{{ $booking->client->phone }}</a>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="fw-semibold text-muted">Member Since:</td>
                                    <td>{{ $booking->client->created_at ? $booking->client->created_at->format('M Y') : 'N/A' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-2 -->

    <!-- Start::row-3 - Google Calendar Integration -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title d-flex align-items-center">
                        <i class="ri-google-line me-2 text-primary"></i>
                        Google Calendar Integration
                    </div>
                </div>
                <div class="card-body">
                    @if($booking->google_event_id)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semibold text-muted">Event Status:</td>
                                                <td>
                                                    <span class="badge bg-success-transparent">
                                                        <i class="ri-check-line me-1"></i>Synced with Google Calendar
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted">Event ID:</td>
                                                <td>
                                                    <code class="text-muted">{{ $booking->google_event_id }}</code>
                                                </td>
                                            </tr>
                                            @if($booking->meet_link)
                                                <tr>
                                                    <td class="fw-semibold text-muted">Google Meet:</td>
                                                    <td>
                                                        <a href="{{ $booking->meet_link }}" target="_blank" class="btn btn-sm btn-primary">
                                                            <i class="ri-video-line me-1"></i>Join Meeting
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td class="fw-semibold text-muted">Sync Status:</td>
                                                <td>
                                                    <span class="badge bg-success">Synced</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted">Last Synced:</td>
                                                <td>{{ $booking->updated_at->format('M d, Y H:i A') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    @if($booking->meet_link)
                                        <div class="alert alert-info" role="alert">
                                            <i class="ri-information-line me-2"></i>
                                            <strong>Google Meet Ready:</strong> The video conference link has been automatically generated and shared with participants.
                                        </div>
                                    @endif
                                    
                                    <button class="btn btn-outline-primary" onclick="syncWithGoogleCalendar('{{ $booking->id }}')">
                                        <i class="ri-refresh-line me-1"></i>Re-sync with Google Calendar
                                    </button>
                                    
                                    @if($booking->google_event_id)
                                        <a href="https://calendar.google.com/calendar/u/0/r/day/{{ $booking->date->format('Y/m/d') }}?pli=1" 
                                           target="_blank" class="btn btn-outline-success">
                                            <i class="ri-external-link-line me-1"></i>View in Google Calendar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="ri-calendar-line display-4 text-muted"></i>
                            </div>
                            <h6 class="text-muted mb-2">Not Synced with Google Calendar</h6>
                            <p class="text-muted mb-3">This booking hasn't been synced with Google Calendar yet.</p>
                            
                            @if($booking->trainer->google_calendar_connected)
                                <button class="btn btn-primary" onclick="syncWithGoogleCalendar('{{ $booking->id }}')">
                                    <i class="ri-google-line me-1"></i>Sync with Google Calendar
                                </button>
                            @else
                                <div class="alert alert-warning" role="alert">
                                    <i class="ri-alert-line me-2"></i>
                                    <strong>Trainer's Google Calendar Not Connected:</strong> 
                                    The trainer needs to connect their Google Calendar first.
                                    <a href="{{ route('admin.trainers.edit', $booking->trainer->id) }}" class="alert-link">
                                        Connect Google Calendar
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-3 -->

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
                    <div class="alert alert-warning" role="alert">
                        <i class="ri-alert-line me-2"></i>
                        <strong>Warning:</strong> Deleting this booking will permanently remove all associated data.
                    </div>
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
@endsection

@section('scripts')
    <script>
        function deleteBooking(id) {
            $('#deleteForm').attr('action', '/admin/bookings/google-calendar/' + id);
            $('#deleteModal').modal('show');
        }

        function syncWithGoogleCalendar(bookingId) {
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="ri-loader-2-line me-1 spinner-border spinner-border-sm"></i>Syncing...';
            button.disabled = true;

            // Make AJAX request to sync with Google Calendar
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
                    // Show success message
                    showAlert('success', 'Google Calendar Sync', data.message || 'Booking successfully synced with Google Calendar!');
                    
                    // Reload page to show updated information
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    showAlert('error', 'Sync Failed', data.message || 'Failed to sync with Google Calendar. Please try again.');
                    
                    // Restore button state
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Sync Failed', 'An error occurred while syncing with Google Calendar. Please try again.');
                
                // Restore button state
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        function showAlert(type, title, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'ri-check-line' : 'ri-error-warning-line';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="${iconClass} me-2"></i>
                    <strong>${title}:</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Insert alert at the top of the page
            const container = document.querySelector('.main-content');
            container.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
@endsection