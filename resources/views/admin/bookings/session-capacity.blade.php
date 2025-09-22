@extends('layouts.master')

@section('styles')
    <style>
        .session-capacity-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .capacity-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .capacity-section:hover {
            border-color: var(--primary-color, #ff6b35);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color, #ff6b35);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .capacity-input-group {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .capacity-input {
            flex: 1;
            max-width: 200px;
        }
        
        .capacity-input input {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s;
        }
        
        .capacity-input input:focus {
            border-color: var(--primary-color, #ff6b35);
            outline: none;
        }
        
        .capacity-label {
            font-weight: 500;
            color: #666;
            min-width: 120px;
        }
        
        .capacity-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-top: 10px;
        }
        
        .save-button {
            background: var(--primary-color, #ff6b35);
            color: white;
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
        
        .current-settings {
            background: #e8f5e8;
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
    </style>
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Session Capacity</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.scheduling-menu') }}">Scheduling Settings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Session Capacity</li>
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

    <div class="session-capacity-container">
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
            @if($sessionCapacity)
            <div class="current-settings">
                <h6><i class="ri-information-line me-2"></i>Current Settings</h6>
                <div class="setting-item">
                    <span class="setting-label">Daily Sessions:</span>
                    <span class="setting-value">{{ $sessionCapacity->max_daily_sessions ?? 'Not set' }}</span>
                </div>
                <div class="setting-item">
                    <span class="setting-label">Weekly Sessions:</span>
                    <span class="setting-value">{{ $sessionCapacity->max_weekly_sessions ?? 'Not set' }}</span>
                </div>
                <div class="setting-item">
                    <span class="setting-label">Session Duration:</span>
                    <span class="setting-value">{{ $sessionCapacity->session_duration_minutes ?? 60 }} minutes</span>
                </div>
                <div class="setting-item">
                    <span class="setting-label">Break Between Sessions:</span>
                    <span class="setting-value">{{ $sessionCapacity->break_between_sessions_minutes ?? 15 }} minutes</span>
                </div>
            </div>
            @endif

            <form action="{{ route('admin.bookings.session-capacity.update') }}" method="POST">
                @csrf
                <input type="hidden" name="trainer_id" value="{{ $trainer->id }}">
                
                <!-- Daily Capacity Section -->
                <div class="capacity-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="ri-calendar-line"></i>
                        </div>
                        Daily Capacity
                    </div>
                    
                    <div class="capacity-input-group">
                        <div class="capacity-label">Maximum Sessions per Day:</div>
                        <div class="capacity-input">
                            <input type="number" name="max_daily_sessions" 
                                   value="{{ $sessionCapacity->max_daily_sessions ?? 8 }}" 
                                   min="1" max="20" required>
                        </div>
                    </div>
                    
                    <div class="capacity-input-group">
                        <div class="capacity-label">Session Duration:</div>
                        <div class="capacity-input">
                            <input type="number" name="session_duration_minutes" 
                                   value="{{ $sessionCapacity->session_duration_minutes ?? 60 }}" 
                                   min="15" max="180" step="15" required>
                        </div>
                        <span class="capacity-label">minutes</span>
                    </div>
                    
                    <div class="capacity-input-group">
                        <div class="capacity-label">Break Between Sessions:</div>
                        <div class="capacity-input">
                            <input type="number" name="break_between_sessions_minutes" 
                                   value="{{ $sessionCapacity->break_between_sessions_minutes ?? 15 }}" 
                                   min="0" max="60" step="5" required>
                        </div>
                        <span class="capacity-label">minutes</span>
                    </div>
                    
                    <div class="capacity-description">
                        Set the maximum number of sessions this trainer can handle per day, along with session duration and break time between sessions.
                    </div>
                </div>

                <!-- Weekly Capacity Section -->
                <div class="capacity-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="ri-calendar-week-line"></i>
                        </div>
                        Weekly Capacity
                    </div>
                    
                    <div class="capacity-input-group">
                        <div class="capacity-label">Maximum Sessions per Week:</div>
                        <div class="capacity-input">
                            <input type="number" name="max_weekly_sessions" 
                                   value="{{ $sessionCapacity->max_weekly_sessions ?? 40 }}" 
                                   min="1" max="100" required>
                        </div>
                    </div>
                    
                    <div class="capacity-description">
                        Set the maximum number of sessions this trainer can handle per week. This helps prevent overloading and ensures quality service.
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
                <p class="text-muted">Please select a trainer from the scheduling menu to manage their session capacity.</p>
                <a href="{{ route('admin.bookings.scheduling-menu') }}" class="btn btn-primary">
                    <i class="ri-arrow-left-line me-2"></i> Back to Settings
                </a>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const dailySessions = parseInt(document.querySelector('input[name="max_daily_sessions"]').value);
            const weeklySessions = parseInt(document.querySelector('input[name="max_weekly_sessions"]').value);
            
            // Basic validation: weekly sessions should be at least equal to daily sessions
            if (weeklySessions < dailySessions) {
                e.preventDefault();
                alert('Weekly sessions cannot be less than daily sessions. Please adjust your values.');
                return false;
            }
            
            // Show loading state
            const submitButton = this.querySelector('.save-button');
            submitButton.innerHTML = '<i class="ri-loader-4-line me-2 spinner-border spinner-border-sm"></i> Saving...';
            submitButton.disabled = true;
        });

        // Auto-calculate suggestions
        document.querySelector('input[name="max_daily_sessions"]').addEventListener('input', function() {
            const dailySessions = parseInt(this.value);
            const weeklyInput = document.querySelector('input[name="max_weekly_sessions"]');
            
            // Suggest weekly sessions (daily * 5 working days)
            if (dailySessions && parseInt(weeklyInput.value) < dailySessions * 5) {
                weeklyInput.value = dailySessions * 5;
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