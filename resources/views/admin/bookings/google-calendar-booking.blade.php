@extends('layouts.master')

@section('styles')
    <!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="{{ asset('assets/libs/flatpickr/flatpickr.min.css') }}">
    <!-- Custom CSS for Google Calendar Booking -->
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
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .status-disconnected {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .availability-slot {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
            cursor: pointer;
            transition: all 0.3s ease;
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
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .form-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 8px;
            color: #4285f4;
        }
    </style>
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTIwIDNINEMzLjQ0NzcxIDMgMyAzLjQ0NzcxIDMgNFYyMEMzIDIwLjU1MjMgMy40NDc3MSAyMSA0IDIxSDIwQzIwLjU1MjMgMjEgMjEgMjAuNTUyMyAyMSAyMFY0QzIxIDMuNDQ3NzEgMjAuNTUyMyAzIDIwIDNaIiBmaWxsPSIjNDI4NUY0Ii8+CjxwYXRoIGQ9Ik0xNiA5VjdIMTRWOUg5VjExSDE0VjEzSDE2VjExSDIxVjlIMTZaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K" class="google-icon" alt="Google Calendar">
                Google Calendar Booking
            </h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Google Calendar Booking</li>
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
    <!-- Page Header Close -->

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card google-calendar-card">
                <div class="google-calendar-header">
                    <div class="d-flex align-items-center">
                        <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTI2LjY2NjcgNEg1LjMzMzMzQzQuNTk2OTUgNCA0IDQuNTk2OTUgNCA1LjMzMzMzVjI2LjY2NjdDNCAyNy40MDMgNC41OTY5NSAyOCA1LjMzMzMzIDI4SDI2LjY2NjdDMjcuNDAzIDI4IDI4IDI3LjQwMyAyOCAyNi42NjY3VjUuMzMzMzNDMjggNC41OTY5NSAyNy40MDMgNCAyNi42NjY3IDRaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMjEuMzMzMyAxMlY5LjMzMzMzSDE4LjY2NjdWMTJIMTJWMTQuNjY2N0gxOC42NjY3VjE3LjMzMzNIMjEuMzMzM1YxNC42NjY3SDI4VjEySDIxLjMzMzNaIiBmaWxsPSIjNDI4NUY0Ii8+Cjwvc3ZnPgo=" class="google-icon" alt="Google Calendar">
                        <div>
                            <h4 class="mb-1">Schedule Trainer Session with Google Calendar</h4>
                            <p class="mb-0 opacity-75">Create bookings with automatic Google Calendar events and Meet links</p>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Trainer Connection Status -->
                    <div id="trainerConnectionStatus" class="trainer-connection-status status-disconnected" style="display: none;">
                        <div class="d-flex align-items-center">
                            <i class="ri-calendar-line me-2"></i>
                            <span id="connectionStatusText">Please select a trainer to check Google Calendar connection</span>
                        </div>
                    </div>

                    <form id="googleCalendarBookingForm" action="{{ route('admin.bookings.google-calendar.store') }}" method="POST">
                        @csrf
                        
                        <!-- Trainer and Client Selection -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="ri-user-line"></i>
                                Participant Selection
                            </div>
                            
                            <div class="row">
                                <!-- Trainer Selection -->
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        <label for="trainer_id" class="form-label">Trainer <span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="trainer_id" id="trainer_id" required>
                                            <option value="">Select Trainer</option>
                                            @foreach($trainers as $trainer)
                                                <option value="{{ $trainer->id }}" {{ old('trainer_id') == $trainer->id ? 'selected' : '' }}>
                                                    {{ $trainer->name }} ({{ $trainer->email }})
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
                                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                    {{ $client->name }} ({{ $client->email }})
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
                                <i class="ri-calendar-2-line"></i>
                                Schedule Selection
                            </div>
                            
                            <div class="row">
                                <!-- Date Range -->
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="start_date" id="start_date" 
                                               value="{{ old('start_date', date('Y-m-d')) }}" required>
                                        @error('start_date')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="end_date" id="end_date" 
                                               value="{{ old('end_date', date('Y-m-d', strtotime('+7 days'))) }}" required>
                                        @error('end_date')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <button type="button" id="checkAvailabilityBtn" class="btn btn-primary btn-wave waves-effect waves-light">
                                        <i class="ri-search-line me-1"></i> Check Availability
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Loading Spinner -->
                        <div class="loading-spinner" id="loadingSpinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Checking trainer availability...</p>
                        </div>

                        <!-- Available Slots -->
                        <div id="availabilitySection" style="display: none;">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="ri-time-line"></i>
                                    Available Time Slots
                                </div>
                                <div id="availableSlots">
                                    <!-- Slots will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Selected Slot Details -->
                        <div id="selectedSlotSection" style="display: none;">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="ri-check-line"></i>
                                    Selected Session Details
                                </div>
                                
                                <div class="row">
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                        <div class="mb-3">
                                            <label class="form-label">Selected Date</label>
                                            <input type="text" class="form-control" id="selectedDate" readonly>
                                            <input type="hidden" name="booking_date" id="bookingDate">
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                        <div class="mb-3">
                                            <label class="form-label">Start Time</label>
                                            <input type="text" class="form-control" id="selectedStartTime" readonly>
                                            <input type="hidden" name="start_time" id="bookingStartTime">
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                        <div class="mb-3">
                                            <label class="form-label">End Time</label>
                                            <input type="text" class="form-control" id="selectedEndTime" readonly>
                                            <input type="hidden" name="end_time" id="bookingEndTime">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Session Notes -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="notes" class="form-label">Session Notes</label>
                                            <textarea class="form-control" name="notes" id="notes" rows="3" 
                                                      placeholder="Add any special instructions or notes for this session...">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
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
                                        <li>A Google Calendar event will be automatically created</li>
                                        <li>A Google Meet link will be generated for the session</li>
                                        <li>Both trainer and client will receive calendar invitations</li>
                                        <li>The session will appear in the trainer's Google Calendar</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="row" id="submitSection" style="display: none;">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                        <i class="ri-refresh-line me-1"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-success btn-wave waves-effect waves-light">
                                        <i class="ri-calendar-check-line me-1"></i> Create Google Calendar Booking
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
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    
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
                if (!$('#bookingDate').val() || !$('#bookingStartTime').val()) {
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
                success: function(response) {
                    const statusDiv = $('#trainerConnectionStatus');
                    const statusText = $('#connectionStatusText');
                    
                    if (response.success && response.data.connected) {
                        statusDiv.removeClass('status-disconnected').addClass('status-connected');
                        statusText.html(`
                            <i class="ri-check-line me-1"></i>
                            Google Calendar connected (${response.data.email})
                        `);
                    } else {
                        statusDiv.removeClass('status-connected').addClass('status-disconnected');
                        statusText.html(`
                            <i class="ri-close-line me-1"></i>
                            Google Calendar not connected - Events will not be created automatically
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

            if (Object.keys(availableSlots).length === 0) {
                slotsContainer.html('<p class="text-muted">No available slots found for the selected date range.</p>');
                return;
            }

            Object.keys(availableSlots).forEach(date => {
                const dayData = availableSlots[date];
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
                            ${dayData.slots.map(slot => `
                                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-2">
                                    <div class="availability-slot" onclick="selectTimeSlot('${date}', '${slot.start_time}', '${slot.end_time}')">
                                        <div class="time-slot">${slot.start_time} - ${slot.end_time}</div>
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
    </script>
@endsection