<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Models\Goal;

/**
 * API Goal Controller
 * 
 * Handles goal management operations via API
 * Provides complete CRUD operations for user goals
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\API
 * @category    Goal Management API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ApiGoalController extends ApiBaseController
{
    /**
     * Get all goals with optional filtering and pagination
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Goal::query();
            
            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            
            // Apply search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);
            
            // Paginate results
            $perPage = (int) $request->input('per_page', 15);
            $goals = $query->paginate($perPage);
            
            $responseData = [
                'data' => $goals->items(),
                'pagination' => [
                    'total' => $goals->total(),
                    'per_page' => $goals->perPage(),
                    'current_page' => $goals->currentPage(),
                    'last_page' => $goals->lastPage(),
                    'from' => $goals->firstItem(),
                    'to' => $goals->lastItem(),
                    'has_more_pages' => $goals->hasMorePages()
                ]
            ];
            
            return $this->sendResponse($responseData, 'Goals retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve goals: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve goals'], 500);
        }
    }
    
    /**
     * Create a new goal
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate goal data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|in:0,1',
                'target_date' => 'nullable|date|after:today',
                'priority' => 'nullable|in:low,medium,high',
                'category' => 'nullable|string|max:100'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Create goal with authenticated user
            $goalData = $request->validated();
            $goalData['user_id'] = Auth::id();
            
            $goal = Goal::create($goalData);
            
            // Prepare response data
            $responseData = [
                'id' => $goal->id,
                'name' => $goal->name,
                'description' => $goal->description,
                'status' => $goal->status,
                'target_date' => $goal->target_date,
                'priority' => $goal->priority,
                'category' => $goal->category,
                'user_id' => $goal->user_id,
                'created_at' => $goal->created_at->toISOString(),
                'updated_at' => $goal->updated_at->toISOString()
            ];
            
            Log::info('Goal created successfully', [
                'goal_id' => $goal->id,
                'user_id' => Auth::id(),
                'goal_name' => $goal->name
            ]);
            
            return $this->sendResponse($responseData, 'Goal created successfully', 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create goal: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Creation Failed', ['error' => 'Unable to create goal'], 500);
        }
    }
    
    /**
     * Show a specific goal
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Goal  $goal
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Goal $goal): JsonResponse
    {
        try {
            // Check if user has access to this goal (optional - implement based on requirements)
            // if ($goal->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            //     return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            // }
            
            $goalData = [
                'id' => $goal->id,
                'name' => $goal->name,
                'description' => $goal->description,
                'status' => $goal->status,
                'target_date' => $goal->target_date,
                'priority' => $goal->priority,
                'category' => $goal->category,
                'user_id' => $goal->user_id,
                'created_at' => $goal->created_at->toISOString(),
                'updated_at' => $goal->updated_at->toISOString()
            ];
            
            return $this->sendResponse($goalData, 'Goal retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve goal: ' . $e->getMessage(), [
                'goal_id' => $goal->id ?? 'unknown',
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve goal'], 500);
        }
    }
    
    /**
     * Update a specific goal
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Goal  $goal
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Goal $goal): JsonResponse
    {
        try {
            // Check if user has access to update this goal
            // if ($goal->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            //     return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            // }
            
            // Validate update data
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'sometimes|required|in:0,1',
                'target_date' => 'nullable|date|after:today',
                'priority' => 'nullable|in:low,medium,high',
                'category' => 'nullable|string|max:100'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Update goal
            $goal->update($request->validated());
            
            // Prepare response data
            $responseData = [
                'id' => $goal->id,
                'name' => $goal->name,
                'description' => $goal->description,
                'status' => $goal->status,
                'target_date' => $goal->target_date,
                'priority' => $goal->priority,
                'category' => $goal->category,
                'user_id' => $goal->user_id,
                'created_at' => $goal->created_at->toISOString(),
                'updated_at' => $goal->updated_at->toISOString()
            ];
            
            Log::info('Goal updated successfully', [
                'goal_id' => $goal->id,
                'user_id' => Auth::id(),
                'updated_fields' => array_keys($request->validated())
            ]);
            
            return $this->sendResponse($responseData, 'Goal updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to update goal: ' . $e->getMessage(), [
                'goal_id' => $goal->id ?? 'unknown',
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Update Failed', ['error' => 'Unable to update goal'], 500);
        }
    }
    
    /**
     * Delete a specific goal
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Goal  $goal
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Goal $goal): JsonResponse
    {
        try {
            // Check if user has access to delete this goal
            // if ($goal->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            //     return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            // }
            
            $goalId = $goal->id;
            $goalName = $goal->name;
            
            $goal->delete();
            
            Log::info('Goal deleted successfully', [
                'goal_id' => $goalId,
                'goal_name' => $goalName,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendResponse([], 'Goal deleted successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to delete goal: ' . $e->getMessage(), [
                'goal_id' => $goal->id ?? 'unknown',
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Deletion Failed', ['error' => 'Unable to delete goal'], 500);
        }
    }
    
    /**
     * Toggle goal status (active/inactive)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Goal  $goal
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, Goal $goal): JsonResponse
    {
        try {
            // Check if user has access to toggle this goal
            // if ($goal->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            //     return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            // }
            
            $goal->status = $goal->status ? 0 : 1;
            $goal->save();
            
            $responseData = [
                'id' => $goal->id,
                'name' => $goal->name,
                'status' => $goal->status,
                'updated_at' => $goal->updated_at->toISOString()
            ];
            
            Log::info('Goal status toggled', [
                'goal_id' => $goal->id,
                'new_status' => $goal->status,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendResponse($responseData, 'Goal status updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle goal status: ' . $e->getMessage(), [
                'goal_id' => $goal->id ?? 'unknown',
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Toggle Failed', ['error' => 'Unable to toggle goal status'], 500);
        }
    }
    
    /**
     * Search goals with advanced filtering
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2|max:255',
                'status' => 'nullable|in:0,1',
                'priority' => 'nullable|in:low,medium,high',
                'category' => 'nullable|string|max:100',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $query = Goal::query();
            
            // Apply search query
            $searchTerm = $request->query;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('category', 'like', "%{$searchTerm}%");
            });
            
            // Apply additional filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }
            
            if ($request->filled('category')) {
                $query->where('category', 'like', "%{$request->category}%");
            }
            
            // Order by relevance (name matches first, then description)
            $query->orderByRaw("CASE WHEN name LIKE '%{$searchTerm}%' THEN 1 ELSE 2 END")
                  ->orderBy('created_at', 'desc');
            
            // Paginate results
            $perPage = (int) $request->input('per_page', 15);
            $goals = $query->paginate($perPage);
            
            $responseData = [
                'query' => $searchTerm,
                'data' => $goals->items(),
                'pagination' => [
                    'total' => $goals->total(),
                    'per_page' => $goals->perPage(),
                    'current_page' => $goals->currentPage(),
                    'last_page' => $goals->lastPage(),
                    'from' => $goals->firstItem(),
                    'to' => $goals->lastItem(),
                    'has_more_pages' => $goals->hasMorePages()
                ]
            ];
            
            return $this->sendResponse($responseData, 'Search completed successfully');
            
        } catch (\Exception $e) {
            Log::error('Goal search failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'search_query' => $request->query,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Search Failed', ['error' => 'Unable to perform search'], 500);
        }
    }
    
    /**
     * Bulk update goals
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'goal_ids' => 'required|array|min:1',
                'goal_ids.*' => 'integer|exists:goals,id',
                'status' => 'nullable|in:0,1',
                'priority' => 'nullable|in:low,medium,high',
                'category' => 'nullable|string|max:100'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $goalIds = $request->goal_ids;
            $updateData = [];
            
            if ($request->filled('status')) {
                $updateData['status'] = $request->status;
            }
            
            if ($request->filled('priority')) {
                $updateData['priority'] = $request->priority;
            }
            
            if ($request->filled('category')) {
                $updateData['category'] = $request->category;
            }
            
            if (empty($updateData)) {
                return $this->sendError('No Updates', ['error' => 'No valid update fields provided'], 400);
            }
            
            $updateData['updated_at'] = now();
            
            // Perform bulk update
            $updatedCount = Goal::whereIn('id', $goalIds)->update($updateData);
            
            Log::info('Bulk goal update completed', [
                'updated_count' => $updatedCount,
                'goal_ids' => $goalIds,
                'update_data' => $updateData,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendResponse([
                'updated_count' => $updatedCount,
                'goal_ids' => $goalIds
            ], "Successfully updated {$updatedCount} goals");
            
        } catch (\Exception $e) {
            Log::error('Bulk goal update failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Bulk Update Failed', ['error' => 'Unable to perform bulk update'], 500);
        }
    }
    
    /**
     * Bulk delete goals
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'goal_ids' => 'required|array|min:1',
                'goal_ids.*' => 'integer|exists:goals,id'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $goalIds = $request->goal_ids;
            
            // Perform bulk delete
            $deletedCount = Goal::whereIn('id', $goalIds)->delete();
            
            Log::warning('Bulk goal deletion completed', [
                'deleted_count' => $deletedCount,
                'goal_ids' => $goalIds,
                'user_id' => Auth::id()
            ]);
            
            return $this->sendResponse([
                'deleted_count' => $deletedCount,
                'goal_ids' => $goalIds
            ], "Successfully deleted {$deletedCount} goals");
            
        } catch (\Exception $e) {
            Log::error('Bulk goal deletion failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Bulk Delete Failed', ['error' => 'Unable to perform bulk deletion'], 500);
        }
    }
}
