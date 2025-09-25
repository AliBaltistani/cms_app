<?php

/**
 * Specialization API Controller
 * 
 * Handles specialization API operations for mobile and web applications
 * Provides read-only access to specializations for trainers and clients
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Api
 * @category    Trainer Specializations API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 * @created     2025-01-19
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SpecializationController extends Controller
{
    /**
     * Display a listing of all specializations with optional filtering
     * 
     * Supports filtering by:
     * - status: Filter by active/inactive status
     * - search: Search in specialization name and description
     * - with_trainers: Include only specializations that have trainers
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:active,inactive,all',
                'search' => 'nullable|string|max:255',
                'with_trainers' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get validated parameters with defaults
            $status = $request->get('status', 'active'); // Default to active only
            $search = $request->get('search');
            $withTrainers = $request->get('with_trainers', false);
            $perPage = $request->get('per_page', 15);

            // Build query
            $query = Specialization::query();

            // Include trainer count
            $query->withCount('trainers');

            // Apply status filter
            if ($status === 'active') {
                $query->where('status', true);
            } elseif ($status === 'inactive') {
                $query->where('status', false);
            }
            // If status is 'all', don't apply any status filter

            // Apply search filter
            if (!empty($search)) {
                $searchTerm = trim($search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Filter specializations that have trainers
            if ($withTrainers) {
                $query->has('trainers');
            }

            // Order by name for consistent results
            $query->orderBy('name', 'asc');

            // Get paginated results
            $specializations = $query->paginate($perPage);

            // Transform data for API response
            $transformedData = $specializations->getCollection()->map(function ($specialization) {
                return [
                    'id' => $specialization->id,
                    'name' => $specialization->name,
                    'description' => $specialization->description,
                    'status' => $specialization->status,
                    'status_text' => $specialization->status ? 'Active' : 'Inactive',
                    'trainers_count' => $specialization->trainers_count,
                    'created_at' => $specialization->created_at->toISOString(),
                    'created_at_formatted' => $specialization->created_at->format('d/m/Y H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Specializations retrieved successfully',
                'data' => [
                    'specializations' => $transformedData,
                    'pagination' => [
                        'current_page' => $specializations->currentPage(),
                        'last_page' => $specializations->lastPage(),
                        'per_page' => $specializations->perPage(),
                        'total' => $specializations->total(),
                        'from' => $specializations->firstItem(),
                        'to' => $specializations->lastItem()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in SpecializationController@index: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving specializations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified specialization with detailed information
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            // Validate ID parameter
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid specialization ID provided'
                ], 422);
            }

            // Find specialization with trainer relationships
            $specialization = Specialization::with([
                'trainers' => function ($query) {
                    $query->select('users.id', 'users.name', 'users.email', 'users.designation', 
                                 'users.profile_image', 'users.experience', 'users.created_at')
                          ->where('users.role', 'trainer');
                }
            ])->withCount('trainers')->find($id);

            if (!$specialization) {
                return response()->json([
                    'success' => false,
                    'message' => 'Specialization not found'
                ], 404);
            }

            // Transform trainer data
            $trainers = $specialization->trainers->map(function ($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email,
                    'designation' => $trainer->designation,
                    'profile_image' => $trainer->profile_image ? asset('storage/' . $trainer->profile_image) : null,
                    'experience' => $trainer->experience,
                    'joined_date' => $trainer->created_at->format('d/m/Y')
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Specialization retrieved successfully',
                'data' => [
                    'specialization' => [
                        'id' => $specialization->id,
                        'name' => $specialization->name,
                        'description' => $specialization->description,
                        'status' => $specialization->status,
                        'status_text' => $specialization->status ? 'Active' : 'Inactive',
                        'trainers_count' => $specialization->trainers_count,
                        'created_at' => $specialization->created_at->toISOString(),
                        'created_at_formatted' => $specialization->created_at->format('d/m/Y H:i'),
                        'trainers' => $trainers
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in SpecializationController@show: ' . $e->getMessage(), [
                'specialization_id' => $id,
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the specialization',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get only active specializations for dropdown/selection purposes
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveSpecializations(): JsonResponse
    {
        try {
            $specializations = Specialization::active()
                                           ->select('id', 'name', 'description')
                                           ->withCount('trainers')
                                           ->orderBy('name', 'asc')
                                           ->get();

            // Transform data for simple selection
            $transformedData = $specializations->map(function ($specialization) {
                return [
                    'id' => $specialization->id,
                    'name' => $specialization->name,
                    'description' => $specialization->description,
                    'trainers_count' => $specialization->trainers_count
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Active specializations retrieved successfully',
                'data' => [
                    'specializations' => $transformedData,
                    'total_count' => $transformedData->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in SpecializationController@getActiveSpecializations: ' . $e->getMessage(), [
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving active specializations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get specializations statistics for dashboard/analytics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_specializations' => Specialization::count(),
                'active_specializations' => Specialization::where('status', true)->count(),
                'inactive_specializations' => Specialization::where('status', false)->count(),
                'specializations_with_trainers' => Specialization::has('trainers')->count(),
                'specializations_without_trainers' => Specialization::doesntHave('trainers')->count(),
                'most_popular_specializations' => Specialization::withCount('trainers')
                    ->where('status', true)
                    ->orderBy('trainers_count', 'desc')
                    ->limit(5)
                    ->get(['id', 'name', 'trainers_count'])
                    ->map(function ($spec) {
                        return [
                            'id' => $spec->id,
                            'name' => $spec->name,
                            'trainers_count' => $spec->trainers_count
                        ];
                    })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Specializations statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in SpecializationController@getStatistics: ' . $e->getMessage(), [
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Search specializations by name with autocomplete support
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:1|max:255',
                'limit' => 'nullable|integer|min:1|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = trim($request->get('query'));
            $limit = $request->get('limit', 10);

            $specializations = Specialization::where('status', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->withCount('trainers')
                ->orderBy('name', 'asc')
                ->limit($limit)
                ->get(['id', 'name', 'description']);

            $transformedData = $specializations->map(function ($specialization) {
                return [
                    'id' => $specialization->id,
                    'name' => $specialization->name,
                    'description' => $specialization->description,
                    'trainers_count' => $specialization->trainers_count
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Search results retrieved successfully',
                'data' => [
                    'specializations' => $transformedData,
                    'query' => $query,
                    'results_count' => $transformedData->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in SpecializationController@search: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching specializations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}