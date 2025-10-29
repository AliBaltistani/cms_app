<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Schedule;
use App\Models\Availability;
use App\Models\BlockedTime;
use App\Models\SessionCapacity;
use App\Models\BookingSetting;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Client Booking API Controller
 * 
 * Handles client booking operations including viewing trainer availability,
 * requesting bookings, and managing client bookings
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Api
 * @category    Scheduling Module
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class ClientBookingController extends ApiBaseController
{
    /**
     * Google Calendar Service instance
     * 
     * @var GoogleCalendarService
     */
    protected $googleCalendarService;

    /**
     * Constructor
     */
    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Get trainer availability for a specific date range
     * 
     * @param Request $request
     * @param int $trainerId
     * @return JsonResponse
     */
    public function getTrainerAvailability(Request $request, int $trainerId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            // Check if trainer exists and is active
            $trainer = User::where('id', $trainerId)
                ->where('role', 'trainer')
                ->first();

            if (!$trainer) {
                return $this->sendError('Trainer not found', [], 404);
            }

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            
            // Get trainer's weekly availability
            $weeklyAvailability = Availability::forTrainer($trainerId)
                ->orderBy('day_of_week')
                ->get()
                ->keyBy('day_of_week');

            // Get trainer's booking settings
            $bookingSettings = BookingSetting::getOrCreateForTrainer($trainerId);
            
            // Get trainer's session capacity
            $sessionCapacity = SessionCapacity::getOrCreateForTrainer($trainerId);

            // Get blocked times for the date range
            $blockedTimes = BlockedTime::forTrainer($trainerId)
                ->dateRange($request->start_date, $request->end_date)
                ->active()
                ->get()
                ->groupBy('date');

            // Get existing bookings for the date range
            $existingBookings = Schedule::forTrainer($trainerId)
                ->dateRange($request->start_date, $request->end_date)
                ->where('status', '!=', Schedule::STATUS_CANCELLED)
                ->get()
                ->groupBy('date');

            $availableSlots = [];
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateString = $currentDate->toDateString();
                $dayOfWeek = $currentDate->dayOfWeek;

                // Check if booking is allowed for this date
                if (!$bookingSettings->isBookingAllowed($dateString, '12:00:00')) {
                    $currentDate->addDay();
                    continue;
                }

                // Get availability for this day of week
                $dayAvailability = $weeklyAvailability->get($dayOfWeek);
                
                if (!$dayAvailability) {
                    $currentDate->addDay();
                    continue;
                }

                $daySlots = [];

                // Check morning availability
                if ($dayAvailability->isMorningAvailable()) {
                    $morningSlots = $this->generateTimeSlots(
                        $dayAvailability->morning_start_time,
                        $dayAvailability->morning_end_time,
                        $sessionCapacity->session_duration_minutes,
                        $sessionCapacity->break_between_sessions_minutes
                    );
                    $daySlots = array_merge($daySlots, $morningSlots);
                }

                // Check evening availability
                if ($dayAvailability->isEveningAvailable()) {
                    $eveningSlots = $this->generateTimeSlots(
                        $dayAvailability->evening_start_time,
                        $dayAvailability->evening_end_time,
                        $sessionCapacity->session_duration_minutes,
                        $sessionCapacity->break_between_sessions_minutes
                    );
                    $daySlots = array_merge($daySlots, $eveningSlots);
                }

                // Filter out blocked times and existing bookings
                $daySlots = $this->filterAvailableSlots(
                    $daySlots,
                    $blockedTimes->get($dateString, collect()),
                    $existingBookings->get($dateString, collect()),
                    $sessionCapacity->session_duration_minutes
                );

                // Check daily capacity
                $dailyBookingsCount = $existingBookings->get($dateString, collect())->count();
                if ($dailyBookingsCount >= $sessionCapacity->max_daily_sessions) {
                    $daySlots = [];
                }

                if (!empty($daySlots)) {
                    $availableSlots[$dateString] = [
                        'date' => $dateString,
                        'day_name' => $currentDate->format('l'),
                        'slots' => $daySlots,
                        'remaining_capacity' => max(0, $sessionCapacity->max_daily_sessions - $dailyBookingsCount)
                    ];
                }

                $currentDate->addDay();
            }

            $response = [
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email
                ],
                'booking_settings' => $bookingSettings->getBookingRules(),
                'session_info' => [
                    'duration_minutes' => $sessionCapacity->session_duration_minutes,
                    'max_daily_sessions' => $sessionCapacity->max_daily_sessions,
                    'max_weekly_sessions' => $sessionCapacity->max_weekly_sessions
                ],
                'available_slots' => $availableSlots
            ];

            return $this->sendResponse($response, 'Trainer availability retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Request a booking
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function requestBooking(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'trainer_id' => 'required|exists:users,id',
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $clientId = Auth::id();
            $trainerId = $request->trainer_id;

            // Verify trainer exists and is active
            $trainer = User::where('id', $trainerId)
                ->where('role', 'trainer')
                ->first();

            if (!$trainer) {
                return $this->sendError('Trainer not found', [], 404);
            }

            // Get trainer's booking settings and session capacity
            $bookingSettings = BookingSetting::getOrCreateForTrainer($trainerId);
            $sessionCapacity = SessionCapacity::getOrCreateForTrainer($trainerId);

            // Calculate end time based on session duration
            $startTime = Carbon::createFromFormat('H:i', $request->start_time);
            $endTime = $startTime->copy()->addMinutes($sessionCapacity->session_duration_minutes);

            // Validate booking is allowed
            if (!$bookingSettings->isBookingAllowed($request->date, $request->start_time)) {
                return $this->sendError('Booking not allowed for this date and time');
            }

            // Check for conflicts with existing bookings
            $conflictingBooking = Schedule::forTrainer($trainerId)
                ->where('date', $request->date)
                ->where('status', '!=', Schedule::STATUS_CANCELLED)
                ->where(function ($query) use ($request, $endTime) {
                    $query->whereBetween('start_time', [$request->start_time, $endTime->format('H:i:s')])
                          ->orWhereBetween('end_time', [$request->start_time, $endTime->format('H:i:s')])
                          ->orWhere(function ($q) use ($request, $endTime) {
                              $q->where('start_time', '<=', $request->start_time)
                                ->where('end_time', '>=', $endTime->format('H:i:s'));
                          });
                })
                ->exists();

            if ($conflictingBooking) {
                return $this->sendError('Time slot is already booked');
            }

            // Check for blocked times
            $blockedTime = BlockedTime::forTrainer($trainerId)
                ->forDate($request->date)
                ->where(function ($query) use ($request, $endTime) {
                    $query->where(function ($q) use ($request, $endTime) {
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>', $request->start_time);
                    })->orWhere(function ($q) use ($request, $endTime) {
                        $q->where('start_time', '<', $endTime->format('H:i:s'))
                          ->where('end_time', '>=', $endTime->format('H:i:s'));
                    });
                })
                ->exists();

            if ($blockedTime) {
                return $this->sendError('Time slot is blocked by trainer');
            }

            // Check daily capacity
            if (!$sessionCapacity->canAcceptMoreSessionsOnDate($request->date)) {
                return $this->sendError('Trainer has reached daily session limit');
            }

            // Check weekly capacity
            if (!$sessionCapacity->canAcceptMoreSessionsInWeek($request->date)) {
                return $this->sendError('Trainer has reached weekly session limit');
            }

            // Determine initial status based on booking settings
            $status = $bookingSettings->require_approval ? Schedule::STATUS_PENDING : Schedule::STATUS_CONFIRMED;

            // Create the booking
            $schedule = Schedule::create([
                'trainer_id' => $trainerId,
                'client_id' => $clientId,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $endTime->format('H:i:s'),
                'status' => $status,
                'notes' => $request->notes,
            ]);

            $schedule->load(['trainer:id,name,email,phone', 'client:id,name,email,phone']);

            // Create Google Calendar event if booking is confirmed and trainer is connected
            $googleEventResult = null;
            if ($status === Schedule::STATUS_CONFIRMED && $this->googleCalendarService->isTrainerConnected($trainer)) {
                try {
                    $googleEventResult = $this->googleCalendarService->createCalendarEvent($schedule);
                } catch (\Exception $e) {
                    Log::warning('Failed to create Google Calendar event for client booking', [
                        'schedule_id' => $schedule->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Prepare response data in Event format
            $startDateTime = Carbon::parse($schedule->date . ' ' . $schedule->start_time);
            $endDateTime = Carbon::parse($schedule->date . ' ' . $schedule->end_time);
            
            $responseData = [
                'id' => $schedule->id,
                'title' => 'Training Session',
                'date' => $startDateTime->toISOString(),
                'time' => $startDateTime->format('g:i A') . ' - ' . $endDateTime->format('g:i A'),
                'googleLink' => $schedule->meet_link ?? '',
                'trainer' => $schedule->trainer->name,
                'status' => $schedule->status,
                'notes' => $schedule->notes,
                'duration_minutes' => $schedule->getDurationInMinutes(),
                'google_event_id' => $schedule->google_event_id,
                'created_at' => $schedule->created_at->toISOString(),
                'updated_at' => $schedule->updated_at->toISOString()
            ];

            // Update meet_link if Google Calendar event was created
            if ($googleEventResult && isset($googleEventResult['meet_link'])) {
                $responseData['googleLink'] = $googleEventResult['meet_link'];
                $responseData['google_event_created'] = true;
            } else {
                $responseData['google_event_created'] = false;
            }

            $message = $status === Schedule::STATUS_PENDING 
                ? 'Booking request submitted successfully. Waiting for trainer approval.' 
                : 'Booking confirmed successfully.';

            // Add Google Calendar status to message if applicable
            if ($status === Schedule::STATUS_CONFIRMED) {
                if ($googleEventResult) {
                    $message .= ' Google Calendar event created with Meet link.';
                } else {
                    $message .= ' Note: Google Calendar event could not be created.';
                }
            }

            return $this->sendResponse($responseData, $message);

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get client bookings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getClientBookings(Request $request): JsonResponse
    {
        try {
            $clientId = Auth::id();
            $query = Schedule::forClient($clientId)
                ->with(['trainer:id,name,email,phone']);

            // Filter by status if provided
            if ($request->has('status')) {
                $query->withStatus($request->status);
            }

            // Filter by date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->dateRange($request->start_date, $request->end_date);
            } else {
                // Default to upcoming bookings
                $query->where('date', '>=', now()->toDateString());
            }

            $bookings = $query->orderBy('date')
                ->orderBy('start_time')
                ->paginate($request->get('per_page', 15));

            return $this->sendResponse($bookings, 'Client bookings retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel a booking
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function cancelBooking(int $id): JsonResponse
    {
        try {
            $clientId = Auth::id();
            $schedule = Schedule::where('id', $id)
                ->where('client_id', $clientId)
                ->first();

            if (!$schedule) {
                return $this->sendError('Booking not found', [], 404);
            }

            if (!$schedule->canBeCancelled()) {
                return $this->sendError('Booking cannot be cancelled');
            }

            // Check cancellation policy
            $bookingSettings = BookingSetting::getOrCreateForTrainer($schedule->trainer_id);
            if (!$bookingSettings->isCancellationAllowed($schedule)) {
                $deadline = $bookingSettings->getCancellationDeadline($schedule);
                return $this->sendError(
                    'Cancellation not allowed. Deadline was ' . $deadline->format('Y-m-d H:i')
                );
            }

            $schedule->update(['status' => Schedule::STATUS_CANCELLED]);

            // Delete Google Calendar event if exists
            if ($schedule->hasGoogleCalendarEvent() && $this->googleCalendarService->isTrainerConnected($schedule->trainer)) {
                try {
                    $this->googleCalendarService->deleteCalendarEvent($schedule);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete Google Calendar event for cancelled booking', [
                        'schedule_id' => $schedule->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $schedule->load(['trainer:id,name,email,phone']);

            return $this->sendResponse($schedule, 'Booking cancelled successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get unified schedule for both trainers and clients
     * Returns events in the required format with role-based filtering
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getUnifiedSchedule(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $userId = $user->id;
            $userRole = $user->role;

            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date|after_or_equal:today',
                'end_date' => 'nullable|date|after:start_date',
                'status' => 'nullable|in:pending,confirmed,cancelled'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            // Set default date range if not provided
            $startDate = $request->start_date ? Carbon::parse($request->start_date)->toDateString() : Carbon::today()->toDateString();
            $endDate = $request->end_date ? Carbon::parse($request->end_date)->toDateString() : Carbon::today()->addDays(30)->toDateString();

            // Build query based on user role
            $query = Schedule::with(['trainer:id,name,email,phone', 'client:id,name,email,phone']);

            if ($userRole === 'trainer') {
                $query->forTrainer($userId);
            } elseif ($userRole === 'client') {
                $query->forClient($userId);
            } else {
                return $this->sendError('Unauthorized access', [], 403);
            }

            // Apply filters
            $query->dateRange($startDate, $endDate);

            if ($request->has('status')) {
                $query->withStatus($request->status);
            } else {
                // Exclude cancelled by default
                $query->where('status', '!=', Schedule::STATUS_CANCELLED);
            }

            $schedules = $query->orderBy('date')
                ->orderBy('start_time')
                ->get();

            // Transform to required Event format
            $events = $schedules->map(function ($schedule) use ($userRole) {
                try {
                    // Ensure proper date and time format
                    $dateStr = $schedule->date instanceof Carbon ? $schedule->date->toDateString() : $schedule->date;
                    $startTimeStr = $schedule->start_time;
                    $endTimeStr = $schedule->end_time;
                    
                    $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $startTimeStr);
                    $endDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $endTimeStr);
                    
                    // Determine title based on user role
                    if ($userRole === 'trainer') {
                        $title = 'Session with ' . ($schedule->client ? $schedule->client->name : 'Client');
                    } else {
                        $title = 'Training Session';
                    }

                    return [
                        'id' => $schedule->id,
                        'title' => $title,
                        'date' => $startDateTime->toISOString(),
                        'time' => $startDateTime->format('g:i A') . ' - ' . $endDateTime->format('g:i A'),
                        'googleLink' => $schedule->meet_link ?? '',
                        'trainer' => $schedule->trainer ? $schedule->trainer->name : 'Unknown',
                        'client' => $userRole === 'trainer' && $schedule->client ? $schedule->client->name : null,
                        'status' => $schedule->status,
                        'notes' => $schedule->notes,
                        'duration_minutes' => $schedule->getDurationInMinutes(),
                        'google_event_id' => $schedule->google_event_id,
                        'created_at' => $schedule->created_at->toISOString(),
                        'updated_at' => $schedule->updated_at->toISOString()
                    ];
                } catch (\Exception $e) {
                    // Log the error and return a basic event structure
                    Log::error('Error parsing schedule event: ' . $e->getMessage(), [
                        'schedule_id' => $schedule->id,
                        'date' => $schedule->date,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time
                    ]);
                    
                    return [
                        'id' => $schedule->id,
                        'title' => 'Training Session',
                        'date' => $schedule->date,
                        'time' => $schedule->start_time . ' - ' . $schedule->end_time,
                        'googleLink' => $schedule->meet_link ?? '',
                        'trainer' => $schedule->trainer ? $schedule->trainer->name : 'Unknown',
                        'client' => null,
                        'status' => $schedule->status,
                        'notes' => $schedule->notes,
                        'duration_minutes' => 60, // Default duration
                        'google_event_id' => $schedule->google_event_id,
                        'created_at' => $schedule->created_at->toISOString(),
                        'updated_at' => $schedule->updated_at->toISOString()
                    ];
                }
            });

            return $this->sendResponse($events, 'Schedule retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get client bookings in Event format
     * Updated to return the required Event structure
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getClientBookingsAsEvents(Request $request): JsonResponse
    {
        try {
            $clientId = Auth::id();
            
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|in:pending,confirmed,cancelled'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $query = Schedule::forClient($clientId)
                ->with(['trainer:id,name,email,phone']);

            // Filter by status if provided
            if ($request->has('status')) {
                $query->withStatus($request->status);
            } else {
                // Exclude cancelled by default
                $query->where('status', '!=', Schedule::STATUS_CANCELLED);
            }

            // Filter by date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->dateRange($request->start_date, $request->end_date);
            } else {
                // Default to upcoming bookings
                $query->where('date', '>=', now()->toDateString());
            }

            $bookings = $query->orderBy('date')
                ->orderBy('start_time')
                ->get();

            // Transform to required Event format
            $events = $bookings->map(function ($schedule) {
                $startDateTime = Carbon::parse($schedule->date . ' ' . $schedule->start_time);
                $endDateTime = Carbon::parse($schedule->date . ' ' . $schedule->end_time);
                
                return [
                    'id' => $schedule->id,
                    'title' => 'Session With' . $schedule->trainer->name,
                    'date' => $startDateTime->toISOString(),
                    'time' => $startDateTime->format('g:i A') . ' - ' . $endDateTime->format('g:i A'),
                    'googleLink' => $schedule->meet_link ?? '',
                    'trainer' => $schedule->trainer->name,
                    'status' => $schedule->status,
                    'notes' => $schedule->notes,
                    'duration_minutes' => $schedule->getDurationInMinutes(),
                    'google_event_id' => $schedule->google_event_id,
                    'created_at' => $schedule->created_at->toISOString(),
                    'updated_at' => $schedule->updated_at->toISOString()
                ];
            });

            return $this->sendResponse($events, 'Client sessions retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate time slots for a given time range
     * 
     * @param string $startTime
     * @param string $endTime
     * @param int $sessionDuration
     * @param int $breakDuration
     * @return array
     */
    private function generateTimeSlots(string $startTime, string $endTime, int $sessionDuration, int $breakDuration): array
    {
        $slots = [];
        $current = Carbon::createFromFormat('H:i:s', $startTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);
        
        while ($current->copy()->addMinutes($sessionDuration)->lte($end)) {
            $slotEnd = $current->copy()->addMinutes($sessionDuration);
            
            $slots[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'duration_minutes' => $sessionDuration
            ];
            
            $current->addMinutes($sessionDuration + $breakDuration);
        }
        
        return $slots;
    }

    /**
     * Filter available slots by removing blocked times and existing bookings
     * 
     * @param array $slots
     * @param \Illuminate\Support\Collection $blockedTimes
     * @param \Illuminate\Support\Collection $existingBookings
     * @param int $sessionDuration
     * @return array
     */
    private function filterAvailableSlots(array $slots, $blockedTimes, $existingBookings, int $sessionDuration): array
    {
        return array_filter($slots, function ($slot) use ($blockedTimes, $existingBookings, $sessionDuration) {
            $slotStart = $slot['start_time'];
            $slotEnd = $slot['end_time'];
            
            // Check against blocked times
            foreach ($blockedTimes as $blockedTime) {
                if ($blockedTime->conflictsWith($slotStart, $slotEnd)) {
                    return false;
                }
            }
            
            // Check against existing bookings
            foreach ($existingBookings as $booking) {
                $bookingStart = Carbon::createFromFormat('H:i:s', $booking->start_time)->format('H:i');
                $bookingEnd = Carbon::createFromFormat('H:i:s', $booking->end_time)->format('H:i');
                
                if (!($slotEnd <= $bookingStart || $slotStart >= $bookingEnd)) {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Check if trainer has Google Calendar connected
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkTrainerGoogleCalendarStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'trainer_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $trainer = User::find($request->trainer_id);
            $isConnected = $this->googleCalendarService->isTrainerConnected($trainer);

            return $this->sendResponse([
                'trainer_id' => $trainer->id,
                'trainer_name' => $trainer->name,
                'google_calendar_connected' => $isConnected
            ], 'Google Calendar connection status retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error checking trainer Google Calendar status', [
                'trainer_id' => $request->trainer_id,
                'error' => $e->getMessage()
            ]);

            return $this->sendError('Server Error', 'Unable to check Google Calendar status', 500);
        }
    }

    /**
     * Get trainer's Google Calendar events for a specific date range
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTrainerGoogleCalendarEvents(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'trainer_id' => 'required|exists:users,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $trainer = User::find($request->trainer_id);
            
            if (!$this->googleCalendarService->isTrainerConnected($trainer)) {
                return $this->sendError('Calendar Not Connected', 'Trainer has not connected their Google Calendar', 400);
            }

            $events = $this->googleCalendarService->getCalendarEvents(
                $trainer,
                $request->start_date,
                $request->end_date
            );

            return $this->sendResponse([
                'trainer_id' => $trainer->id,
                'trainer_name' => $trainer->name,
                'events' => $events,
                'date_range' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date
                ]
            ], 'Google Calendar events retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving trainer Google Calendar events', [
                'trainer_id' => $request->trainer_id,
                'error' => $e->getMessage()
            ]);

            return $this->sendError('Server Error', 'Unable to retrieve Google Calendar events', 500);
        }
    }
}