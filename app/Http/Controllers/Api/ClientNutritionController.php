<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NutritionPlan;
use App\Models\NutritionMeal;
use App\Models\NutritionMacro;
use App\Models\NutritionRestriction;
use App\Models\NutritionRecommendation;
use App\Models\FoodDiary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * ClientNutritionController
 * 
 * Handles API operations for clients (trainees) to view their assigned nutrition plans
 * Clients have read-only access to their nutrition plans and meals
 * 
 * @package App\Http\Controllers\Api
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class ClientNutritionController extends Controller
{
    /**
     * Get all nutrition plans assigned to the authenticated trainee
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $trainee = Auth::user();
            
            // Validate trainee role
            if ($trainee->role !== 'client') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Client role required.'
                ], 403);
            }
            
            $query = NutritionPlan::with([
                'trainer:id,name,email,profile_image',
                'meals:id,plan_id,title,meal_type,calories_per_serving',
                'dailyMacros:id,plan_id,protein,carbs,fats,total_calories',
                'restrictions:id,plan_id'
            ])->where('client_id', $trainee->id);
            
            // Apply status filter (only show active plans by default)
            $status = $request->get('status', 'active');
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            
            // Apply search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('plan_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('goal_type', 'like', "%{$search}%");
                });
            }
            
            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $plans = $query->orderBy('created_at', 'desc')->paginate($perPage);
            
            // Transform data
            $plans->getCollection()->transform(function ($plan) {
                return [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'description' => $plan->description,
                    'trainer' => [
                        'id' => $plan->trainer->id,
                        'name' => $plan->trainer->name,
                        'email' => $plan->trainer->email,
                        'profile_image' => $plan->trainer->profile_image ? asset('storage/' . $plan->trainer->profile_image) : null
                    ],
                    'goal_type' => $plan->goal_type,
                    'goal_type_display' => $plan->goal_type ? ucfirst(str_replace('_', ' ', $plan->goal_type)) : null,
                    'duration_days' => $plan->duration_days,
                    'duration_text' => $plan->duration_text,
                    'target_weight' => $plan->target_weight,
                    'status' => $plan->status,
                    'media_url' => $plan->media_url ? asset('storage/' . $plan->media_url) : null,
                    'meals_count' => $plan->meals->count(),
                    'total_calories' => $plan->meals->sum('calories_per_serving'),
                    'daily_macros' => $plan->dailyMacros ? [
                        'protein' => $plan->dailyMacros->protein,
                        'carbs' => $plan->dailyMacros->carbs,
                        'fats' => $plan->dailyMacros->fats,
                        'total_calories' => $plan->dailyMacros->total_calories,
                        'macro_distribution' => $plan->dailyMacros->macro_distribution
                    ] : null,
                    'has_restrictions' => $plan->restrictions !== null,
                    'restrictions_summary' => $plan->restrictions ? $plan->restrictions->restrictions_summary : 'No dietary restrictions',
                    'tags' => $plan->tags,
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Nutrition plans retrieved successfully',
                'data' => $plans
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve trainee nutrition plans: ' . $e->getMessage(), [
                'trainee_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve nutrition plans',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific nutrition plan assigned to the trainee
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $trainee = Auth::user();
            
            $plan = NutritionPlan::with([
                'trainer:id,name,email,profile_image',
                'meals' => function($query) {
                    $query->orderBy('sort_order');
                },
                'dailyMacros',
                'restrictions'
            ])->where('client_id', $trainee->id)
              ->findOrFail($id);
            
            // Calculate plan statistics
            $stats = [
                'total_meals' => $plan->meals->count(),
                'total_calories' => $plan->meals->sum('calories_per_serving'),
                'avg_prep_time' => $plan->meals->avg('prep_time'),
                'meal_types' => $plan->meals->groupBy('meal_type')->map->count(),
                'daily_protein' => $plan->meals->sum('protein_per_serving'),
                'daily_carbs' => $plan->meals->sum('carbs_per_serving'),
                'daily_fats' => $plan->meals->sum('fats_per_serving')
            ];
            
            // Group meals by type for better organization
            $mealsByType = $plan->meals->groupBy('meal_type')->map(function($meals, $type) {
                return [
                    'type' => $type,
                    'type_display' => ucfirst(str_replace('_', ' ', $type)),
                    'meals' => $meals->map(function($meal) {
                        return [
                            'id' => $meal->id,
                            'title' => $meal->title,
                            'description' => $meal->description,
                            'ingredients' => $meal->ingredients_array,
                            'instructions' => $meal->instructions_array,
                            'prep_time' => $meal->prep_time,
                            'cook_time' => $meal->cook_time,
                            'prep_time_formatted' => $meal->prep_time_formatted,
                            'cook_time_formatted' => $meal->cook_time_formatted,
                            'total_time' => $meal->total_time,
                            'servings' => $meal->servings,
                            'calories_per_serving' => $meal->calories_per_serving,
                            'protein_per_serving' => $meal->protein_per_serving,
                            'carbs_per_serving' => $meal->carbs_per_serving,
                            'fats_per_serving' => $meal->fats_per_serving,
                            'total_macros' => $meal->total_macros,
                            'image_url' => $meal->image_url ? asset('storage/' . $meal->image_url) : null,
                            'sort_order' => $meal->sort_order
                        ];
                    })->values()
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'message' => 'Nutrition plan retrieved successfully',
                'data' => [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'description' => $plan->description,
                    'trainer' => [
                        'id' => $plan->trainer->id,
                        'name' => $plan->trainer->name,
                        'email' => $plan->trainer->email,
                        'profile_image' => $plan->trainer->profile_image ? asset('storage/' . $plan->trainer->profile_image) : null
                    ],
                    'goal_type' => $plan->goal_type,
                    'goal_type_display' => $plan->goal_type ? ucfirst(str_replace('_', ' ', $plan->goal_type)) : null,
                    'duration_days' => $plan->duration_days,
                    'duration_text' => $plan->duration_text,
                    'target_weight' => $plan->target_weight,
                    'status' => $plan->status,
                    'media_url' => $plan->media_url ? asset('storage/' . $plan->media_url) : null,
                    'tags' => $plan->tags,
                    'meals_by_type' => $mealsByType,
                    'all_meals' => $plan->meals->map(function($meal) {
                        return [
                            'id' => $meal->id,
                            'title' => $meal->title,
                            'description' => $meal->description,
                            'meal_type' => $meal->meal_type,
                            'meal_type_display' => $meal->meal_type_display,
                            'ingredients' => $meal->ingredients_array,
                            'instructions' => $meal->instructions_array,
                            'prep_time' => $meal->prep_time,
                            'cook_time' => $meal->cook_time,
                            'prep_time_formatted' => $meal->prep_time_formatted,
                            'cook_time_formatted' => $meal->cook_time_formatted,
                            'total_time' => $meal->total_time,
                            'servings' => $meal->servings,
                            'calories_per_serving' => $meal->calories_per_serving,
                            'protein_per_serving' => $meal->protein_per_serving,
                            'carbs_per_serving' => $meal->carbs_per_serving,
                            'fats_per_serving' => $meal->fats_per_serving,
                            'total_macros' => $meal->total_macros,
                            'image_url' => $meal->image_url ? asset('storage/' . $meal->image_url) : null,
                            'sort_order' => $meal->sort_order
                        ];
                    }),
                    'daily_macros' => $plan->dailyMacros ? [
                        'protein' => $plan->dailyMacros->protein,
                        'carbs' => $plan->dailyMacros->carbs,
                        'fats' => $plan->dailyMacros->fats,
                        'total_calories' => $plan->dailyMacros->total_calories,
                        'fiber' => $plan->dailyMacros->fiber,
                        'sugar' => $plan->dailyMacros->sugar,
                        'sodium' => $plan->dailyMacros->sodium,
                        'sodium_formatted' => $plan->dailyMacros->sodium_formatted,
                        'water' => $plan->dailyMacros->water,
                        'water_formatted' => $plan->dailyMacros->water_formatted,
                        'macro_distribution' => $plan->dailyMacros->macro_distribution,
                        'is_balanced' => $plan->dailyMacros->is_balanced,
                        'protein_percentage' => $plan->dailyMacros->protein_percentage,
                        'carbs_percentage' => $plan->dailyMacros->carbs_percentage,
                        'fats_percentage' => $plan->dailyMacros->fats_percentage
                    ] : null,
                    'restrictions' => $plan->restrictions ? [
                        'dietary_preferences' => $plan->restrictions->dietary_preferences,
                        'allergens' => $plan->restrictions->allergens,
                        'medical_restrictions' => $plan->restrictions->medical_restrictions,
                        'custom_restrictions' => $plan->restrictions->custom_restrictions,
                        'restrictions_summary' => $plan->restrictions->restrictions_summary,
                        'restriction_badges' => $plan->restrictions->restriction_badges,
                        'has_dietary_preferences' => $plan->restrictions->has_dietary_preferences,
                        'has_allergens' => $plan->restrictions->has_allergens,
                        'has_medical_restrictions' => $plan->restrictions->has_medical_restrictions,
                        'notes' => $plan->restrictions->notes
                    ] : null,
                    'statistics' => $stats,
                    'progress_tracking' => [
                        'start_date' => $plan->created_at,
                        'days_active' => $plan->created_at->diffInDays(now()),
                        'completion_percentage' => $plan->duration_days ? min(100, ($plan->created_at->diffInDays(now()) / $plan->duration_days) * 100) : null
                    ],
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve nutrition plan via trainee API: ' . $e->getMessage(), [
                'trainee_id' => Auth::id(),
                'plan_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Nutrition plan not found or access denied'
            ], 404);
        }
    }

    /**
     * Get a specific meal from the trainee's nutrition plan
     * 
     * @param int $planId
     * @param int $mealId
     * @return JsonResponse
     */
    public function getMeal(int $planId, int $mealId): JsonResponse
    {
        try {
            $trainee = Auth::user();
            
            // Verify plan ownership
            $plan = NutritionPlan::where('client_id', $trainee->id)
                                ->findOrFail($planId);
            
            // Get the specific meal
            $meal = NutritionMeal::where('plan_id', $planId)
                                ->findOrFail($mealId);
            
            return response()->json([
                'success' => true,
                'message' => 'Meal retrieved successfully',
                'data' => [
                    'id' => $meal->id,
                    'title' => $meal->title,
                    'description' => $meal->description,
                    'meal_type' => $meal->meal_type,
                    'meal_type_display' => $meal->meal_type_display,
                    'ingredients' => $meal->ingredients_array,
                    'instructions' => $meal->instructions_array,
                    'prep_time' => $meal->prep_time,
                    'cook_time' => $meal->cook_time,
                    'prep_time_formatted' => $meal->prep_time_formatted,
                    'cook_time_formatted' => $meal->cook_time_formatted,
                    'total_time' => $meal->total_time,
                    'servings' => $meal->servings,
                    'calories_per_serving' => $meal->calories_per_serving,
                    'protein_per_serving' => $meal->protein_per_serving,
                    'carbs_per_serving' => $meal->carbs_per_serving,
                    'fats_per_serving' => $meal->fats_per_serving,
                    'total_macros' => $meal->total_macros,
                    'image_url' => $meal->image_url ? asset('storage/' . $meal->image_url) : null,
                    'plan' => [
                        'id' => $plan->id,
                        'plan_name' => $plan->plan_name,
                        'trainer_name' => $plan->trainer->name ?? 'Admin'
                    ],
                    'nutritional_info' => [
                        'per_serving' => [
                            'calories' => $meal->calories_per_serving,
                            'protein' => $meal->protein_per_serving,
                            'carbs' => $meal->carbs_per_serving,
                            'fats' => $meal->fats_per_serving
                        ],
                        'total' => $meal->total_macros,
                        'percentage_of_daily' => $plan->dailyMacros ? [
                            'calories' => $plan->dailyMacros->total_calories > 0 ? round(($meal->calories_per_serving / $plan->dailyMacros->total_calories) * 100, 1) : 0,
                            'protein' => $plan->dailyMacros->protein > 0 ? round(($meal->protein_per_serving / $plan->dailyMacros->protein) * 100, 1) : 0,
                            'carbs' => $plan->dailyMacros->carbs > 0 ? round(($meal->carbs_per_serving / $plan->dailyMacros->carbs) * 100, 1) : 0,
                            'fats' => $plan->dailyMacros->fats > 0 ? round(($meal->fats_per_serving / $plan->dailyMacros->fats) * 100, 1) : 0
                        ] : null
                    ],
                    'created_at' => $meal->created_at,
                    'updated_at' => $meal->updated_at
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve meal via trainee API: ' . $e->getMessage(), [
                'trainee_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_id' => $mealId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Meal not found or access denied'
            ], 404);
        }
    }

    /**
     * Get meals by type for a specific plan
     * 
     * @param int $planId
     * @param string $mealType
     * @return JsonResponse
     */
    public function getMealsByType(int $planId, string $mealType): JsonResponse
    {
        try {
            $trainee = Auth::user();
            
            // Verify plan ownership
            $plan = NutritionPlan::where('client_id', $trainee->id)
                                ->findOrFail($planId);
            
            // Validate meal type
            $validMealTypes = ['breakfast', 'lunch', 'dinner', 'snack', 'pre_workout', 'post_workout'];
            if (!in_array($mealType, $validMealTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid meal type'
                ], 400);
            }
            
            // Get meals of the specified type
            $meals = NutritionMeal::where('plan_id', $planId)
                                 ->where('meal_type', $mealType)
                                 ->orderBy('sort_order')
                                 ->get()
                                 ->map(function($meal) {
                                     return [
                                         'id' => $meal->id,
                                         'title' => $meal->title,
                                         'description' => $meal->description,
                                         'ingredients' => $meal->ingredients_array,
                                         'instructions' => $meal->instructions_array,
                                         'prep_time' => $meal->prep_time,
                                         'cook_time' => $meal->cook_time,
                                         'prep_time_formatted' => $meal->prep_time_formatted,
                                         'cook_time_formatted' => $meal->cook_time_formatted,
                                         'total_time' => $meal->total_time,
                                         'servings' => $meal->servings,
                                         'calories_per_serving' => $meal->calories_per_serving,
                                         'protein_per_serving' => $meal->protein_per_serving,
                                         'carbs_per_serving' => $meal->carbs_per_serving,
                                         'fats_per_serving' => $meal->fats_per_serving,
                                         'total_macros' => $meal->total_macros,
                                         'image_url' => $meal->image_url ? asset('storage/' . $meal->image_url) : null,
                                         'sort_order' => $meal->sort_order
                                     ];
                                 });
            
            // Calculate totals for this meal type
            $totals = [
                'total_meals' => $meals->count(),
                'total_calories' => $meals->sum('calories_per_serving'),
                'total_protein' => $meals->sum('protein_per_serving'),
                'total_carbs' => $meals->sum('carbs_per_serving'),
                'total_fats' => $meals->sum('fats_per_serving'),
                'avg_prep_time' => $meals->avg('prep_time')
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Meals retrieved successfully',
                'data' => [
                    'meal_type' => $mealType,
                    'meal_type_display' => ucfirst(str_replace('_', ' ', $mealType)),
                    'plan' => [
                        'id' => $plan->id,
                        'plan_name' => $plan->plan_name
                    ],
                    'meals' => $meals,
                    'totals' => $totals
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve meals by type via trainee API: ' . $e->getMessage(), [
                'trainee_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_type' => $mealType
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve meals'
            ], 500);
        }
    }

    /**
     * Get nutrition summary for the trainee
     * 
     * @return JsonResponse
     */
    public function getNutritionSummary(): JsonResponse
    {
        try {
            $trainee = Auth::user();
            
            // Get active plans
            $activePlans = NutritionPlan::with(['meals', 'dailyMacros', 'restrictions'])
                                       ->where('client_id', $trainee->id)
                                       ->where('status', 'active')
                                       ->get();
            
            // Calculate overall statistics
            $totalPlans = $activePlans->count();
            $totalMeals = $activePlans->sum(function($plan) {
                return $plan->meals->count();
            });
            
            $avgDailyCalories = $activePlans->avg(function($plan) {
                return $plan->dailyMacros ? $plan->dailyMacros->total_calories : 0;
            });
            
            // Get common restrictions across all plans
            $allRestrictions = $activePlans->map(function($plan) {
                return $plan->restrictions ? $plan->restrictions->all_restrictions : [];
            })->flatten()->unique()->values();
            
            // Get goal types distribution
            $goalTypes = $activePlans->groupBy('goal_type')->map->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Nutrition summary retrieved successfully',
                'data' => [
                    'overview' => [
                        'total_active_plans' => $totalPlans,
                        'total_meals' => $totalMeals,
                        'avg_daily_calories' => round($avgDailyCalories, 0),
                        'common_restrictions' => $allRestrictions,
                        'goal_types_distribution' => $goalTypes
                    ],
                    'active_plans' => $activePlans->map(function($plan) {
                        return [
                            'id' => $plan->id,
                            'plan_name' => $plan->plan_name,
                            'goal_type' => $plan->goal_type,
                            'duration_text' => $plan->duration_text,
                            'meals_count' => $plan->meals->count(),
                            'daily_calories' => $plan->dailyMacros ? $plan->dailyMacros->total_calories : 0,
                            'restrictions_summary' => $plan->restrictions ? $plan->restrictions->restrictions_summary : 'None',
                            'progress_percentage' => $plan->duration_days ? min(100, ($plan->created_at->diffInDays(now()) / $plan->duration_days) * 100) : null,
                            'created_at' => $plan->created_at
                        ];
                    }),
                    'recommendations' => [
                        'hydration_reminder' => 'Remember to drink at least 8 glasses of water daily',
                        'meal_timing' => 'Try to eat meals at consistent times each day',
                        'portion_control' => 'Follow the serving sizes specified in your meal plans'
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve nutrition summary via client API: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve nutrition summary'
            ], 500);
        }
    }

    /**
     * Get client's current nutrition plan with recommendations
     * 
     * Supports filtering by search terms and tags for enhanced user experience
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyPlan(Request $request)
    {
        try {
            $clientId = Auth::id();
            
            // Get filter parameters
            $search = $request->get('search');
            $tags = $request->get('tags');
            
            // Build the base query for nutrition plan
            $planQuery = NutritionPlan::with(['meals', 'recommendations', 'trainer'])
                ->where('client_id', $clientId)
                ->where('status', 'active');
            
            // Apply search filter to plan if provided
            if (!empty($search)) {
                $planQuery->where(function($query) use ($search) {
                    $query->where('plan_name', 'LIKE', "%{$search}%")
                          ->orWhere('description', 'LIKE', "%{$search}%")
                          ->orWhere('goal_type', 'LIKE', "%{$search}%");
                });
            }
            
            // Apply tags filter to plan if provided
            if (!empty($tags)) {
                $tagsArray = is_array($tags) ? $tags : explode(',', $tags);
                $tagsArray = array_map('trim', $tagsArray);
                
                $planQuery->where(function($query) use ($tagsArray) {
                    foreach ($tagsArray as $tag) {
                        $query->orWhereJsonContains('tags', $tag);
                    }
                });
            }
            
            $plan = $planQuery->first();
            
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active nutrition plan found matching the criteria'
                ], 404);
            }
            
            // Filter meals based on search criteria
            $filteredMeals = $plan->meals;
            
            if (!empty($search)) {
                $filteredMeals = $filteredMeals->filter(function($meal) use ($search) {
                    return stripos($meal->title, $search) !== false ||
                           stripos($meal->description, $search) !== false ||
                           stripos($meal->meal_type, $search) !== false ||
                           stripos($meal->ingredients, $search) !== false ||
                           stripos($meal->instructions, $search) !== false;
                });
            }
            
            // Group filtered meals by type
            $mealsByType = $filteredMeals->groupBy('meal_type')->map(function($meals) {
                return $meals->map(function($meal) {
                    return [
                        'id' => $meal->id,
                        'title' => $meal->title,
                        'description' => $meal->description,
                        'meal_type' => $meal->meal_type,
                        'meal_type_display' => $meal->meal_type_display,
                        'ingredients' => $meal->ingredients_array,
                        'instructions' => $meal->instructions_array,
                        'prep_time' => $meal->prep_time,
                        'cook_time' => $meal->cook_time,
                        'prep_time_formatted' => $meal->prep_time_formatted,
                        'cook_time_formatted' => $meal->cook_time_formatted,
                        'total_time' => $meal->total_time,
                        'servings' => $meal->servings,
                        'calories' => $meal->calories,
                        'protein' => $meal->protein,
                        'carbs' => $meal->carbs,
                        'fats' => $meal->fats,
                        'media_url' => $meal->media_url ? asset('storage/' . $meal->media_url) : null,
                        'sort_order' => $meal->sort_order
                    ];
                });
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'plan' => [
                        'id' => $plan->id,
                        'plan_name' => $plan->plan_name,
                        'description' => $plan->description,
                        'goal_type' => $plan->goal_type,
                        'duration_days' => $plan->duration_days,
                        'target_weight' => $plan->target_weight,
                        'status' => $plan->status,
                        'tags' => $plan->tags,
                        'image_url' => $plan->image_url ? asset('storage/' . $plan->image_url) : null,
                        'trainer' => $plan->trainer ? [
                            'id' => $plan->trainer->id,
                            'name' => $plan->trainer->name,
                            'email' => $plan->trainer->email,
                            'profile_image' => $plan->trainer->profile_image ? asset('storage/' . $plan->trainer->profile_image) : null
                        ] : null,
                        'created_at' => $plan->created_at,
                        'updated_at' => $plan->updated_at
                    ],
                    'recommendations' => $plan->recommendations ? [
                        'target_calories' => $plan->recommendations->target_calories,
                        'protein' => $plan->recommendations->protein,
                        'carbs' => $plan->recommendations->carbs,
                        'fats' => $plan->recommendations->fats,
                        'total_macro_calories' => $plan->recommendations->total_macro_calories,
                        'macro_distribution' => $plan->recommendations->macro_distribution
                    ] : null,
                    'meals_by_type' => $mealsByType,
                    'total_meals' => $filteredMeals->count(),
                    'filters_applied' => [
                        'search' => $search,
                        'tags' => $tags
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve client nutrition plan: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve nutrition plan' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available recipes/meals from global plans
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecipes(Request $request)
    {
        try {
            $query = NutritionMeal::query()
                ->whereHas('plan', function($q) {
                    $q->where('is_global', true);
                });
            
            // Filter by meal type if provided
            if ($request->has('meal_type') && $request->meal_type) {
                $query->where('meal_type', $request->meal_type);
            }
            
            // Filter by category if provided
            if ($request->has('category') && $request->category) {
                $query->whereHas('plan', function($q) use ($request) {
                    $q->where('category', $request->category);
                });
            }
            
            // Search by title or description
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $meals = $query->with('plan:id,plan_name,category')
                ->orderBy('title')
                ->paginate($perPage);

            $formattedMeals = $meals->getCollection()->map(function($meal) {
                return [
                    'id' => $meal->id,
                    'title' => $meal->title,
                    'description' => $meal->description,
                    'meal_type' => $meal->meal_type,
                    'meal_type_display' => $meal->meal_type_display,
                    'ingredients' => $meal->ingredients_array,
                    'instructions' => $meal->instructions_array,
                    'prep_time' => $meal->prep_time,
                    'cook_time' => $meal->cook_time,
                    'prep_time_formatted' => $meal->prep_time_formatted,
                    'cook_time_formatted' => $meal->cook_time_formatted,
                    'total_time' => $meal->total_time,
                    'servings' => $meal->servings,
                    'calories' => $meal->calories,
                    'protein' => $meal->protein,
                    'carbs' => $meal->carbs,
                    'fats' => $meal->fats,
                    'media_url' => $meal->media_url ? asset('storage/' . $meal->media_url) : null,
                    'plan' => [
                        'id' => $meal->plan->id,
                        'name' => $meal->plan->plan_name,
                        'category' => $meal->plan->category
                    ]
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'recipes' => $formattedMeals,
                    'pagination' => [
                        'current_page' => $meals->currentPage(),
                        'last_page' => $meals->lastPage(),
                        'per_page' => $meals->perPage(),
                        'total' => $meals->total(),
                        'from' => $meals->firstItem(),
                        'to' => $meals->lastItem()
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve recipes: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recipes'.$e->getMessage()
            ], 500);
        }
    }

    /**
     * Log food diary entry
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logFoodDiary(Request $request)
    {
        try {
            $request->validate([
                'meal_id' => 'nullable|exists:nutrition_meals,id',
                'meal_name' => 'required|string|max:255',
                'calories' => 'required|numeric|min:0',
                'protein' => 'required|numeric|min:0',
                'carbs' => 'required|numeric|min:0',
                'fats' => 'required|numeric|min:0',
                'logged_at' => 'nullable|date'
            ]);
            
            $clientId = Auth::id();
            
            $foodDiary = FoodDiary::create([
                'client_id' => $clientId,
                'meal_id' => $request->meal_id,
                'meal_name' => $request->meal_name,
                'calories' => $request->calories,
                'protein' => $request->protein,
                'carbs' => $request->carbs,
                'fats' => $request->fats,
                'logged_at' => $request->logged_at ? Carbon::parse($request->logged_at) : now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Food diary entry logged successfully',
                'data' => [
                    'entry' => [
                        'id' => $foodDiary->id,
                        'meal_name' => $foodDiary->meal_name,
                        'calories' => $foodDiary->calories,
                        'protein' => $foodDiary->protein,
                        'carbs' => $foodDiary->carbs,
                        'fats' => $foodDiary->fats,
                        'logged_at' => $foodDiary->logged_at,
                        'formatted_date' => $foodDiary->formatted_date,
                        'formatted_time' => $foodDiary->formatted_time
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to log food diary entry: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to log food diary entry'
            ], 500);
        }
    }

    /**
     * Get client's food diary entries
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFoodDiary(Request $request)
    {
        try {
            $request->validate([
                'date' => 'nullable|date',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);
            
            $clientId = Auth::id();
            $query = FoodDiary::where('client_id', $clientId);
            
            // Filter by specific date
            if ($request->has('date') && $request->date) {
                $query->whereDate('logged_at', $request->date);
            }
            // Filter by date range
            elseif ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('logged_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }
            // Default to current week if no date filters
            else {
                $query->thisWeek();
            }
            
            $perPage = $request->get('per_page', 20);
            $entries = $query->with('meal:id,title,meal_type')
                ->orderBy('logged_at', 'desc')
                ->paginate($perPage);
            
            // Group entries by date for summary
            $entriesByDate = $entries->getCollection()->groupBy(function($entry) {
                return $entry->logged_at->format('Y-m-d');
            });
            
            $dailySummaries = $entriesByDate->map(function($dayEntries) {
                return [
                    'date' => $dayEntries->first()->logged_at->format('Y-m-d'),
                    'total_calories' => $dayEntries->sum('calories'),
                    'total_protein' => $dayEntries->sum('protein'),
                    'total_carbs' => $dayEntries->sum('carbs'),
                    'total_fats' => $dayEntries->sum('fats'),
                    'entries_count' => $dayEntries->count(),
                    'entries' => $dayEntries->map(function($entry) {
                        return [
                            'id' => $entry->id,
                            'meal_name' => $entry->meal_name,
                            'calories' => $entry->calories,
                            'protein' => $entry->protein,
                            'carbs' => $entry->carbs,
                            'fats' => $entry->fats,
                            'logged_at' => $entry->logged_at,
                            'formatted_date' => $entry->formatted_date,
                            'formatted_time' => $entry->formatted_time,
                            'meal_type' => $entry->meal_type,
                            'meal' => $entry->meal ? [
                                'id' => $entry->meal->id,
                                'title' => $entry->meal->title,
                                'meal_type' => $entry->meal->meal_type
                            ] : null
                        ];
                    })
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'daily_summaries' => $dailySummaries,
                    'pagination' => [
                        'current_page' => $entries->currentPage(),
                        'last_page' => $entries->lastPage(),
                        'per_page' => $entries->perPage(),
                        'total' => $entries->total(),
                        'from' => $entries->firstItem(),
                        'to' => $entries->lastItem()
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve food diary: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve food diary'
            ], 500);
        }
    }

    /**
     * Update food diary entry
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $entryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFoodDiary(Request $request, $entryId)
    {
        try {
            $request->validate([
                'meal_name' => 'required|string|max:255',
                'calories' => 'required|numeric|min:0',
                'protein' => 'required|numeric|min:0',
                'carbs' => 'required|numeric|min:0',
                'fats' => 'required|numeric|min:0',
                'logged_at' => 'nullable|date'
            ]);
            
            $clientId = Auth::id();
            
            $entry = FoodDiary::where('id', $entryId)
                ->where('client_id', $clientId)
                ->first();
            
            if (!$entry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Food diary entry not found'
                ], 404);
            }
            
            $entry->update([
                'meal_name' => $request->meal_name,
                'calories' => $request->calories,
                'protein' => $request->protein,
                'carbs' => $request->carbs,
                'fats' => $request->fats,
                'logged_at' => $request->logged_at ? Carbon::parse($request->logged_at) : $entry->logged_at
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Food diary entry updated successfully',
                'data' => [
                    'entry' => [
                        'id' => $entry->id,
                        'meal_name' => $entry->meal_name,
                        'calories' => $entry->calories,
                        'protein' => $entry->protein,
                        'carbs' => $entry->carbs,
                        'fats' => $entry->fats,
                        'logged_at' => $entry->logged_at,
                        'formatted_date' => $entry->formatted_date,
                        'formatted_time' => $entry->formatted_time
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update food diary entry: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'entry_id' => $entryId,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update food diary entry'
            ], 500);
        }
    }

    /**
     * Delete food diary entry
     * 
     * @param int $entryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFoodDiary($entryId)
    {
        try {
            $clientId = Auth::id();
            
            $entry = FoodDiary::where('id', $entryId)
                ->where('client_id', $clientId)
                ->first();
            
            if (!$entry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Food diary entry not found'
                ], 404);
            }
            
            $entry->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Food diary entry deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete food diary entry: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'entry_id' => $entryId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete food diary entry'
            ], 500);
        }
    }

    /**
     * Get current nutrition recommendations for the authenticated client
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentRecommendations(): JsonResponse
    {
        try {
            $clientId = Auth::id();
            
            // Get the client's active nutrition plan with recommendations
            $plan = NutritionPlan::where('client_id', $clientId)
                ->where('status', 'active')
                ->with(['recommendations', 'trainer:id,name,email,profile_image'])
                ->first();
            
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active nutrition plan found'
                ], 404);
            }
            
            // Get recommendations or create default ones if none exist
            $recommendations = $plan->recommendations;
            if (!$recommendations) {
                // Create default recommendations based on goal type
                $defaultCalories = $this->getDefaultCaloriesByGoalType($plan->goal_type);
                $recommendations = NutritionRecommendation::create([
                    'plan_id' => $plan->id,
                    'target_calories' => $defaultCalories,
                    'protein' => round($defaultCalories * 0.25 / 4), // 25% of calories from protein
                    'carbs' => round($defaultCalories * 0.45 / 4),   // 45% of calories from carbs
                    'fats' => round($defaultCalories * 0.30 / 9)     // 30% of calories from fats
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Current recommendations retrieved successfully',
                'data' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'goal_type' => $plan->goal_type,
                    'goal_type_display' => $plan->goal_type ? ucfirst(str_replace('_', ' ', $plan->goal_type)) : null,
                    'trainer' => $plan->trainer ? [
                        'id' => $plan->trainer->id,
                        'name' => $plan->trainer->name,
                        'email' => $plan->trainer->email,
                        'profile_image' => $plan->trainer->profile_image ? asset('storage/' . $plan->trainer->profile_image) : null
                    ] : null,
                    'recommendations' => [
                        'target_calories' => $recommendations->target_calories,
                        'protein' => $recommendations->protein,
                        'carbs' => $recommendations->carbs,
                        'fats' => $recommendations->fats,
                        'macro_distribution' => $recommendations->macro_distribution,
                        'total_macro_calories' => $recommendations->total_macro_calories
                    ],
                    'last_updated' => $recommendations->updated_at,
                    'created_at' => $recommendations->created_at
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve current recommendations: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve current recommendations'
            ], 500);
        }
    }

    /**
     * Update current nutrition recommendations for the authenticated client
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCurrentRecommendations(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'target_calories' => 'required|numeric|min:800|max:5000',
                'protein' => 'required|numeric|min:20|max:300',
                'carbs' => 'required|numeric|min:50|max:500',
                'fats' => 'required|numeric|min:20|max:200'
            ]);
            
            $clientId = Auth::id();
            
            // Get the client's active nutrition plan
            $plan = NutritionPlan::where('client_id', $clientId)
                ->where('status', 'active')
                ->with(['recommendations', 'trainer:id,name,email'])
                ->first();
            
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active nutrition plan found'
                ], 404);
            }
            
            // Update or create recommendations
            $recommendations = $plan->recommendations;
            if ($recommendations) {
                $recommendations->update([
                    'target_calories' => $request->target_calories,
                    'protein' => $request->protein,
                    'carbs' => $request->carbs,
                    'fats' => $request->fats
                ]);
            } else {
                $recommendations = NutritionRecommendation::create([
                    'plan_id' => $plan->id,
                    'target_calories' => $request->target_calories,
                    'protein' => $request->protein,
                    'carbs' => $request->carbs,
                    'fats' => $request->fats
                ]);
            }
            
            // Log the update for audit purposes
            Log::info('Client updated nutrition recommendations', [
                'client_id' => $clientId,
                'plan_id' => $plan->id,
                'old_values' => $plan->recommendations ? $plan->recommendations->getOriginal() : null,
                'new_values' => $recommendations->toArray()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Nutrition recommendations updated successfully',
                'data' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'goal_type' => $plan->goal_type,
                    'goal_type_display' => $plan->goal_type ? ucfirst(str_replace('_', ' ', $plan->goal_type)) : null,
                    'recommendations' => [
                        'target_calories' => $recommendations->target_calories,
                        'protein' => $recommendations->protein,
                        'carbs' => $recommendations->carbs,
                        'fats' => $recommendations->fats,
                        'macro_distribution' => $recommendations->macro_distribution,
                        'total_macro_calories' => $recommendations->total_macro_calories
                    ],
                    'updated_at' => $recommendations->updated_at
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Failed to update nutrition recommendations: ' . $e->getMessage(), [
                'client_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update nutrition recommendations'
            ], 500);
        }
    }

    /**
     * Get list of available nutrition goal types
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNutritionGoalTypes(): JsonResponse
    {
        try {
            // Define available nutrition goal types with descriptions and typical calorie ranges
            $goalTypes = [
                [
                    'value' => 'weight_loss',
                    'label' => 'Weight Loss',
                    'description' => 'Designed to create a caloric deficit for healthy weight reduction',
                    'typical_calorie_range' => '1200-1800 calories/day',
                    'macro_focus' => 'Higher protein, moderate carbs, controlled fats',
                    'icon' => 'trending-down',
                    'color' => '#e74c3c'
                ],
                [
                    'value' => 'weight_gain',
                    'label' => 'Weight Gain',
                    'description' => 'Focused on healthy weight gain and muscle building',
                    'typical_calorie_range' => '2200-3000+ calories/day',
                    'macro_focus' => 'High protein, high carbs, healthy fats',
                    'icon' => 'trending-up',
                    'color' => '#27ae60'
                ],
                [
                    'value' => 'muscle_gain',
                    'label' => 'Muscle Gain',
                    'description' => 'Optimized for lean muscle development and strength',
                    'typical_calorie_range' => '2000-2800 calories/day',
                    'macro_focus' => 'Very high protein, moderate carbs, moderate fats',
                    'icon' => 'activity',
                    'color' => '#3498db'
                ],
                [
                    'value' => 'maintenance',
                    'label' => 'Maintenance',
                    'description' => 'Balanced nutrition to maintain current weight and health',
                    'typical_calorie_range' => '1800-2400 calories/day',
                    'macro_focus' => 'Balanced macronutrients for overall health',
                    'icon' => 'target',
                    'color' => '#f39c12'
                ],
                [
                    'value' => 'athletic_performance',
                    'label' => 'Athletic Performance',
                    'description' => 'High-performance nutrition for athletes and active individuals',
                    'typical_calorie_range' => '2500-4000+ calories/day',
                    'macro_focus' => 'High carbs for energy, high protein for recovery',
                    'icon' => 'zap',
                    'color' => '#9b59b6'
                ],
                [
                    'value' => 'general_health',
                    'label' => 'General Health',
                    'description' => 'Overall wellness and disease prevention focused nutrition',
                    'typical_calorie_range' => '1600-2200 calories/day',
                    'macro_focus' => 'Balanced with emphasis on whole foods',
                    'icon' => 'heart',
                    'color' => '#1abc9c'
                ]
            ];
            
            // Get statistics about goal type usage from existing plans
            $goalTypeStats = NutritionPlan::selectRaw('goal_type, COUNT(*) as count')
                ->whereNotNull('goal_type')
                ->groupBy('goal_type')
                ->pluck('count', 'goal_type')
                ->toArray();
            
            // Add usage statistics to each goal type
            foreach ($goalTypes as &$goalType) {
                $goalType['usage_count'] = $goalTypeStats[$goalType['value']] ?? 0;
                $goalType['is_popular'] = ($goalTypeStats[$goalType['value']] ?? 0) > 5;
            }
            
            // Sort by popularity (most used first)
            usort($goalTypes, function($a, $b) {
                return $b['usage_count'] <=> $a['usage_count'];
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Nutrition goal types retrieved successfully',
                'data' => [
                    'goal_types' => $goalTypes,
                    'total_types' => count($goalTypes),
                    'most_popular' => $goalTypes[0]['value'] ?? null,
                    'recommendations' => [
                        'beginner' => 'general_health',
                        'weight_focused' => ['weight_loss', 'weight_gain'],
                        'fitness_focused' => ['muscle_gain', 'athletic_performance'],
                        'lifestyle' => ['maintenance', 'general_health']
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve nutrition goal types: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve nutrition goal types'
            ], 500);
        }
    }

    /**
     * Get default calories based on goal type
     * 
     * @param string|null $goalType
     * @return int
     */
    private function getDefaultCaloriesByGoalType(?string $goalType): int
    {
        $defaults = [
            'weight_loss' => 1500,
            'weight_gain' => 2500,
            'muscle_gain' => 2200,
            'maintenance' => 2000,
            'athletic_performance' => 2800,
            'general_health' => 1800
        ];
        
        return $defaults[$goalType] ?? 2000;
    }
}