<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Availability;
use App\Models\BlockedTime;
use App\Models\SessionCapacity;
use App\Models\BookingSetting;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Trainer Scheduling API Controller
 * 
 * Handles trainer scheduling operations including availability, blocked times,
 * session capacity, and booking settings management
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Api
 * @category    Scheduling Module
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class TrainerSchedulingController extends ApiBaseController
{
    /**
     * Set trainer weekly availability
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function setAvailability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'availability' => 'required|array',
                'availability.*.day_of_week' => 'required|integer|between:0,6',
                'availability.*.morning_available' => 'boolean',
                'availability.*.evening_available' => 'boolean',
                'availability.*.morning_start_time' => 'nullable|date_format:H:i',
                'availability.*.morning_end_time' => 'nullable|date_format:H:i',
                'availability.*.evening_start_time' => 'nullable|date_format:H:i',
                'availability.*.evening_end_time' => 'nullable|date_format:H:i',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $trainerId = Auth::id();
            $availabilityData = [];

            foreach ($request->availability as $dayData) {
                // Validate time ranges
                if ($dayData['morning_available'] ?? false) {
                    if (empty($dayData['morning_start_time']) || empty($dayData['morning_end_time'])) {
                        return $this->sendError('Morning start and end times are required when morning is available');
                    }
                }

                if ($dayData['evening_available'] ?? false) {
                    if (empty($dayData['evening_start_time']) || empty($dayData['evening_end_time'])) {
                        return $this->sendError('Evening start and end times are required when evening is available');
                    }
                }

                $availability = Availability::updateOrCreate(
                    [
                        'trainer_id' => $trainerId,
                        'day_of_week' => $dayData['day_of_week']
                    ],
                    [
                        'morning_available' => $dayData['morning_available'] ?? false,
                        'evening_available' => $dayData['evening_available'] ?? false,
                        'morning_start_time' => $dayData['morning_start_time'] ?? null,
                        'morning_end_time' => $dayData['morning_end_time'] ?? null,
                        'evening_start_time' => $dayData['evening_start_time'] ?? null,
                        'evening_end_time' => $dayData['evening_end_time'] ?? null,
                    ]
                );

                $availabilityData[] = $availability;
            }

            return $this->sendResponse($availabilityData, 'Availability updated successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get trainer availability
     * 
     * @return JsonResponse
     */
    public function getAvailability(): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $availability = Availability::forTrainer($trainerId)
                ->orderBy('day_of_week')
                ->get();

            return $this->sendResponse($availability, 'Availability retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add blocked time slot
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function addBlockedTime(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'reason' => 'required|string|max:255',
                'is_recurring' => 'boolean',
                'recurring_type' => 'nullable|in:daily,weekly,monthly',
                'recurring_end_date' => 'nullable|date|after:date',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $trainerId = Auth::id();

            // Check for conflicts with existing schedules
            $existingSchedules = Schedule::forTrainer($trainerId)
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

            if ($existingSchedules) {
                return $this->sendError('Time slot conflicts with existing bookings');
            }

            $blockedTime = BlockedTime::create([
                'trainer_id' => $trainerId,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'reason' => $request->reason,
                'is_recurring' => $request->is_recurring ?? false,
                'recurring_type' => $request->recurring_type,
                'recurring_end_date' => $request->recurring_end_date,
            ]);

            return $this->sendResponse($blockedTime, 'Blocked time added successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get trainer blocked times
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getBlockedTimes(Request $request): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $query = BlockedTime::forTrainer($trainerId);

            // Filter by date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->dateRange($request->start_date, $request->end_date);
            } else {
                // Default to active blocked times
                $query->active();
            }

            $blockedTimes = $query->orderBy('date')
                ->orderBy('start_time')
                ->get();

            return $this->sendResponse($blockedTimes, 'Blocked times retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete blocked time
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function deleteBlockedTime(int $id): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $blockedTime = BlockedTime::where('id', $id)
                ->where('trainer_id', $trainerId)
                ->first();

            if (!$blockedTime) {
                return $this->sendError('Blocked time not found', [], 404);
            }

            $blockedTime->delete();

            return $this->sendResponse([], 'Blocked time deleted successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Set session capacity
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function setSessionCapacity(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'max_daily_sessions' => 'required|integer|min:1|max:24',
                'max_weekly_sessions' => 'required|integer|min:1|max:168',
                'session_duration_minutes' => 'nullable|integer|min:15|max:480',
                'break_between_sessions_minutes' => 'nullable|integer|min:0|max:120',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $trainerId = Auth::id();

            // Validate that weekly capacity is reasonable for daily capacity
            if ($request->max_daily_sessions * 7 > $request->max_weekly_sessions) {
                return $this->sendError('Weekly session limit is too low for the daily session limit');
            }

            $sessionCapacity = SessionCapacity::updateOrCreate(
                ['trainer_id' => $trainerId],
                [
                    'max_daily_sessions' => $request->max_daily_sessions,
                    'max_weekly_sessions' => $request->max_weekly_sessions,
                    'session_duration_minutes' => $request->session_duration_minutes ?? 45,
                    'break_between_sessions_minutes' => $request->break_between_sessions_minutes ?? 10,
                ]
            );

             $reposeData = [
                "trainer_id" => $sessionCapacity->trainer_id,
                "max_daily_sessions" => $sessionCapacity->max_daily_sessions,
                "max_weekly_sessions" => $sessionCapacity->max_weekly_sessions,
                "updated_at" => $sessionCapacity->updated_at,
                "created_at" => $sessionCapacity->created_at,
                "id" => $sessionCapacity->id
             ];

            return $this->sendResponse($reposeData, 'Session capacity updated successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get session capacity
     * 
     * @return JsonResponse
     */
    public function getSessionCapacity(): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $sessionCapacity = SessionCapacity::getOrCreateForTrainer($trainerId);

             $reposeData = [
                "trainer_id" => $sessionCapacity->trainer_id,
                "max_daily_sessions" => $sessionCapacity->max_daily_sessions,
                "max_weekly_sessions" => $sessionCapacity->max_weekly_sessions,
                "updated_at" => $sessionCapacity->updated_at,
                "created_at" => $sessionCapacity->created_at,
                "id" => $sessionCapacity->id
             ];
            return $this->sendResponse($reposeData, 'Session capacity retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Set booking settings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function setBookingSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'allow_self_booking' => 'required|boolean',
                'require_approval' => 'required|boolean'
                // 'advance_booking_days' => 'required|integer|min:1|max:365',
                // 'cancellation_hours' => 'required|integer|min:0|max:168',
                // 'allow_weekend_booking' => 'required|boolean',
                // 'earliest_booking_time' => 'required|date_format:H:i',
                // 'latest_booking_time' => 'required|date_format:H:i|after:earliest_booking_time',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $trainerId = Auth::id();

            $bookingSettings = BookingSetting::updateOrCreate(
                ['trainer_id' => $trainerId],
                [
                    'allow_self_booking' => $request->allow_self_booking,
                    'require_approval' => $request->require_approval
                    // 'advance_booking_days' => $request->advance_booking_days,
                    // 'cancellation_hours' => $request->cancellation_hours,
                    // 'allow_weekend_booking' => $request->allow_weekend_booking,
                    // 'earliest_booking_time' => $request->earliest_booking_time,
                    // 'latest_booking_time' => $request->latest_booking_time,
                ]
            );

             $reposeData = [
                "trainer_id" => $bookingSettings->trainer_id,
                "allow_self_booking" => $bookingSettings->allow_self_booking,
                "require_approval" => $bookingSettings->require_approval,
                "updated_at" => $bookingSettings->updated_at,
                "created_at" => $bookingSettings->created_at,
                "id" => $bookingSettings->id
             ];
            return $this->sendResponse($reposeData, 'Booking settings updated successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get booking settings
     * 
     * @return JsonResponse
     */
    public function getBookingSettings(): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $bookingSettings = BookingSetting::getOrCreateForTrainer($trainerId);

             $reposeData = [
                "trainer_id" => $bookingSettings->trainer_id,
                "allow_self_booking" => $bookingSettings->allow_self_booking,
                "require_approval" => $bookingSettings->require_approval,
                "updated_at" => $bookingSettings->updated_at,
                "created_at" => $bookingSettings->created_at,
                "id" => $bookingSettings->id
             ];
            return $this->sendResponse($reposeData, 'Booking settings retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get trainer bookings
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getBookings(Request $request): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $query = Schedule::forTrainer($trainerId)
                ->with(['client:id,name,email,phone']);

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

            return $this->sendResponse($bookings, 'Bookings retrieved successfully');

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update booking status (approve/reject)
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateBookingStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:confirmed,cancelled',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $trainerId = Auth::id();
            $schedule = Schedule::where('id', $id)
                ->where('trainer_id', $trainerId)
                ->first();

            if (!$schedule) {
                return $this->sendError('Booking not found', [], 404);
            }

            if ($schedule->status !== Schedule::STATUS_PENDING) {
                return $this->sendError('Only pending bookings can be updated');
            }

            $oldStatus = $schedule->status;
            
            $schedule->update([
                'status' => $request->status,
                'notes' => $request->notes
            ]);

            $schedule->load('client:id,name,email,phone');

            // Handle Google Calendar events based on status change
            $googleEventResult = null;
            $googleMessage = '';
            
            if ($request->status === Schedule::STATUS_CONFIRMED && $oldStatus === Schedule::STATUS_PENDING) {
                // Create Google Calendar event when confirming
                $googleEventResult = $schedule->createGoogleCalendarEvent();
                if ($googleEventResult) {
                    $googleMessage = ' Google Calendar event created with Meet link.';
                } else {
                    $googleMessage = ' Note: Google Calendar event could not be created.';
                }
            } elseif ($request->status === Schedule::STATUS_CANCELLED && $schedule->hasGoogleCalendarEvent()) {
                // Delete Google Calendar event when cancelling
                $deleteResult = $schedule->deleteGoogleCalendarEvent();
                if ($deleteResult) {
                    $googleMessage = ' Google Calendar event deleted.';
                } else {
                    $googleMessage = ' Note: Google Calendar event could not be deleted.';
                }
            }

            // Prepare response data
            $responseData = $schedule->toArray();
            if ($googleEventResult && isset($googleEventResult['meet_link'])) {
                $responseData['meet_link'] = $googleEventResult['meet_link'];
                $responseData['google_event_created'] = true;
            } else {
                $responseData['meet_link'] = $schedule->meet_link;
                $responseData['google_event_created'] = false;
            }

            $message = 'Booking status updated successfully' . $googleMessage;

            return $this->sendResponse($responseData, $message);

        } catch (\Exception $e) {
            return $this->sendError('Server Error', ['error' => $e->getMessage()], 500);
        }
    }
}