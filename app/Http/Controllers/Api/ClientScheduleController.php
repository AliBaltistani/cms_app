<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Schedule;
use App\Models\WorkoutAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Client Schedule API Controller
 * 
 * Handles client schedule operations for retrieving workouts and sessions by date
 * Provides comprehensive schedule information including assigned workouts and booked sessions
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\API\Client
 * @category    Client Schedule Management API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ClientScheduleController extends ApiBaseController
{
    /**
     * Get client schedule for a specific date
     * 
     * Returns both scheduled sessions and assigned workouts for the given date
     * If no date provided, returns current date schedule
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScheduleByDate(Request $request): JsonResponse
    {
        try {
            // Get authenticated client
            $clientId = Auth::id();
            
            // Validate date parameter
            $request->validate([
                'date' => 'nullable|date|date_format:Y-m-d'
            ]);
            
            // Get date from request or use current date
            $selectedDate = $request->get('date', Carbon::now()->format('Y-m-d'));
            
            // Parse the date to ensure it's valid
            $date = Carbon::parse($selectedDate);
            
            Log::info('Client requesting schedule for date', [
                'client_id' => $clientId,
                'requested_date' => $selectedDate,
                'parsed_date' => $date->format('Y-m-d')
            ]);
            
            // Get scheduled sessions for the date
            $scheduledSessions = Schedule::forClient($clientId)
                ->where('date', $date->format('Y-m-d'))
                ->whereIn('status', [Schedule::STATUS_PENDING, Schedule::STATUS_CONFIRMED])
                ->with([
                    'trainer:id,name,email,phone,profile_image',
                    'trainer.specializations:id,name'
                ])
                ->orderBy('start_time')
                ->get();
            
            // Get assigned workouts due on this date
            $assignedWorkouts = WorkoutAssignment::forClients()
                ->where('assigned_to', $clientId)
                ->whereDate('due_date', $date->format('Y-m-d'))
                ->whereIn('status', ['assigned', 'in_progress'])
                ->with([
                    'workout:id,name,description,duration,thumbnail,price',
                    'workout.videos' => function ($query) {
                        $query->select('id', 'workout_id', 'title', 'duration', 'thumbnail', 'order')
                              ->orderBy('order');
                    },
                    'assignedBy:id,name,email,profile_image'
                ])
                ->orderBy('assigned_at')
                ->get();
            
            // Transform scheduled sessions data
            $transformedSessions = $scheduledSessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'type' => 'session',
                    'title' => 'Training Session',
                    'workout_name' => 'Training Session', // Session name as workout_name
                    'workout_thumbnail' => null, // Sessions don't have thumbnails
                    'session_start_time' => $session->start_time->format('H:i'),
                    'session_end_time' => $session->end_time->format('H:i'),
                    'trainer' => [
                        'id' => $session->trainer->id,
                        'name' => $session->trainer->name,
                        'email' => $session->trainer->email,
                        'phone' => $session->trainer->phone ?? null,
                        'profile_image' => $session->trainer->profile_image,
                        'specializations' => $session->trainer->specializations->map(function ($spec) {
                            return [
                                'id' => $spec->id,
                                'name' => $spec->name
                            ];
                        })
                    ],
                    'date' => $session->date,
                    'start_time' => $session->start_time->format('H:i'),
                    'end_time' => $session->end_time->format('H:i'),
                    'duration_minutes' => $session->getDurationInMinutes(),
                    'status' => $session->status,
                    'status_label' => ucfirst($session->status),
                    'notes' => $session->notes,
                    'can_cancel' => $session->canBeCancelled(),
                    'is_confirmed' => $session->isConfirmed(),
                    'is_pending' => $session->isPending()
                ];
            });
            
            // Transform assigned workouts data
            $transformedWorkouts = $assignedWorkouts->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'type' => 'workout',
                    'workout_name' => $assignment->workout->name,
                    'workout_thumbnail' => $assignment->workout->thumbnail,
                    'session_start_time' => null, // Workouts don't have specific session times
                    'session_end_time' => null, // Workouts don't have specific session times
                    'workout' => [
                        'id' => $assignment->workout->id,
                        'name' => $assignment->workout->name,
                        'description' => $assignment->workout->description,
                        'duration' => $assignment->workout->duration,
                        'thumbnail' => $assignment->workout->thumbnail,
                        'price' => $assignment->workout->price,
                        'video_count' => $assignment->workout->videos->count(),
                        'total_duration' => $assignment->workout->videos->sum('duration')
                    ],
                    'assigned_by' => [
                        'id' => $assignment->assignedBy->id,
                        'name' => $assignment->assignedBy->name,
                        'email' => $assignment->assignedBy->email,
                        'profile_image' => $assignment->assignedBy->profile_image
                    ],
                    'assigned_at' => $assignment->assigned_at->format('Y-m-d H:i:s'),
                    'due_date' => $assignment->due_date->format('Y-m-d'),
                    'status' => $assignment->status,
                    'status_label' => ucfirst(str_replace('_', ' ', $assignment->status)),
                    'progress' => $assignment->progress ?? 0,
                    'notes' => $assignment->notes,
                    'is_overdue' => $assignment->isOverdue(),
                    'completed_at' => $assignment->completed_at?->format('Y-m-d H:i:s')
                ];
            });
            
            // Combine and sort all schedule items by time
            $allScheduleItems = collect()
                ->merge($transformedSessions)
                ->merge($transformedWorkouts)
                ->sortBy(function ($item) {
                    // For sessions, sort by start_time
                    if ($item['type'] === 'session') {
                        return $item['start_time'];
                    }
                    // For workouts, sort by assigned_at time
                    return Carbon::parse($item['assigned_at'])->format('H:i');
                })
                ->values();
            
            // Prepare response data
            $responseData = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'formatted_date' => $date->format('d M, Y'),
                'is_today' => $date->isToday(),
                'is_past' => $date->isPast(),
                'is_future' => $date->isFuture(),
                'total_items' => $allScheduleItems->count(),
                'sessions_count' => $transformedSessions->count(),
                'workouts_count' => $transformedWorkouts->count(),
                'schedule_items' => $allScheduleItems,
                'summary' => [
                    'has_sessions' => $transformedSessions->isNotEmpty(),
                    'has_workouts' => $transformedWorkouts->isNotEmpty(),
                    'pending_sessions' => $transformedSessions->where('status', Schedule::STATUS_PENDING)->count(),
                    'confirmed_sessions' => $transformedSessions->where('status', Schedule::STATUS_CONFIRMED)->count(),
                    'overdue_workouts' => $transformedWorkouts->where('is_overdue', true)->count(),
                    'in_progress_workouts' => $transformedWorkouts->where('status', 'in_progress')->count()
                ]
            ];
            
            Log::info('Client schedule retrieved successfully', [
                'client_id' => $clientId,
                'date' => $selectedDate,
                'sessions_count' => $transformedSessions->count(),
                'workouts_count' => $transformedWorkouts->count(),
                'total_items' => $allScheduleItems->count()
            ]);
            
            return $this->sendResponse($responseData, 'Schedule retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve client schedule: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'requested_date' => $request->get('date'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError(
                'Failed to retrieve schedule',
                ['error' => 'Unable to fetch schedule data'],
                500
            );
        }
    }
    
    /**
     * Get client schedule for a date range
     * 
     * Returns schedule items for multiple dates
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScheduleRange(Request $request): JsonResponse
    {
        try {
            // Get authenticated client
            $clientId = Auth::id();
            
            // Validate date range parameters
            $request->validate([
                'start_date' => 'required|date|date_format:Y-m-d',
                'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date'
            ]);
            
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            
            // Limit range to maximum 30 days
            if ($startDate->diffInDays($endDate) > 30) {
                return $this->sendError('Date range cannot exceed 30 days');
            }
            
            Log::info('Client requesting schedule range', [
                'client_id' => $clientId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days_count' => $startDate->diffInDays($endDate) + 1
            ]);
            
            // Get scheduled sessions for the date range
            $scheduledSessions = Schedule::forClient($clientId)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->whereIn('status', [Schedule::STATUS_PENDING, Schedule::STATUS_CONFIRMED])
                ->with(['trainer:id,name,email,profile_image'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get()
                ->groupBy('date');
            
            // Get assigned workouts for the date range
            $assignedWorkouts = WorkoutAssignment::forClients()
                ->where('assigned_to', $clientId)
                ->whereBetween('due_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->whereIn('status', ['assigned', 'in_progress'])
                ->with(['workout:id,name,duration,thumbnail', 'assignedBy:id,name'])
                ->orderBy('due_date')
                ->get()
                ->groupBy(function ($assignment) {
                    return $assignment->due_date->format('Y-m-d');
                });
            
            // Build response for each date in range
            $scheduleByDate = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                $dateString = $currentDate->format('Y-m-d');
                $daySessions = $scheduledSessions->get($dateString, collect());
                $dayWorkouts = $assignedWorkouts->get($dateString, collect());
                
                $scheduleByDate[] = [
                    'date' => $dateString,
                    'day_name' => $currentDate->format('l'),
                    'formatted_date' => $currentDate->format('d M'),
                    'is_today' => $currentDate->isToday(),
                    'sessions_count' => $daySessions->count(),
                    'workouts_count' => $dayWorkouts->count(),
                    'total_items' => $daySessions->count() + $dayWorkouts->count(),
                    'has_items' => $daySessions->isNotEmpty() || $dayWorkouts->isNotEmpty()
                ];
                
                $currentDate->addDay();
            }
            
            $responseData = [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days_count' => $startDate->diffInDays($endDate) + 1,
                'schedule_by_date' => $scheduleByDate,
                'summary' => [
                    'total_sessions' => $scheduledSessions->flatten()->count(),
                    'total_workouts' => $assignedWorkouts->flatten()->count(),
                    'days_with_items' => collect($scheduleByDate)->where('has_items', true)->count()
                ]
            ];
            
            return $this->sendResponse($responseData, 'Schedule range retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve client schedule range: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return $this->sendError(
                'Failed to retrieve schedule range',
                ['error' => 'Unable to fetch schedule data'],
                500
            );
        }
    }
}