<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Availability;
use App\Models\BlockedTime;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Trainer Booking API Controller
 * 
 * Handles API endpoints for trainer booking management with Google Calendar integration
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Api
 * @category    Booking API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class TrainerBookingController extends Controller
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
     * Get all bookings for the authenticated trainer
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            $query = Schedule::with(['client:id,name,email,phone'])
                ->where('trainer_id', $trainer->id)
                ->orderBy('date', 'desc')
                ->orderBy('start_time', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            if ($request->filled('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $bookings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Bookings retrieved successfully',
                'data' => $bookings,
                'trainer' => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'google_connected' => $this->googleCalendarService->isTrainerConnected($trainer)
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to retrieve trainer bookings', [
                'trainer_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bookings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific booking
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            $booking = Schedule::with(['client:id,name,email,phone'])
                ->where('trainer_id', $trainer->id)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Booking retrieved successfully',
                'data' => $booking
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create a new booking
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $trainer = Auth::user();

            $validator = Validator::make($request->all(), [
                'client_id' => 'required|exists:users,id',
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'session_type' => 'nullable|string|in:personal_training,consultation,group_session',
                'notes' => 'nullable|string|max:500',
                'create_google_event' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify client role
            $client = User::where('id', $request->client_id)
                ->where('role', 'client')
                ->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid client selected'
                ], 422);
            }

            // Check for conflicts
            $conflictingBooking = Schedule::where('trainer_id', $trainer->id)
                ->where('date', $request->date)
                ->where('status', '!=', Schedule::STATUS_CANCELLED)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                          ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                          ->orWhere(function ($q) use ($request) {
                              $q->where('start_time', '<=', $request->start_time)
                                ->where('end_time', '>=', $request->end_time);
                          });
                })
                ->exists();

            if ($conflictingBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Time slot conflicts with existing booking'
                ], 422);
            }

            // Create the booking
            $schedule = Schedule::create([
                'trainer_id' => $trainer->id,
                'client_id' => $request->client_id,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'session_type' => $request->session_type ?? 'personal_training',
                'status' => Schedule::STATUS_CONFIRMED,
                'notes' => $request->notes,
            ]);

            // Create Google Calendar event if requested and trainer is connected
            $googleEventResult = null;
            if ($request->get('create_google_event', true) && $this->googleCalendarService->isTrainerConnected($trainer)) {
                try {
                    $googleEventResult = $this->googleCalendarService->createCalendarEvent($schedule);
                } catch (Exception $e) {
                    Log::warning('Failed to create Google Calendar event for new booking', [
                        'schedule_id' => $schedule->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Load the created booking with relationships
            $schedule->load(['client:id,name,email,phone']);

            $response = [
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => $schedule
            ];

            if ($googleEventResult) {
                $response['google_calendar'] = $googleEventResult;
                $response['message'] .= ' with Google Calendar event';
            }

            return response()->json($response, 201);

        } catch (Exception $e) {
            Log::error('Failed to create booking', [
                'trainer_id' => Auth::id(),
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing booking
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $trainer = Auth::user();

            $booking = Schedule::where('trainer_id', $trainer->id)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'client_id' => 'sometimes|required|exists:users,id',
                'date' => 'sometimes|required|date',
                'start_time' => 'sometimes|required|date_format:H:i',
                'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
                'session_type' => 'sometimes|nullable|string|in:personal_training,consultation,group_session',
                'status' => 'sometimes|required|in:pending,confirmed,cancelled',
                'notes' => 'sometimes|nullable|string|max:500',
                'update_google_event' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for conflicts if time/date is being changed
            if ($request->has(['date', 'start_time', 'end_time'])) {
                $date = $request->get('date', $booking->date);
                $startTime = $request->get('start_time', $booking->start_time);
                $endTime = $request->get('end_time', $booking->end_time);

                $conflictingBooking = Schedule::where('trainer_id', $trainer->id)
                    ->where('date', $date)
                    ->where('id', '!=', $id)
                    ->where('status', '!=', Schedule::STATUS_CANCELLED)
                    ->where(function ($query) use ($startTime, $endTime) {
                        $query->whereBetween('start_time', [$startTime, $endTime])
                              ->orWhereBetween('end_time', [$startTime, $endTime])
                              ->orWhere(function ($q) use ($startTime, $endTime) {
                                  $q->where('start_time', '<=', $startTime)
                                    ->where('end_time', '>=', $endTime);
                              });
                    })
                    ->exists();

                if ($conflictingBooking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Time slot conflicts with existing booking'
                    ], 422);
                }
            }

            $oldStatus = $booking->status;
            
            // Update the booking
            $booking->update($request->only([
                'client_id', 'date', 'start_time', 'end_time', 
                'session_type', 'status', 'notes'
            ]));

            // Handle Google Calendar events based on status change
            $googleMessage = '';
            if ($request->get('update_google_event', true) && $this->googleCalendarService->isTrainerConnected($trainer)) {
                try {
                    if ($booking->status === Schedule::STATUS_CONFIRMED && $oldStatus !== Schedule::STATUS_CONFIRMED) {
                        // Create or update Google Calendar event when confirming
                        $googleEventResult = $booking->hasGoogleCalendarEvent() 
                            ? $this->googleCalendarService->updateCalendarEvent($booking)
                            : $this->googleCalendarService->createCalendarEvent($booking);
                            
                        if ($googleEventResult) {
                            $googleMessage = ' with Google Calendar event updated';
                        }
                    } elseif ($booking->status === Schedule::STATUS_CANCELLED && $booking->hasGoogleCalendarEvent()) {
                        // Delete Google Calendar event when cancelling
                        $deleteResult = $this->googleCalendarService->deleteCalendarEvent($booking);
                        if ($deleteResult) {
                            $googleMessage = ' and Google Calendar event deleted';
                        }
                    } elseif ($booking->hasGoogleCalendarEvent()) {
                        // Update existing Google Calendar event for other changes
                        $updateResult = $this->googleCalendarService->updateCalendarEvent($booking);
                        if ($updateResult) {
                            $googleMessage = ' and Google Calendar event updated';
                        }
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to update Google Calendar event', [
                        'schedule_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Load updated booking with relationships
            $booking->load(['client:id,name,email,phone']);

            return response()->json([
                'success' => true,
                'message' => 'Booking updated successfully' . $googleMessage,
                'data' => $booking
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update booking', [
                'trainer_id' => Auth::id(),
                'booking_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a booking
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            $booking = Schedule::where('trainer_id', $trainer->id)->findOrFail($id);

            // Delete Google Calendar event if exists
            if ($booking->hasGoogleCalendarEvent() && $this->googleCalendarService->isTrainerConnected($trainer)) {
                try {
                    $this->googleCalendarService->deleteCalendarEvent($booking);
                } catch (Exception $e) {
                    Log::warning('Failed to delete Google Calendar event', [
                        'schedule_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $booking->delete();

            return response()->json([
                'success' => true,
                'message' => 'Booking deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete booking', [
                'trainer_id' => Auth::id(),
                'booking_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trainer's available time slots
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        try {
            $trainer = Auth::user();

            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if trainer is connected to Google Calendar
            if ($this->googleCalendarService->isTrainerConnected($trainer)) {
                // Get available slots from Google Calendar
                $availableSlots = $this->googleCalendarService->getAvailableSlots(
                    $trainer,
                    $request->start_date,
                    $request->end_date
                );
            } else {
                // Fallback to basic availability check
                $availableSlots = $this->getBasicAvailableSlots($trainer, $request->start_date, $request->end_date);
            }

            return response()->json([
                'success' => true,
                'message' => 'Available slots retrieved successfully',
                'data' => $availableSlots,
                'google_calendar_connected' => $this->googleCalendarService->isTrainerConnected($trainer)
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get available slots', [
                'trainer_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get available slots',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get basic available slots (fallback when Google Calendar is not connected)
     * 
     * @param User $trainer
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getBasicAvailableSlots(User $trainer, string $startDate, string $endDate): array
    {
        $availableSlots = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Get trainer's availability settings
        $availabilities = Availability::where('trainer_id', $trainer->id)
            ->get()
            ->keyBy('day_of_week');

        // Get existing bookings
        $existingBookings = Schedule::where('trainer_id', $trainer->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', Schedule::STATUS_CANCELLED)
            ->get();

        // Get blocked times
        $blockedTimes = BlockedTime::where('trainer_id', $trainer->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        while ($current->lte($end)) {
            $dayOfWeek = $current->dayOfWeek;
            $availability = $availabilities->get($dayOfWeek);

            if ($availability) {
                // Generate slots based on availability
                if ($availability->morning_available) {
                    $this->addTimeSlots($availableSlots, $current, 9, 12, $existingBookings, $blockedTimes);
                }
                if ($availability->evening_available) {
                    $this->addTimeSlots($availableSlots, $current, 14, 17, $existingBookings, $blockedTimes);
                }
            }

            $current->addDay();
        }

        return $availableSlots;
    }

    /**
     * Add time slots for a specific time range
     * 
     * @param array &$availableSlots
     * @param Carbon $date
     * @param int $startHour
     * @param int $endHour
     * @param \Illuminate\Database\Eloquent\Collection $existingBookings
     * @param \Illuminate\Database\Eloquent\Collection $blockedTimes
     */
    private function addTimeSlots(&$availableSlots, Carbon $date, int $startHour, int $endHour, $existingBookings, $blockedTimes)
    {
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $slotStart = $date->copy()->setTime($hour, 0, 0);
            $slotEnd = $slotStart->copy()->addHour();

            // Skip past times
            if ($slotStart->lt(Carbon::now())) {
                continue;
            }

            // Check for conflicts with existing bookings
            $hasConflict = $existingBookings->contains(function ($booking) use ($slotStart, $slotEnd, $date) {
                return $booking->date->format('Y-m-d') === $date->format('Y-m-d') &&
                       (($slotStart->format('H:i') >= $booking->start_time->format('H:i') && 
                         $slotStart->format('H:i') < $booking->end_time->format('H:i')) ||
                        ($slotEnd->format('H:i') > $booking->start_time->format('H:i') && 
                         $slotEnd->format('H:i') <= $booking->end_time->format('H:i')));
            });

            // Check for blocked times
            $isBlocked = $blockedTimes->contains(function ($blockedTime) use ($slotStart, $slotEnd, $date) {
                return $blockedTime->date->format('Y-m-d') === $date->format('Y-m-d') &&
                       (($slotStart->format('H:i') >= $blockedTime->start_time->format('H:i') && 
                         $slotStart->format('H:i') < $blockedTime->end_time->format('H:i')) ||
                        ($slotEnd->format('H:i') > $blockedTime->start_time->format('H:i') && 
                         $slotEnd->format('H:i') <= $blockedTime->end_time->format('H:i')));
            });

            if (!$hasConflict && !$isBlocked) {
                $availableSlots[] = [
                    'start' => $slotStart->toISOString(),
                    'end' => $slotEnd->toISOString(),
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'date' => $slotStart->format('Y-m-d'),
                    'display' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A')
                ];
            }
        }
    }

    /**
     * Get trainer's clients
     * 
     * @return JsonResponse
     */
    public function getClients(): JsonResponse
    {
        try {
            $trainer = Auth::user();

            // Get clients who have bookings with this trainer
            $clients = User::where('role', 'client')
                ->whereHas('clientBookings', function ($query) use ($trainer) {
                    $query->where('trainer_id', $trainer->id);
                })
                ->select('id', 'name', 'email', 'phone')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Clients retrieved successfully',
                'data' => $clients
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Google Calendar connection status
     * 
     * @return JsonResponse
     */
    public function getGoogleCalendarStatus(): JsonResponse
    {
        try {
            $trainer = Auth::user();
            $isConnected = $this->googleCalendarService->isTrainerConnected($trainer);

            return response()->json([
                'success' => true,
                'message' => 'Google Calendar status retrieved successfully',
                'data' => [
                    'connected' => $isConnected,
                    'trainer_id' => $trainer->id,
                    'trainer_name' => $trainer->name
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get Google Calendar status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}