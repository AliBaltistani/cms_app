@extends('layouts.master')

@section('title', 'Google Calendar Integration')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Google Calendar Integration</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('trainer.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Google Calendar</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- Page Header Close -->

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            Google Calendar Integration
                        </div>
                    </div>
                    <div class="card-body">
                        @if($isConnected)
                            <!-- Connected State -->
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="ri-check-circle-line me-2 fs-16"></i>
                                <div>
                                    <strong>Google Calendar Connected!</strong><br>
                                    Connected as: <strong>{{ $connectedEmail }}</strong>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border border-success">
                                        <div class="card-body text-center">
                                            <i class="ri-calendar-check-line text-success fs-48 mb-3"></i>
                                            <h5 class="card-title text-success">Calendar Sync Active</h5>
                                            <p class="card-text">Your bookings will automatically sync with Google Calendar and generate Google Meet links.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border border-info">
                                        <div class="card-body text-center">
                                            <i class="ri-video-line text-info fs-48 mb-3"></i>
                                            <h5 class="card-title text-info">Google Meet Integration</h5>
                                            <p class="card-text">Automatic Google Meet links will be created for all your training sessions.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h6 class="fw-semibold mb-3">Integration Features:</h6>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex align-items-center">
                                        <i class="ri-check-line text-success me-2"></i>
                                        Automatic calendar event creation for confirmed bookings
                                    </li>
                                    <li class="list-group-item d-flex align-items-center">
                                        <i class="ri-check-line text-success me-2"></i>
                                        Google Meet links generated automatically
                                    </li>
                                    <li class="list-group-item d-flex align-items-center">
                                        <i class="ri-check-line text-success me-2"></i>
                                        Real-time availability checking
                                    </li>
                                    <li class="list-group-item d-flex align-items-center">
                                        <i class="ri-check-line text-success me-2"></i>
                                        Automatic event updates when booking status changes
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-4 text-center">
                                <button type="button" class="btn btn-danger" onclick="disconnectGoogle()">
                                    <i class="ri-unlink me-2"></i>Disconnect Google Calendar
                                </button>
                            </div>
                        @else
                            <!-- Not Connected State -->
                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                <i class="ri-alert-line me-2 fs-16"></i>
                                <div>
                                    <strong>Google Calendar Not Connected</strong><br>
                                    Connect your Google Calendar to enable automatic booking sync and Google Meet integration.
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8 mx-auto">
                                    <div class="text-center mb-4">
                                        <i class="ri-calendar-line text-muted fs-64 mb-3"></i>
                                        <h4>Connect Your Google Calendar</h4>
                                        <p class="text-muted">Enhance your training sessions with automatic calendar sync and Google Meet integration.</p>
                                    </div>

                                    <div class="card border border-primary">
                                        <div class="card-body">
                                            <h6 class="fw-semibold mb-3">Benefits of connecting Google Calendar:</h6>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex align-items-center border-0 px-0">
                                                    <i class="ri-calendar-check-line text-primary me-3 fs-18"></i>
                                                    <div>
                                                        <strong>Automatic Sync</strong><br>
                                                        <small class="text-muted">All confirmed bookings automatically appear in your Google Calendar</small>
                                                    </div>
                                                </li>
                                                <li class="list-group-item d-flex align-items-center border-0 px-0">
                                                    <i class="ri-video-line text-primary me-3 fs-18"></i>
                                                    <div>
                                                        <strong>Google Meet Links</strong><br>
                                                        <small class="text-muted">Automatic video call links for virtual training sessions</small>
                                                    </div>
                                                </li>
                                                <li class="list-group-item d-flex align-items-center border-0 px-0">
                                                    <i class="ri-time-line text-primary me-3 fs-18"></i>
                                                    <div>
                                                        <strong>Real-time Availability</strong><br>
                                                        <small class="text-muted">Prevent double bookings by checking your calendar availability</small>
                                                    </div>
                                                </li>
                                                <li class="list-group-item d-flex align-items-center border-0 px-0">
                                                    <i class="ri-notification-line text-primary me-3 fs-18"></i>
                                                    <div>
                                                        <strong>Smart Notifications</strong><br>
                                                        <small class="text-muted">Get reminders and updates directly in Google Calendar</small>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <a href="{{ route('trainer.google.connect') }}" class="btn btn-primary btn-lg">
                                            <i class="ri-google-line me-2"></i>Connect Google Calendar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($isConnected)
<!-- Disconnect Confirmation Modal -->
<div class="modal fade" id="disconnectModal" tabindex="-1" aria-labelledby="disconnectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="disconnectModalLabel">Disconnect Google Calendar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="ri-alert-line text-warning fs-48"></i>
                </div>
                <p>Are you sure you want to disconnect your Google Calendar?</p>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This will:
                    <ul class="mb-0 mt-2">
                        <li>Stop automatic calendar sync</li>
                        <li>Disable Google Meet link generation</li>
                        <li>Remove real-time availability checking</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDisconnect()">
                    <i class="ri-unlink me-2"></i>Disconnect
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    function disconnectGoogle() {
        $('#disconnectModal').modal('show');
    }

    function confirmDisconnect() {
        // Show loading state
        const disconnectBtn = document.querySelector('#disconnectModal .btn-danger');
        const originalText = disconnectBtn.innerHTML;
        disconnectBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Disconnecting...';
        disconnectBtn.disabled = true;

        // Make AJAX request to disconnect
        fetch('{{ route("trainer.google.disconnect") }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show disconnected state
                window.location.reload();
            } else {
                // Show error message
                alert('Failed to disconnect Google Calendar: ' + data.message);
                disconnectBtn.innerHTML = originalText;
                disconnectBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while disconnecting Google Calendar');
            disconnectBtn.innerHTML = originalText;
            disconnectBtn.disabled = false;
        });
    }

    // Show success/error messages
    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
</script>
@endsection