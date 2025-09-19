@extends('layouts.master')

@section('styles')
    <style>
        .availability-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .day-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .day-row:last-child {
            border-bottom: none;
        }
        
        .day-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            min-width: 100px;
        }
        
        .time-slots {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .time-slot {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
            background: #ccc;
            border-radius: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .toggle-switch.active {
            background: var(--primary-color, #ff6b35);
        }
        
        .toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 22px;
            height: 22px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .toggle-switch.active .toggle-slider {
            transform: translateX(24px);
        }
        
        .time-label {
            font-size: 14px;
            color: #666;
            min-width: 120px;
        }
        
        .save-button {
            background: var(--primary-color, #ff6b35);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 30px;
            width: 100%;
        }
        
        .save-button:hover {
            background: #e55a2b;
            transform: translateY(-2px);
        }
        
        .trainer-info {
            background: #f8f9fa;
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
            color: #333;
        }
        
        .trainer-info .role {
            color: #666;
            font-size: 14px;
        }
        
        /* Spinner animation for loader icon */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Weekly Availability</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.scheduling-menu') }}">Scheduling Settings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Availability</li>
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

    <div class="availability-container">
        @if($trainer)
            <!-- Trainer Info -->
            <div class="trainer-info">
                <img src="{{ $trainer->profile_image ? asset('storage/' . $trainer->profile_image) : asset('assets/images/faces/9.jpg') }}" 
                     alt="{{ $trainer->name }}">
                <div>
                    <div class="name">{{ $trainer->name }}</div>
                    <div class="role">Personal Trainer</div>
                </div>
            </div>

            <form id="availability-form" action="{{ route('admin.bookings.availability.update') }}" method="POST">
                @csrf
                <input type="hidden" name="trainer_id" value="{{ $trainer->id }}">
                
                @php
                    $days = [
                        1 => 'Monday',
                        2 => 'Tuesday', 
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        0 => 'Sunday'
                    ];
                @endphp

                @foreach($days as $dayNumber => $dayName)
                    @php
                        $availability = $availabilities->where('day_of_week', $dayNumber)->first();
                    @endphp
                    
                    <div class="day-row">
                        <div class="day-name">{{ $dayName }}</div>
                        
                        <div class="time-slots">
                            <!-- Morning Slot -->
                            <div class="time-slot">
                                <div class="time-label">Morning 9:00 – 5:00</div>
                                <div class="toggle-switch {{ $availability && $availability->morning_available ? 'active' : '' }}" 
                                     onclick="toggleAvailability(this, 'morning', {{ $dayNumber }})">
                                    <div class="toggle-slider"></div>
                                </div>
                                <input type="hidden" name="availability[{{ $dayNumber }}][morning_available]" 
                                       value="{{ $availability && $availability->morning_available ? '1' : '0' }}">
                            </div>
                            
                            <!-- Evening Slot -->
                            <div class="time-slot">
                                <div class="time-label">Evening 5:00 – 9:00</div>
                                <div class="toggle-switch {{ $availability && $availability->evening_available ? 'active' : '' }}" 
                                     onclick="toggleAvailability(this, 'evening', {{ $dayNumber }})">
                                    <div class="toggle-slider"></div>
                                </div>
                                <input type="hidden" name="availability[{{ $dayNumber }}][evening_available]" 
                                       value="{{ $availability && $availability->evening_available ? '1' : '0' }}">
                            </div>
                        </div>
                    </div>
                @endforeach
                
                <button type="submit" class="save-button">
                    <i class="ri-save-line me-2"></i> Save Changes
                </button>
            </form>
        @else
            <div class="text-center py-5">
                <i class="ri-user-line fs-48 text-muted mb-3"></i>
                <h4 class="text-muted">No Trainer Selected</h4>
                <p class="text-muted">Please select a trainer from the scheduling menu to manage their availability.</p>
                <a href="{{ route('admin.bookings.scheduling-menu') }}" class="btn btn-primary">
                    <i class="ri-arrow-left-line me-2"></i> Back to Settings
                </a>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function toggleAvailability(element, period, day) {
            const isActive = element.classList.contains('active');
            const input = element.parentElement.querySelector('input[type="hidden"]');
            
            if (isActive) {
                element.classList.remove('active');
                input.value = '0';
            } else {
                element.classList.add('active');
                input.value = '1';
            }
        }

        // Form submission with loading state
        document.getElementById('availability-form').addEventListener('submit', function(e) {
            const submitButton = this.querySelector('.save-button');
            submitButton.innerHTML = '<i class="ri-loader-2-line me-2"></i> Saving...';
            submitButton.disabled = true;
            
            // Add spinning animation to the loader icon
            const loaderIcon = submitButton.querySelector('.ri-loader-2-line');
            if (loaderIcon) {
                loaderIcon.style.animation = 'spin 1s linear infinite';
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