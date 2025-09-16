<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Models\WorkoutVideo;
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
            // Only show active workouts to clients
            $query = Workout::where('is_active', true)
                           ->with(['user:id,name,email']);
            
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
            
            // Include videos if requested
            if ($request->boolean('include_videos')) {
                $query->with(['videos' => function ($q) {
                    $q->orderBy('order');
                }]);
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
                'data' => ['error' => 'Unable to retrieve workouts'. $e->getMessage()]
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
                                 'user:id,name,email',
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
                           ->with(['user:id,name,email']);
            
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
     * @param  int  $workoutId
     * @param  int  $videoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showVideo(Request $request, int $workoutId, int $videoId): JsonResponse
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
            
            $video = $workout->videos()->where('id', $videoId)->first();
            
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
                                      ->with(['user:id,name,email'])
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
                'data' => ['error' => 'Unable to retrieve dashboard data'. $e->getMessage()]
            ], 500);
        }
    }
}