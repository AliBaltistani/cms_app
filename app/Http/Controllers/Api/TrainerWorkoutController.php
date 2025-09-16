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
use Illuminate\Support\Facades\Storage;

/**
 * Trainer Workout API Controller
 * 
 * Handles workout and workout video CRUD operations for trainers via API
 * Trainers can only manage their own workouts and videos
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\API
 * @category    Trainer Workout Management API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class TrainerWorkoutController extends Controller
{
    /**
     * Get trainer's workouts with optional filtering and pagination
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Workout::where('user_id', Auth::id());
            
            // Apply filters
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            // Apply search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
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
            $query->orderBy($sortBy, $sortDirection);
            
            // Paginate results
            $perPage = (int) $request->input('per_page', 15);
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
            Log::error('Failed to retrieve trainer workouts: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Retrieval Failed',
                'data' => ['error' => 'Unable to retrieve workouts']
            ], 500);
        }
    }
    
    /**
     * Create a new workout for the authenticated trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate workout data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'duration' => 'required|integer|min:1|max:1440', // Max 24 hours
                'description' => 'nullable|string|max:5000',
                'is_active' => 'nullable|boolean',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            // Prepare workout data
            $workoutData = $validator->validated();
            $workoutData['user_id'] = Auth::id();
            
            // Handle thumbnail upload
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('workouts/thumbnails', 'public');
                $workoutData['thumbnail'] = $thumbnailPath;
            }
            
            // Create workout
            $workout = Workout::create($workoutData);
            
            // Load relationships for response
            $workout->load('user', 'videos');
            
            Log::info('Workout created successfully by trainer', [
                'workout_id' => $workout->id,
                'trainer_id' => Auth::id(),
                'workout_name' => $workout->name
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $workout,
                'message' => 'Workout created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create workout: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Creation Failed',
                'data' => ['error' => 'Unable to create workout']
            ], 500);
        }
    }
    
    /**
     * Show a specific workout owned by the trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $workout = Workout::where('user_id', Auth::id())
                             ->where('id', $id)
                             ->with(['user', 'videos' => function ($q) {
                                 $q->orderBy('order');
                             }])
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or access denied'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $workout,
                'message' => 'Workout retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout: ' . $e->getMessage(), [
                'workout_id' => $id,
                'trainer_id' => Auth::id(),
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
     * Update a specific workout owned by the trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $workout = Workout::where('user_id', Auth::id())
                             ->where('id', $id)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or access denied'
                ], 404);
            }
            
            // Validate update data
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'duration' => 'sometimes|required|integer|min:1|max:1440',
                'description' => 'sometimes|nullable|string|max:5000',
                'is_active' => 'sometimes|boolean',
                'thumbnail' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            $updateData = $validator->validated();
            
            // Handle thumbnail upload
            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail if exists
                if ($workout->thumbnail) {
                    Storage::disk('public')->delete($workout->thumbnail);
                }
                
                $thumbnailPath = $request->file('thumbnail')->store('workouts/thumbnails', 'public');
                $updateData['thumbnail'] = $thumbnailPath;
            }
            
            // Update workout
            $workout->update($updateData);
            
            // Load relationships for response
            $workout->load('user', 'videos');
            
            Log::info('Workout updated successfully by trainer', [
                'workout_id' => $workout->id,
                'trainer_id' => Auth::id(),
                'updated_fields' => array_keys($updateData)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $workout,
                'message' => 'Workout updated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update workout: ' . $e->getMessage(), [
                'workout_id' => $id,
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Update Failed',
                'data' => ['error' => 'Unable to update workout']
            ], 500);
        }
    }
    
    /**
     * Delete a specific workout owned by the trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $workout = Workout::where('user_id', Auth::id())
                             ->where('id', $id)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or access denied'
                ], 404);
            }
            
            $workoutName = $workout->name;
            
            // Delete thumbnail if exists
            if ($workout->thumbnail) {
                Storage::disk('public')->delete($workout->thumbnail);
            }
            
            // Delete workout (videos will be deleted via cascade)
            $workout->delete();
            
            Log::info('Workout deleted successfully by trainer', [
                'workout_id' => $id,
                'workout_name' => $workoutName,
                'trainer_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Workout deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete workout: ' . $e->getMessage(), [
                'workout_id' => $id,
                'trainer_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Deletion Failed',
                'data' => ['error' => 'Unable to delete workout']
            ], 500);
        }
    }
    
    /**
     * Toggle workout status (active/inactive)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        try {
            $workout = Workout::where('user_id', Auth::id())
                             ->where('id', $id)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or access denied'
                ], 404);
            }
            
            $workout->is_active = !$workout->is_active;
            $workout->save();
            
            Log::info('Workout status toggled by trainer', [
                'workout_id' => $workout->id,
                'new_status' => $workout->is_active,
                'trainer_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $workout->id,
                    'name' => $workout->name,
                    'is_active' => $workout->is_active,
                    'updated_at' => $workout->updated_at->toISOString()
                ],
                'message' => 'Workout status updated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle workout status: ' . $e->getMessage(), [
                'workout_id' => $id,
                'trainer_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Toggle Failed',
                'data' => ['error' => 'Unable to toggle workout status']
            ], 500);
        }
    }
    
    // ========== WORKOUT VIDEOS MANAGEMENT ==========
    
    /**
     * Get videos for a specific workout owned by the trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workoutId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVideos(Request $request, int $workoutId): JsonResponse
    {
        try {
            $workout = Workout::where('user_id', Auth::id())
                             ->where('id', $workoutId)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or access denied'
                ], 404);
            }
            
            $videos = $workout->videos()->orderBy('order')->get();
            
            return response()->json([
                'success' => true,
                'data' => $videos,
                'message' => 'Workout videos retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout videos: ' . $e->getMessage(), [
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
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
     * Add a video to a workout owned by the trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workoutId
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeVideo(Request $request, int $workoutId): JsonResponse
    {
        try {
            $workout = Workout::where('user_id', Auth::id())
                             ->where('id', $workoutId)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or access denied'
                ], 404);
            }
            
            // Validate video data
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'video_url' => 'required|string|max:500',
                'video_type' => 'required|in:url,file,youtube,vimeo',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'duration' => 'nullable|integer|min:1|max:86400', // Max 24 hours in seconds
                'order' => 'nullable|integer|min:0',
                'is_preview' => 'nullable|boolean',
                'metadata' => 'nullable|json'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            $videoData = $validator->validated();
            $videoData['workout_id'] = $workoutId;
            
            // Set order if not provided
            if (!isset($videoData['order'])) {
                $videoData['order'] = $workout->videos()->max('order') + 1;
            }
            
            // Handle thumbnail upload
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('workouts/videos/thumbnails', 'public');
                $videoData['thumbnail'] = $thumbnailPath;
            }
            
            // Create video
            $video = WorkoutVideo::create($videoData);
            
            Log::info('Workout video created successfully by trainer', [
                'video_id' => $video->id,
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
                'video_title' => $video->title
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $video,
                'message' => 'Workout video created successfully'
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create workout video: ' . $e->getMessage(), [
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Creation Failed',
                'data' => ['error' => 'Unable to create workout video']
            ], 500);
        }
    }
    
    /**
     * Update a workout video owned by the trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workoutId
     * @param  int  $videoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVideo(Request $request, int $workoutId, int $videoId): JsonResponse
    {
        try {
            $workout = Workout::where('user_id', Auth::id())
                             ->where('id', $workoutId)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or access denied'
                ], 404);
            }
            
            $video = $workout->videos()->where('id', $videoId)->first();
            
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            
            // Validate update data
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string|max:5000',
                'video_url' => 'sometimes|required|string|max:500',
                'video_type' => 'sometimes|required|in:url,file,youtube,vimeo',
                'thumbnail' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'duration' => 'sometimes|nullable|integer|min:1|max:86400',
                'order' => 'sometimes|integer|min:0',
                'is_preview' => 'sometimes|boolean',
                'metadata' => 'sometimes|nullable|json'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            $updateData = $validator->validated();
            
            // Handle thumbnail upload
            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail if exists
                if ($video->thumbnail) {
                    Storage::disk('public')->delete($video->thumbnail);
                }
                
                $thumbnailPath = $request->file('thumbnail')->store('workouts/videos/thumbnails', 'public');
                $updateData['thumbnail'] = $thumbnailPath;
            }
            
            // Update video
            $video->update($updateData);
            
            Log::info('Workout video updated successfully by trainer', [
                'video_id' => $video->id,
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
                'updated_fields' => array_keys($updateData)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $video,
                'message' => 'Workout video updated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update workout video: ' . $e->getMessage(), [
                'video_id' => $videoId,
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Update Failed',
                'data' => ['error' => 'Unable to update workout video']
            ], 500);
        }
    }
    
    /**
     * Delete a workout video owned by the trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workoutId
     * @param  int  $videoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyVideo(Request $request, int $workoutId, int $videoId): JsonResponse
    {
        try {
            $workout = Workout::where('user_id', Auth::id())
                             ->where('id', $workoutId)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or access denied'
                ], 404);
            }
            
            $video = $workout->videos()->where('id', $videoId)->first();
            
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found'
                ], 404);
            }
            
            $videoTitle = $video->title;
            
            // Delete thumbnail if exists
            if ($video->thumbnail) {
                Storage::disk('public')->delete($video->thumbnail);
            }
            
            // Delete video
            $video->delete();
            
            Log::info('Workout video deleted successfully by trainer', [
                'video_id' => $videoId,
                'video_title' => $videoTitle,
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Workout video deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete workout video: ' . $e->getMessage(), [
                'video_id' => $videoId,
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Deletion Failed',
                'data' => ['error' => 'Unable to delete workout video']
            ], 500);
        }
    }
    
    /**
     * Reorder videos in a workout owned by the trainer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $workoutId
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderVideos(Request $request, int $workoutId): JsonResponse
    {
        try {
            $workout = Workout::where('user_id', Auth::id())
                             ->where('id', $workoutId)
                             ->first();
            
            if (!$workout) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workout not found or access denied'
                ], 404);
            }
            
            // Validate video IDs
            $validator = Validator::make($request->all(), [
                'video_ids' => 'required|array|min:1',
                'video_ids.*' => 'integer|exists:workout_videos,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            $videoIds = $request->video_ids;
            
            // Verify all videos belong to this workout
            $videoCount = $workout->videos()->whereIn('id', $videoIds)->count();
            if ($videoCount !== count($videoIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some videos do not belong to this workout'
                ], 400);
            }
            
            // Reorder videos
            foreach ($videoIds as $index => $videoId) {
                $workout->videos()->where('id', $videoId)->update(['order' => $index + 1]);
            }
            
            Log::info('Workout videos reordered successfully by trainer', [
                'workout_id' => $workoutId,
                'video_ids' => $videoIds,
                'trainer_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => ['video_ids' => $videoIds],
                'message' => 'Videos reordered successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to reorder workout videos: ' . $e->getMessage(), [
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Reorder Failed',
                'data' => ['error' => 'Unable to reorder videos']
            ], 500);
        }
    }
}