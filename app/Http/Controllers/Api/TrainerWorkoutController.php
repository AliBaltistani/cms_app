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
use App\Models\WorkoutExercise;

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

    /**
     * Get workout builder data for creating new workout templates
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWorkoutBuilder()
    {
        try {
            $trainer = Auth::user();
            
            // Get trainer's existing workouts for the builder
            $workouts = $trainer->workouts()
                ->select('id', 'name', 'duration', 'description', 'is_active')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            // Get workout categories from existing workouts
            $categories = [
                'cardio' => 'Cardio',
                'strength' => 'Strength Training',
                'yoga' => 'Yoga & Flexibility',
                'pilates' => 'Pilates',
                'hiit' => 'HIIT',
                'functional' => 'Functional Training',
                'sports' => 'Sports Performance',
                'rehabilitation' => 'Rehabilitation'
            ];

            // Get equipment options
            $equipment = [
                'dumbbells' => 'Dumbbells',
                'barbells' => 'Barbells',
                'kettlebells' => 'Kettlebells',
                'resistance_bands' => 'Resistance Bands',
                'yoga_mat' => 'Yoga Mat',
                'pull_up_bar' => 'Pull-up Bar',
                'medicine_ball' => 'Medicine Ball',
                'foam_roller' => 'Foam Roller',
                'none' => 'No Equipment'
            ];

            // Get muscle groups
            $muscleGroups = [
                'chest' => 'Chest',
                'back' => 'Back',
                'shoulders' => 'Shoulders',
                'arms' => 'Arms',
                'legs' => 'Legs',
                'glutes' => 'Glutes',
                'core' => 'Core',
                'full_body' => 'Full Body'
            ];

            Log::info('Workout builder data retrieved successfully', [
                'trainer_id' => $trainer->id,
                'workouts_count' => $workouts->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'workouts' => $workouts,
                    'categories' => $categories,
                    'equipment' => $equipment,
                    'muscle_groups' => $muscleGroups
                ],
                'message' => 'Workout builder data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get workout builder data: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load workout builder data',
                'data' => ['error' => 'Unable to retrieve workout builder data']
            ], 500);
        }
    }

    /**
     * Search exercises for adding to workout
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchExercises(Request $request)
    {
        try {
            $trainer = Auth::user();
            $search = $request->get('search', '');
            $category = $request->get('category', '');
            $equipment = $request->get('equipment', '');
            $muscleGroup = $request->get('muscle_group', '');
            $perPage = $request->get('per_page', 20);

            // Build query for exercises (using Workout model as exercise library)
            $query = Workout::where('is_active', true);

            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Apply category filter (using description or tags if available)
            if (!empty($category)) {
                $query->where('description', 'LIKE', "%{$category}%");
            }

            // Apply equipment filter
            if (!empty($equipment)) {
                $query->where('description', 'LIKE', "%{$equipment}%");
            }

            // Apply muscle group filter
            if (!empty($muscleGroup)) {
                $query->where('description', 'LIKE', "%{$muscleGroup}%");
            }

            $exercises = $query->select('id', 'name', 'description', 'duration')
                ->orderBy('name')
                ->paginate($perPage);

            // Format exercises for the response
            $formattedExercises = $exercises->map(function($exercise) {
                return [
                    'id' => $exercise->id,
                    'name' => $exercise->name,
                    'description' => $exercise->description,
                    'duration' => $exercise->duration,
                    'formatted_duration' => $exercise->formatted_duration,
                    // Extract category from description (simplified approach)
                    'category' => $this->extractCategoryFromDescription($exercise->description),
                    'equipment' => $this->extractEquipmentFromDescription($exercise->description),
                    'muscle_group' => $this->extractMuscleGroupFromDescription($exercise->description)
                ];
            });

            Log::info('Exercise search completed', [
                'trainer_id' => $trainer->id,
                'search_term' => $search,
                'results_count' => $exercises->count(),
                'filters' => compact('category', 'equipment', 'muscleGroup')
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'exercises' => $formattedExercises,
                    'pagination' => [
                        'current_page' => $exercises->currentPage(),
                        'last_page' => $exercises->lastPage(),
                        'per_page' => $exercises->perPage(),
                        'total' => $exercises->total()
                    ]
                ],
                'message' => 'Exercises retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to search exercises: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Exercise search failed',
                'data' => ['error' => 'Unable to search exercises']
            ], 500);
        }
    }

    /**
     * Add exercise to workout
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $workoutId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addExerciseToWorkout(Request $request, $workoutId)
    {
        try {
            $trainer = Auth::user();
            
            // Verify workout belongs to trainer
            $workout = $trainer->workouts()->findOrFail($workoutId);
            
            $validator = Validator::make($request->all(), [
                'exercise_id' => 'required|exists:workouts,id',
                'order' => 'nullable|integer|min:0',
                'sets' => 'nullable|integer|min:1|max:10',
                'reps' => 'nullable|integer|min:1|max:100',
                'weight' => 'nullable|numeric|min:0|max:1100',
                'duration' => 'nullable|integer|min:1|max:3600',
                'rest_interval' => 'nullable|integer|min:0|max:600',
                'tempo' => 'nullable|string|max:20',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }

            // Get the next order if not provided
            $order = $request->get('order');
            if (is_null($order)) {
                $order = $workout->workoutExercises()->max('order') + 1;
            }

            // Create workout exercise
            $weightKg = $request->get('weight') !== null ? \App\Support\UnitConverter::lbsToKg((float)$request->get('weight')) : null;
            $workoutExercise = $workout->workoutExercises()->create([
                'exercise_id' => $request->exercise_id,
                'order' => $order,
                'sets' => $request->get('sets', 3),
                'reps' => $request->get('reps', 10),
                'weight' => $weightKg,
                'duration' => $request->get('duration'),
                'rest_interval' => $request->get('rest_interval', 60),
                'tempo' => $request->get('tempo'),
                'notes' => $request->get('notes'),
                'is_active' => true
            ]);

            // Load the exercise details
            $workoutExercise->load('exercise');

            Log::info('Exercise added to workout successfully', [
                'workout_id' => $workoutId,
                'exercise_id' => $request->exercise_id,
                'workout_exercise_id' => $workoutExercise->id,
                'trainer_id' => $trainer->id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'workout_exercise' => [
                        'id' => $workoutExercise->id,
                        'exercise_id' => $workoutExercise->exercise_id,
                        'exercise_name' => $workoutExercise->exercise->name ?? 'Unknown Exercise',
                        'order' => $workoutExercise->order,
                        'sets' => $workoutExercise->sets,
                        'reps' => $workoutExercise->reps,
                        'weight' => $workoutExercise->weight,
                        'duration' => $workoutExercise->duration,
                        'rest_interval' => $workoutExercise->rest_interval,
                        'tempo' => $workoutExercise->tempo,
                        'notes' => $workoutExercise->notes,
                        'formatted_rest_interval' => $workoutExercise->formatted_rest_interval,
                        'formatted_weight' => $workoutExercise->formatted_weight
                    ]
                ],
                'message' => 'Exercise added to workout successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add exercise to workout: ' . $e->getMessage(), [
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add exercise',
                'data' => ['error' => 'Unable to add exercise to workout']
            ], 500);
        }
    }

    /**
     * Get workout exercises for configuration
     * 
     * @param int $workoutId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWorkoutExercises($workoutId)
    {
        try {
            $trainer = Auth::user();
            
            // Verify workout belongs to trainer
            $workout = $trainer->workouts()->findOrFail($workoutId);
            
            // Get workout exercises with their sets
            $workoutExercises = $workout->workoutExercises()
                ->with(['exercise', 'exerciseSets' => function($query) {
                    $query->orderBy('set_number');
                }])
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            // Format the response
            $formattedExercises = $workoutExercises->map(function($workoutExercise) {
                return [
                    'id' => $workoutExercise->id,
                    'exercise_id' => $workoutExercise->exercise_id,
                    'exercise_name' => $workoutExercise->exercise->name ?? 'Unknown Exercise',
                    'exercise_description' => $workoutExercise->exercise->description ?? '',
                    'order' => $workoutExercise->order,
                    'sets' => $workoutExercise->sets,
                    'reps' => $workoutExercise->reps,
                    'weight' => $workoutExercise->weight,
                    'duration' => $workoutExercise->duration,
                    'rest_interval' => $workoutExercise->rest_interval,
                    'tempo' => $workoutExercise->tempo,
                    'notes' => $workoutExercise->notes,
                    'formatted_rest_interval' => $workoutExercise->formatted_rest_interval,
                    'formatted_weight' => $workoutExercise->formatted_weight,
                    'exercise_sets' => $workoutExercise->exerciseSets->map(function($set) {
                        return [
                            'id' => $set->id,
                            'set_number' => $set->set_number,
                            'reps' => $set->reps,
                            'weight' => $set->weight,
                            'duration' => $set->duration,
                            'rest_time' => $set->rest_time,
                            'notes' => $set->notes,
                            'is_completed' => $set->is_completed,
                            'formatted_weight' => $set->formatted_weight,
                            'formatted_duration' => $set->formatted_duration,
                            'formatted_rest_time' => $set->formatted_rest_time
                        ];
                    })
                ];
            });

            Log::info('Workout exercises retrieved successfully', [
                'workout_id' => $workoutId,
                'exercises_count' => $workoutExercises->count(),
                'trainer_id' => $trainer->id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'workout' => [
                        'id' => $workout->id,
                        'name' => $workout->name,
                        'description' => $workout->description,
                        'duration' => $workout->duration
                    ],
                    'exercises' => $formattedExercises
                ],
                'message' => 'Workout exercises retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get workout exercises: ' . $e->getMessage(), [
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workout exercises',
                'data' => ['error' => 'Unable to get workout exercises'.$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Configure exercise sets, reps, duration, and rest times
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $exerciseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function configureExercise(Request $request, $exerciseId)
    {
        try {
            $trainer = Auth::user();
            
            // Verify workout exercise belongs to trainer's workout
            $workoutExercise = WorkoutExercise::whereHas('workout', function($query) use ($trainer) {
                $query->where('user_id', $trainer->id);
            })->findOrFail($exerciseId);
            
            $validator = Validator::make($request->all(), [
                'sets' => 'required|integer|min:1|max:10',
                'reps' => 'required|integer|min:1|max:100',
                'weight' => 'required|numeric|min:0|max:1100',
                'duration' => 'required|integer|min:1|max:3600',
                'rest_interval' => 'required|integer|min:0|max:600',
                'tempo' => 'required|string|max:20',
                'notes' => 'required|string|max:500',
                'exercise_sets' => 'required|array',
                'exercise_sets.*.set_number' => 'required_with:exercise_sets|integer|min:1|max:10',
                'exercise_sets.*.reps' => 'required|integer|min:1|max:100',
                'exercise_sets.*.weight' => 'required|numeric|min:0|max:1100',
                'exercise_sets.*.duration' => 'required|integer|min:1|max:3600',
                'exercise_sets.*.rest_time' => 'required|integer|min:0|max:600',
                'exercise_sets.*.notes' => 'required|string|max:200'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }

            // Update workout exercise basic configuration
            $weightKg = $request->get('weight') !== null ? \App\Support\UnitConverter::lbsToKg((float)$request->get('weight')) : null;
            $updateData = array_filter([
                'sets' => $request->get('sets'),
                'reps' => $request->get('reps'),
                'weight' => $weightKg,
                'duration' => $request->get('duration'),
                'rest_interval' => $request->get('rest_interval'),
                'tempo' => $request->get('tempo'),
                'notes' => $request->get('notes')
            ], function($value) {
                return !is_null($value);
            });

            if (!empty($updateData)) {
                $workoutExercise->update($updateData);
            }

            // Handle individual exercise sets if provided
            if ($request->has('exercise_sets')) {
                $exerciseSets = $request->get('exercise_sets');
                
                // Delete existing sets and create new ones
                $workoutExercise->exerciseSets()->delete();
                
                foreach ($exerciseSets as $setData) {
                    $setWeightKg = isset($setData['weight']) && $setData['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$setData['weight']) : null;
                    $workoutExercise->exerciseSets()->create([
                        'set_number' => $setData['set_number'],
                        'reps' => $setData['reps'] ?? null,
                        'weight' => $setWeightKg,
                        'duration' => $setData['duration'] ?? null,
                        'rest_time' => $setData['rest_time'] ?? null,
                        'notes' => $setData['notes'] ?? null,
                        'is_completed' => false
                    ]);
                }
            }

            // Reload the workout exercise with updated data
            $workoutExercise->load(['exercise', 'exerciseSets' => function($query) {
                $query->orderBy('set_number');
            }]);

            Log::info('Exercise configuration updated successfully', [
                'workout_exercise_id' => $exerciseId,
                'trainer_id' => $trainer->id,
                'updated_fields' => array_keys($updateData),
                'sets_count' => $workoutExercise->exerciseSets->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'workout_exercise' => [
                        'id' => $workoutExercise->id,
                        'exercise_id' => $workoutExercise->exercise_id,
                        'exercise_name' => $workoutExercise->exercise->name ?? 'Unknown Exercise',
                        'sets' => $workoutExercise->sets,
                        'reps' => $workoutExercise->reps,
                        'weight' => $workoutExercise->weight,
                        'duration' => $workoutExercise->duration,
                        'rest_interval' => $workoutExercise->rest_interval,
                        'tempo' => $workoutExercise->tempo,
                        'notes' => $workoutExercise->notes,
                        'formatted_rest_interval' => $workoutExercise->formatted_rest_interval,
                        'formatted_weight' => $workoutExercise->formatted_weight,
                        'exercise_sets' => $workoutExercise->exerciseSets->map(function($set) {
                            return [
                                'id' => $set->id,
                                'set_number' => $set->set_number,
                                'reps' => $set->reps,
                                'weight' => $set->weight,
                                'duration' => $set->duration,
                                'rest_time' => $set->rest_time,
                                'notes' => $set->notes,
                                'formatted_weight' => $set->formatted_weight,
                                'formatted_duration' => $set->formatted_duration,
                                'formatted_rest_time' => $set->formatted_rest_time
                            ];
                        })
                    ]
                ],
                'message' => 'Exercise configuration updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to configure exercise: ' . $e->getMessage(), [
                'workout_exercise_id' => $exerciseId,
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Exercise configuration failed',
                'data' => ['error' => 'Unable to configure exercise' .$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Remove exercise from workout
     * 
     * @param int $workoutId
     * @param int $workoutExerciseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeExerciseFromWorkout($workoutId, $workoutExerciseId)
    {
        try {
            $trainer = Auth::user();
            
            // Verify workout belongs to trainer
            $workout = $trainer->workouts()->findOrFail($workoutId);
            
            // Find and delete the workout exercise
            $workoutExercise = $workout->workoutExercises()->findOrFail($workoutExerciseId);
            
            // Delete associated exercise sets first
            $workoutExercise->workoutExerciseSets()->delete();
            
            // Delete the workout exercise
            $workoutExercise->delete();

            Log::info('Exercise removed from workout successfully', [
                'workout_id' => $workoutId,
                'workout_exercise_id' => $workoutExerciseId,
                'trainer_id' => $trainer->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exercise removed from workout successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to remove exercise from workout: ' . $e->getMessage(), [
                'workout_id' => $workoutId,
                'workout_exercise_id' => $workoutExerciseId,
                'trainer_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove exercise',
                'data' => ['error' => 'Unable to remove exercise from workout']
            ], 500);
        }
    }

    /**
     * Reorder exercises in workout
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $workoutId
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderWorkoutExercises(Request $request, $workoutId)
    {
        try {
            $trainer = Auth::user();
            
            // Verify workout belongs to trainer
            $workout = $trainer->workouts()->findOrFail($workoutId);
            
            $validator = Validator::make($request->all(), [
                'exercise_ids' => 'required|array|min:1',
                'exercise_ids.*' => 'required|integer|exists:workout_exercises,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'data' => $validator->errors()
                ], 422);
            }
            
            $exerciseIds = $request->exercise_ids;
            
            // Verify all exercises belong to this workout
            $exerciseCount = $workout->workoutExercises()->whereIn('id', $exerciseIds)->count();
            if ($exerciseCount !== count($exerciseIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some exercises do not belong to this workout'
                ], 400);
            }
            
            // Reorder exercises
            foreach ($exerciseIds as $index => $exerciseId) {
                $workout->workoutExercises()->where('id', $exerciseId)->update(['order' => $index + 1]);
            }

            Log::info('Workout exercises reordered successfully', [
                'workout_id' => $workoutId,
                'exercise_ids' => $exerciseIds,
                'trainer_id' => $trainer->id
            ]);

            return response()->json([
                'success' => true,
                'data' => ['exercise_ids' => $exerciseIds],
                'message' => 'Exercises reordered successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reorder workout exercises: ' . $e->getMessage(), [
                'workout_id' => $workoutId,
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Reorder failed',
                'data' => ['error' => 'Unable to reorder exercises']
            ], 500);
        }
    }

    /**
     * Helper method to extract category from exercise description
     * 
     * @param string $description
     * @return string
     */
    private function extractCategoryFromDescription($description)
    {
        $categories = ['cardio', 'strength', 'yoga', 'pilates', 'hiit', 'functional'];
        
        foreach ($categories as $category) {
            if (stripos($description, $category) !== false) {
                return $category;
            }
        }
        
        return 'general';
    }

    /**
     * Helper method to extract equipment from exercise description
     * 
     * @param string $description
     * @return string
     */
    private function extractEquipmentFromDescription($description)
    {
        $equipment = ['dumbbells', 'barbells', 'kettlebells', 'resistance bands', 'yoga mat'];
        
        foreach ($equipment as $equip) {
            if (stripos($description, $equip) !== false) {
                return $equip;
            }
        }
        
        return 'none';
    }

    /**
     * Helper method to extract muscle group from exercise description
     * 
     * @param string $description
     * @return string
     */
    private function extractMuscleGroupFromDescription($description)
    {
        $muscleGroups = ['chest', 'back', 'shoulders', 'arms', 'legs', 'glutes', 'core'];
        
        foreach ($muscleGroups as $muscle) {
            if (stripos($description, $muscle) !== false) {
                return $muscle;
            }
        }
        
        return 'full_body';
    }
}