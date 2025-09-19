<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NutritionPlan;
use App\Models\NutritionMeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

/**
 * TraineeNutritionController
 * 
 * Handles API operations for trainees (clients) to view their assigned nutrition plans
 * Trainees have read-only access to their nutrition plans and meals
 * 
 * @package App\Http\Controllers\Api
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class TraineeNutritionController extends Controller
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
            Log::error('Failed to retrieve nutrition summary via trainee API: ' . $e->getMessage(), [
                'trainee_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve nutrition summary'
            ], 500);
        }
    }
}