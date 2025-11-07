<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Schedule;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Session Booking Controller
 * 
 * Unified API controller for session booking management for both clients and trainers
 * Provides comprehensive CRUD operations with Google Calendar integration
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Api
 * @category    Session Booking Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class SessionBookingController extends ApiBaseController
{
    /**
     * Google Calendar Service instance
     * 
     * @var GoogleCalendarService
     */
    protected $googleCalendarService;

    /**
     * Constructor
     * 
     * @param GoogleCalendarService $googleCalendarService
     */
    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Get all bookings for the authenticated user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            // Default to filtering from "today" onward if no start_date provided
            // Uses the app timezone to respect localization preferences
            $startDate = $request->get('start_date', Carbon::now(config('app.timezone'))->toDateString());
            $endDate = $request->get('end_date');

            // Build query based on user role
            $query = Schedule::with(['trainer:id,name,email,phone', 'client:id,name,email,phone']);

            if ($user->role === 'trainer') {
                $query->forTrainer($user->id);
            } elseif ($user->role === 'client') {
                $query->forClient($user->id);
            } else {
                return $this->sendError('Unauthorized', ['error' => 'Invalid user role'], 403);
            }

            // Apply filters
            if ($status && in_array($status, [Schedule::STATUS_PENDING, Schedule::STATUS_CONFIRMED, Schedule::STATUS_CANCELLED])) {
                $query->withStatus($status);
            }

            // Apply date filters: if end_date present use range, else return from today onward
            if ($endDate) {
                $query->dateRange($startDate, $endDate);
            } else {
                $query->whereDate('date', '>=', $startDate);
            }

            // Order by date and time
            $query->orderBy('date', 'desc')->orderBy('start_time', 'desc');

            $bookings = $query->paginate($perPage);

            // Transform the data to include additional information
            $transformedBookings = $bookings->getCollection()->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'title' => $booking->meeting_agenda,
                    'date' => $booking->date->format('Y-m-d'),
                    'start_time' => $booking->start_time->format('H:i'),
                    'end_time' => $booking->end_time->format('H:i'),
                    'status' => $booking->status,
                    'notes' => $booking->notes,
                    'timezone' => $booking->timezone,
                    'duration_minutes' => $booking->getDurationInMinutes(),
                    'google_event_id' => $booking->google_event_id,
                    'meet_link' => $booking->meet_link,
                    'has_google_event' => $booking->hasGoogleCalendarEvent(),
                    'has_meet_link' => $booking->hasGoogleMeetLink(),
                    'can_be_cancelled' => $booking->canBeCancelled(),
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->updated_at,
                    'trainer' => $booking->trainer,
                    'client' => $booking->client
                ];
            });

            $bookings->setCollection($transformedBookings);

            return $this->sendResponse($bookings, 'Bookings retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving bookings', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created booking
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Validation rules
            $rules = [
                'trainer_id' => 'required|exists:users,id',
                'client_id' => 'required|exists:users,id',
                'title' => 'required|string|max:255',
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'timezone' => 'required|string',
                'notes' => 'nullable|string|max:500',
                'session_type' => 'nullable|string|max:100',
                'status' => 'nullable|string|in:' . implode(',', [Schedule::STATUS_PENDING, Schedule::STATUS_CONFIRMED, Schedule::STATUS_CANCELLED])
            ];

            // Role-specific validation
            if ($user->role === 'client') {
                $rules['trainer_id'] = 'required|exists:users,id';
            } elseif ($user->role === 'trainer') {
                $rules['client_id'] = 'required|exists:users,id';
            } else {
                return $this->sendError('Unauthorized', ['error' => 'Invalid user role'], 403);
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            // Determine trainer and client IDs based on user role
            if ($user->role === 'client') {
                $trainerId = $request->trainer_id;
                $clientId = $user->id;
            } else {
                $trainerId = $user->id;
                $clientId = $request->client_id;
            }

            // Verify trainer and client roles
            $trainer = User::where('id', $trainerId)->where('role', 'trainer')->first();
            $client = User::where('id', $clientId)->where('role', 'client')->first();

            if (!$trainer || !$client) {
                return $this->sendError('Validation Error', ['error' => 'Invalid trainer or client'], 422);
            }

            // Check for conflicts (unless override flag provided)
            if (!$request->has('override_conflicts')) {
                $conflictingBooking = Schedule::where('trainer_id', $trainerId)
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
                    return $this->sendError('Conflict Error', ['error' => 'Time slot conflicts with existing booking'], 409);
                }
            }

            // Create the booking (default to pending if status not provided)
            $status = $request->get('status', Schedule::STATUS_PENDING);

            $schedule = Schedule::create([
                'trainer_id' => $trainerId,
                'client_id' => $clientId,
                'timezone' => $request->timezone,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $status,
                'notes' => $request->notes,
                'meeting_agenda' => $request->title,
                'session_type' => $request->session_type,
            ]);

            // Create Google Calendar event if booking is confirmed
            $googleEventResult = null;
            if ($schedule->status === Schedule::STATUS_CONFIRMED) {
                $googleEventResult = $schedule->createGoogleCalendarEvent();
            }

            $message = 'Booking created successfully';
            if ($schedule->status === Schedule::STATUS_CONFIRMED) {
                if ($googleEventResult) {
                    $message .= ' with Google Calendar event and Meet link';
                } else {
                    $message .= ' (Google Calendar event could not be created)';
                     $schedule->update([
                        'status' => Schedule::STATUS_PENDING,
                        'notes' => 'Google Calendar event creation failed: ' . $googleEventResult,
                     ]);
                }
            }

            // Prepare response data
            $responseData = [
                'id' => $schedule->id,
                'trainer_id' => $schedule->trainer_id,
                'client_id' => $schedule->client_id,
                "title" => $schedule->meeting_agenda,
                'timezone' => $schedule->timezone,
                'date' => is_object($schedule->date) ? $schedule->date->format('Y-m-d') : $schedule->date,
                'start_time' => is_object($schedule->start_time) ? $schedule->start_time->format('H:i') : $schedule->start_time,
                'end_time' => is_object($schedule->end_time) ? $schedule->end_time->format('H:i') : $schedule->end_time,
                'status' => $schedule->status,
                'notes' => $schedule->notes,
                'session_type' => $schedule->session_type,
                'meet_link' => $schedule->meet_link,
                'google_event_id' => $schedule->google_event_id,
            ];

            return $this->sendResponse($responseData, $message);

        } catch (\Exception $e) {
            Log::error('Error creating booking', [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified booking
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $query = Schedule::with(['trainer:id,name,email,phone', 'client:id,name,email,phone']);

            // Apply user role restrictions
            if ($user->role === 'trainer') {
                $query->forTrainer($user->id);
            } elseif ($user->role === 'client') {
                $query->forClient($user->id);
            } else {
                return $this->sendError('Unauthorized', ['error' => 'Invalid user role'], 403);
            }

            $booking = $query->find($id);

            if (!$booking) {
                return $this->sendError('Not Found', ['error' => 'Booking not found'], 404);
            }

            $responseData = [
                'id' => $booking->id,
                'title' => $booking->meeting_agenda,
                'timezone' => $booking->timezone,
                'date' => $booking->date->format('Y-m-d'),
                'start_time' => $booking->start_time->format('H:i'),
                'end_time' => $booking->end_time->format('H:i'),
                'status' => $booking->status,
                'notes' => $booking->notes,
                'session_type' => $booking->session_type,
                'duration_minutes' => $booking->getDurationInMinutes(),
                'google_event_id' => $booking->google_event_id,
                'meet_link' => $booking->meet_link,
                'has_google_event' => $booking->hasGoogleCalendarEvent(),
                'has_meet_link' => $booking->hasGoogleMeetLink(),
                'can_be_cancelled' => $booking->canBeCancelled(),
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,
                'trainer' => $booking->trainer,
                'client' => $booking->client
            ];

            return $this->sendResponse($responseData, 'Booking retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving booking', [
                'user_id' => Auth::id(),
                'booking_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified booking
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Only trainers can update bookings
            if ($user->role !== 'trainer') {
                return $this->sendError('Unauthorized', ['error' => 'Only trainers can update bookings'], 403);
            }

            $validator = Validator::make($request->all(),  [
                'trainer_id' => 'required|exists:users,id',
                'client_id' => 'required|exists:users,id',
                'title' => 'required|string|max:255',
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'timezone' => 'required|string',
                'notes' => 'nullable|string|max:500',
                'session_type' => 'nullable|string|max:100',
                'status' => 'nullable|string|in:' . implode(',', [Schedule::STATUS_PENDING, Schedule::STATUS_CONFIRMED, Schedule::STATUS_CANCELLED])
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            // Restrict to trainer's own bookings
            $booking = Schedule::where('trainer_id', $user->id)->find($id);

            if (!$booking) {
                return $this->sendError('Not Found', ['error' => 'Booking not found'], 404);
            }

            // Enforce trainer ownership as per payload
            if ((int) $request->trainer_id !== (int) $user->id) {
                return $this->sendError('Unauthorized', ['error' => 'You can only update your own bookings'], 403);
            }

            // Check for conflicts (unless override or same booking)
            if (!$request->has('override_conflicts')) {
                $conflictingBooking = Schedule::where('trainer_id', $request->trainer_id)
                    ->where('date', $request->date)
                    ->where('id', '!=', $id)
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
                    return $this->sendError('Time slot conflicts with existing booking');
                }
            }

            $oldStatus = $booking->status;

            $booking->update([
                'trainer_id' => $request->trainer_id,
                'client_id' => $request->client_id,
                'meeting_agenda' => $request->title,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $request->status,
                'notes' => $request->notes,
                'timezone' => $request->timezone,
                'session_type' => $request->session_type,
            ]);

            // Handle Google Calendar events based on status change (same logic as ClientBookingController)
            $googleMessage = '';

            if ($request->status === Schedule::STATUS_CONFIRMED && $oldStatus !== Schedule::STATUS_CONFIRMED) {
                // Create or update Google Calendar event when confirming
                $googleEventResult = $booking->hasGoogleCalendarEvent()
                    ? $booking->updateGoogleCalendarEvent()
                    : $booking->createGoogleCalendarEvent();
                
                if ($googleEventResult) {
                    $googleMessage = ' with Google Calendar event and Meet link';
                } else {
                    $googleMessage = ' (Google Calendar event could not be created)';
                }
            } elseif ($request->status === Schedule::STATUS_CANCELLED && $booking->hasGoogleCalendarEvent()) {
                // Delete Google Calendar event when cancelling
                $deleteResult = $booking->deleteGoogleCalendarEvent();
                if ($deleteResult) {
                    $googleMessage = ' and Google Calendar event deleted';
                } else {
                    $googleMessage = ' (Google Calendar event could not be deleted)';
                }
            } elseif ($oldStatus !== $request->status && $booking->hasGoogleCalendarEvent()) {
                // Update existing Google Calendar event for other status changes
                $updateResult = $booking->updateGoogleCalendarEvent();
                if ($updateResult) {
                    $googleMessage = ' and Google Calendar event updated';
                }
            }

            $message = 'Booking updated successfully' . $googleMessage;

            return $this->sendResponse($booking, $message);

        } catch (\Exception $e) {
            Log::error('Error updating booking', [
                'user_id' => Auth::id(),
                'booking_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified booking
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Only trainers can delete bookings
            if ($user->role !== 'trainer') {
                return $this->sendError('Unauthorized', ['error' => 'Only trainers can delete bookings'], 403);
            }

            // Restrict to trainer's own bookings
            $booking = Schedule::where('trainer_id', $user->id)->find($id);

            if (!$booking) {
                return $this->sendError('Not Found', ['error' => 'Booking not found'], 404);
            }

            // Delete Google Calendar event if it exists
            $googleMessage = '';
            if ($booking->hasGoogleCalendarEvent()) {
                $deleteResult = $booking->deleteGoogleCalendarEvent();
                if ($deleteResult) {
                    $googleMessage = ' and Google Calendar event deleted';
                } else {
                    $googleMessage = ' (Google Calendar event could not be deleted)';
                }
            }

            $booking->delete();

            $message = 'Booking deleted successfully' . $googleMessage;

            return $this->sendResponse(['deleted' => true], $message);

        } catch (\Exception $e) {
            Log::error('Error deleting booking', [
                'user_id' => Auth::id(),
                'booking_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update booking status (for trainers to confirm/cancel client bookings)
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();

            // Only trainers can update booking status
            if ($user->role !== 'trainer') {
                return $this->sendError('Unauthorized', ['error' => 'Only trainers can update booking status'], 403);
            }

            $booking = Schedule::forTrainer($user->id)->find($id);

            if (!$booking) {
                return $this->sendError('Not Found', ['error' => 'Booking not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:' . implode(',', array_keys(Schedule::getStatuses()))
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $oldStatus = $booking->status;
            $booking->status = $request->status;
            $booking->save();

            // Load relationships
            $booking->load(['trainer:id,name,email,phone', 'client:id,name,email,phone']);

            // Handle Google Calendar events based on status change
            $googleMessage = '';
            $googleEventResult = null;

            if ($request->status === Schedule::STATUS_CONFIRMED && $oldStatus === Schedule::STATUS_PENDING) {
                // Create Google Calendar event when confirming
                $googleEventResult = $booking->createGoogleCalendarEvent();
                if ($googleEventResult) {
                    $googleMessage = ' Google Calendar event created with Meet link.';
                } else {
                    $googleMessage = ' Note: Google Calendar event could not be created.';
                }
            } elseif ($request->status === Schedule::STATUS_CANCELLED && $booking->hasGoogleCalendarEvent()) {
                // Delete Google Calendar event when cancelling
                $deleteResult = $booking->deleteGoogleCalendarEvent();
                if ($deleteResult) {
                    $googleMessage = ' Google Calendar event deleted.';
                } else {
                    $googleMessage = ' Note: Google Calendar event could not be deleted.';
                }
            }

            // Prepare response data
            $responseData = [
                'id' => $booking->id,
                'trainer' => $booking->trainer,
                'client' => $booking->client,
                'date' => $booking->date->format('Y-m-d'),
                'start_time' => $booking->start_time->format('H:i'),
                'end_time' => $booking->end_time->format('H:i'),
                'status' => $booking->status,
                'notes' => $booking->notes,
                'session_type' => $booking->session_type,
                'duration_minutes' => $booking->getDurationInMinutes(),
                'google_event_id' => $booking->google_event_id,
                'meet_link' => $booking->meet_link,
                'has_google_event' => $booking->hasGoogleCalendarEvent(),
                'has_meet_link' => $booking->hasGoogleMeetLink(),
                'can_be_cancelled' => $booking->canBeCancelled(),
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at
            ];

            if ($googleEventResult && isset($googleEventResult['meet_link'])) {
                $responseData['meet_link'] = $googleEventResult['meet_link'];
                $responseData['google_event_created'] = true;
            } else {
                $responseData['meet_link'] = $booking->meet_link;
                $responseData['google_event_created'] = false;
            }

            $message = 'Booking status updated successfully' . $googleMessage;

            return $this->sendResponse($responseData, $message);

        } catch (\Exception $e) {
            Log::error('Error updating booking status', [
                'user_id' => Auth::id(),
                'booking_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get available time slots for booking
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'trainer_id' => 'required|exists:users,id',
                'date' => 'required|date|after_or_equal:today',
                'duration' => 'sometimes|integer|min:30|max:180' // Duration in minutes
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $trainerId = $request->trainer_id;
            $date = $request->date;
            $duration = $request->get('duration', 60); // Default 60 minutes

            // Verify trainer exists and has trainer role
            $trainer = User::where('id', $trainerId)->where('role', 'trainer')->first();
            if (!$trainer) {
                return $this->sendError('Validation Error', ['error' => 'Invalid trainer'], 422);
            }

            // Get existing bookings for the date
            $existingBookings = Schedule::where('trainer_id', $trainerId)
                ->where('date', $date)
                ->where('status', '!=', Schedule::STATUS_CANCELLED)
                ->get(['start_time', 'end_time']);

            // Generate available slots (simplified version - can be enhanced with trainer availability)
            $availableSlots = $this->generateAvailableSlots($date, $duration, $existingBookings);

            return $this->sendResponse([
                'date' => $date,
                'trainer_id' => $trainerId,
                'duration_minutes' => $duration,
                'available_slots' => $availableSlots
            ], 'Available slots retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving available slots', [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate available time slots
     * 
     * @param string $date
     * @param int $duration
     * @param \Illuminate\Database\Eloquent\Collection $existingBookings
     * @return array
     */
    private function generateAvailableSlots(string $date, int $duration, $existingBookings): array
    {
        $slots = [];
        $startHour = 9; // 9 AM
        $endHour = 18; // 6 PM
        
        $currentTime = Carbon::parse($date)->setHour($startHour)->setMinute(0);
        $endTime = Carbon::parse($date)->setHour($endHour)->setMinute(0);

        while ($currentTime->addMinutes($duration)->lte($endTime)) {
            $slotStart = $currentTime->copy()->subMinutes($duration);
            $slotEnd = $currentTime->copy();

            // Check if this slot conflicts with existing bookings
            $hasConflict = false;
            foreach ($existingBookings as $booking) {
                $bookingStart = Carbon::parse($date . ' ' . $booking->start_time->format('H:i:s'));
                $bookingEnd = Carbon::parse($date . ' ' . $booking->end_time->format('H:i:s'));

                if ($slotStart->lt($bookingEnd) && $slotEnd->gt($bookingStart)) {
                    $hasConflict = true;
                    break;
                }
            }

            if (!$hasConflict) {
                $slots[] = [
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'start_datetime' => $slotStart->toISOString(),
                    'end_datetime' => $slotEnd->toISOString(),
                    'display' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A')
                ];
            }

            $currentTime = $slotEnd;
        }

        return $slots;
    }

    /**
     * Send success response
     * 
     * @param mixed $result
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    //  function sendResponse($result, string $message, int $code = 200): JsonResponse
    // {
    //     $response = [
    //         'success' => true,
    //         'data' => $result,
    //         'message' => $message
    //     ];

    //     return response()->json($response, $code);
    // }


}