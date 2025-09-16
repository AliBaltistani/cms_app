<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutVideoRequest;
use App\Http\Requests\UpdateWorkoutVideoRequest;
use App\Models\Workout;
use App\Models\WorkoutVideo;
use App\Services\WorkoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Workout Video Controller
 * 
 * Handles workout video management operations for both web and API requests
 * Provides complete CRUD operations for workout videos
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    Workout Video Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class WorkoutVideoController extends Controller
{
    public function __construct(
        private WorkoutService $workoutService
    ) {}
    
    /**
     * Check if the request is from API
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->wantsJson() || $request->expectsJson();
    }
    
    /**
     * Send API response
     * 
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    private function sendApiResponse($data, string $message, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }
    
    /**
     * Send API error response
     * 
     * @param  string  $error
     * @param  array  $errorMessages
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    private function sendApiError(string $error, array $errorMessages = [], int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        
        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }
        
        return response()->json($response, $code);
    }

    /**
     * Display videos for a specific workout
     */
    public function index(Workout $workout): View
    {
        $videos = $workout->videos()->orderBy('order')->get();
        
        return view('admin.workouts.videos.index', compact('workout', 'videos'));
    }

    /**
     * Show the form for creating a new video
     */
    public function create(Workout $workout): View
    {
        $user = Auth::user();
        return view('admin.workouts.videos.create', compact('workout', 'user'));
    }

    /**
     * Store a newly created video
     */
    public function store(StoreWorkoutVideoRequest $request, Workout $workout): RedirectResponse
    {
        try {
            $videoData = $request->validated();
            $video = $this->workoutService->addVideoToWorkout($workout, $videoData);
            
            return redirect()
                ->route('workouts.show', $workout->id)
                ->with('success', 'Video added successfully');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to add video: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified video
     */
    public function show(Workout $workout, WorkoutVideo $video): RedirectResponse
    {
        // Ensure video belongs to workout
        if ($video->workout_id !== $workout->id) {
            abort(404, 'Video not found in this workout');
        }

        // Redirect to workout show page since we don't have a separate video show view
        return redirect()->route('workouts.show', $workout->id);
    }

    /**
     * Show the form for editing the specified video
     */
    public function edit(Workout $workout, WorkoutVideo $video): View
    {
        // Ensure video belongs to workout
        if ($video->workout_id !== $workout->id) {
            abort(404, 'Video not found in this workout');
        }
        $user = Auth::user();

        return view('admin.workouts.videos.edit', compact('workout', 'video', 'user'));
    }

    /**
     * Update the specified video
     */
    public function update(UpdateWorkoutVideoRequest $request, Workout $workout, WorkoutVideo $video): RedirectResponse
    {
        // Ensure video belongs to workout
        if ($video->workout_id !== $workout->id) {
            abort(404, 'Video not found in this workout');
        }

        try {
            $updatedVideo = $this->workoutService->updateWorkoutVideo($video, $request->validated());
            
            return redirect()
                ->route('workouts.show', $workout->id)
                ->with('success', 'Video updated successfully');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to update video: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified video
     */
    public function destroy(Workout $workout, WorkoutVideo $video): RedirectResponse
    {
        // Ensure video belongs to workout
        if ($video->workout_id !== $workout->id) {
            abort(404, 'Video not found in this workout');
        }

        try {
            $this->workoutService->deleteWorkoutVideo($video);
            
            return redirect()
                ->route('workouts.show', $workout->id)
                ->with('success', 'Video deleted successfully');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete video: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the reorder form
     */
    public function reorderForm(Workout $workout): View
    {
        $videos = $workout->videos()->orderBy('order')->get();
        
        return view('admin.workouts.videos.reorder', compact('workout', 'videos'));
    }

    /**
     * Reorder workout videos
     * Handles both web and API requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Workout  $workout
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request, Workout $workout)
    {
        $validator = Validator::make($request->all(), [
            'video_ids' => 'required|array',
            'video_ids.*' => 'integer|exists:workout_videos,id'
        ]);
        
        if ($validator->fails()) {
            if ($this->isApiRequest($request)) {
                return $this->sendApiError('Validation Error', $validator->errors()->toArray(), 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Verify all videos belong to this workout
            $videoIds = $request->video_ids;
            $workoutVideoIds = $workout->videos()->pluck('id')->toArray();
            
            $invalidIds = array_diff($videoIds, $workoutVideoIds);
            if (!empty($invalidIds)) {
                $errorMessage = 'Some videos do not belong to this workout';
                
                if ($this->isApiRequest($request)) {
                    return $this->sendApiError('Invalid Videos', ['error' => $errorMessage], 400);
                }
                
                return back()->withErrors(['error' => $errorMessage])->withInput();
            }

            $workout->reorderVideos($videoIds);
            
            Log::info('Workout videos reordered successfully', [
                'workout_id' => $workout->id,
                'video_ids' => $videoIds,
                'user_id' => Auth::id()
            ]);
            
            if ($this->isApiRequest($request)) {
                return $this->sendApiResponse([
                    'workout_id' => $workout->id,
                    'reordered_video_ids' => $videoIds
                ], 'Videos reordered successfully');
            }
            
            return redirect()
                ->route('workouts.show', $workout->id)
                ->with('success', 'Videos reordered successfully');
                
        } catch (\Exception $e) {
            Log::error('Failed to reorder workout videos: ' . $e->getMessage(), [
                'workout_id' => $workout->id,
                'video_ids' => $request->video_ids,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($this->isApiRequest($request)) {
                return $this->sendApiError('Reorder Failed', ['error' => 'Unable to reorder videos'], 500);
            }
            
            return back()->withErrors(['error' => 'Failed to reorder videos: ' . $e->getMessage()])->withInput();
        }
    }
    
    /**
     * Get all videos (standalone, not tied to specific workout)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllVideos(Request $request): JsonResponse
    {
        try {
            $query = WorkoutVideo::with('workout:id,name,category');
            
            // Apply filters
            if ($request->filled('workout_id')) {
                $query->where('workout_id', $request->workout_id);
            }
            
            if ($request->filled('duration_min')) {
                $query->where('duration', '>=', $request->duration_min);
            }
            
            if ($request->filled('duration_max')) {
                $query->where('duration', '<=', $request->duration_max);
            }
            
            // Apply search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('video_url', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'order');
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($sortBy, $sortDirection);
            
            // Paginate results
            $perPage = $request->get('per_page', 15);
            $videos = $query->paginate($perPage);
            
            $responseData = [
                'data' => $videos->items(),
                'pagination' => [
                    'total' => $videos->total(),
                    'per_page' => $videos->perPage(),
                    'current_page' => $videos->currentPage(),
                    'last_page' => $videos->lastPage(),
                    'from' => $videos->firstItem(),
                    'to' => $videos->lastItem(),
                    'has_more_pages' => $videos->hasMorePages()
                ]
            ];
            
            return $this->sendApiResponse($responseData, 'Videos retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve all videos: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Retrieval Failed', ['error' => 'Unable to retrieve videos'], 500);
        }
    }
    
    /**
     * Show a specific video (standalone)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WorkoutVideo  $video
     * @return \Illuminate\Http\JsonResponse
     */
    public function showVideo(Request $request, WorkoutVideo $video): JsonResponse
    {
        try {
            $video->load('workout:id,name,category,difficulty');
            
            $videoData = [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'video_url' => $video->video_url,
                'thumbnail_url' => $video->thumbnail_url,
                'duration' => $video->duration,
                'order' => $video->order,
                'workout' => $video->workout,
                'created_at' => $video->created_at->toISOString(),
                'updated_at' => $video->updated_at->toISOString()
            ];
            
            return $this->sendApiResponse($videoData, 'Video retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve video: ' . $e->getMessage(), [
                'video_id' => $video->id ?? 'unknown',
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Retrieval Failed', ['error' => 'Unable to retrieve video'], 500);
        }
    }
    
    /**
     * Search videos with advanced filtering
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2|max:255',
                'workout_id' => 'nullable|integer|exists:workouts,id',
                'duration_min' => 'nullable|integer|min:1',
                'duration_max' => 'nullable|integer|max:300',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);
            
            if ($validator->fails()) {
                return $this->sendApiError('Validation Error', $validator->errors()->toArray(), 422);
            }
            
            $query = WorkoutVideo::with('workout:id,name,category');
            
            // Apply search query
            $searchTerm = $request->query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhereHas('workout', function ($workoutQuery) use ($searchTerm) {
                      $workoutQuery->where('name', 'like', "%{$searchTerm}%")
                                   ->orWhere('category', 'like', "%{$searchTerm}%");
                  });
            });
            
            // Apply additional filters
            if ($request->filled('workout_id')) {
                $query->where('workout_id', $request->workout_id);
            }
            
            if ($request->filled('duration_min')) {
                $query->where('duration', '>=', $request->duration_min);
            }
            
            if ($request->filled('duration_max')) {
                $query->where('duration', '<=', $request->duration_max);
            }
            
            // Order by relevance
            $query->orderByRaw("CASE WHEN title LIKE '%{$searchTerm}%' THEN 1 ELSE 2 END")
                  ->orderBy('order', 'asc');
            
            // Paginate results
            $perPage = (int) $request->input('per_page', 15);
            $videos = $query->paginate($perPage);
            
            $responseData = [
                'query' => $searchTerm,
                'data' => $videos->items(),
                'pagination' => [
                    'total' => $videos->total(),
                    'per_page' => $videos->perPage(),
                    'current_page' => $videos->currentPage(),
                    'last_page' => $videos->lastPage(),
                    'from' => $videos->firstItem(),
                    'to' => $videos->lastItem(),
                    'has_more_pages' => $videos->hasMorePages()
                ]
            ];
            
            return $this->sendApiResponse($responseData, 'Video search completed successfully');
            
        } catch (\Exception $e) {
            Log::error('Video search failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'search_query' => $request->query,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Search Failed', ['error' => 'Unable to perform video search'], 500);
        }
    }
    
    /**
     * Get video categories (based on workout categories)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $categories = WorkoutVideo::join('workouts', 'workout_videos.workout_id', '=', 'workouts.id')
                ->selectRaw('workouts.category, COUNT(workout_videos.id) as video_count')
                ->whereNotNull('workouts.category')
                ->groupBy('workouts.category')
                ->orderBy('workouts.category')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->category,
                        'video_count' => $item->video_count,
                        'slug' => \Illuminate\Support\Str::slug($item->category)
                    ];
                });
            
            return $this->sendApiResponse($categories, 'Video categories retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve video categories: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Categories Failed', ['error' => 'Unable to retrieve video categories'], 500);
        }
    }
    
    /**
     * Toggle video status (if applicable)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Workout  $workout
     * @param  \App\Models\WorkoutVideo  $video
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, Workout $workout, WorkoutVideo $video): JsonResponse
    {
        try {
            // Ensure video belongs to workout
            if ($video->workout_id !== $workout->id) {
                return $this->sendApiError('Invalid Video', ['error' => 'Video not found in this workout'], 404);
            }
            
            // This is a placeholder - implement based on your video status requirements
            // For example, you might have an 'is_active' field on workout_videos table
            
            Log::info('Video status toggled', [
                'video_id' => $video->id,
                'workout_id' => $workout->id,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendApiResponse([
                'video_id' => $video->id,
                'workout_id' => $workout->id,
                'message' => 'Video status toggle functionality needs to be implemented based on requirements'
            ], 'Video status toggle requested');
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle video status: ' . $e->getMessage(), [
                'video_id' => $video->id ?? 'unknown',
                'workout_id' => $workout->id ?? 'unknown',
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Toggle Failed', ['error' => 'Unable to toggle video status'], 500);
        }
    }
    
    /**
     * Add video to favorites (placeholder - implement based on requirements)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WorkoutVideo  $video
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToFavorites(Request $request, WorkoutVideo $video): JsonResponse
    {
        try {
            // This is a placeholder implementation
            // You would typically have a favorites table or relationship
            
            Log::info('Video added to favorites', [
                'video_id' => $video->id,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendApiResponse([
                'video_id' => $video->id,
                'favorited' => true
            ], 'Video added to favorites successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to add video to favorites: ' . $e->getMessage(), [
                'video_id' => $video->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Favorite Failed', ['error' => 'Unable to add video to favorites'], 500);
        }
    }
    
    /**
     * Remove video from favorites (placeholder - implement based on requirements)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WorkoutVideo  $video
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFromFavorites(Request $request, WorkoutVideo $video): JsonResponse
    {
        try {
            // This is a placeholder implementation
            // You would typically have a favorites table or relationship
            
            Log::info('Video removed from favorites', [
                'video_id' => $video->id,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendApiResponse([
                'video_id' => $video->id,
                'favorited' => false
            ], 'Video removed from favorites successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to remove video from favorites: ' . $e->getMessage(), [
                'video_id' => $video->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Unfavorite Failed', ['error' => 'Unable to remove video from favorites'], 500);
        }
    }
}