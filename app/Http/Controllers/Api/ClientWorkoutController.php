<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Models\WorkoutVideo;
use App\Models\WorkoutAssignment;
use App\Models\WorkoutVideoProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Client Workout API Controller
 * 
 * Handles read-only workout operations for clients via API
 * Clients can only view active workouts and their videos
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\API
 * @category    Client Workout Management API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ClientWorkoutController extends Controller
{
    /**
     * Get all active workouts with optional filtering and pagination
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Only show active workouts to clients - include videos by default
            $query = Workout::where('is_active', true)
                           ->with([
                               'user:id,name,email,profile_image',
                               'videos' => function ($q) {
                                   $q->orderBy('order');
                               }
                           ]);
            
            // Apply search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Filter by trainer
            if ($request->filled('trainer_id')) {
                $query->where('user_id', $request->trainer_id);
            }
            
            // Filter by duration range
            if ($request->filled('duration_min')) {
                $query->where('duration', '>=', $request->duration_min);
            }
            
            if ($request->filled('duration_max')) {
                $query->where('duration', '<=', $request->duration_max);
            }
            
            // Option to exclude videos if specifically requested (opposite of before)
            if ($request->boolean('exclude_videos')) {
                $query = Workout::where('is_active', true)
                               ->with(['user:id,name,email,profile_image']);
                
                // Re-apply filters if videos are excluded
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                    });
                }
                
                if ($request->filled('trainer_id')) {
                    $query->where('user_id', $request->trainer_id);
                }
                
                if ($request->filled('duration_min')) {
                    $query->where('duration', '>=', $request->duration_min);
                }
                
                if ($request->filled('duration_max')) {
                    $query->where('duration', '<=', $request->duration_max);
                }
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            // Validate sort fields to prevent SQL injection
            $allowedSortFields = ['name', 'duration', 'created_at', 'updated_at'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            
            $query->orderBy($sortBy, $sortDirection);
            
            // Paginate results
            $perPage = (int) $request->input('per_page', 15);
            $perPage = min($perPage, 50); // Limit max per page for performance
            $workouts = $query->paginate($perPage);
            
            $responseData = [
                'data' => $workouts->items(),
                'pagination' => [
                    'total' => $workouts->total(),
                    'per_page' => $workouts->perPage(),
                    'current_page' => $workouts->currentPage(),
                    'last_page' => $workouts->lastPage(),
                    'from' => $workouts->firstItem(),
                    'to' => $workouts->lastItem(),
                    'has_more_pages' => $workouts->hasMorePages()
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Workouts retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workouts for client: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Retrieval Failed',
                'data' => ['error' => 'Unable to retrieve workouts: ' . $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Show a specific active workout
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $workout = Workout::where('is_active', true)
                             ->where('id', $id)
                             ->with([
                                 'user:id,name,email,profile_image',
                                 'videos' => function ($q) {
                                     $q->orderBy('order');
                                 }
                             ])
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or not available'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $workout,
                'message' => 'Workout retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout for client: ' . $e->getMessage(), [
                'workout_id' => $id,
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Retrieval Failed',
                'data' => ['error' => 'Unable to retrieve workout']
            ], 500);
        }
    }
    
    /**
     * Search workouts with advanced filtering
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2|max:255',
                'trainer_id' => 'nullable|integer|exists:users,id',
                'duration_min' => 'nullable|integer|min:1',
                'duration_max' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            $query = Workout::where('is_active', true)
                           ->with([
                               'user:id,name,email,profile_image',
                               'videos' => function ($q) {
                                   $q->orderBy('order');
                               }
                           ]);
            
            // Apply search query
            $searchTerm = $request->query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
            
            // Apply additional filters
            if ($request->filled('trainer_id')) {
                $query->where('user_id', $request->trainer_id);
            }
            
            if ($request->filled('duration_min')) {
                $query->where('duration', '>=', $request->duration_min);
            }
            
            if ($request->filled('duration_max')) {
                $query->where('duration', '<=', $request->duration_max);
            }
            
            // Order by relevance (name matches first, then description)
            $query->orderByRaw("CASE WHEN name LIKE '%{$searchTerm}%' THEN 1 ELSE 2 END")
                  ->orderBy('created_at', 'desc');
            
            // Paginate results
            $perPage = (int) $request->input('per_page', 15);
            $workouts = $query->paginate($perPage);
            
            $responseData = [
                'query' => $searchTerm,
                'data' => $workouts->items(),
                'pagination' => [
                    'total' => $workouts->total(),
                    'per_page' => $workouts->perPage(),
                    'current_page' => $workouts->currentPage(),
                    'last_page' => $workouts->lastPage(),
                    'from' => $workouts->firstItem(),
                    'to' => $workouts->lastItem(),
                    'has_more_pages' => $workouts->hasMorePages()
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Search completed successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Workout search failed for client: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'search_query' => $request->query,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Search Failed',
                'data' => ['error' => 'Unable to perform search']
            ], 500);
        }
    }
    
    /**
     * Get videos for a specific active workout
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workoutId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVideos(Request $request, int $workoutId): JsonResponse
    {
        try {
            $workout = Workout::where('is_active', true)
                             ->where('id', $workoutId)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or not available'
                ], 404);
            }
            
            $videos = $workout->videos()->orderBy('order')->get();
            
            return response()->json([
                'success' => true,
                'data' => $videos,
                'message' => 'Workout videos retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout videos for client: ' . $e->getMessage(), [
                'workout_id' => $workoutId,
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Retrieval Failed',
                'data' => ['error' => 'Unable to retrieve workout videos']
            ], 500);
        }
    }
    
    /**
     * Show a specific video from an active workout
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $workoutId
     * @param  string  $videoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showVideo(Request $request, string $workoutId, string $videoId): JsonResponse
    {
        try {
            // Convert string parameters to integers
            $workoutIdInt = (int) $workoutId;
            $videoIdInt = (int) $videoId;
            
            // Validate that the conversion was successful (non-zero for valid IDs)
            if ($workoutIdInt <= 0 || $videoIdInt <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid workout or video ID'
                ], 400);
            }
            
            $workout = Workout::where('is_active', true)
                             ->where('id', $workoutIdInt)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or not available'
                ], 404);
            }
            
            $video = $workout->videos()->where('id', $videoIdInt)->first();
            
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $video,
                'message' => 'Video retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout video for client: ' . $e->getMessage(), [
                'video_id' => $videoId,
                'workout_id' => $workoutId,
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Retrieval Failed',
                'data' => ['error' => 'Unable to retrieve video']
            ], 500);
        }
    }
    
    /**
     * Get workout statistics for clients
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_workouts' => Workout::where('is_active', true)->count(),
                'total_trainers' => Workout::where('is_active', true)
                                          ->distinct('user_id')
                                          ->count('user_id'),
                'total_videos' => WorkoutVideo::whereHas('workout', function ($q) {
                    $q->where('is_active', true);
                })->count(),
                'average_duration' => (int) Workout::where('is_active', true)->avg('duration'),
                'duration_ranges' => [
                    'short' => Workout::where('is_active', true)->where('duration', '<=', 30)->count(),
                    'medium' => Workout::where('is_active', true)->whereBetween('duration', [31, 60])->count(),
                    'long' => Workout::where('is_active', true)->where('duration', '>', 60)->count()
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout statistics for client: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Statistics Failed',
                'data' => ['error' => 'Unable to retrieve statistics']
            ], 500);
        }
    }
    
    /**
     * Get featured/popular workouts for clients
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFeatured(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 10);
            $limit = min($limit, 20); // Max 20 featured workouts
            
            // Get recently created active workouts as featured
            $featuredWorkouts = Workout::where('is_active', true)
                                      ->with([
                                          'user:id,name,email,profile_image',
                                          'videos' => function ($q) {
                                              $q->orderBy('order');
                                          }
                                      ])
                                      ->orderBy('created_at', 'desc')
                                      ->limit($limit)
                                      ->get();
            
            return response()->json([
                'success' => true,
                'data' => $featuredWorkouts,
                'message' => 'Featured workouts retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve featured workouts for client: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Featured Workouts Failed',
                'data' => ['error' => 'Unable to retrieve featured workouts']
            ], 500);
        }
    }
    
    /**
     * Get dashboard data for client including upcoming workouts
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            // Get upcoming/recent workouts (limit to 5 for dashboard)
            $upcomingWorkouts = Workout::where('is_active', true)
                                      ->with([
                                          'user:id,name,email,profile_image',
                                          'videos' => function ($q) {
                                              $q->orderBy('order')->limit(1); // Get first video for preview
                                          }
                                      ])
                                      ->orderBy('created_at', 'desc')
                                      ->limit(3) // Changed limit to 3 for latest upcoming workouts
                                      ->get()
                                      ->map(function ($workout) {
                                          return [
                                              'id' => $workout->id,
                                              'name' => $workout->name,
                                              'description' => $workout->description,
                                              'duration' => $workout->duration,
                                              'formatted_duration' => $workout->formatted_duration,
                                              'thumbnail' => $workout->thumbnail,
                                              'trainer' => $workout->user ? [
                                                  'id' => $workout->user->id,
                                                  'name' => $workout->user->name,
                                                  'email' => $workout->user->email,
                                                  'profile_image' => $workout->user->profile_image ?? null
                                              ] : [
                                                  'id' => null,
                                                  'name' => 'Unknown Trainer',
                                                  'email' => null,
                                                  'profile_image' => null
                                              ],
                                              'video_count' => $workout->videos->count(),
                                              'preview_video' => $workout->videos->first() ? [
                                                  'id' => $workout->videos->first()->id,
                                                  'title' => $workout->videos->first()->title,
                                                  'thumbnail' => $workout->videos->first()->thumbnail,
                                                  'video_url' => $workout->videos->first()->video_url,
                                                  'video_type' => $workout->videos->first()->video_type
                                              ] : null,
                                              'created_at' => $workout->created_at->toISOString()
                                          ];
                                      });
            
            // Get workout statistics for dashboard
            $stats = [
                'total_trainers' => Workout::where('is_active', true)
                                          ->distinct('user_id')
                                          ->count('user_id')
            ];
            
            
            
            // Re-index the array after filtering
            
            $dashboardData = [
                'upcoming_workouts' => $upcomingWorkouts,
                'statistics' => $stats,
                'messages' => [],
                
            ];
            
            return response()->json([
                'success' => true,
                'data' => $dashboardData,
                'message' => 'Dashboard data retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve dashboard data for client: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Dashboard Failed',
                'data' => ['error' => 'Unable to retrieve dashboard data: ' . $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Get assigned workouts for the authenticated client with comprehensive video details
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAssignedWorkouts(Request $request): JsonResponse
    {
        try {
            $clientId = Auth::id();
            
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:pending,in_progress,completed,overdue',
                'per_page' => 'nullable|integer|min:1|max:50',
                'sort_by' => 'nullable|in:due_date,assigned_at,status',
                'sort_direction' => 'nullable|in:asc,desc',
                'include_progress' => 'nullable|boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            $includeProgress = $request->boolean('include_progress', true);
            
            $query = WorkoutAssignment::where('assigned_to', $clientId)
                                    ->with([
                                        'workout' => function ($q) {
                                            $q->select('id', 'name', 'description', 'duration', 'thumbnail', 'user_id', 'price', 'is_active', 'created_at', 'updated_at')
                                              ->with('user:id,name,email,profile_image')
                                              ->with(['videos' => function ($vq) {
                                                  $vq->orderBy('order')
                                                    ->select('id', 'workout_id', 'title', 'description', 'video_url', 'video_type', 'thumbnail', 'duration', 'order', 'is_preview', 'metadata', 'created_at', 'updated_at');
                                              }]);
                                        },
                                        'assignedBy:id,name,email'
                                    ]);
            
            // Apply status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            // Apply sorting
            $sortBy = $request->input('sort_by', 'due_date');
            $sortDirection = $request->input('sort_direction', 'asc');
            
            $allowedSortFields = ['due_date', 'assigned_at', 'status'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'due_date';
            }
            
            $query->orderBy($sortBy, $sortDirection);
            
            // Paginate results
            $perPage = (int) $request->input('per_page', 15);
            $perPage = min($perPage, 50);
            $assignments = $query->paginate($perPage);
            
            // Get video progress data if requested
            $videoProgressData = [];
            if ($includeProgress && $assignments->count() > 0) {
                $workoutIds = $assignments->pluck('workout_id')->unique();
                $videoProgressData = WorkoutVideoProgress::where('user_id', $clientId)
                    ->whereIn('workout_id', $workoutIds)
                    ->get()
                    ->groupBy('workout_video_id');
            }
            
            // Transform the data to include comprehensive video information
            $transformedData = $assignments->getCollection()->map(function ($assignment) use ($videoProgressData, $includeProgress, $clientId) {
                $workout = $assignment->workout;
                if (!$workout) {
                    return null;
                }
                
                // Calculate overall workout progress
                $totalVideos = $workout->videos->count();
                $completedVideos = 0;
                $totalWatchedDuration = 0;
                $totalVideoDuration = 0;
                
                // Transform videos with comprehensive details
                $videosData = $workout->videos->map(function ($video) use ($videoProgressData, $includeProgress, $clientId, &$completedVideos, &$totalWatchedDuration, &$totalVideoDuration) {
                    $videoProgress = null;
                    $progressPercentage = 0;
                    $isCompleted = false;
                    $watchedDuration = 0;
                    
                    if ($includeProgress && isset($videoProgressData[$video->id])) {
                        $videoProgress = $videoProgressData[$video->id]->first();
                        $isCompleted = $videoProgress->is_completed ?? false;
                        $watchedDuration = $videoProgress->watched_duration ?? 0;
                        
                        if ($video->duration > 0) {
                            $progressPercentage = min(100, round(($watchedDuration / $video->duration) * 100, 2));
                        }
                        
                        if ($isCompleted) {
                            $completedVideos++;
                        }
                    }
                    
                    $totalWatchedDuration += $watchedDuration;
                    $totalVideoDuration += $video->duration ?? 0;
                    
                    return [
                        'id' => $video->id,
                        'title' => $video->title,
                        'description' => $video->description,
                        'duration' => $video->duration,
                        'formatted_duration' => $video->formatted_duration,
                        'thumbnail' => $video->thumbnail,
                        'thumbnail_url' => $video->thumbnail_url,
                        'video_url' => $video->video_url,
                        'video_file_url' => $video->video_file_url,
                        'embed_url' => $video->embed_url,
                        'video_type' => $video->video_type,
                        'order' => $video->order,
                        'is_preview' => $video->is_preview,
                        'metadata' => $video->metadata,
                        'is_youtube' => $video->isYouTube(),
                        'is_vimeo' => $video->isVimeo(),
                        'is_local_file' => $video->isLocalFile(),
                        'created_at' => $video->created_at,
                        'updated_at' => $video->updated_at,
                        'progress' => $includeProgress ? [
                            'is_completed' => $isCompleted,
                            'watched_duration' => $watchedDuration,
                            'progress_percentage' => $progressPercentage,
                            'first_watched_at' => $videoProgress->first_watched_at ?? null,
                            'last_watched_at' => $videoProgress->last_watched_at ?? null,
                            'completed_at' => $videoProgress->completed_at ?? null
                        ] : null
                    ];
                });
                
                // Calculate overall workout progress
                $overallProgress = 0;
                if ($totalVideos > 0) {
                    $overallProgress = round(($completedVideos / $totalVideos) * 100, 2);
                }
                
                // Alternative progress calculation based on watched duration
                $durationBasedProgress = 0;
                if ($totalVideoDuration > 0) {
                    $durationBasedProgress = round(($totalWatchedDuration / $totalVideoDuration) * 100, 2);
                }
                
                return [
                    'assignment_id' => $assignment->id,
                    'workout' => [
                        'id' => $workout->id,
                        'name' => $workout->name,
                        'description' => $workout->description,
                        'duration' => $workout->duration,
                        'formatted_duration' => $workout->formatted_duration,
                        'thumbnail' => $workout->thumbnail,
                        'price' => $workout->price,
                        'formatted_price' => $workout->formatted_price,
                        'is_active' => $workout->is_active,
                        'total_videos' => $workout->total_videos,
                        'total_duration_seconds' => $workout->total_duration_seconds,
                        'created_at' => $workout->created_at,
                        'updated_at' => $workout->updated_at,
                        'trainer' => $workout->user ? [
                            'id' => $workout->user->id,
                            'name' => $workout->user->name,
                            'email' => $workout->user->email,
                            'profile_image' => $workout->user->profile_image
                        ] : null,
                        'video_count' => $totalVideos,
                        'videos' => $videosData
                    ],
                    'assignment_details' => [
                        'status' => $assignment->status,
                        'due_date' => $assignment->due_date,
                        'assigned_at' => $assignment->assigned_at,
                        'completed_at' => $assignment->completed_at,
                        'notes' => $assignment->notes,
                        'assigned_by' => $assignment->assignedBy ? [
                            'id' => $assignment->assignedBy->id,
                            'name' => $assignment->assignedBy->name,
                            'email' => $assignment->assignedBy->email
                        ] : null
                    ],
                    'progress' => [
                        'overall_progress' => $assignment->progress ?? $overallProgress,
                        'completion_based_progress' => $overallProgress,
                        'duration_based_progress' => $durationBasedProgress,
                        'completed_videos' => $completedVideos,
                        'total_videos' => $totalVideos,
                        'total_watched_duration' => $totalWatchedDuration,
                        'total_video_duration' => $totalVideoDuration,
                        'is_overdue' => $assignment->due_date && now()->gt($assignment->due_date) && $assignment->status !== 'completed'
                    ]
                ];
            })->filter(); // Remove null entries
            
            $responseData = [
                'data' => $transformedData->values(),
                'pagination' => [
                    'total' => $assignments->total(),
                    'per_page' => $assignments->perPage(),
                    'current_page' => $assignments->currentPage(),
                    'last_page' => $assignments->lastPage(),
                    'from' => $assignments->firstItem(),
                    'to' => $assignments->lastItem(),
                    'has_more_pages' => $assignments->hasMorePages()
                ],
                'meta' => [
                    'include_progress' => $includeProgress,
                    'client_id' => $clientId,
                    'total_assignments' => $assignments->total()
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Assigned workouts with comprehensive video details retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve assigned workouts for client: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Retrieval Failed',
                'data' => ['error' => 'Unable to retrieve assigned workouts with video details']
            ], 500);
        }
    }
    
    /**
     * Update workout progress for the authenticated client
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id The workout ID from the route parameter
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProgress(Request $request, int $id): JsonResponse
    {
        try {
            $clientId = Auth::id();
            
            $validator = Validator::make($request->all(), [
                'progress' => 'required|numeric|min:0|max:100',
                'status' => 'nullable|in:pending,in_progress,completed',
                'notes' => 'nullable|string|max:1000'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            // Find the assignment for this client using workout_id and assigned_to
            $assignment = WorkoutAssignment::where('workout_id', $id)
                                         ->where('assigned_to', $clientId)
                                         ->first();
            
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found or access denied'
                ], 404);
            }
            
            // Update progress
            $assignment->progress = $request->progress;
            
            // Auto-update status based on progress
            if ($request->progress >= 100) {
                $assignment->status = 'completed';
                $assignment->completed_at = now();
            } elseif ($request->progress > 0) {
                $assignment->status = 'in_progress';
            }
            
            // Allow manual status override if provided
            if ($request->filled('status')) {
                $assignment->status = $request->status;
                if ($request->status === 'completed') {
                    $assignment->completed_at = now();
                }
            }
            
            // Update notes if provided
            if ($request->filled('notes')) {
                $assignment->notes = $request->notes;
            }
            
            $assignment->save();
            
            // Log the progress update
            Log::info('Workout progress updated by client', [
                'workout_id' => $id,
                'assignment_id' => $assignment->id,
                'client_id' => $clientId,
                'progress' => $request->progress,
                'status' => $assignment->status
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'assignment_id' => $assignment->id,
                    'progress' => $assignment->progress,
                    'status' => $assignment->status,
                    'completed_at' => $assignment->completed_at,
                    'updated_at' => $assignment->updated_at
                ],
                'message' => 'Progress updated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update workout progress: ' . $e->getMessage(), [
                'workout_id' => $id,
                'client_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Update Failed',
                'data' => ['error' => 'Unable to update progress'.$e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Update video progress for a specific workout video
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $videoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVideoProgress(Request $request, int $videoId): JsonResponse
    {
        try {
            $clientId = Auth::id();
            
            $validator = Validator::make($request->all(), [
                'watched_duration' => 'required|numeric|min:0',
                'workout_id' => 'required|integer|exists:workouts,id',
                'is_completed' => 'nullable|boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            // Verify the video exists and is accessible
            $video = WorkoutVideo::find($videoId);
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            
            // Calculate if completed based on watched duration (90% threshold)
            $isCompleted = $request->input('is_completed', false);
            if (!$isCompleted && $video->duration > 0) {
                $progressPercentage = ($request->watched_duration / $video->duration) * 100;
                $isCompleted = $progressPercentage >= 90;
            }
            
            // Update or create video progress record
            $videoProgress = WorkoutVideoProgress::updateOrCreate(
                [
                    'user_id' => $clientId,
                    'workout_id' => $request->workout_id,
                    'workout_video_id' => $videoId
                ],
                [
                    'watched_duration' => $request->watched_duration,
                    'is_completed' => $isCompleted,
                    'last_watched_at' => now(),
                    'first_watched_at' => WorkoutVideoProgress::where('user_id', $clientId)
                        ->where('workout_video_id', $videoId)
                        ->value('first_watched_at') ?? now(),
                    'completed_at' => $isCompleted ? now() : null
                ]
            );
            
            // Log video progress update
            Log::info('Video progress updated by client', [
                'video_id' => $videoId,
                'workout_id' => $request->workout_id,
                'client_id' => $clientId,
                'watched_duration' => $request->watched_duration,
                'is_completed' => $isCompleted
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'video_id' => $videoId,
                    'workout_id' => $request->workout_id,
                    'watched_duration' => $videoProgress->watched_duration,
                    'progress_percentage' => $video->duration > 0 ? round(($videoProgress->watched_duration / $video->duration) * 100, 2) : 0,
                    'is_completed' => $videoProgress->is_completed,
                    'first_watched_at' => $videoProgress->first_watched_at,
                    'last_watched_at' => $videoProgress->last_watched_at,
                    'completed_at' => $videoProgress->completed_at
                ],
                'message' => 'Video progress updated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update video progress: ' . $e->getMessage(), [
                'video_id' => $videoId,
                'client_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Update Failed',
                'data' => ['error' => 'Unable to update video progress']
            ], 500);
        }
    }
    
    /**
     * Get video progress for all videos in a workout
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workoutId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVideoProgress(Request $request, int $workoutId): JsonResponse
    {
        try {
            $clientId = Auth::id();
            
            // Verify workout exists and is accessible
            $workout = Workout::where('is_active', true)
                            ->where('id', $workoutId)
                            ->with(['videos' => function ($q) {
                                $q->orderBy('order');
                            }])
                            ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or not available'
                ], 404);
            }
            
            // Get progress for all videos in this workout
            $videoIds = $workout->videos->pluck('id');
            $progressRecords = WorkoutVideoProgress::where('user_id', $clientId)
                                          ->where('workout_id', $workoutId)
                                          ->whereIn('workout_video_id', $videoIds)
                                          ->get()
                                          ->keyBy('workout_video_id');
            
            // Map videos with their progress
            $videosWithProgress = $workout->videos->map(function ($video) use ($progressRecords) {
                $progress = $progressRecords->get($video->id);
                
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'duration' => $video->duration,
                    'thumbnail' => $video->thumbnail,
                        'thumbnail_url' => $video->thumbnail_url,
                        'video_url' => $video->video_url,
                        'video_type' => $video->video_type,
                        'order' => $video->order,
                    'progress' => $progress ? [
                        'watched_duration' => $progress->watched_duration,
                        'progress_percentage' => $video->duration > 0 ? round(($progress->watched_duration / $video->duration) * 100, 2) : 0,
                        'is_completed' => $progress->is_completed,
                        'first_watched_at' => $progress->first_watched_at,
                        'last_watched_at' => $progress->last_watched_at,
                        'completed_at' => $progress->completed_at
                    ] : [
                        'watched_duration' => 0,
                        'progress_percentage' => 0,
                        'is_completed' => false,
                        'first_watched_at' => null,
                        'last_watched_at' => null,
                        'completed_at' => null
                    ]
                ];
            });
            
            // Calculate overall workout progress
            $totalVideos = $workout->videos->count();
            $completedVideos = $progressRecords->where('is_completed', true)->count();
            $overallProgress = $totalVideos > 0 ? ($completedVideos / $totalVideos) * 100 : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'workout_id' => $workoutId,
                    'overall_progress' => round($overallProgress, 2),
                    'completed_videos' => $completedVideos,
                    'total_videos' => $totalVideos,
                    'videos' => $videosWithProgress
                ],
                'message' => 'Video progress retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve video progress: ' . $e->getMessage(), [
                'workout_id' => $workoutId,
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Retrieval Failed',
                'data' => ['error' => 'Unable to retrieve video progress: ' . $e->getMessage()]
            ], 500);
        }
    }
    
    /**
     * Show video progress for a specific video
     * 
     * Retrieves the progress information for a specific workout video
     * including watched duration, completion status, and timestamps
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $videoId The ID of the workout video
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception When video not found or database error occurs
     * @author Go Globe CMS Team
     * @since 1.0.0
     */
    public function showVideoProgress(Request $request, int $videoId): JsonResponse
    {
        try {
            $clientId = Auth::id();
            
            // Verify the video exists and get video details
            $video = WorkoutVideo::find($videoId);
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            
            // Verify the workout is active and accessible
            $workout = Workout::where('is_active', true)
                            ->where('id', $video->workout_id)
                            ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or not available'
                ], 404);
            }
            
            // Get progress record for this specific video
            $videoProgress = WorkoutVideoProgress::where('user_id', $clientId)
                                        ->where('workout_video_id', $videoId)
                                        ->first();
            
            // Calculate progress data
            $watchedDuration = $videoProgress ? $videoProgress->watched_duration : 0;
            $progressPercentage = $video->duration > 0 ? round(($watchedDuration / $video->duration) * 100, 2) : 0;
            $isCompleted = $videoProgress ? $videoProgress->is_completed : false;
            
            // Prepare response data
            $responseData = [
                'video' => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'duration' => $video->duration,
                    'thumbnail' => $video->thumbnail,
                    'thumbnail_url' => $video->thumbnail_url,
                    'video_url' => $video->video_url,
                    'video_type' => $video->video_type,
                    'order' => $video->order,
                    'workout_id' => $video->workout_id
                ],
                'workout' => [
                    'id' => $workout->id,
                    'name' => $workout->name,
                    'description' => $workout->description
                ],
                'progress' => [
                    'watched_duration' => $watchedDuration,
                    'progress_percentage' => $progressPercentage,
                    'is_completed' => $isCompleted,
                    'first_watched_at' => $videoProgress->first_watched_at ?? null,
                    'last_watched_at' => $videoProgress->last_watched_at ?? null,
                    'completed_at' => $videoProgress->completed_at ?? null
                ]
            ];
            
            // Log successful retrieval
            Log::info('Video progress retrieved by client', [
                'video_id' => $videoId,
                'workout_id' => $video->workout_id,
                'client_id' => $clientId,
                'progress_percentage' => $progressPercentage
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Video progress retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve video progress: ' . $e->getMessage(), [
                'video_id' => $videoId,
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Retrieval Failed',
                'data' => ['error' => 'Unable to retrieve video progress']
            ], 500);
        }
    }

    /**
     * Get workout progress for the authenticated client
     * 
     * @param  int  $id The workout ID from the route parameter
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgress(int $id): JsonResponse
    {
        try {
            $clientId = Auth::id();
            
            // Find the assignment for this client using workout_id and assigned_to
            $assignment = WorkoutAssignment::where('workout_id', $id)
                                         ->where('assigned_to', $clientId)
                                         ->with(['workout:id,name,description,duration,is_active,thumbnail'])
                                         ->first();
            
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found or access denied'
                ], 404);
            }
            
            // Calculate overall video progress for this workout
            $videoProgress = WorkoutVideoProgress::whereHas('workoutVideo', function ($query) use ($id) {
                $query->where('workout_id', $id);
            })
            ->where('user_id', $clientId)
            ->get();
            
            $totalVideos = WorkoutVideo::where('workout_id', $id)->count();
            $completedVideos = $videoProgress->where('is_completed', true)->count();
            $videoProgressPercentage = $totalVideos > 0 ? round(($completedVideos / $totalVideos) * 100, 2) : 0;
            
            // Prepare response data
            $responseData = [
                'assignment_id' => $assignment->id,
                'workout_id' => $assignment->workout_id,
                'workout' => $assignment->workout,
                'progress' => (float) $assignment->progress,
                'status' => $assignment->status,
                'assigned_at' => $assignment->assigned_at,
                'due_date' => $assignment->due_date,
                'completed_at' => $assignment->completed_at,
                'notes' => $assignment->notes,
                'video_progress' => [
                    'total_videos' => $totalVideos,
                    'completed_videos' => $completedVideos,
                    'progress_percentage' => $videoProgressPercentage
                ],
                'updated_at' => $assignment->updated_at
            ];
            
            // Log successful retrieval
            Log::info('Workout progress retrieved by client', [
                'workout_id' => $id,
                'assignment_id' => $assignment->id,
                'client_id' => $clientId,
                'progress' => $assignment->progress,
                'status' => $assignment->status
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Workout progress retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout progress: ' . $e->getMessage(), [
                'workout_id' => $id,
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Retrieval Failed',
                'data' => ['error' => 'Unable to retrieve workout progress'.$e->getMessage()]
            ], 500);
        }
    }
}