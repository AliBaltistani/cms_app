<?php

namespace App\Services;

use App\Models\User;
use App\Models\Schedule;
use App\Models\Availability;
use App\Models\BlockedTime;
use App\Models\SessionCapacity;
use App\Models\BookingSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AvailabilityService
{
    /**
     * Check if a trainer is available for a specific time slot
     * 
     * @param User $trainer
     * @param string $date
     * @param string $startTime
     * @param string $endTime
     * @param int|null $excludeBookingId
     * @return array
     */
    public function checkAvailability(User $trainer, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): array
    {
        $result = [
            'available' => false,
            'reasons' => [],
            'conflicts' => []
        ];

        try {
            // 1. Check if trainer role is valid
            if ($trainer->role !== 'trainer') {
                $result['reasons'][] = 'User is not a trainer';
                return $result;
            }

            // 2. Check trainer's weekly availability
            $weeklyAvailabilityCheck = $this->checkWeeklyAvailability($trainer, $date, $startTime, $endTime);
            if (!$weeklyAvailabilityCheck['available']) {
                $result['reasons'] = array_merge($result['reasons'], $weeklyAvailabilityCheck['reasons']);
                return $result;
            }

            // 3. Check blocked times
            $blockedTimeCheck = $this->checkBlockedTimes($trainer, $date, $startTime, $endTime);
            if (!$blockedTimeCheck['available']) {
                $result['reasons'] = array_merge($result['reasons'], $blockedTimeCheck['reasons']);
                $result['conflicts'] = array_merge($result['conflicts'], $blockedTimeCheck['conflicts']);
                return $result;
            }

            // 4. Check existing bookings
            $bookingConflictCheck = $this->checkBookingConflicts($trainer, $date, $startTime, $endTime, $excludeBookingId);
            if (!$bookingConflictCheck['available']) {
                $result['reasons'] = array_merge($result['reasons'], $bookingConflictCheck['reasons']);
                $result['conflicts'] = array_merge($result['conflicts'], $bookingConflictCheck['conflicts']);
                return $result;
            }

            // 5. Check session capacity limits
            $capacityCheck = $this->checkSessionCapacity($trainer, $date, $excludeBookingId);
            if (!$capacityCheck['available']) {
                $result['reasons'] = array_merge($result['reasons'], $capacityCheck['reasons']);
                return $result;
            }

            // 6. Check booking settings (advance booking, etc.)
            $bookingSettingsCheck = $this->checkBookingSettings($trainer, $date, $startTime);
            if (!$bookingSettingsCheck['available']) {
                $result['reasons'] = array_merge($result['reasons'], $bookingSettingsCheck['reasons']);
                return $result;
            }

            $result['available'] = true;
            return $result;

        } catch (\Exception $e) {
            $result['reasons'][] = 'Error checking availability: ' . $e->getMessage();
            return $result;
        }
    }

    /**
     * Check trainer's weekly availability
     */
    private function checkWeeklyAvailability(User $trainer, string $date, string $startTime, string $endTime): array
    {
        $result = ['available' => false, 'reasons' => []];

        try {
            $bookingDate = Carbon::parse($date);
            $dayOfWeek = $bookingDate->dayOfWeek;
            
            $availability = Availability::where('trainer_id', $trainer->id)
                ->where('day_of_week', $dayOfWeek)
                ->first();

            if (!$availability) {
                $result['reasons'][] = 'Trainer is not available on ' . $bookingDate->format('l');
                return $result;
            }

            // Handle different time formats - try H:i first, then H:i:s
            $startTimeCarbon = $this->parseTimeFormat($startTime);
            $endTimeCarbon = $this->parseTimeFormat($endTime);
            
            if (!$startTimeCarbon || !$endTimeCarbon) {
                $result['reasons'][] = 'Invalid time format provided';
                return $result;
            }

            $isWithinAvailableHours = false;

            // Check morning availability
            if ($availability->morning_available && 
                $availability->morning_start_time && 
                $availability->morning_end_time) {
                
                $morningStart = $this->parseTimeFormat($availability->morning_start_time);
                $morningEnd = $this->parseTimeFormat($availability->morning_end_time);
                
                if ($morningStart && $morningEnd && 
                    $startTimeCarbon->gte($morningStart) && $endTimeCarbon->lte($morningEnd)) {
                    $isWithinAvailableHours = true;
                }
            }

            // Check evening availability if not already within morning hours
            if (!$isWithinAvailableHours && 
                $availability->evening_available && 
                $availability->evening_start_time && 
                $availability->evening_end_time) {
                
                $eveningStart = $this->parseTimeFormat($availability->evening_start_time);
                $eveningEnd = $this->parseTimeFormat($availability->evening_end_time);
                
                if ($eveningStart && $eveningEnd && 
                    $startTimeCarbon->gte($eveningStart) && $endTimeCarbon->lte($eveningEnd)) {
                    $isWithinAvailableHours = true;
                }
            }

            if (!$isWithinAvailableHours) {
                $result['reasons'][] = 'Selected time is outside trainer\'s available hours';
                return $result;
            }

            $result['available'] = true;
            return $result;
            
        } catch (\Exception $e) {
            $result['reasons'][] = 'Error parsing date/time: ' . $e->getMessage();
            return $result;
        }
    }

    /**
     * Parse time format - handles both H:i and H:i:s formats
     */
    private function parseTimeFormat(string $time): ?Carbon
    {
        try {
            // First, check if it's a full datetime string and extract just the time part
            if (preg_match('/^\d{4}-\d{2}-\d{2} (\d{2}:\d{2}:\d{2})$/', $time, $matches)) {
                $time = $matches[1]; // Extract just the time part (HH:MM:SS)
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2} (\d{2}:\d{2})$/', $time, $matches)) {
                $time = $matches[1]; // Extract just the time part (HH:MM)
            }
            
            // Try H:i:s format first (most common for extracted times)
            return Carbon::createFromFormat('H:i:s', $time);
        } catch (\Exception $e) {
            try {
                // Try H:i format
                return Carbon::createFromFormat('H:i', $time);
            } catch (\Exception $e2) {
                try {
                    // Try full datetime format as last resort
                    return Carbon::parse($time);
                } catch (\Exception $e3) {
                    // Log the error for debugging
                    Log::warning('Failed to parse time format', [
                        'time' => $time,
                        'error_H:i:s' => $e->getMessage(),
                        'error_H:i' => $e2->getMessage(),
                        'error_parse' => $e3->getMessage()
                    ]);
                    return null;
                }
            }
        }
    }

    /**
     * Check for blocked times
     */
    private function checkBlockedTimes(User $trainer, string $date, string $startTime, string $endTime): array
    {
        $result = ['available' => true, 'reasons' => [], 'conflicts' => []];

        $blockedTimes = BlockedTime::where('trainer_id', $trainer->id)
            ->where('date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->get();

        if ($blockedTimes->isNotEmpty()) {
            $result['available'] = false;
            foreach ($blockedTimes as $blockedTime) {
                $result['reasons'][] = 'Time slot is blocked: ' . ($blockedTime->reason ?? 'No reason provided');
                $result['conflicts'][] = [
                    'type' => 'blocked_time',
                    'start_time' => $blockedTime->start_time,
                    'end_time' => $blockedTime->end_time,
                    'reason' => $blockedTime->reason
                ];
            }
        }

        return $result;
    }

    /**
     * Check for booking conflicts
     */
    private function checkBookingConflicts(User $trainer, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): array
    {
        $result = ['available' => true, 'reasons' => [], 'conflicts' => []];

        $query = Schedule::where('trainer_id', $trainer->id)
            ->where('date', $date)
            ->where('status', '!=', Schedule::STATUS_CANCELLED)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        $conflictingBookings = $query->with(['client'])->get();

        if ($conflictingBookings->isNotEmpty()) {
            $result['available'] = false;
            foreach ($conflictingBookings as $booking) {
                $result['reasons'][] = 'Trainer already has a booking during this time slot';
                $result['conflicts'][] = [
                    'type' => 'existing_booking',
                    'booking_id' => $booking->id,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'client_name' => $booking->client->name ?? 'Unknown',
                    'session_type' => $booking->session_type
                ];
            }
        }

        return $result;
    }

    /**
     * Check session capacity limits
     */
    private function checkSessionCapacity(User $trainer, string $date, ?int $excludeBookingId = null): array
    {
        $result = ['available' => true, 'reasons' => []];

        $sessionCapacity = SessionCapacity::where('trainer_id', $trainer->id)->first();
        
        if (!$sessionCapacity) {
            return $result; // No limits set
        }

        $bookingDate = Carbon::parse($date);

        // Check daily session limit
        $dailyQuery = Schedule::where('trainer_id', $trainer->id)
            ->where('date', $date)
            ->where('status', '!=', Schedule::STATUS_CANCELLED);

        if ($excludeBookingId) {
            $dailyQuery->where('id', '!=', $excludeBookingId);
        }

        $dailySessionsCount = $dailyQuery->count();

        if ($dailySessionsCount >= $sessionCapacity->max_daily_sessions) {
            $result['available'] = false;
            $result['reasons'][] = 'Trainer has reached maximum daily sessions limit (' . $sessionCapacity->max_daily_sessions . ')';
            return $result;
        }

        // Check weekly session limit
        $weekStart = $bookingDate->copy()->startOfWeek();
        $weekEnd = $bookingDate->copy()->endOfWeek();
        
        $weeklyQuery = Schedule::where('trainer_id', $trainer->id)
            ->whereBetween('date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
            ->where('status', '!=', Schedule::STATUS_CANCELLED);

        if ($excludeBookingId) {
            $weeklyQuery->where('id', '!=', $excludeBookingId);
        }

        $weeklySessionsCount = $weeklyQuery->count();

        if ($weeklySessionsCount >= $sessionCapacity->max_weekly_sessions) {
            $result['available'] = false;
            $result['reasons'][] = 'Trainer has reached maximum weekly sessions limit (' . $sessionCapacity->max_weekly_sessions . ')';
            return $result;
        }

        return $result;
    }

    /**
     * Check booking settings (advance booking limits, weekend restrictions, time restrictions, etc.)
     */
    private function checkBookingSettings(User $trainer, string $date, string $startTime): array
    {
        $result = ['available' => true, 'reasons' => []];

        $bookingSetting = BookingSetting::where('trainer_id', $trainer->id)->first();
        
        if (!$bookingSetting) {
            return $result; // No restrictions set
        }

        $bookingDateTime = Carbon::parse($date . ' ' . $startTime);
        $now = Carbon::now();

        // Check if self-booking is allowed
        if (!$bookingSetting->allow_self_booking) {
            $result['available'] = false;
            $result['reasons'][] = 'Trainer does not allow self-booking';
            return $result;
        }

        // Check weekend booking restrictions
        if (!$bookingSetting->allow_weekend_booking && $bookingDateTime->isWeekend()) {
            $result['available'] = false;
            $result['reasons'][] = 'Weekend bookings are not allowed for this trainer';
            return $result;
        }

        // Check booking time window restrictions
        if ($bookingSetting->earliest_booking_time && $bookingSetting->latest_booking_time) {
            $bookingTime = $bookingDateTime->format('H:i');
            if ($bookingTime < $bookingSetting->earliest_booking_time || $bookingTime > $bookingSetting->latest_booking_time) {
                $result['available'] = false;
                $result['reasons'][] = 'Booking time must be between ' . $bookingSetting->earliest_booking_time . ' and ' . $bookingSetting->latest_booking_time;
                return $result;
            }
        }

        // Check advance booking days limit
        if ($bookingSetting->advance_booking_days) {
            $maxAdvanceTime = $now->copy()->addDays($bookingSetting->advance_booking_days);
            if ($bookingDateTime->gt($maxAdvanceTime)) {
                $result['available'] = false;
                $result['reasons'][] = 'Booking cannot be made more than ' . $bookingSetting->advance_booking_days . ' days in advance';
                return $result;
            }
        }

        // Check if booking is in the past
        if ($bookingDateTime->lt($now)) {
            $result['available'] = false;
            $result['reasons'][] = 'Cannot book sessions in the past';
            return $result;
        }

        return $result;
    }

    /**
     * Get available time slots for a trainer within a date range
     */
    public function getAvailableSlots(User $trainer, string $startDate, string $endDate, int $slotDuration = 60): array
    {
        $availableSlots = [];
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        // Get trainer's weekly availability
        $weeklyAvailability = Availability::where('trainer_id', $trainer->id)
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week');

        // Get session capacity settings
        $sessionCapacity = SessionCapacity::where('trainer_id', $trainer->id)->first();
        
        // Use session capacity duration if available, otherwise use provided duration
        if ($sessionCapacity && $sessionCapacity->session_duration_minutes) {
            $slotDuration = $sessionCapacity->session_duration_minutes;
        }

        // Get booking settings
        $bookingSettings = BookingSetting::where('trainer_id', $trainer->id)->first();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek;
            
            // Skip if weekend bookings are not allowed
            if ($bookingSettings && !$bookingSettings->allow_weekend_booking && $currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }
            
            // Get weekly availability for this day
            $dayAvailability = $weeklyAvailability->get($dayOfWeek);
            
            if ($dayAvailability) {
                $timeSlots = [];
                
                // Generate morning slots if available
                if ($dayAvailability->morning_available && $dayAvailability->morning_start_time && $dayAvailability->morning_end_time) {
                    $morningSlots = $this->generateTimeSlots(
                        $dayAvailability->morning_start_time,
                        $dayAvailability->morning_end_time,
                        $slotDuration,
                        $sessionCapacity ? $sessionCapacity->break_between_sessions_minutes : 0
                    );
                    $timeSlots = array_merge($timeSlots, $morningSlots);
                }
                
                // Generate evening slots if available
                if ($dayAvailability->evening_available && $dayAvailability->evening_start_time && $dayAvailability->evening_end_time) {
                    $eveningSlots = $this->generateTimeSlots(
                        $dayAvailability->evening_start_time,
                        $dayAvailability->evening_end_time,
                        $slotDuration,
                        $sessionCapacity ? $sessionCapacity->break_between_sessions_minutes : 0
                    );
                    $timeSlots = array_merge($timeSlots, $eveningSlots);
                }
                
                // Check each time slot for comprehensive availability
                foreach ($timeSlots as $slot) {
                    $availabilityCheck = $this->checkAvailability(
                        $trainer,
                        $dateString,
                        $slot['start_time'],
                        $slot['end_time']
                    );

                    if ($availabilityCheck['available']) {
                        $slotStart = $this->parseTimeFormat($slot['start_time']);
                        $slotEnd = $this->parseTimeFormat($slot['end_time']);
                        
                        if ($slotStart && $slotEnd) {
                            $slotDateTime = $currentDate->copy()->setTime($slotStart->hour, $slotStart->minute);
                            
                            // Skip past slots and apply booking time restrictions
                            if ($slotDateTime->gt(Carbon::now())) {
                                // Additional check for booking time window
                                $timeCheck = $this->checkBookingSettings($trainer, $dateString, $slot['start_time']);
                                
                                if ($timeCheck['available']) {
                                    $availableSlots[] = [
                                        'start' => $slotDateTime->toISOString(),
                                        'end' => $slotDateTime->copy()->addMinutes($slotDuration)->toISOString(),
                                        'start_time' => $slot['start_time'],
                                        'end_time' => $slot['end_time'],
                                        'date' => $dateString,
                                        'display' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A'),
                                        'duration_minutes' => $slotDuration,
                                        'requires_approval' => $bookingSettings ? $bookingSettings->require_approval : false
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            
            $currentDate->addDay();
        }

        return $availableSlots;
    }

    /**
     * Generate time slots for a given time range with break times
     */
    private function generateTimeSlots(string $startTime, string $endTime, int $slotDuration = 60, int $breakMinutes = 0): array
    {
        $slots = [];
        
        try {
            $start = $this->parseTimeFormat($startTime);
            $end = $this->parseTimeFormat($endTime);
            
            if (!$start || !$end) {
                Log::warning('Failed to parse time formats in generateTimeSlots', [
                    'startTime' => $startTime,
                    'endTime' => $endTime
                ]);
                return $slots;
            }
            
            while ($start->lt($end)) {
                $slotEnd = $start->copy()->addMinutes($slotDuration);
                
                if ($slotEnd->lte($end)) {
                    $slots[] = [
                        'start_time' => $start->format('H:i'),
                        'end_time' => $slotEnd->format('H:i'),
                        'duration_minutes' => $slotDuration
                    ];
                }
                
                // Add session duration plus break time for next slot
                $start->addMinutes($slotDuration + $breakMinutes);
            }
            
        } catch (\Exception $e) {
            Log::error('Error generating time slots', [
                'startTime' => $startTime,
                'endTime' => $endTime,
                'slotDuration' => $slotDuration,
                'breakMinutes' => $breakMinutes,
                'error' => $e->getMessage()
            ]);
        }
        
        return $slots;
    }
}