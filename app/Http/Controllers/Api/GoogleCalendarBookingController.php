<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Schedule;
use App\Models\BookingSetting;
use App\Services\GoogleCalendarService;
use App\Services\AvailabilityService;
use App\Http\Controllers\GoogleController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * Google Calendar Booking API Controller
 * 
 * Provides comprehensive API endpoints for Google Calendar booking functionality
 * Mirrors the admin panel Google Calendar booking system for mobile/API access
 * 
 * @package     Laravel CMS App
 * @subpackage  API Controllers
 * @category    Google Calendar Integration
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class GoogleCalendarBookingController extends Controller
{
    /**
     * Google Calendar Service instance
     * 
     * @var GoogleCalendarService
     */
    protected $googleCalendarService;

    /**
     * Google Controller instance
     * 
     * @var GoogleController
     */
    protected $googleController;

    /**
     * Availability Service instance
     * 
     * @var AvailabilityService
     */
    protected $availabilityService;

    /**
     * Constructor - Initialize required services
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->googleCalendarService = new GoogleCalendarService();
        $this->googleController = new GoogleController();
        $this->availabilityService = new AvailabilityService();
    }

    /**
     * Check Google Calendar connection status for a trainer
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkConnectionStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'trainer_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $trainer = User::findOrFail($request->trainer_id);
            
            if ($trainer->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a trainer'
                ], 400);
            }

            $connectionStatus = $this->googleController->getTrainerConnectionStatus($trainer);

            return response()->json([
                'success' => true,
                'data' => [
                    'trainer_id' => $trainer->id,
                    'trainer_name' => $trainer->name,
                    'connected' => $connectionStatus['connected'],
                    'email' => $connectionStatus['email'] ?? null,
                    'connection_status' => $connectionStatus['connected'] ? 'connected' : 'disconnected'
                ],
                'message' => 'Connection status retrieved successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Error checking Google Calendar connection status', [
                'trainer_id' => $request->trainer_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking Google Calendar connection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Google OAuth authentication URL for trainer
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user is a trainer or admin
            if (!in_array($user->role, ['trainer', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only trainers and admins can connect Google Calendar'
                ], 403);
            }

            // For admin users, they might be connecting on behalf of a trainer
            if ($user->role === 'admin' && $request->has('trainer_id')) {
                $validator = Validator::make($request->all(), [
                    'trainer_id' => 'required|exists:users,id'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $trainer = User::findOrFail($request->trainer_id);
                if ($trainer->role !== 'trainer') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Specified user is not a trainer'
                    ], 400);
                }
            }

            // Use the GoogleController to generate auth URL
            $response = $this->googleController->redirectToGoogle($request);
            
            if ($response instanceof JsonResponse) {
                return $response;
            }

            // If it's a redirect response, extract the URL
            if (method_exists($response, 'getTargetUrl')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'auth_url' => $response->getTargetUrl()
                    ],
                    'message' => 'Google OAuth URL generated successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Google OAuth URL'
            ], 500);

        } catch (Exception $e) {
            Log::error('Error generating Google OAuth URL', [
                'user_id' => Auth::id(),
                'trainer_id' => $request->trainer_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error generating Google OAuth URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available time slots for a trainer with Google Calendar integration
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'trainer_id' => 'required|exists:users,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'slot_duration' => 'nullable|integer|min:15|max:240'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Load trainer with all related settings
            $trainer = User::with([
                'availabilities', 
                'sessionCapacity', 
                'bookingSettings', 
                'blockedTimes' => function($query) use ($request) {
                    $query->whereBetween('date', [$request->start_date, $request->end_date])
                          ->orWhere('is_recurring', true);
                }
            ])->findOrFail($request->trainer_id);
            
            if ($trainer->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a trainer'
                ], 400);
            }

            // Check if trainer has availability settings
            if ($trainer->availabilities->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainer has not configured their availability schedule yet.',
                    'data' => [
                        'trainer_info' => [
                            'id' => $trainer->id,
                            'name' => $trainer->name,
                            'has_availability' => false
                        ]
                    ]
                ], 400);
            }

            // Check booking settings
            $bookingSettings = $trainer->bookingSettings;
            if ($bookingSettings && !$bookingSettings->allow_self_booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'This trainer does not allow self-booking. Please contact them directly.',
                    'data' => [
                        'trainer_info' => [
                            'id' => $trainer->id,
                            'name' => $trainer->name,
                            'allows_self_booking' => false
                        ]
                    ]
                ], 400);
            }

            $slotDuration = $request->slot_duration ?? 60;
            
            // Use session capacity duration if available
            if ($trainer->sessionCapacity && $trainer->sessionCapacity->session_duration_minutes) {
                $slotDuration = $trainer->sessionCapacity->session_duration_minutes;
            }

            // Check if trainer has Google Calendar connected
            $connectionStatus = $this->googleController->getTrainerConnectionStatus($trainer);
            $source = 'local';
            $availableSlots = [];

            if ($connectionStatus['connected']) {
                // Reload trainer from database to get any updated token from connection check
                $trainer->refresh();
                
                // Create a fresh GoogleCalendarService instance to ensure it has the latest token
                $freshGoogleCalendarService = new \App\Services\GoogleCalendarService();
                
                // Use Google Calendar for availability
                try {
                    $availableSlots = $freshGoogleCalendarService->getAvailableSlots(
                        $trainer, 
                        $request->start_date, 
                        $request->end_date,
                        $slotDuration
                    );
                    $source = 'google_calendar';
                } catch (Exception $e) {
                    // If Google Calendar fails, fall back to local availability
                    Log::warning('Google Calendar failed, falling back to local availability', [
                        'trainer_id' => $trainer->id,
                        'error' => $e->getMessage()
                    ]);
                    $availableSlots = $this->availabilityService->getAvailableSlots(
                        $trainer, 
                        $request->start_date, 
                        $request->end_date,
                        $slotDuration
                    );
                    $source = 'local_fallback';
                }
            } else {
                // Use AvailabilityService for local availability system
                $availableSlots = $this->availabilityService->getAvailableSlots(
                    $trainer, 
                    $request->start_date, 
                    $request->end_date,
                    $slotDuration
                );
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'available_slots' => $availableSlots,
                    'source' => $source,
                    'google_calendar_connected' => $connectionStatus['connected'],
                    'trainer_info' => [
                        'id' => $trainer->id,
                        'name' => $trainer->name,
                        'email' => $trainer->email,
                        'has_availability' => true,
                        'allows_self_booking' => $bookingSettings ? $bookingSettings->allow_self_booking : true,
                        'requires_approval' => $bookingSettings ? $bookingSettings->require_approval : false,
                        'allow_weekend_booking' => $bookingSettings ? $bookingSettings->allow_weekend_booking : true
                    ],
                    'settings' => [
                        'slot_duration' => $slotDuration,
                        'session_capacity' => $trainer->sessionCapacity ? [
                            'max_daily_sessions' => $trainer->sessionCapacity->max_daily_sessions,
                            'max_weekly_sessions' => $trainer->sessionCapacity->max_weekly_sessions,
                            'break_between_sessions' => $trainer->sessionCapacity->break_between_sessions_minutes,
                            'session_duration_minutes' => $trainer->sessionCapacity->session_duration_minutes
                        ] : null,
                        'booking_restrictions' => $bookingSettings ? [
                            'advance_booking_days' => $bookingSettings->advance_booking_days,
                            'cancellation_hours' => $bookingSettings->cancellation_hours,
                            'earliest_booking_time' => $bookingSettings->earliest_booking_time,
                            'latest_booking_time' => $bookingSettings->latest_booking_time
                        ] : null
                    ]
                ],
                'message' => 'Available slots retrieved successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Error getting available slots', [
                'trainer_id' => $request->trainer_id ?? null,
                'start_date' => $request->start_date ?? null,
                'end_date' => $request->end_date ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving available slots: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store Google Calendar booking with comprehensive validation
     * 
     * This method mirrors the admin panel storeGoogleCalendarBooking functionality
     * but provides JSON API responses suitable for mobile/API clients
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function storeGoogleCalendarBooking(Request $request): JsonResponse
    {
        // Debug logging - comprehensive request tracking
        Log::info('Google Calendar booking API request submitted', [
            'all_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->url(),
            'user_id' => Auth::id(),
            'user_role' => Auth::user()->role ?? 'unknown',
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Comprehensive validation with detailed error messages
            $validator = Validator::make($request->all(), [
                'trainer_id' => 'required|exists:users,id',
                'client_id' => 'required|exists:users,id',
                'booking_date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'session_type' => 'required|string|in:personal_training,consultation,assessment,follow_up',
                'notes' => 'nullable|string|max:500',
                'meeting_agenda' => 'nullable|string|max:255',
                'timezone' => 'nullable|string|in:' . implode(',', timezone_identifiers_list())
            ]);

            if ($validator->fails()) {
                Log::warning('Google Calendar booking validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all(),
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Role-based access control
            $currentUser = Auth::user();
            $allowedRoles = ['trainer', 'client', 'admin'];
            
            if (!in_array($currentUser->role, $allowedRoles)) {
                Log::warning('Unauthorized booking attempt', [
                    'user_id' => $currentUser->id,
                    'user_role' => $currentUser->role,
                    'request_data' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only trainers, clients, and admins can create bookings.'
                ], 403);
            }

            // Additional role-specific validation
            if ($currentUser->role === 'client' && $currentUser->id != $request->client_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clients can only create bookings for themselves.'
                ], 403);
            }

            if ($currentUser->role === 'trainer' && $currentUser->id != $request->trainer_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainers can only create bookings for themselves.'
                ], 403);
            }

            $trainer = User::findOrFail($request->trainer_id);
            $client = User::findOrFail($request->client_id);

            // Verify user roles
            if ($trainer->role !== 'trainer') {
                Log::error('Invalid trainer role', [
                    'trainer_id' => $trainer->id,
                    'actual_role' => $trainer->role,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not a trainer'
                ], 400);
            }

            if ($client->role !== 'client') {
                Log::error('Invalid client role', [
                    'client_id' => $client->id,
                    'actual_role' => $client->role,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not a client'
                ], 400);
            }

            // Check if trainer has Google Calendar connected
            $googleController = new \App\Http\Controllers\GoogleController();
            $connectionStatus = $googleController->getTrainerConnectionStatus($trainer);

            // if (!$connectionStatus['connected']) {
            //     Log::warning('Trainer Google Calendar not connected', [
            //         'trainer_id' => $trainer->id,
            //         'trainer_name' => $trainer->name,
            //         'user_id' => Auth::id()
            //     ]);

            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Trainer does not have Google Calendar connected'
            //     ], 400);
            // }

            // Comprehensive meeting conflict checking
            Log::info('Starting conflict checking', [
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
                'date' => $request->booking_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'user_id' => Auth::id()
            ]);

            // Check for trainer conflicts - overlapping bookings for the trainer
            $trainerConflicts = Schedule::where('trainer_id', $trainer->id)
                ->where('date', $request->booking_date)
                ->where('status', '!=', Schedule::STATUS_CANCELLED)
                ->where(function ($query) use ($request) {
                    // Check for any time overlap scenarios:
                    // 1. New booking starts during existing booking
                    // 2. New booking ends during existing booking  
                    // 3. New booking completely contains existing booking
                    // 4. Existing booking completely contains new booking
                    $query->where(function ($q) use ($request) {
                        // New start time falls within existing booking
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>', $request->start_time);
                    })->orWhere(function ($q) use ($request) {
                        // New end time falls within existing booking
                        $q->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>=', $request->end_time);
                    })->orWhere(function ($q) use ($request) {
                        // New booking completely contains existing booking
                        $q->where('start_time', '>=', $request->start_time)
                          ->where('end_time', '<=', $request->end_time);
                    })->orWhere(function ($q) use ($request) {
                        // Existing booking completely contains new booking
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>=', $request->end_time);
                    });
                })
                ->with(['client:id,name,email'])
                ->get();

            if ($trainerConflicts->isNotEmpty()) {
                $conflictDetails = $trainerConflicts->map(function ($conflict) {
                    return [
                        'id' => $conflict->id,
                        'client_name' => $conflict->client->name,
                        'client_email' => $conflict->client->email,
                        'start_time' => $conflict->start_time->format('H:i'),
                        'end_time' => $conflict->end_time->format('H:i'),
                        'status' => $conflict->status,
                        'session_type' => $conflict->session_type
                    ];
                });

                Log::warning('Trainer schedule conflict detected', [
                    'trainer_id' => $trainer->id,
                    'trainer_name' => $trainer->name,
                    'requested_slot' => [
                        'date' => $request->booking_date,
                        'start_time' => $request->start_time,
                        'end_time' => $request->end_time
                    ],
                    'conflicts' => $conflictDetails,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Trainer is not available during the requested time slot',
                    'error_type' => 'trainer_conflict',
                    'conflicts' => $conflictDetails,
                    'details' => 'The trainer has ' . $trainerConflicts->count() . ' conflicting booking(s) during this time'
                ], 409);
            }

            // Check for client conflicts - overlapping bookings for the client
            $clientConflicts = Schedule::where('client_id', $client->id)
                ->where('date', $request->booking_date)
                ->where('status', '!=', Schedule::STATUS_CANCELLED)
                ->where(function ($query) use ($request) {
                    // Same overlap logic as trainer conflicts
                    $query->where(function ($q) use ($request) {
                        // New start time falls within existing booking
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>', $request->start_time);
                    })->orWhere(function ($q) use ($request) {
                        // New end time falls within existing booking
                        $q->where('start_time', '<', $request->end_time)
                          ->where('end_time', '>=', $request->end_time);
                    })->orWhere(function ($q) use ($request) {
                        // New booking completely contains existing booking
                        $q->where('start_time', '>=', $request->start_time)
                          ->where('end_time', '<=', $request->end_time);
                    })->orWhere(function ($q) use ($request) {
                        // Existing booking completely contains new booking
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>=', $request->end_time);
                    });
                })
                ->with(['trainer:id,name,email'])
                ->get();

            if ($clientConflicts->isNotEmpty()) {
                $conflictDetails = $clientConflicts->map(function ($conflict) {
                    return [
                        'id' => $conflict->id,
                        'trainer_name' => $conflict->trainer->name,
                        'trainer_email' => $conflict->trainer->email,
                        'start_time' => $conflict->start_time->format('H:i'),
                        'end_time' => $conflict->end_time->format('H:i'),
                        'status' => $conflict->status,
                        'session_type' => $conflict->session_type
                    ];
                });

                Log::warning('Client schedule conflict detected', [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'requested_slot' => [
                        'date' => $request->booking_date,
                        'start_time' => $request->start_time,
                        'end_time' => $request->end_time
                    ],
                    'conflicts' => $conflictDetails,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Client is not available during the requested time slot',
                    'error_type' => 'client_conflict',
                    'conflicts' => $conflictDetails,
                    'details' => 'The client has ' . $clientConflicts->count() . ' conflicting booking(s) during this time'
                ], 409);
            }

            // Use AvailabilityService for additional availability checking (trainer working hours, blocked times, etc.)
            $availabilityService = new AvailabilityService();
            $availabilityCheck = $availabilityService->checkAvailability(
                $trainer,
                $request->booking_date,
                $request->start_time,
                $request->end_time
            );

            // Log availability check results
            Log::info('Availability service check completed', [
                'trainer_id' => $trainer->id,
                'available' => $availabilityCheck['available'],
                'reasons' => $availabilityCheck['reasons'] ?? [],
                'user_id' => Auth::id()
            ]);

            // Optional: Uncomment to enforce strict availability checking
            // if (!$availabilityCheck['available']) {
            //     $errorMessage = implode(' ', $availabilityCheck['reasons']);
            //     
            //     Log::warning('Booking availability check failed', [
            //         'trainer_id' => $trainer->id,
            //         'date' => $request->booking_date,
            //         'start_time' => $request->start_time,
            //         'end_time' => $request->end_time,
            //         'reasons' => $availabilityCheck['reasons'],
            //         'user_id' => Auth::id()
            //     ]);
            //     
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Booking slot not available',
            //         'error_type' => 'availability_restriction',
            //         'details' => $errorMessage,
            //         'availability_check' => $availabilityCheck
            //     ], 400);
            // }

            Log::info('All conflict checks passed successfully', [
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
                'date' => $request->booking_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'user_id' => Auth::id()
            ]);

            // Check booking approval settings
            $bookingSettings = BookingSetting::where('trainer_id', $trainer->id)->first();
            $initialStatus = 'pending'; // Default for API bookings
            
            // if ($bookingSettings && $bookingSettings->require_approval) {
            //     $initialStatus = 'pending';
            // }

            // Create the booking with comprehensive logging
            Log::info('Creating schedule with data:', [
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
                'date' => $request->booking_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $initialStatus,
                'notes' => $request->notes,
                'session_type' => $request->session_type,
                'meeting_agenda' => $request->meeting_agenda,
                'timezone' => $request->timezone ?? 'UTC',
                'user_id' => Auth::id()
            ]);

            $schedule = Schedule::create([
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
                'date' => $request->booking_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $initialStatus,
                'notes' => $request->notes,
                'session_type' => $request->session_type,
                'meeting_agenda' => $request->meeting_agenda,
                'timezone' => $request->timezone ?? 'UTC'
            ]);

            Log::info('Schedule created successfully', [
                'schedule_id' => $schedule->id,
                'status' => $schedule->status,
                'user_id' => Auth::id()
            ]);

            // Load relationships for response
            $schedule->load(['trainer:id,name,email,phone,google_token', 'client:id,name,email,phone']);

            // Create Google Calendar event only if booking is confirmed
            $googleMessage = '';
            $googleEventResult = null;
            
            if ($initialStatus === 'confirmed') {
                try {
                    $googleCalendarService = new \App\Services\GoogleCalendarService();
                    $googleEventResult = $googleCalendarService->createEvent($schedule);
                    
                    if ($googleEventResult && $googleEventResult['success']) {
                        // Update booking with Google Calendar details
                        $schedule->update([
                            'google_event_id' => $googleEventResult['event_id'],
                            'meet_link' => $googleEventResult['meet_link'] ?? null,
                            'google_calendar_sync_status' => 'synced',
                            'google_calendar_last_synced_at' => now()
                        ]);
                        
                        Log::info('Google Calendar event created successfully', [
                            'schedule_id' => $schedule->id,
                            'google_event_id' => $googleEventResult['event_id'],
                            'meet_link' => $googleEventResult['meet_link'] ?? null,
                            'user_id' => Auth::id()
                        ]);
                        
                        $googleMessage = ' with Google Calendar event and Meet link';
                    } else {
                        Log::warning('Google Calendar event creation returned false', [
                            'schedule_id' => $schedule->id,
                            'result' => $googleEventResult,
                            'user_id' => Auth::id()
                        ]);
                        $googleMessage = ' (Google Calendar event could not be created)';
                    }
                } catch (\Exception $e) {
                    // If Google Calendar event creation fails, still keep the booking but notify
                    Log::error('Failed to create Google Calendar event for booking ' . $schedule->id, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'schedule_id' => $schedule->id,
                        'user_id' => Auth::id()
                    ]);
                    $googleMessage = ' (Google Calendar event could not be created: ' . $e->getMessage() . ')';
                }
            }

            // Prepare comprehensive response data
            $responseData = [
                'id' => $schedule->id,
                'trainer' => [
                    'id' => $schedule->trainer->id,
                    'name' => $schedule->trainer->name,
                    'email' => $schedule->trainer->email,
                    'phone' => $schedule->trainer->phone
                ],
                'client' => [
                    'id' => $schedule->client->id,
                    'name' => $schedule->client->name,
                    'email' => $schedule->client->email,
                    'phone' => $schedule->client->phone
                ],
                'date' => $schedule->date->format('Y-m-d'),
                'start_time' => $schedule->start_time->format('H:i'),
                'end_time' => $schedule->end_time->format('H:i'),
                'status' => $schedule->status,
                'session_type' => $schedule->session_type,
                'notes' => $schedule->notes,
                'meeting_agenda' => $schedule->meeting_agenda,
                'timezone' => $schedule->timezone,
                'google_event_id' => $schedule->google_event_id,
                'meet_link' => $schedule->meet_link,
                'google_calendar_sync_status' => $schedule->google_calendar_sync_status,
                'google_calendar_last_synced_at' => $schedule->google_calendar_last_synced_at?->toISOString(),
                'google_event_created' => $googleEventResult && $googleEventResult['success'],
                'created_at' => $schedule->created_at->toISOString(),
                'updated_at' => $schedule->updated_at->toISOString(),
                'booking_settings' => $bookingSettings ? [
                    'require_approval' => $bookingSettings->require_approval,
                    'advance_booking_days' => $bookingSettings->advance_booking_days,
                    'cancellation_hours' => $bookingSettings->cancellation_hours
                ] : null
            ];

            $message = $initialStatus === 'confirmed' 
                ? 'Booking created successfully' . $googleMessage
                : 'Booking created and is pending trainer approval';

            Log::info('Google Calendar booking created successfully', [
                'schedule_id' => $schedule->id,
                'status' => $schedule->status,
                'google_event_created' => $googleEventResult && $googleEventResult['success'],
                'message' => $message,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => $message
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating Google Calendar booking via API', [
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating booking: ' . $e->getMessage(),
                'error_details' => [
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Legacy method - kept for backward compatibility
     * Redirects to the new storeGoogleCalendarBooking method
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createBooking(Request $request): JsonResponse
    {
        Log::info('Legacy createBooking method called, redirecting to storeGoogleCalendarBooking', [
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);
        
        return $this->storeGoogleCalendarBooking($request);
    }

    /**
     * Get list of trainers with their Google Calendar connection status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTrainers(Request $request): JsonResponse
    {
        try {
            $trainers = User::where('role', 'trainer')
                ->select('id', 'name', 'email', 'phone', 'google_token')
                ->get();

            $trainersWithStatus = $trainers->map(function ($trainer) {
                $connectionStatus = $this->googleController->getTrainerConnectionStatus($trainer);
                
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email,
                    'phone' => $trainer->phone,
                    'google_calendar_connected' => $connectionStatus['connected'],
                    'google_email' => $connectionStatus['email'] ?? null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'trainers' => $trainersWithStatus,
                    'total_count' => $trainersWithStatus->count(),
                    'connected_count' => $trainersWithStatus->where('google_calendar_connected', true)->count()
                ],
                'message' => 'Trainers list retrieved successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Error getting trainers list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving trainers list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of clients for booking
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getClients(Request $request): JsonResponse
    {
        try {
            $clients = User::where('role', 'client')
                ->select('id', 'name', 'email', 'phone')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'clients' => $clients,
                    'total_count' => $clients->count()
                ],
                'message' => 'Clients list retrieved successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Error getting clients list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving clients list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available timezones
     * 
     * @return JsonResponse
     */
    public function getTimezones(): JsonResponse
    {
        try {
            $timezones = timezone_identifiers_list();
            
            // Group timezones by region for better UX
            $groupedTimezones = [];
            foreach ($timezones as $timezone) {
                $parts = explode('/', $timezone);
                $region = $parts[0];
                $city = isset($parts[1]) ? str_replace('_', ' ', $parts[1]) : $timezone;
                
                if (!isset($groupedTimezones[$region])) {
                    $groupedTimezones[$region] = [];
                }
                
                $groupedTimezones[$region][] = [
                    'value' => $timezone,
                    'label' => $city,
                    'full_name' => $timezone
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'timezones' => $timezones,
                    'grouped_timezones' => $groupedTimezones,
                    'default_timezone' => config('app.timezone', 'UTC')
                ],
                'message' => 'Timezones retrieved successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Error getting timezones', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving timezones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get session types available for booking
     * 
     * @return JsonResponse
     */
    public function getSessionTypes(): JsonResponse
    {
        try {
            $sessionTypes = [
                [
                    'value' => 'personal_training',
                    'label' => 'Personal Training',
                    'description' => 'One-on-one fitness training session'
                ],
                [
                    'value' => 'consultation',
                    'label' => 'Consultation',
                    'description' => 'Initial consultation and assessment'
                ],
                [
                    'value' => 'assessment',
                    'label' => 'Assessment',
                    'description' => 'Fitness assessment and evaluation'
                ],
                [
                    'value' => 'follow_up',
                    'label' => 'Follow-up',
                    'description' => 'Follow-up session and progress review'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'session_types' => $sessionTypes
                ],
                'message' => 'Session types retrieved successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Error getting session types', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving session types: ' . $e->getMessage()
            ], 500);
        }
    }
}