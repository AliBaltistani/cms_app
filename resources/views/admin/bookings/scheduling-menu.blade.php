@extends('layouts.master')

@section('styles')
    <style>
        .scheduling-menu-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .menu-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .menu-item:hover {
            border-color: var(--primary-color, #ff6b35);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
        }
        
        .menu-item-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-color, #ff6b35);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-bottom: 16px;
        }
        
        .menu-item-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .menu-item-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .trainer-select {
            margin-bottom: 30px;
        }
        
        .trainer-select select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            background: white;
            width: 100%;
            max-width: 350px;
        }
        
        .trainer-select select:focus {
            border-color: var(--primary-color, #ff6b35);
            outline: none;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
    </style>
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Scheduling & Booking Settings</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Scheduling Settings</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.bookings.schedule') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
                <i class="ri-calendar-line fw-semibold align-middle me-1"></i> Back to Schedule
            </a>
        </div> -->
    </div>
    <!-- Page Header Close -->

    <div class="scheduling-menu-container">
        <!-- Trainer Selection (Admin Only) -->
        <div class="trainer-select">
            <label for="trainer-select" class="form-label fw-semibold">Select Trainer</label>
            <select id="trainer-select" class="form-select" onchange="changeTrainer(this.value)">
                <option value="">Select a trainer to manage settings</option>
                @foreach($trainers as $trainer)
                    <option value="{{ $trainer->id }}" {{ request('trainer_id') == $trainer->id ? 'selected' : '' }}>
                        {{ $trainer->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Menu Items Grid -->
        <div class="menu-grid">
            <!-- Weekly Availability -->
            <a href="{{ route('admin.bookings.availability', ['trainer_id' => request('trainer_id')]) }}" class="menu-item">
                <div class="menu-item-icon">
                    <i class="ri-calendar-check-line"></i>
                </div>
                <div class="menu-item-title">Weekly Availability</div>
                <div class="menu-item-description">
                    Set trainer's weekly schedule with morning and evening time slots for each day of the week.
                </div>
            </a>

            <!-- Blocked Times -->
            <a href="{{ route('admin.bookings.blocked-times', ['trainer_id' => request('trainer_id')]) }}" class="menu-item">
                <div class="menu-item-icon">
                    <i class="ri-calendar-close-line"></i>
                </div>
                <div class="menu-item-title">Blocked Times</div>
                <div class="menu-item-description">
                    Manage blocked time periods when the trainer is not available for bookings.
                </div>
            </a>

            <!-- Session Capacity -->
            <a href="{{ route('admin.bookings.session-capacity', ['trainer_id' => request('trainer_id')]) }}" class="menu-item">
                <div class="menu-item-icon">
                    <i class="ri-group-line"></i>
                </div>
                <div class="menu-item-title">Session Capacity</div>
                <div class="menu-item-description">
                    Configure daily and weekly session limits and duration settings for the trainer.
                </div>
            </a>

            <!-- Booking Approval -->
            <a href="{{ route('admin.bookings.booking-approval', ['trainer_id' => request('trainer_id')]) }}" class="menu-item">
                <div class="menu-item-icon">
                    <i class="ri-shield-check-line"></i>
                </div>
                <div class="menu-item-title">Booking Approval</div>
                <div class="menu-item-description">
                    Manage booking approval settings and self-booking permissions for clients.
                </div>
            </a>
        </div>

        @if(!request('trainer_id'))
            <div class="text-center mt-4 p-4">
                <i class="ri-user-settings-line fs-48 text-muted mb-3"></i>
                <p class="text-muted">Please select a trainer above to manage their scheduling settings.</p>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        function changeTrainer(trainerId) {
            const url = new URL(window.location.href);
            if (trainerId) {
                url.searchParams.set('trainer_id', trainerId);
            } else {
                url.searchParams.delete('trainer_id');
            }
            
            window.location.href = url.toString();
        }

        // Update menu item links when trainer changes
        document.addEventListener('DOMContentLoaded', function() {
            const trainerSelect = document.getElementById('trainer-select');
            const menuItems = document.querySelectorAll('.menu-item');
            
            trainerSelect.addEventListener('change', function() {
                const trainerId = this.value;
                
                menuItems.forEach(item => {
                    const url = new URL(item.href);
                    if (trainerId) {
                        url.searchParams.set('trainer_id', trainerId);
                    } else {
                        url.searchParams.delete('trainer_id');
                    }
                    item.href = url.toString();
                });
            });
        });
    </script>
@endsection