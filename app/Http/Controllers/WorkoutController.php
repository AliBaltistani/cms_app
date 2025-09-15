<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Requests\UpdateWorkoutRequest;
use App\Models\Workout;
use App\Services\WorkoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Workout Controller
 * 
 * Handles workout management operations for both web and API requests
 * Provides complete CRUD operations and additional workout functionality
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    Workout Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class WorkoutController extends Controller
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
     * Display a listing of workouts
     * Handles both web and API requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Workout::query();

            // Apply filters
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }
            
            if ($request->filled('difficulty')) {
                $query->where('difficulty', $request->difficulty);
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
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // Include videos if requested
            if ($request->boolean('include_videos')) {
                $query->withVideos();
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = $request->get('per_page', 15);
            $workouts = $query->paginate($perPage);
            
            // Handle API request
            if ($this->isApiRequest($request)) {
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
                
                return $this->sendApiResponse($responseData, 'Workouts retrieved successfully');
            }

            // Handle web request
            $user = Auth::user();
            return view('workouts.index', compact('workouts', 'user'));
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workouts: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($this->isApiRequest($request)) {
                return $this->sendApiError('Retrieval Failed', ['error' => 'Unable to retrieve workouts'], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to retrieve workouts');
        }
    }

    /**
     * Show the form for creating a new workout
     */
    public function create(): View
    {
        $user = Auth::user();
        return view('workouts.create', compact('user'));
    }

    /**
     * Store a newly created workout
     */
    public function store(StoreWorkoutRequest $request): RedirectResponse
    {
        try {
            $workout = $this->workoutService->createWorkout($request->validated());
            
            return redirect()
                ->route('workouts.show', $workout)
                ->with('success', 'Workout created successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create workout: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified workout
     */
    public function show(Workout $workout): View
    {
        $workout->load('videos');

        $user = Auth::user();

        return view('workouts.show', compact('workout', 'user'));
    }

    /**
     * Show the form for editing the specified workout
     */
    public function edit(Workout $workout): View
    {
        $user = Auth::user();
        return view('workouts.edit', compact('workout', 'user'));
    }

    /**
     * Update the specified workout
     */
    public function update(UpdateWorkoutRequest $request, Workout $workout): RedirectResponse
    {
        try {
            $updatedWorkout = $this->workoutService->updateWorkout($workout, $request->validated());
            
            return redirect()
                ->route('workouts.show', $updatedWorkout)
                ->with('success', 'Workout updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update workout: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified workout
     */
    public function destroy(Workout $workout): RedirectResponse
    {
        try {
            $this->workoutService->deleteWorkout($workout);
            
            return redirect()
                ->route('workouts.index')
                ->with('success', 'Workout deleted successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete workout: ' . $e->getMessage());
        }
    }

    /**
     * Get workout statistics
     * Handles both web and API requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request)
    {
        try {
            $stats = [
                'total_workouts' => Workout::count(),
                'active_workouts' => Workout::active()->count(),
                'inactive_workouts' => Workout::where('is_active', false)->count(),
                'total_videos' => \App\Models\WorkoutVideo::count(),
                'by_difficulty' => Workout::selectRaw('difficulty, COUNT(*) as count')
                    ->groupBy('difficulty')
                    ->pluck('count', 'difficulty'),
                'by_category' => Workout::selectRaw('category, COUNT(*) as count')
                    ->whereNotNull('category')
                    ->groupBy('category')
                    ->pluck('count', 'category'),
                'average_duration' => round(Workout::avg('duration'), 2),
                'recent_workouts' => Workout::latest()->take(5)->get(['id', 'name', 'created_at']),
                'popular_categories' => Workout::selectRaw('category, COUNT(*) as count')
                    ->whereNotNull('category')
                    ->groupBy('category')
                    ->orderByDesc('count')
                    ->take(5)
                    ->get()
            ];
            
            // Handle API request
            if ($this->isApiRequest($request)) {
                return $this->sendApiResponse($stats, 'Workout statistics retrieved successfully');
            }
            
            // Handle web request
            return view('workouts.statistics', compact('stats'));
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout statistics: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($this->isApiRequest($request)) {
                return $this->sendApiError('Statistics Failed', ['error' => 'Unable to retrieve statistics'], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to retrieve statistics');
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
                'category' => 'nullable|string|max:100',
                'difficulty' => 'nullable|string|max:50',
                'duration_min' => 'nullable|integer|min:1',
                'duration_max' => 'nullable|integer|max:300',
                'is_active' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);
            
            if ($validator->fails()) {
                return $this->sendApiError('Validation Error', $validator->errors()->toArray(), 422);
            }
            
            $searchResults = $this->workoutService->searchWorkouts($request->validated());
            
            $responseData = [
                'query' => $request->query,
                'data' => $searchResults->items(),
                'pagination' => [
                    'total' => $searchResults->total(),
                    'per_page' => $searchResults->perPage(),
                    'current_page' => $searchResults->currentPage(),
                    'last_page' => $searchResults->lastPage(),
                    'from' => $searchResults->firstItem(),
                    'to' => $searchResults->lastItem(),
                    'has_more_pages' => $searchResults->hasMorePages()
                ]
            ];
            
            return $this->sendApiResponse($responseData, 'Search completed successfully');
            
        } catch (\Exception $e) {
            Log::error('Workout search failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'search_query' => $request->query,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Search Failed', ['error' => 'Unable to perform search'], 500);
        }
    }
    
    /**
     * Get workout categories
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $categories = Workout::selectRaw('category, COUNT(*) as workout_count')
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderBy('category')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->category,
                        'workout_count' => $item->workout_count,
                        'slug' => \Illuminate\Support\Str::slug($item->category)
                    ];
                });
            
            return $this->sendApiResponse($categories, 'Categories retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout categories: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Categories Failed', ['error' => 'Unable to retrieve categories'], 500);
        }
    }
    
    /**
     * Add workout to favorites (placeholder - implement based on requirements)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Workout  $workout
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToFavorites(Request $request, Workout $workout): JsonResponse
    {
        try {
            // This is a placeholder implementation
            // You would typically have a favorites table or relationship
            
            Log::info('Workout added to favorites', [
                'workout_id' => $workout->id,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendApiResponse([
                'workout_id' => $workout->id,
                'favorited' => true
            ], 'Workout added to favorites successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to add workout to favorites: ' . $e->getMessage(), [
                'workout_id' => $workout->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Favorite Failed', ['error' => 'Unable to add to favorites'], 500);
        }
    }
    
    /**
     * Remove workout from favorites (placeholder - implement based on requirements)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Workout  $workout
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFromFavorites(Request $request, Workout $workout): JsonResponse
    {
        try {
            // This is a placeholder implementation
            // You would typically have a favorites table or relationship
            
            Log::info('Workout removed from favorites', [
                'workout_id' => $workout->id,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendApiResponse([
                'workout_id' => $workout->id,
                'favorited' => false
            ], 'Workout removed from favorites successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to remove workout from favorites: ' . $e->getMessage(), [
                'workout_id' => $workout->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Unfavorite Failed', ['error' => 'Unable to remove from favorites'], 500);
        }
    }
    
    /**
     * Bulk update workouts
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'workout_ids' => 'required|array|min:1',
                'workout_ids.*' => 'integer|exists:workouts,id',
                'is_active' => 'nullable|boolean',
                'category' => 'nullable|string|max:100',
                'difficulty' => 'nullable|string|max:50'
            ]);
            
            if ($validator->fails()) {
                return $this->sendApiError('Validation Error', $validator->errors()->toArray(), 422);
            }
            
            $workoutIds = $request->workout_ids;
            $updateData = [];
            
            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->boolean('is_active');
            }
            
            if ($request->filled('category')) {
                $updateData['category'] = $request->category;
            }
            
            if ($request->filled('difficulty')) {
                $updateData['difficulty'] = $request->difficulty;
            }
            
            if (empty($updateData)) {
                return $this->sendApiError('No Updates', ['error' => 'No valid update fields provided'], 400);
            }
            
            $updateData['updated_at'] = now();
            
            // Perform bulk update
            $updatedCount = Workout::whereIn('id', $workoutIds)->update($updateData);
            
            Log::info('Bulk workout update completed', [
                'updated_count' => $updatedCount,
                'workout_ids' => $workoutIds,
                'update_data' => $updateData,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendApiResponse([
                'updated_count' => $updatedCount,
                'workout_ids' => $workoutIds
            ], "Successfully updated {$updatedCount} workouts");
            
        } catch (\Exception $e) {
            Log::error('Bulk workout update failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Bulk Update Failed', ['error' => 'Unable to perform bulk update'], 500);
        }
    }
    
    /**
     * Bulk delete workouts
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'workout_ids' => 'required|array|min:1',
                'workout_ids.*' => 'integer|exists:workouts,id'
            ]);
            
            if ($validator->fails()) {
                return $this->sendApiError('Validation Error', $validator->errors()->toArray(), 422);
            }
            
            $workoutIds = $request->workout_ids;
            
            // Perform bulk delete
            $deletedCount = Workout::whereIn('id', $workoutIds)->delete();
            
            Log::warning('Bulk workout deletion completed', [
                'deleted_count' => $deletedCount,
                'workout_ids' => $workoutIds,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendApiResponse([
                'deleted_count' => $deletedCount,
                'workout_ids' => $workoutIds
            ], "Successfully deleted {$deletedCount} workouts");
            
        } catch (\Exception $e) {
            Log::error('Bulk workout deletion failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendApiError('Bulk Delete Failed', ['error' => 'Unable to perform bulk deletion'], 500);
        }
    }

    /**
     * Toggle workout active status
     */
    public function toggleStatus(Workout $workout): RedirectResponse
    {
        $workout->update(['is_active' => !$workout->is_active]);

        $status = $workout->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Workout {$status} successfully");
    }

    /**
     * Duplicate a workout
     */
    public function duplicate(Workout $workout): RedirectResponse
    {
        try {
            $duplicatedWorkout = $this->workoutService->duplicateWorkout($workout);
            
            return redirect()
                ->route('workouts.show', $duplicatedWorkout)
                ->with('success', 'Workout duplicated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to duplicate workout: ' . $e->getMessage());
        }
    }
}