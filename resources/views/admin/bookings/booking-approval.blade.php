@extends('layouts.master')

@section('styles')
    <style>
        .booking-approval-container {
            /* background: white; */
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .approval-section {
            /* background: #f8f9fa; */
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .approval-section:hover {
            border-color: var(--primary-color, #ff6b35);
        }
        
        .setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .setting-row:last-child {
            border-bottom: none;
        }
        
        .setting-info {
            flex: 1;
        }
        
        .setting-title {
            font-size: 16px;
            font-weight: 600;
            /* color: #333; */
            margin-bottom: 5px;
        }
        
        .setting-description {
            /* color: #666; */
            font-size: 14px;
            line-height: 1.5;
        }
        
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 32px;
            background: #ccc;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .toggle-switch.active {
            background: var(--primary-color, #ff6b35);
        }
        
        .toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 26px;
            height: 26px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .toggle-switch.active .toggle-slider {
            transform: translateX(28px);
        }
        
        .save-button {
            background: var(--primary-color, #ff6b35);
            /* color: white; */
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 16px;
        }
        
        .save-button:hover {
            background: #e55a2b;
            transform: translateY(-2px);
        }
        
        .trainer-info {
             background: var(--black-1);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .trainer-info img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }
        
        .trainer-info .name {
            font-weight: 600;
            /* color: #333; */
        }
        
        .trainer-info .role {
            /* color: #666; */
            font-size: 14px;
        }
        
        .current-settings {
              background: var(--black-1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .current-settings h6 {
            color: #155724;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .current-settings .setting-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .current-settings .setting-label {
            color: #155724;
        }
        
        .current-settings .setting-value {
            font-weight: 600;
            color: #155724;
        }
        
        .additional-settings {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color, #ff6b35);
            box-shadow: none;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-left: none;
        }
    </style>
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Booking Approval Settings</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.scheduling-menu') }}">Scheduling Settings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Booking Approval</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.bookings.scheduling-menu', ['trainer_id' => request('trainer_id')]) }}" class="btn btn-secondary btn-wave waves-effect waves-light">
                <i class="ri-arrow-left-line fw-semibold align-middle me-1"></i> Back to Settings
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <div class="booking-approval-container">
        @if($trainer)
            <!-- Trainer Info -->
            <div class="trainer-info">
                 @if($trainer->profile_image && file_exists(public_path('storage/' . $trainer->profile_image)))
                    <img src="{{ asset('storage/' . $trainer->profile_image) }}" alt="{{ $trainer->name }}">
                @else
                    <div style="width:48px;height:48px;border-radius:50%;background:#e0e0e0;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:600;color:#666;">
                        {{ strtoupper(substr($trainer->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="name">{{ $trainer->name }}</div>
                    <div class="role">Personal Trainer</div>
                </div>
            </div>

            <!-- Current Settings Display -->
            @if($bookingSettings)
            <div class="current-settings">
                <h6><i class="ri-information-line me-2"></i>Current Settings</h6>
                <div class="setting-item">
                    <span class="setting-label">Self-Booking Enabled:</span>
                    <span class="setting-value">{{ $bookingSettings->allow_self_booking ? 'Yes' : 'No' }}</span>
                </div>
                <div class="setting-item">
                    <span class="setting-label">Approval Required:</span>
                    <span class="setting-value">{{ $bookingSettings->require_approval ? 'Yes' : 'No' }}</span>
                </div>
                <!-- <div class="setting-item">
                    <span class="setting-label">Advance Booking:</span>
                    <span class="setting-value">{{ $bookingSettings->advance_booking_days ?? 7 }} days</span>
                </div>
                <div class="setting-item">
                    <span class="setting-label">Cancellation Notice:</span>
                    <span class="setting-value">{{ $bookingSettings->cancellation_hours ?? 24 }} hours</span>
                </div> -->
            </div>
            @endif

            <form action="{{ route('admin.bookings.booking-approval.update') }}" method="POST">
                @csrf
                <input type="hidden" name="trainer_id" value="{{ $trainer->id }}">
                
                <!-- Main Approval Settings -->
                <div class="approval-section">
                    <!-- Enable Self-Booking -->
                    <div class="setting-row">
                        <div class="setting-info">
                            <div class="setting-title">Enable Self-Booking</div>
                            <div class="setting-description">
                                Allow clients to book sessions directly without trainer intervention. When disabled, only admins can create bookings.
                            </div>
                        </div>
                        <div class="toggle-switch {{ $bookingSettings && $bookingSettings->allow_self_booking ? 'active' : '' }}" 
                             onclick="toggleSetting(this, 'allow_self_booking')">
                            <div class="toggle-slider"></div>
                        </div>
                        <input type="hidden" name="allow_self_booking" 
                               value="{{ $bookingSettings && $bookingSettings->allow_self_booking ? '1' : '0' }}">
                    </div>

                    <!-- Require Approval -->
                    <div class="setting-row">
                        <div class="setting-info">
                            <div class="setting-title">Require Approval</div>
                            <div class="setting-description">
                                All booking requests must be approved by the trainer or admin before confirmation. Recommended for busy trainers.
                            </div>
                        </div>
                        <div class="toggle-switch {{ $bookingSettings && $bookingSettings->require_approval ? 'active' : '' }}" 
                             onclick="toggleSetting(this, 'require_approval')">
                            <div class="toggle-slider"></div>
                        </div>
                        <input type="hidden" name="require_approval" 
                               value="{{ $bookingSettings && $bookingSettings->require_approval ? '1' : '0' }}">
                    </div>

                    <!-- Allow Weekend Booking -->
                    <div class="setting-row d-none" >
                        <div class="setting-info">
                            <div class="setting-title">Allow Weekend Booking</div>
                            <div class="setting-description">
                                Enable clients to book sessions on weekends (Saturday and Sunday). Disable if trainer is not available on weekends.
                            </div>
                        </div>
                        <div class="toggle-switch {{ $bookingSettings && $bookingSettings->allow_weekend_booking ? 'active' : '' }}" 
                             onclick="toggleSetting(this, 'allow_weekend_booking')">
                            <div class="toggle-slider"></div>
                        </div>
                        <input type="hidden" name="allow_weekend_booking" 
                               value="{{ $bookingSettings && $bookingSettings->allow_weekend_booking ? '1' : '0' }}">
                    </div>
                </div>

                <!-- Additional Settings -->
                <div class="approval-section" style="display:none;">
                    <h5 class="mb-3">Booking Rules</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="advance_booking_days" class="form-label">Advance Booking Period</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="advance_booking_days" 
                                           name="advance_booking_days" 
                                           value="{{ $bookingSettings->advance_booking_days ?? 7 }}" 
                                           min="1" max="90" required>
                                    <span class="input-group-text">days</span>
                                </div>
                                <small class="text-muted">How many days in advance clients can book sessions</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cancellation_hours" class="form-label">Cancellation Notice</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cancellation_hours" 
                                           name="cancellation_hours" 
                                           value="{{ $bookingSettings->cancellation_hours ?? 24 }}" 
                                           min="1" max="168" required>
                                    <span class="input-group-text">hours</span>
                                </div>
                                <small class="text-muted">Minimum notice required for cancellations</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="earliest_booking_time" class="form-label">Earliest Booking Time</label>
                                <input type="time" class="form-control" id="earliest_booking_time" 
                                       name="earliest_booking_time" 
                                       value="{{ $bookingSettings->earliest_booking_time ?? '06:00' }}">
                                <small class="text-muted">Earliest time clients can book sessions</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="latest_booking_time" class="form-label">Latest Booking Time</label>
                                <input type="time" class="form-control" id="latest_booking_time" 
                                       name="latest_booking_time" 
                                       value="{{ $bookingSettings->latest_booking_time ?? '22:00' }}">
                                <small class="text-muted">Latest time clients can book sessions</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="save-button">
                    <i class="ri-save-line me-2"></i> Save Changes
                </button>
            </form>
        @else
            <div class="text-center py-5">
                <i class="ri-user-line fs-48 text-muted mb-3"></i>
                <h4 class="text-muted">No Trainer Selected</h4>
                <p class="text-muted">Please select a trainer from the scheduling menu to manage their booking approval settings.</p>
                <a href="{{ route('admin.bookings.scheduling-menu') }}" class="btn btn-primary">
                    <i class="ri-arrow-left-line me-2"></i> Back to Settings
                </a>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function toggleSetting(element, fieldName) {
            const isActive = element.classList.contains('active');
            const input = element.parentElement.querySelector(`input[name="${fieldName}"]`);
            
            if (isActive) {
                element.classList.remove('active');
                input.value = '0';
            } else {
                element.classList.add('active');
                input.value = '1';
            }
        }

        // Form submission with loading state
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitButton = this.querySelector('.save-button');
            submitButton.innerHTML = '<i class="ri-loader-4-line me-2 spinner-border spinner-border-sm"></i> Saving...';
            submitButton.disabled = true;
        });

        // Validation for time inputs
        document.getElementById('earliest_booking_time').addEventListener('change', function() {
            const latestTime = document.getElementById('latest_booking_time');
            if (this.value >= latestTime.value) {
                alert('Earliest booking time must be before latest booking time');
                this.focus();
            }
        });

        document.getElementById('latest_booking_time').addEventListener('change', function() {
            const earliestTime = document.getElementById('earliest_booking_time');
            if (this.value <= earliestTime.value) {
                alert('Latest booking time must be after earliest booking time');
                this.focus();
            }
        });

        // Show success/error messages
        @if(session('success'))
            // You can add a toast notification here
            console.log('Success: {{ session('success') }}');
        @endif

        @if(session('error'))
            // You can add a toast notification here
            console.log('Error: {{ session('error') }}');
        @endif
    </script>
@endsection