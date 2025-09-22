<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * Handles both web and API requests, including DataTable AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Handle DataTable AJAX request
            if ($request->ajax() || $request->has('draw')) {
                return $this->getDataTableData($request);
            }

            $query = Workout::query()->with(['user:id,name,email,role']);

            // Apply filters
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            // Filter by trainer (user_id)
            if ($request->filled('trainer_id')) {
                $query->where('user_id', $request->trainer_id);
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
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
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

            // Handle web request - Get trainers for filter dropdown
            $user = Auth::user();
            $trainers = \App\Models\User::where('role', 'trainer')
                                        ->select('id', 'name', 'email')
                                        ->orderBy('name')
                                        ->get();
            
            return view('admin.workouts.index', compact('workouts', 'user', 'trainers'));
            
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
     * Handle DataTable AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function getDataTableData(Request $request): JsonResponse
    {
        try {
            $query = Workout::query()->with(['user:id,name,email,role', 'videos:id,workout_id']);

            // Handle DataTable search
            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Get total count before pagination
            $totalRecords = Workout::count();
            $filteredRecords = $query->count();

            // Handle DataTable ordering
            if ($request->filled('order.0.column')) {
                $columns = ['id', 'name', 'trainer', 'duration', 'total_videos', 'price', 'is_active', 'thumbnail', 'created_at', 'actions'];
                $orderColumn = $columns[$request->input('order.0.column')] ?? 'id';
                $orderDirection = $request->input('order.0.dir', 'desc');
                
                if ($orderColumn === 'trainer') {
                    $query->join('users', 'workouts.user_id', '=', 'users.id')
                          ->orderBy('users.name', $orderDirection)
                          ->select('workouts.*');
                } else {
                    $query->orderBy($orderColumn, $orderDirection);
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Handle pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            $workouts = $query->skip($start)->take($length)->get();

            // Format data for DataTable
            $data = $workouts->map(function ($workout) {
                return [
                    'id' => $workout->id,
                    'name' => $workout->name,
                    'trainer' => $workout->user ? $workout->user->name : 'Unknown',
                    'formatted_duration' => $workout->duration ? $workout->duration . ' min' : 'N/A',
                    'total_videos' => $workout->videos->count(),
                    'formatted_price' => $workout->price > 0 ? '$' . number_format($workout->price, 2) : 'Free',
                    'is_active' => $workout->is_active ? 
                        '<span class="badge bg-success">Active</span>' : 
                        '<span class="badge bg-danger">Inactive</span>',
                    'thumbnail' => $workout->thumbnail ? 
                        '<img src="' . asset('storage/' . $workout->thumbnail) . '" alt="Thumbnail" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">' : 
                        '',
                    'created_at' => $workout->created_at->format('M d, Y'),
                    'actions' => $this->getActionButtons($workout->id)
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('DataTable request failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
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
     * Generate action buttons for DataTable
     * 
     * @param  int  $workoutId
     * @return string
     */
    private function getActionButtons(int $workoutId): string
    {
        return '
            <div class="d-flex justify-content-end">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-success" onclick="window.location.href=\'/admin/workouts/' . $workoutId . '\'" title="View">
                    <i class="ri-eye-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="window.location.href=\'/admin/workouts/' . $workoutId . '/edit\'" title="Edit">
                    <i class="ri-edit-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="showVideos(' . $workoutId . ')" title="Manage Videos">
                    <i class="ri-video-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-info" onclick="toggleStatus(' . $workoutId . ')" title="Toggle Status">
                    <i class="ri-toggle-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteWorkout(' . $workoutId . ')" title="Delete">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
            </div>
        ';
    }

    /**
     * Show the form for creating a new workout
     */
    public function create(): View
    {
        $user = Auth::user();
        
        // Get all trainers for the dropdown
        $trainers = \App\Models\User::where('role', 'trainer')
                                    ->select('id', 'name', 'email')
                                    ->orderBy('name')
                                    ->get();
        
        return view('admin.workouts.create', compact('user', 'trainers'));
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

        return view('admin.workouts.show', compact('workout', 'user'));
    }

    /**
     * Show the form for editing the specified workout
     */
    public function edit(Workout $workout): View
    {
        $user = Auth::user();
        
        // Get all trainers for the dropdown
        $trainers = \App\Models\User::where('role', 'trainer')
                                    ->select('id', 'name', 'email')
                                    ->orderBy('name')
                                    ->get();
        
        return view('admin.workouts.edit', compact('workout', 'user', 'trainers'));
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
            return view('admin.workouts.statistics', compact('stats'));
            
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
     * Get workout statistics for dashboard
     */
    public function stats(Request $request)
    {
        try {
            $stats = [
                'total_workouts' => Workout::count(),
                'active_workouts' => Workout::active()->count(),
                'total_videos' => \App\Models\WorkoutVideo::count(),
                'paid_workouts' => Workout::where('price', '>', 0)->count(),
            ];
            
            if ($this->isApiRequest($request)) {
                return $this->sendApiResponse($stats, 'Workout statistics retrieved successfully');
            }
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }

    /**
     * Get videos list for a workout
     */
    public function videosList(Workout $workout, Request $request)
    {
        try {
            $videos = $workout->videos()
                ->orderBy('order')
                ->get(['id', 'title', 'duration', 'video_type', 'video_url', 'thumbnail']);
            
            if ($this->isApiRequest($request)) {
                return $this->sendApiResponse($videos, 'Workout videos retrieved successfully');
            }
            
            return response()->json([
                'success' => true,
                'data' => $videos
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve workout videos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve videos'
            ], 500);
        }
    }

    /**
     * Toggle workout active status
     */
    public function toggleStatus(Workout $workout, Request $request)
    {
        try {
            $workout->update(['is_active' => !$workout->is_active]);
            $status = $workout->is_active ? 'activated' : 'deactivated';
            
            if ($this->isApiRequest($request) || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Workout {$status} successfully",
                    'data' => [
                        'is_active' => $workout->is_active,
                        'status' => $status
                    ]
                ]);
            }

            return redirect()
                ->back()
                ->with('success', "Workout {$status} successfully");
                
        } catch (\Exception $e) {
            Log::error('Failed to toggle workout status: ' . $e->getMessage());
            
            if ($this->isApiRequest($request) || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle workout status'
                ], 500);
            }
            
            return redirect()
                ->back()
                ->with('error', 'Failed to toggle workout status');
        }
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