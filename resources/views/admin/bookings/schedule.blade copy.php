@extends('layouts.master')

@section('styles')
    <style>
        .calendar-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            background: #f5f5f5;
            color: #333;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .calendar-day-header {
            background: #f8f9fa;
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
        }
        
        .calendar-day:hover {
            background: #f0f8ff;
        }
        
        .calendar-day.other-month {
            color: #ccc;
        }
        
        .calendar-day.selected {
            background: var(--primary-color, #ff6b35);
            color: white;
            border-radius: 50%;
            font-weight: 600;
        }
        
        .calendar-day.today {
            background: var(--secondary-color, #ffeaa7);
            border-radius: 50%;
            font-weight: 600;
        }
        
        .sessions-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .session-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border-left: 4px solid var(--primary-color, #ff6b35);
            transition: all 0.2s;
        }
        
        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .session-time {
            font-weight: 600;
            color: var(--primary-color, #ff6b35);
            margin-bottom: 8px;
        }
        
        .session-client {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .session-client img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .session-type {
            background: var(--primary-color, #ff6b35);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .trainer-select {
            margin-bottom: 20px;
        }
        
        .trainer-select select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 14px;
            background: white;
            width: 100%;
            max-width: 300px;
        }
        
        .trainer-select select:focus {
            border-color: var(--primary-color, #ff6b35);
            outline: none;
        }
    </style>
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Schedule</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Schedule</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.bookings.scheduling-menu') }}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
                <i class="ri-settings-3-line fw-semibold align-middle me-1"></i> Scheduling Settings
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Trainer Selection (Admin Only) -->
    <div class="trainer-select">
        <label for="trainer-select" class="form-label fw-semibold">Select Trainer</label>
        <select id="trainer-select" class="form-select" onchange="changeTrainer(this.value)">
            <option value="">All Trainers</option>
            @foreach($trainers as $trainer)
                <option value="{{ $trainer->id }}" {{ request('trainer_id') == $trainer->id ? 'selected' : '' }}>
                    {{ $trainer->name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Calendar Section -->
    <div class="calendar-container">
        <div class="calendar-header">
            <button class="calendar-nav" onclick="changeMonth(-1)">
                <i class="ri-arrow-left-line"></i>
            </button>
            <h3 class="mb-0" id="calendar-month-year">{{ $currentMonth->format('F Y') }}</h3>
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
                <div class="calendar-day {{ $day['isOtherMonth'] ? 'other-month' : '' }} {{ $day['isToday'] ? 'today' : '' }} {{ $day['isSelected'] ? 'selected' : '' }}" 
                     onclick="selectDate({{ $day['date'] }})">
                    {{ $day['day'] }}
                </div>
            @endforeach
        </div>
    </div>

    <!-- Today's Sessions -->
    <div class="sessions-container">
        <h4 class="mb-3">Today's Sessions</h4>
        
        @forelse($todaysSessions as $session)
            <div class="session-card">
                <div class="session-time">
                    {{ $session->start_time->format('h:i A') }} – {{ $session->end_time->format('h:i A') }}
                </div>
                <div class="session-client">
                    <img src="{{ $session->client->profile_image ? asset('storage/' . $session->client->profile_image) : asset('assets/images/faces/9.jpg') }}" 
                         alt="{{ $session->client->name }}">
                    <span class="fw-semibold">{{ $session->client->name }}</span>
                </div>
                <div class="session-type">
                    {{ $session->session_type ?? 'Training Session' }}
                </div>
            </div>
        @empty
            <div class="text-center py-4">
                <i class="ri-calendar-line fs-48 text-muted mb-3"></i>
                <p class="text-muted">No sessions scheduled for today</p>
            </div>
        @endforelse
    </div>
@endsection

@section('scripts')
    <script>
        let currentDate = new Date('{{ $currentMonth->format("Y-m-d") }}');
        let selectedDate = '{{ $selectedDate }}';
        
        function changeMonth(direction) {
            currentDate.setMonth(currentDate.getMonth() + direction);
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth() + 1;
            
            const trainerId = document.getElementById('trainer-select').value;
            const url = new URL(window.location.href);
            url.searchParams.set('year', year);
            url.searchParams.set('month', month);
            if (trainerId) {
                url.searchParams.set('trainer_id', trainerId);
            } else {
                url.searchParams.delete('trainer_id');
            }
            
            window.location.href = url.toString();
        }
        
        function selectDate(date) {
            const trainerId = document.getElementById('trainer-select').value;
            const url = new URL(window.location.href);
            url.searchParams.set('date', date);
            if (trainerId) {
                url.searchParams.set('trainer_id', trainerId);
            } else {
                url.searchParams.delete('trainer_id');
            }
            
            window.location.href = url.toString();
        }
        
        function changeTrainer(trainerId) {
            const url = new URL(window.location.href);
            if (trainerId) {
                url.searchParams.set('trainer_id', trainerId);
            } else {
                url.searchParams.delete('trainer_id');
            }
            
            window.location.href = url.toString();
        }
    </script>
@endsection