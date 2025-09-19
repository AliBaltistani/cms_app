@extends('layouts.master')

@section('styles')
    <style>
        .blocked-times-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .calendar-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-nav {
            background: none;
            border: none;
            font-size: 18px;
            color: #666;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .calendar-nav:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .calendar-day-header {
            background: #e9ecef;
            padding: 12px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .calendar-day {
            background: white;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .calendar-day:hover {
            background: #f0f8ff;
        }
        
        .calendar-day.other-month {
            color: #ccc;
        }
        
        .calendar-day.blocked {
            background: #ffe6e6;
            color: #d63384;
            font-weight: 600;
        }
        
        .blocked-times-list {
            margin-bottom: 30px;
        }
        
        .blocked-time-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 4px solid #dc3545;
        }
        
        .blocked-time-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .blocked-time-icon {
            width: 40px;
            height: 40px;
            background: #dc3545;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .blocked-time-details .date {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .blocked-time-details .time {
            color: #666;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .blocked-time-details .reason {
            color: #666;
            font-size: 14px;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .delete-btn:hover {
            background: #c82333;
        }
        
        .add-blocked-time-btn {
            background: var(--primary-color, #ff6b35);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 16px;
        }
        
        .add-blocked-time-btn:hover {
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

        /* Modal Styles */
        .modal-content {
            border-radius: 12px;
            border: none;
        }
        
        .modal-header {
            border-bottom: 1px solid #f0f0f0;
            padding: 20px 30px;
        }
        
        .modal-body {
            padding: 30px;
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
            padding: 12px 16px;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color, #ff6b35);
            box-shadow: none;
        }
    </style>
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Blocked Times</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.scheduling-menu') }}">Scheduling Settings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Blocked Times</li>
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

    <div class="blocked-times-container">
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

            <!-- Calendar -->
            <div class="calendar-container">
                <div class="calendar-header">
                    <button class="calendar-nav" onclick="changeMonth(-1)">
                        <i class="ri-arrow-left-line"></i>
                    </button>
                    <h4 class="mb-0" id="calendar-month-year">{{ $currentMonth->format('F Y') }}</h4>
                    <button class="calendar-nav" onclick="changeMonth(1)">
                        <i class="ri-arrow-right-line"></i>
                    </button>
                </div>
                
                <div class="calendar-grid">
                    <!-- Day Headers -->
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    
                    <!-- Calendar Days -->
                    @foreach($calendarDays as $day)
                        <div class="calendar-day {{ $day['isOtherMonth'] ? 'other-month' : '' }} {{ $day['isBlocked'] ? 'blocked' : '' }}">
                            {{ $day['day'] }}
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Blocked Times List -->
            <div class="blocked-times-list">
                <h5 class="mb-3">Blocked Times</h5>
                
                @forelse($blockedTimes as $blockedTime)
                    <div class="blocked-time-item">
                        <div class="blocked-time-info">
                            <div class="blocked-time-icon">
                                <i class="ri-calendar-close-line"></i>
                            </div>
                            <div class="blocked-time-details">
                                <div class="date">{{ $blockedTime->date->format('F j, Y') }}</div>
                                <div class="time">{{ $blockedTime->start_time->format('h:i A') }} - {{ $blockedTime->end_time->format('h:i A') }}</div>
                                <div class="reason">{{ $blockedTime->reason }}</div>
                            </div>
                        </div>
                        <form action="{{ route('admin.bookings.blocked-times.destroy', $blockedTime->id) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to remove this blocked time?')">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="ri-calendar-line fs-48 text-muted mb-3"></i>
                        <p class="text-muted">No blocked times set</p>
                    </div>
                @endforelse
            </div>

            <!-- Add Blocked Time Button -->
            <button class="add-blocked-time-btn" data-bs-toggle="modal" data-bs-target="#addBlockedTimeModal">
                <i class="ri-add-line me-2"></i> Add Blocked Time
            </button>
        @else
            <div class="text-center py-5">
                <i class="ri-user-line fs-48 text-muted mb-3"></i>
                <h4 class="text-muted">No Trainer Selected</h4>
                <p class="text-muted">Please select a trainer from the scheduling menu to manage their blocked times.</p>
                <a href="{{ route('admin.bookings.scheduling-menu') }}" class="btn btn-primary">
                    <i class="ri-arrow-left-line me-2"></i> Back to Settings
                </a>
            </div>
        @endif
    </div>

    <!-- Add Blocked Time Modal -->
    @if($trainer)
    <div class="modal fade" id="addBlockedTimeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Blocked Time</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.bookings.blocked-times.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="trainer_id" value="{{ $trainer->id }}">
                    
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="reason" class="form-label">Reason</label>
                            <input type="text" class="form-control" id="reason" name="reason" placeholder="e.g., Personal Appointment, Vacation" required>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Blocked Time</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('scripts')
    <script>
        let currentDate = new Date('{{ $currentMonth->format('Y-m-d') }}');
        
        function changeMonth(direction) {
            currentDate.setMonth(currentDate.getMonth() + direction);
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth() + 1;
            
            const trainerId = {{ $trainer ? $trainer->id : 'null' }};
            const url = new URL(window.location.href);
            url.searchParams.set('year', year);
            url.searchParams.set('month', month);
            if (trainerId) {
                url.searchParams.set('trainer_id', trainerId);
            }
            
            window.location.href = url.toString();
        }

        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('date');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.min = today;
            }
        });
    </script>
@endsection