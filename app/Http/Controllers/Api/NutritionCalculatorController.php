<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NutritionCalculatorService;
use App\Models\NutritionPlan;
use App\Models\NutritionRecommendation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * NutritionCalculatorController
 * 
 * Handles API requests for nutrition calculations
 * Provides endpoints for calculating, saving, and retrieving nutrition plans
 * 
 * @package App\Http\Controllers\Api
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionCalculatorController extends Controller
{
    /**
     * @var NutritionCalculatorService
     */
    protected $calculatorService;

    /**
     * Constructor
     */
    public function __construct(NutritionCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    /**
     * Calculate nutrition recommendations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculate(Request $request): JsonResponse
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'weight' => 'required|numeric|min:1|max:1100',
                'height' => 'required|numeric|min:1|max:300',
                'age' => 'required|integer|min:1|max:120',
                'gender' => 'required|in:male,female',
                'activity_level' => [
                    'required',
                    Rule::in(array_keys($this->calculatorService->getActivityLevels()))
                ],
                'goal_type' => [
                    'required',
                    Rule::in(array_keys($this->calculatorService->getGoalTypes()))
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculate nutrition recommendations
            $userData = $request->only(['weight', 'height', 'age', 'gender', 'activity_level', 'goal_type']);
            $calculations = $this->calculatorService->calculateNutrition($userData);

            return response()->json([
                'success' => true,
                'message' => 'Nutrition recommendations calculated successfully',
                'data' => $calculations
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'error' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to calculate nutrition recommendations: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate nutrition recommendations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Save nutrition plan with calculated recommendations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function savePlan(Request $request): JsonResponse
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'client_id' => 'required|exists:users,id',
                'plan_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'weight' => 'required|numeric|min:1|max:1100',
                'height' => 'required|numeric|min:1|max:300',
                'age' => 'required|integer|min:1|max:120',
                'gender' => 'required|in:male,female',
                'activity_level' => [
                    'required',
                    Rule::in(array_keys($this->calculatorService->getActivityLevels()))
                ],
                'goal_type' => [
                    'required',
                    Rule::in(array_keys($this->calculatorService->getGoalTypes()))
                ],
                'target_weight' => 'nullable|numeric|min:1|max:1100',
                'duration_days' => 'nullable|integer|min:1|max:365'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify client exists and user has permission
            $client = User::findOrFail($request->client_id);
            $currentUser = Auth::user();

            // Check if user is trainer/admin or the client themselves
            if ($currentUser->role !== 'admin' && 
                $currentUser->role !== 'trainer' && 
                $currentUser->id !== $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to create plan for this client'
                ], 403);
            }

            // Calculate nutrition recommendations
            $userData = $request->only(['weight', 'height', 'age', 'gender', 'activity_level', 'goal_type']);
            $calculations = $this->calculatorService->calculateNutrition($userData);

            // Create nutrition plan
            $targetWeightKg = $request->target_weight !== null ? round(((float)$request->target_weight) / 2.20462, 2) : null;
            $plan = NutritionPlan::create([
                'client_id' => $request->client_id,
                'trainer_id' => $currentUser->role === 'trainer' ? $currentUser->id : null,
                'plan_name' => $request->plan_name,
                'description' => $request->description,
                'goal_type' => $request->goal_type,
                'target_weight' => $targetWeightKg,
                'duration_days' => $request->duration_days ?? 30,
                'status' => 'active',
                'is_global' => false
            ]);

            // Create nutrition recommendations
            $recommendation = NutritionRecommendation::create([
                'plan_id' => $plan->id,
                'target_calories' => $calculations['recommendations']['target_calories'],
                'protein' => $calculations['recommendations']['protein'],
                'carbs' => $calculations['recommendations']['carbs'],
                'fats' => $calculations['recommendations']['fats'],
                'bmr' => $calculations['calculations']['bmr'],
                'tdee' => $calculations['calculations']['tdee'],
                'activity_level' => $request->activity_level,
                'calculation_method' => 'mifflin_st_jeor',
                'macro_distribution' => json_encode($calculations['recommendations']['macro_distribution'])
            ]);

            // Load relationships for response
            $plan->load(['client:id,name,email', 'trainer:id,name,email', 'recommendations']);

            return response()->json([
                'success' => true,
                'message' => 'Nutrition plan created successfully',
                'data' => [
                    'plan' => [
                        'id' => $plan->id,
                        'plan_name' => $plan->plan_name,
                        'description' => $plan->description,
                        'goal_type' => $plan->goal_type,
                        'target_weight' => $plan->target_weight,
                        'duration_days' => $plan->duration_days,
                        'status' => $plan->status,
                        'client' => $plan->client,
                        'trainer' => $plan->trainer,
                        'created_at' => $plan->created_at
                    ],
                    'recommendations' => [
                        'target_calories' => $recommendation->target_calories,
                        'protein' => $recommendation->protein,
                        'carbs' => $recommendation->carbs,
                        'fats' => $recommendation->fats,
                        'bmr' => $recommendation->bmr,
                        'tdee' => $recommendation->tdee,
                        'activity_level' => $recommendation->activity_level,
                        'macro_distribution' => json_decode($recommendation->macro_distribution, true)
                    ],
                    'calculations' => $calculations
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to save nutrition plan: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save nutrition plan',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get client's nutrition plan with calculated recommendations
     * 
     * @param int $clientId
     * @return JsonResponse
     */
    public function getClientNutrition(int $clientId): JsonResponse
    {
        try {
            // Verify client exists
            $client = User::findOrFail($clientId);
            $currentUser = Auth::user();

            // Check permissions
            if ($currentUser->role !== 'admin' && 
                $currentUser->role !== 'trainer' && 
                $currentUser->id !== $client->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to access this client\'s nutrition data'
                ], 403);
            }

            // Get active nutrition plan with recommendations
            $plan = NutritionPlan::with([
                'client:id,name,email,profile_image',
                'trainer:id,name,email,profile_image',
                'recommendations',
                'meals' => function($query) {
                    $query->orderBy('sort_order')->limit(10);
                },
                'recipes' => function($query) {
                    $query->orderBy('sort_order')->limit(6);
                }
            ])
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active nutrition plan found for this client',
                    'data' => null
                ], 404);
            }

            // Format response
            $response = [
                'plan' => [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'description' => $plan->description,
                    'goal_type' => $plan->goal_type,
                    'goal_type_display' => ucfirst(str_replace('_', ' ', $plan->goal_type)),
                    'target_weight' => $plan->target_weight,
                    'duration_days' => $plan->duration_days,
                    'status' => $plan->status,
                    'client' => $plan->client,
                    'trainer' => $plan->trainer,
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at
                ],
                'recommendations' => null,
                'meals_count' => $plan->meals->count(),
                'recipes_count' => $plan->recipes->count(),
                'recent_meals' => $plan->meals->map(function($meal) {
                    return [
                        'id' => $meal->id,
                        'title' => $meal->title,
                        'meal_type' => $meal->meal_type,
                        'calories_per_serving' => $meal->calories_per_serving
                    ];
                }),
                'recent_recipes' => $plan->recipes->map(function($recipe) {
                    return [
                        'id' => $recipe->id,
                        'title' => $recipe->title,
                        'description' => $recipe->short_description,
                        'image_url' => $recipe->image_url
                    ];
                })
            ];

            // Add recommendations if available
            if ($plan->recommendations) {
                $response['recommendations'] = [
                    'target_calories' => $plan->recommendations->target_calories,
                    'protein' => $plan->recommendations->protein,
                    'carbs' => $plan->recommendations->carbs,
                    'fats' => $plan->recommendations->fats,
                    'bmr' => $plan->recommendations->bmr,
                    'tdee' => $plan->recommendations->tdee,
                    'activity_level' => $plan->recommendations->activity_level,
                    'activity_level_display' => $this->calculatorService->getActivityLevels()[$plan->recommendations->activity_level]['label'] ?? 'Unknown',
                    'calculation_method' => $plan->recommendations->calculation_method,
                    'macro_distribution' => json_decode($plan->recommendations->macro_distribution, true),
                    'created_at' => $plan->recommendations->created_at
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Client nutrition data retrieved successfully',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve client nutrition data: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'client_id' => $clientId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve client nutrition data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available activity levels
     * 
     * @return JsonResponse
     */
    public function getActivityLevels(): JsonResponse
    {
        try {
            $activityLevels = $this->calculatorService->getActivityLevels();

            return response()->json([
                'success' => true,
                'message' => 'Activity levels retrieved successfully',
                'data' => array_values($activityLevels)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve activity levels: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity levels'
            ], 500);
        }
    }

    /**
     * Get available goal types
     * 
     * @return JsonResponse
     */
    public function getGoalTypes(): JsonResponse
    {
        try {
            $goalTypes = $this->calculatorService->getGoalTypes();

            return response()->json([
                'success' => true,
                'message' => 'Goal types retrieved successfully',
                'data' => array_values($goalTypes)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve goal types: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve goal types'
            ], 500);
        }
    }

    /**
     * Recalculate nutrition recommendations for existing plan
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function recalculate(Request $request, int $planId): JsonResponse
    {
        try {
            // Find the plan
            $plan = NutritionPlan::with(['client', 'recommendations'])->findOrFail($planId);
            $currentUser = Auth::user();

            // Check permissions
            if ($currentUser->role !== 'admin' && 
                $currentUser->role !== 'trainer' && 
                $currentUser->id !== $plan->client_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to modify this nutrition plan'
                ], 403);
            }

            // Validation rules
            $validator = Validator::make($request->all(), [
                'weight' => 'required|numeric|min:1|max:1100',
                'height' => 'required|numeric|min:1|max:300',
                'age' => 'required|integer|min:1|max:120',
                'gender' => 'required|in:male,female',
                'activity_level' => [
                    'required',
                    Rule::in(array_keys($this->calculatorService->getActivityLevels()))
                ],
                'goal_type' => [
                    'required',
                    Rule::in(array_keys($this->calculatorService->getGoalTypes()))
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculate new nutrition recommendations
            $userData = $request->only(['weight', 'height', 'age', 'gender', 'activity_level', 'goal_type']);
            $calculations = $this->calculatorService->calculateNutrition($userData);

            // Update plan goal type if changed
            if ($plan->goal_type !== $request->goal_type) {
                $plan->update(['goal_type' => $request->goal_type]);
            }

            // Update or create recommendations
            if ($plan->recommendations) {
                $plan->recommendations->update([
                    'target_calories' => $calculations['recommendations']['target_calories'],
                    'protein' => $calculations['recommendations']['protein'],
                    'carbs' => $calculations['recommendations']['carbs'],
                    'fats' => $calculations['recommendations']['fats'],
                    'bmr' => $calculations['calculations']['bmr'],
                    'tdee' => $calculations['calculations']['tdee'],
                    'activity_level' => $request->activity_level,
                    'macro_distribution' => json_encode($calculations['recommendations']['macro_distribution'])
                ]);
            } else {
                NutritionRecommendation::create([
                    'plan_id' => $plan->id,
                    'target_calories' => $calculations['recommendations']['target_calories'],
                    'protein' => $calculations['recommendations']['protein'],
                    'carbs' => $calculations['recommendations']['carbs'],
                    'fats' => $calculations['recommendations']['fats'],
                    'bmr' => $calculations['calculations']['bmr'],
                    'tdee' => $calculations['calculations']['tdee'],
                    'activity_level' => $request->activity_level,
                    'calculation_method' => 'mifflin_st_jeor',
                    'macro_distribution' => json_encode($calculations['recommendations']['macro_distribution'])
                ]);
            }

            // Reload plan with updated recommendations
            $plan->load(['recommendations']);

            return response()->json([
                'success' => true,
                'message' => 'Nutrition recommendations recalculated successfully',
                'data' => [
                    'plan_id' => $plan->id,
                    'recommendations' => [
                        'target_calories' => $plan->recommendations->target_calories,
                        'protein' => $plan->recommendations->protein,
                        'carbs' => $plan->recommendations->carbs,
                        'fats' => $plan->recommendations->fats,
                        'bmr' => $plan->recommendations->bmr,
                        'tdee' => $plan->recommendations->tdee,
                        'activity_level' => $plan->recommendations->activity_level,
                        'macro_distribution' => json_decode($plan->recommendations->macro_distribution, true)
                    ],
                    'calculations' => $calculations
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to recalculate nutrition recommendations: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'plan_id' => $planId,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to recalculate nutrition recommendations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}