@extends('layouts.master')

@section('styles')
    <!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        .google-calendar-card {
            border: 2px solid #4285f4;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.15);
        }
        
        .google-calendar-header {
            background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 20px;
        }
        
        .google-icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }
        
        .trainer-connection-status {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid;
        }
        
        .status-connected {
            /* background-color: #d4edda; */
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .status-disconnected {
            /* background-color: #f8d7da; */
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .availability-slot {
            /* background: #f8f9fa; */
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .availability-slot:hover {
            border-color: #4285f4;
            background: #f0f7ff;
        }
        
        .availability-slot.selected {
            border-color: #4285f4;
            background: #e3f2fd;
            box-shadow: 0 2px 8px rgba(66, 133, 244, 0.2);
        }
        
        .time-slot {
            display: inline-block;
            background: #4285f4;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            margin: 2px;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .google-meet-info {
            /* background: #e8f5e8; */
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
             color: var(--default-text-color) !important;
        }
        
        .form-section {
            /* background: #f8f9fa; */
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--default-text-color) !important;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 8px;
            color: #4285f4;
        }
        
        .edit-mode-indicator {
            /* background: #fff3cd; */
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
            color: #856404;
            font-weight: 500;
        }
        
        .edit-mode-indicator i {
            color: #f39c12;
        }

        .select2-selection {
            color: var(--default-text-color) !important;
            background-color: var(--form-control-bg) !important;
            border: var(--bs-border-width) solid var(--bs-border-color) !important;
        }
    </style>
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">
                @if(isset($booking))
                    Edit Google Calendar Booking
                @else
                    Google Calendar Booking
                @endif
            </h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                            @if(isset($booking))
                                Edit Booking #{{ $booking->id }}
                            @else
                                Google Calendar Booking
                            @endif
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
                <i class="ri-arrow-left-line fw-semibold align-middle me-1"></i> Back to Bookings
            </a>
        </div>
    </div>

    <!-- Start::row-1 -->
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card google-calendar-card">
                <div class="google-calendar-header">
                    <div class="d-flex align-items-center">
                        <i class="ri-google-line fs-24 me-3"></i>
                        <div>
                            <h4 class="mb-1">
                                @if(isset($booking))
                                    Edit Google Calendar Booking
                                @else
                                    Schedule Trainer Session with Google Calendar
                                @endif
                            </h4>
                            <p class="mb-0 opacity-75">
                                @if(isset($booking))
                                    Update booking details and sync with Google Calendar
                                @else
                                    Create a new booking with automatic Google Calendar integration
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if(isset($booking))
                        <div class="edit-mode-indicator">
                            <i class="ri-edit-line me-1"></i> Editing Booking #{{ $booking->id }}
                        </div>
                    @endif

                

                    <!-- Availability Error Alert -->
                    @if ($errors->has('booking_date') || $errors->has('start_time'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ri-error-warning-line me-2"></i>
                            <strong>Availability Error:</strong>
                            @if ($errors->has('booking_date'))
                                {{ $errors->first('booking_date') }}
                            @elseif ($errors->has('start_time'))
                                {{ $errors->first('start_time') }}
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif


                    <form id="googleCalendarBookingForm" action="{{ isset($booking) ? route('admin.bookings.google-calendar.update', $booking->id) : route('admin.bookings.google-calendar.store') }}" method="POST">
                        @csrf
                        @if(isset($booking))
                            @method('PUT')
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="update_google_calendar" value="1">
                            <input type="hidden" name="original_date" value="{{ $booking->date->format('Y-m-d') }}">
                            <input type="hidden" name="original_start_time" value="{{ $booking->start_time }}">
                            <input type="hidden" name="original_end_time" value="{{ $booking->end_time }}">
                        @endif

                        <!-- Participant Selection -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="ri-user-line me-2"></i>Participant Selection
                            </div>
                              
                            
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        <label for="trainer_id" class="form-label">Select Trainer <span class="text-danger">*</span></label>
                                        {{-- <select class="form-control select2" name="trainer_id" id="trainer_id" required> --}}
                                        <select class="form-control " name="trainer_id" id="trainer_id" required>
                                            <option value="">Choose a trainer...</option>
                                            @foreach($trainers as $trainer)
                                                <option value="{{ $trainer->id }}" 
                                                    {{ (isset($booking) && $booking->trainer_id == $trainer->id) || old('trainer_id') == $trainer->id ? 'selected' : '' }}>
                                                    {{ $trainer->name }} - {{ $trainer->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('trainer_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror

                                        <div class="row">
                                            <div class="col">
                                                <div id="trainerConnectionStatus" class="connection-status p-2" style="display: none;">
                                                    <div id="connectionStatusText"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">Select Client <span class="text-danger">*</span></label>
                                        <select class="form-control " name="client_id" id="client_id" required>
                                            <option value="">Choose a client...</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}" 
                                                    {{ (isset($booking) && $booking->client_id == $client->id) || old('client_id') == $client->id ? 'selected' : '' }}>
                                                    {{ $client->name }} - {{ $client->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('client_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date and Availability Selection -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="ri-calendar-line me-2"></i>Date and Availability Selection
                                @if(isset($booking))
                                    <small class="text-muted ms-2">(Current booking date: {{ $booking->date->format('M j, Y') }})</small>
                                @endif
                            </div>
                            
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="start_date" id="start_date" 
                                               value="{{ isset($booking) ? $booking->date->format('Y-m-d') : old('start_date') }}" required>
                                        @error('start_date')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="end_date" id="end_date" 
                                               value="{{ isset($booking) ? $booking->date->format('Y-m-d') : old('end_date') }}" required>
                                        @error('end_date')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" id="checkAvailabilityBtn">
                                        <i class="ri-search-line me-1"></i> 
                                        @if(isset($booking))
                                            Check New Availability
                                        @else
                                            Check Availability
                                        @endif
                                    </button>
                                    @if(isset($booking))
                                        <small class="text-muted d-block mt-2">
                                            <i class="ri-information-line me-1"></i>
                                            You can change the date and time by selecting new availability slots
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Loading Spinner -->
                        <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2">Checking availability...</span>
                        </div>

                        <!-- Available Time Slots -->
                        <div id="availabilitySection" style="display: none;">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="ri-time-line me-2"></i>Available Time Slots
                                    @if(isset($booking))
                                        <small class="text-muted ms-2">(Select a new time slot to reschedule)</small>
                                    @endif
                                </div>
                                <div id="availableSlots"></div>
                            </div>
                        </div>

                        <!-- Selected Session Details -->
                        <div id="selectedSlotSection" style="{{ isset($booking) ? '' : 'display: none;' }}">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="ri-calendar-check-line me-2"></i>Session Details
                                </div>
                                
                                <!-- Hidden fields for booking data -->
                                <input type="hidden" name="booking_date" id="bookingDate" value="{{ isset($booking) ? $booking->date->format('Y-m-d') : old('booking_date') }}">
                                <input type="hidden" name="start_time" id="bookingStartTime" value="{{ isset($booking) ? $booking->start_time->format('H:i') : old('start_time') }}">
                                <input type="hidden" name="end_time" id="bookingEndTime" value="{{ isset($booking) ? $booking->end_time->format('H:i') : old('end_time') }}">
                                
                                <div class="row">
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                        <div class="mb-3">
                                            <label class="form-label">Selected Date</label>
                                            <input type="text" class="form-control" id="selectedDate" readonly 
                                                   value="{{ isset($booking) ? $booking->date->format('l, F j, Y') : '' }}">
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                        <div class="mb-3">
                                            <label class="form-label">Start Time</label>
                                            <input type="text" class="form-control" id="selectedStartTime" readonly 
                                                   value="{{ isset($booking) ? $booking->start_time->format('H:i') : '' }}">
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                        <div class="mb-3">
                                            <label class="form-label">End Time</label>
                                            <input type="text" class="form-control" id="selectedEndTime" readonly 
                                                   value="{{ isset($booking) ? $booking->end_time->format('H:i') : '' }}">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Session Type and Status -->
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                        <div class="mb-3">
                                            <label for="session_type" class="form-label">Session Type</label>
                                            <select class="form-control" name="session_type" id="session_type">
                                                <option value="personal_training" {{ (isset($booking) && $booking->session_type == 'personal_training') || old('session_type') == 'personal_training' ? 'selected' : '' }}>Personal Training</option>
                                                <option value="consultation" {{ (isset($booking) && $booking->session_type == 'consultation') || old('session_type') == 'consultation' ? 'selected' : '' }}>Consultation</option>
                                                <option value="assessment" {{ (isset($booking) && $booking->session_type == 'assessment') || old('session_type') == 'assessment' ? 'selected' : '' }}>Assessment</option>
                                                <option value="follow_up" {{ (isset($booking) && $booking->session_type == 'follow_up') || old('session_type') == 'follow_up' ? 'selected' : '' }}>Follow-up</option>
                                            </select>
                                            @error('session_type')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Booking Status</label>
                                            <select class="form-control" name="status" id="status">
                                                <option value="pending" {{ (isset($booking) && $booking->status == 'pending') || old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="confirmed" {{ (isset($booking) && $booking->status == 'confirmed') || old('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                                <option value="cancelled" {{ (isset($booking) && $booking->status == 'cancelled') || old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Timezone Selection -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="timezone" class="form-label">
                                                <i class="ri-time-zone-line me-1"></i>
                                                Meeting Timezone
                                            </label>
                                            {{-- <select class="form-control select2 select2-hidden-accessible" name="timezone" id="timezone"> --}}
                                            <select class="form-control " name="timezone" id="timezone">
                                                @php
                                                    $defaultTimezone = $currentUser->timezone ?? 'UTC';
                                                    $selectedTimezone = isset($booking) ? ($booking->timezone ?? $defaultTimezone) : old('timezone', $defaultTimezone);
                                                @endphp
                                                @foreach($timezones as $timezone)
                                                    <option value="{{ $timezone }}" {{ $selectedTimezone == $timezone ? 'selected' : '' }}>
                                                        {{ $timezone }} ({{ now()->setTimezone($timezone)->format('P') }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('timezone')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                <i class="ri-information-line me-1"></i>
                                                This timezone will be used for the Google Calendar event and meeting times.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Session Notes -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="notes" class="form-label">Session Notes</label>
                                            <textarea class="form-control" name="notes" id="notes" rows="3" 
                                                      placeholder="Add any special instructions or notes for this session...">{{ isset($booking) ? $booking->notes : old('notes') }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Meeting Agenda -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="meeting_agenda" class="form-label">
                                                <i class="ri-file-list-3-line me-1"></i>
                                                Meeting Agenda <span class="text-muted">(This will be used as the calendar event title)</span>
                                            </label>
                                            <input type="text" class="form-control" name="meeting_agenda" id="meeting_agenda" 
                                                   value="{{ isset($booking) ? $booking->meeting_agenda : old('meeting_agenda') }}"
                                                   placeholder="Enter the meeting agenda or title for this session...">
                                            @error('meeting_agenda')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                <i class="ri-information-line me-1"></i>
                                                This agenda will appear as the title in Google Calendar and help participants understand the session's purpose.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Google Calendar Integration Info -->
                                <div class="google-meet-info">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="ri-video-line me-2 text-success"></i>
                                        <strong>Google Calendar Integration</strong>
                                    </div>
                                    <ul class="mb-0">
                                        @if(isset($booking))
                                            @if($booking->hasGoogleCalendarEvent())
                                                <li>Google Calendar event is already created</li>
                                                @if($booking->hasGoogleMeetLink())
                                                    <li>Google Meet link: <a href="{{ $booking->meet_link }}" target="_blank">{{ $booking->meet_link }}</a></li>
                                                @endif
                                                <li>Updates will sync with Google Calendar</li>
                                            @else
                                                <li>Google Calendar event will be created upon saving</li>
                                                <li>A Google Meet link will be generated for the session</li>
                                            @endif
                                        @else
                                            <li>A Google Calendar event will be automatically created</li>
                                            <li>A Google Meet link will be generated for the session</li>
                                            <li>Both trainer and client will receive calendar invitations</li>
                                            <li>The session will appear in the trainer's Google Calendar</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="booking-actions">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    @if(isset($booking))
                                        <button type="button" class="delete-btn" onclick="confirmDelete('{{ $booking->id }}')">
                                            <i class="ri-delete-bin-line me-1"></i> Delete Booking
                                        </button>
                                    @endif
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                        <i class="ri-refresh-line me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-success btn-wave waves-effect waves-light" id="submitBtn">
                                        <i class="ri-calendar-check-line me-1"></i> 
                                        @if(isset($booking))
                                            Update Google Calendar Booking
                                        @else
                                            Create Google Calendar Booking
                                        @endif
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

    <!-- Delete Confirmation Modal -->
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
@endsection

@section('scripts')
    <!-- Select2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            $('#start_date, #end_date').attr('min', today);

            // Update end date when start date changes
            $('#start_date').on('change', function() {
                const startDate = new Date(this.value);
                const endDate = new Date(startDate);
                endDate.setDate(endDate.getDate() + 7); // Default to 7 days later
                
                $('#end_date').attr('min', this.value);
                if ($('#end_date').val() < this.value) {
                    $('#end_date').val(endDate.toISOString().split('T')[0]);
                }
            });

            // Check trainer Google Calendar connection when trainer is selected
            $('#trainer_id').on('change', function() {
                const trainerId = $(this).val();
                if (trainerId) {
                    checkTrainerGoogleConnection(trainerId);
                } else {
                    $('#trainerConnectionStatus').hide();
                }
            });

            // Check availability button
            $('#checkAvailabilityBtn').on('click', function() {
                checkAvailability();
            });

            // Form submission
            $('#googleCalendarBookingForm').on('submit', function(e) {
                const bookingDate = $('#bookingDate').val();
                const bookingStartTime = $('#bookingStartTime').val();
                const trainerId = $('#trainer_id').val();
                const clientId = $('#client_id').val();
                
                console.log('Form submission validation:', {
                    bookingDate: bookingDate,
                    bookingStartTime: bookingStartTime,
                    trainerId: trainerId,
                    clientId: clientId
                });
                
                if (!trainerId) {
                    e.preventDefault();
                    alert('Please select a trainer.');
                    return false;
                }
                
                if (!clientId) {
                    e.preventDefault();
                    alert('Please select a client.');
                    return false;
                }
                
                if (!bookingDate || !bookingStartTime) {
                    e.preventDefault();
                    alert('Please select a time slot before creating the booking.');
                    return false;
                }
            });
        });

        function checkTrainerGoogleConnection(trainerId) {
            $.ajax({
                url: '{{ route("admin.bookings.trainer.google-connection", ":trainerId") }}'.replace(':trainerId', trainerId),
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                        var statusDiv = $('#trainerConnectionStatus');
                        var statusText = $('#connectionStatusText');

                        // Reset classes and show a loading indication
                        statusDiv.removeClass('status-connected status-disconnected');
                        statusText.html('<span class="text-info"><i class="ri-loader-2-line ri-spin me-1 text-info"></i> Checking Google Calendar connection...</span>');
                        statusDiv.show();
                
                },
                success: function(response) {
                    const statusDiv = $('#trainerConnectionStatus');
                    const statusText = $('#connectionStatusText');
                    
                    if (response.success && response.connected) {
                        statusDiv.removeClass('status-disconnected').addClass('status-connected');
                        statusText.html(`
                            <i class="ri-check-line me-1"></i>
                            Google Calendar connected
                        `);
                    } else {
                        statusDiv.removeClass('status-connected').addClass('status-disconnected');
                        const connectUrl = '{{ route("admin.google.connect", ["trainerId" => "__TRAINER_ID__"]) }}'.replace('__TRAINER_ID__', trainerId);
                        statusText.html(`
                            <i class="ri-close-line me-1"></i>
                            Google Calendar not connected - Events will not be created automatically
                            <a href="${connectUrl}" class="btn btn-primary btn-sm">
                                <i class="ri-google-line me-2"></i>connect now
                            </a>
                        `);
                    }
                    statusDiv.show();
                },
                error: function() {
                    const statusDiv = $('#trainerConnectionStatus');
                    const statusText = $('#connectionStatusText');
                    
                    statusDiv.removeClass('status-connected').addClass('status-disconnected');
                    statusText.html(`
                        <i class="ri-error-warning-line me-1"></i>
                        Unable to check Google Calendar connection
                    `);
                    statusDiv.show();
                }
            });
        }

        function checkAvailability() {
            const trainerId = $('#trainer_id').val();
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();

            if (!trainerId || !startDate || !endDate) {
                alert('Please select trainer and date range first.');
                return;
            }

            $('#loadingSpinner').show();
            $('#availabilitySection').hide();
            $('#selectedSlotSection').hide();
            $('#submitSection').hide();

            $.ajax({
                url: '{{ route("admin.bookings.trainer.available-slots") }}',
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    trainer_id: trainerId,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    $('#loadingSpinner').hide();
                    
                    if (response.success && response.data && response.data.available_slots) {
                        displayAvailableSlots(response.data.available_slots);
                        $('#availabilitySection').show();
                    } else {
                        alert('No available slots found for the selected date range.');
                    }
                },
                error: function(xhr) {
                    $('#loadingSpinner').hide();
                    const errorMsg = xhr.responseJSON?.message || 'Error checking availability';
                    alert('Error: ' + errorMsg);
                }
            });
        }

        function displayAvailableSlots(availableSlots) {
            const slotsContainer = $('#availableSlots');
            slotsContainer.empty();

            if (!Array.isArray(availableSlots) || availableSlots.length === 0) {
                slotsContainer.html('<p class="text-muted">No available slots found for the selected date range.</p>');
                return;
            }

            // Group slots by date
            const slotsByDate = {};
            availableSlots.forEach(slot => {
                const date = slot.date;
                if (!slotsByDate[date]) {
                    slotsByDate[date] = [];
                }
                slotsByDate[date].push(slot);
            });

            // Display slots grouped by date
            Object.keys(slotsByDate).forEach(date => {
                const daySlots = slotsByDate[date];
                const dateObj = new Date(date);
                const formattedDate = dateObj.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });

                const dayHtml = `
                    <div class="mb-4">
                        <h6 class="mb-3">${formattedDate}</h6>
                        <div class="row">
                            ${daySlots.map(slot => `
                                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-2">
                                    <div class="availability-slot" onclick="selectTimeSlot('${date}', '${slot.start_time}', '${slot.end_time}')">
                                        <div class="time-slot">${slot.display || (slot.start_time + ' - ' + slot.end_time)}</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                slotsContainer.append(dayHtml);
            });
        }

        function selectTimeSlot(date, startTime, endTime) {
            // Remove previous selection
            $('.availability-slot').removeClass('selected');
            
            // Add selection to clicked slot
            event.currentTarget.classList.add('selected');

            // Update form fields
            const dateObj = new Date(date);
            const formattedDate = dateObj.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });

            $('#selectedDate').val(formattedDate);
            $('#bookingDate').val(date);
            $('#selectedStartTime').val(startTime);
            $('#bookingStartTime').val(startTime);
            $('#selectedEndTime').val(endTime);
            $('#bookingEndTime').val(endTime);

            // Show selected slot section and submit button
            $('#selectedSlotSection').show();
            $('#submitSection').show();

            // Scroll to selected section
            $('html, body').animate({
                scrollTop: $('#selectedSlotSection').offset().top - 100
            }, 500);
        }

        function resetForm() {
             $('#googleCalendarBookingForm')[0].reset();
             $('.select2').val(null).trigger('change');
             $('.availability-slot').removeClass('selected');
             $('#availabilitySection').hide();
             $('#selectedSlotSection').hide();
             $('#submitSection').hide();
             $('#trainerConnectionStatus').hide();
         }

         function confirmDelete(bookingId) {
             $('#deleteForm').attr('action', '{{ route("admin.bookings.destroy", ":id") }}'.replace(':id', bookingId));
             $('#deleteModal').modal('show');
         }

         // Form submission handling
         $('#googleCalendarBookingForm').on('submit', function(e) {
             @if(isset($booking))
             // For editing, show confirmation if date/time changed
             const originalDate = $('input[name="original_date"]').val();
             const originalStartTime = $('input[name="original_start_time"]').val();
             const originalEndTime = $('input[name="original_end_time"]').val();
             
             const newDate = $('#bookingDate').val();
             const newStartTime = $('#bookingStartTime').val();
             const newEndTime = $('#bookingEndTime').val();
             
             if (originalDate !== newDate || originalStartTime !== newStartTime || originalEndTime !== newEndTime) {
                 if (!confirm('This will update the Google Calendar event. Are you sure you want to continue?')) {
                     e.preventDefault();
                     return false;
                 }
             }
             @else
             // For new bookings, ensure a time slot is selected
             if (!$('#bookingDate').val() || !$('#bookingStartTime').val() || !$('#bookingEndTime').val()) {
                 e.preventDefault();
                 alert('Please select a time slot before submitting.');
                 return false;
             }
             @endif
         });

         // Auto-check trainer connection if editing existing booking
         @if(isset($booking))
         $(document).ready(function() {
             if ($('#trainer_id').val()) {
                 checkTrainerGoogleConnection($('#trainer_id').val());
             }
             
             // Show availability section for editing
             $('#availabilitySection').show();
         });
         @endif
     </script>
 @endsection