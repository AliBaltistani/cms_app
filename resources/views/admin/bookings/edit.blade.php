@extends('layouts.master')

@section('styles')
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/libs/select2/css/select2.min.css') }}">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="{{ asset('assets/libs/flatpickr/flatpickr.min.css') }}">
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Edit Booking</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.show', $booking->id) }}">Booking #{{ $booking->id }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-secondary btn-wave waves-effect waves-light">
                <i class="ri-arrow-left-line fw-semibold align-middle me-1"></i> Back to Booking
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Edit Booking #{{ $booking->id }}
                    </div>
                    <div class="ms-auto">
                        @if($booking->status == 'pending')
                            <span class="badge bg-warning-transparent">Pending</span>
                        @elseif($booking->status == 'confirmed')
                            <span class="badge bg-success-transparent">Confirmed</span>
                        @else
                            <span class="badge bg-danger-transparent">Cancelled</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST" id="bookingForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <!-- Trainer Selection -->
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="trainer_id" class="form-label">Trainer <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="trainer_id" id="trainer_id" required>
                                        <option value="">Select Trainer</option>
                                        @foreach($trainers as $trainer)
                                            <option value="{{ $trainer->id }}" 
                                                {{ (old('trainer_id', $booking->trainer_id) == $trainer->id) ? 'selected' : '' }}>
                                                {{ $trainer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('trainer_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Client Selection -->
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="client_id" id="client_id" required>
                                        <option value="">Select Client</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" 
                                                {{ (old('client_id', $booking->client_id) == $client->id) ? 'selected' : '' }}>
                                                {{ $client->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Date -->
                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="date" id="date" 
                                           value="{{ old('date', $booking->date->format('Y-m-d')) }}" placeholder="Select date" required>
                                    @error('date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Start Time -->
                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="start_time" id="start_time" 
                                           value="{{ old('start_time', $booking->start_time->format('H:i')) }}" required>
                                    @error('start_time')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- End Time -->
                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="end_time" id="end_time" 
                                           value="{{ old('end_time', $booking->end_time->format('H:i')) }}" required>
                                    @error('end_time')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Status -->
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-control" name="status" id="status" required>
                                        @foreach($statuses as $key => $status)
                                            <option value="{{ $key }}" 
                                                {{ (old('status', $booking->status) == $key) ? 'selected' : '' }}>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Override Conflicts -->
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="override_conflicts" id="override_conflicts" 
                                               {{ old('override_conflicts') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="override_conflicts">
                                            Override time conflicts (Admin only)
                                        </label>
                                    </div>
                                    <small class="text-muted">Check this to allow booking even if there are time conflicts</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" id="notes" rows="4" 
                                              placeholder="Add any additional notes or instructions...">{{ old('notes', $booking->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Conflict Warning -->
                        <div class="row" id="conflictWarning" style="display: none;">
                            <div class="col-xl-12">
                                <div class="alert alert-warning" role="alert">
                                    <i class="ri-alert-line me-2"></i>
                                    <strong>Time Conflict Detected!</strong>
                                    <span id="conflictMessage"></span>
                                    <br><small>You can still proceed by checking "Override time conflicts" above.</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Booking History -->
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Booking History</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-semibold text-muted" width="150">Created:</td>
                                                        <td>{{ $booking->created_at->format('M d, Y h:i A') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted">Last Updated:</td>
                                                        <td>{{ $booking->updated_at->format('M d, Y h:i A') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-muted">Current Status:</td>
                                                        <td>
                                                            @if($booking->status == 'pending')
                                                                <span class="badge bg-warning-transparent">Pending</span>
                                                            @elseif($booking->status == 'confirmed')
                                                                <span class="badge bg-success-transparent">Confirmed</span>
                                                            @else
                                                                <span class="badge bg-danger-transparent">Cancelled</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-light">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="ri-save-line me-1"></i> Update Booking
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->
@endsection

@section('scripts')
    <!-- Select2 JS -->
    <script src="{{ asset('assets/libs/select2/js/select2.min.js') }}"></script>
    <!-- Flatpickr JS -->
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                placeholder: 'Select an option',
                allowClear: true
            });
            
            // Initialize Flatpickr for date
            flatpickr('#date', {
                dateFormat: 'Y-m-d',
                onChange: function(selectedDates, dateStr, instance) {
                    checkConflicts();
                }
            });
            
            // Check conflicts when times change
            $('#start_time, #end_time').on('change', function() {
                checkConflicts();
            });
            
            // Check conflicts when trainer changes
            $('#trainer_id').on('change', function() {
                checkConflicts();
            });
            
            // Form validation
            $('#bookingForm').on('submit', function(e) {
                const startTime = $('#start_time').val();
                const endTime = $('#end_time').val();
                
                if (startTime && endTime && startTime >= endTime) {
                    e.preventDefault();
                    alert('End time must be after start time.');
                    return false;
                }
                
                // Show loading state
                $('#submitBtn').prop('disabled', true).html('<i class="ri-loader-2-line me-1 spinner-border spinner-border-sm"></i> Updating...');
            });
            
            // Status change confirmation
            $('#status').on('change', function() {
                const newStatus = $(this).val();
                const currentStatus = '{{ $booking->status }}';
                
                if (newStatus !== currentStatus) {
                    if (newStatus === 'cancelled') {
                        if (!confirm('Are you sure you want to cancel this booking?')) {
                            $(this).val(currentStatus);
                            return;
                        }
                    } else if (newStatus === 'confirmed' && currentStatus === 'pending') {
                        if (!confirm('Are you sure you want to confirm this booking?')) {
                            $(this).val(currentStatus);
                            return;
                        }
                    }
                }
            });
        });
        
        function checkConflicts() {
            const trainerId = $('#trainer_id').val();
            const date = $('#date').val();
            const startTime = $('#start_time').val();
            const endTime = $('#end_time').val();
            const currentBookingId =  {{ $booking->id }};
            
            if (!trainerId || !date || !startTime || !endTime) {
                $('#conflictWarning').hide();
                return;
            }
            
            // Here you would make an AJAX call to check for conflicts
            // For now, we'll just show a placeholder
            // This would be implemented in a real application
            
            /*
            $.ajax({
                url: '/admin/bookings/check-conflicts',
                method: 'POST',
                data: {
                    trainer_id: trainerId,
                    date: date,
                    start_time: startTime,
                    end_time: endTime,
                    exclude_booking_id: currentBookingId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.conflicts) {
                        $('#conflictMessage').text(response.message);
                        $('#conflictWarning').show();
                    } else {
                        $('#conflictWarning').hide();
                    }
                }
            });
            */
        }
    </script>
@endsection