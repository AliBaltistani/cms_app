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
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Carbon;

/**
 * TrainerNutritionController
 * 
 * Handles API operations for trainers to manage nutrition plans
 * Trainers can create plans for assigned trainees and manage their own plans
 * 
 * @package App\Http\Controllers\Api
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class TrainerNutritionController extends Controller
{
    /**
     * Get all nutrition plans created by the authenticated trainer
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            // Validate trainer role
            if ($trainer->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Trainer role required.'
                ], 403);
            }
            
            $query = NutritionPlan::with([
                'client:id,name,email',
                'meals:id,plan_id,title,meal_type,calories',
                'dailyMacros:id,plan_id,protein,carbs,fats,total_calories',
                'restrictions:id,plan_id'
            ])->where('trainer_id', $trainer->id);
            
            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }
            
            if ($request->has('goal_type') && $request->goal_type !== 'all') {
                $query->where('goal_type', $request->goal_type);
            }
            
            // Apply search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('plan_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('client', function($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%");
                      });
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
                    'client' => $plan->client,
                    'goal_type' => $plan->goal_type,
                    'duration_days' => $plan->duration_days,
                    'duration_text' => $plan->duration_text,
                    'target_weight' => $plan->target_weight,
                    'status' => $plan->status,
                    'is_featured' => $plan->is_featured,
                    'media_url' => $plan->media_url ? asset('storage/' . $plan->media_url) : null,
                    'meals_count' => $plan->meals->count(),
                    'total_calories' => $plan->meals->sum('calories'),
                    'daily_macros' => $plan->dailyMacros,
                    'has_restrictions' => $plan->restrictions !== null,
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
            Log::error('Failed to retrieve trainer nutrition plans: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
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
     * Create a new nutrition plan for a trainee
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            // Validate trainer role
            if ($trainer->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Trainer role required.'
                ], 403);
            }
            
            // Enhanced validation rules according to UI requirements
            $validator = Validator::make($request->all(), [
                'client_id' => 'required|exists:users,id',
                'plan_name' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                
                // Meals array validation
                'meals' => 'required|array|min:1',
                'meals.*.name' => 'required|string|max:255',
                'meals.*.type' => 'required|in:breakfast,lunch,dinner,snack',
                'meals.*.description' => 'nullable|string|max:500',
                'meals.*.media_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                
                // Macronutrient targets validation
                'macros' => 'required|array',
                'macros.protein' => 'required|numeric|min:0|max:500',
                'macros.carbs' => 'required|numeric|min:0|max:800',
                'macros.fats' => 'required|numeric|min:0|max:200',
                'macros.total_calories' => 'required|numeric|min:0|max:5000',
                
                // Dietary restrictions validation
                'restrictions' => 'required|array',
                'restrictions.vegetarian' => 'nullable|boolean',
                'restrictions.gluten_free' => 'nullable|boolean',
                'restrictions.dairy_free' => 'nullable|boolean',
                'restrictions.notes' => 'nullable|string|max:1000',
                
                // Optional fields
                'goal_type' => 'nullable|in:weight_loss,weight_gain,maintenance,muscle_gain',
                'duration_days' => 'nullable|integer|min:1|max:365',
                'target_weight' => 'nullable|numeric|min:30|max:300',
                'is_featured' => 'nullable|boolean',
                'tags' => 'nullable|array'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verify client exists and has client role
            $client = User::where('id', $request->client_id)
                         ->where('role', 'client')
                         ->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid client ID or client not found'
                ], 404);
            }
            
            // Handle image upload if provided
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('nutrition_plans', $imageName, 'public');
                $imageUrl = $imagePath;
            }
            
            // Create nutrition plan
            $plan = NutritionPlan::create([
                'trainer_id' => $trainer->id,
                'client_id' => $request->client_id,
                'plan_name' => $request->plan_name,
                'description' => $request->description ?? '',
                'image_url' => $imageUrl,
                'goal_type' => $request->goal_type ?? 'maintenance',
                'duration_days' => $request->duration_days ?? 30,
                'target_weight' => $request->target_weight,
                'status' => 'active',
                'is_global' => false,
                'is_featured' => $request->boolean('is_featured'),
                'tags' => $request->tags ?? []
            ]);
            

            
            // Create meals for the nutrition plan
            if ($request->has('meals') && is_array($request->meals)) {
                foreach ($request->meals as $index => $mealData) {
                    NutritionMeal::create([
                        'plan_id' => $plan->id,
                        'title' => $mealData['name'],
                        'description' => $mealData['description'] ?? '',
                        'meal_type' => $mealData['type'],
                        'ingredients' => $mealData['ingredients'] ?? [],
                        'instructions' => $mealData['instructions'] ?? [],
                        'prep_time' => $mealData['prep_time'] ?? 0,
                        'cook_time' => $mealData['cook_time'] ?? 0,
                        'servings' => $mealData['servings'] ?? 1,
                        'calories' => $mealData['calories_per_serving'] ?? 0,
                        'protein' => $mealData['protein_per_serving'] ?? 0,
                        'carbs' => $mealData['carbs_per_serving'] ?? 0,
                        'fats' => $mealData['fats_per_serving'] ?? 0,
                        'sort_order' => $index + 1
                    ]);
                }
            }
            
            // Create macronutrient targets
            if ($request->has('macros') && $request->macros) {
                NutritionMacro::create([
                    'plan_id' => $plan->id,
                    'protein' => $request->macros['protein'],
                    'carbs' => $request->macros['carbs'],
                    'fats' => $request->macros['fats'],
                    'total_calories' => $request->macros['total_calories'],
                    'macro_type' => 'daily_target'
                ]);
            }
            
            // Create dietary restrictions if provided
            if ($request->has('restrictions') && $request->restrictions) {
                $restrictionData = array_merge(
                    ['plan_id' => $plan->id],
                    $request->restrictions
                );
                
                // Ensure boolean fields are properly cast
                $booleanFields = [
                    'vegetarian', 'vegan', 'pescatarian', 'keto', 'paleo', 'mediterranean',
                    'low_carb', 'low_fat', 'high_protein', 'gluten_free', 'dairy_free',
                    'nut_free', 'soy_free', 'egg_free', 'shellfish_free', 'fish_free',
                    'sesame_free', 'diabetic_friendly'
                ];
                
                foreach ($booleanFields as $field) {
                    if (isset($restrictionData[$field])) {
                        $restrictionData[$field] = (bool) $restrictionData[$field];
                    }
                }
                
                NutritionRestriction::create($restrictionData);
            }
            
            // Load relationships for response
            $plan->load([
                'client:id,name,email',
                'meals:id,plan_id,title,meal_type,calories,protein,carbs,fats,sort_order',
                'dailyMacros:id,plan_id,protein,carbs,fats,total_calories',
                'restrictions:id,plan_id,vegetarian,vegan,gluten_free,dairy_free,keto,paleo,custom_restrictions,notes'
            ]);
            
            // Log the creation
            Log::info('Comprehensive nutrition plan created by trainer via API', [
                'trainer_id' => $trainer->id,
                'client_id' => $request->client_id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->plan_name,
                'meals_count' => count($request->meals ?? []),
                'has_image' => !is_null($imageUrl),
                'has_restrictions' => !is_null($request->restrictions)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Nutrition plan created successfully with all components',
                'data' => [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'description' => $plan->description,
                    'image_url' => $plan->image_url ? asset('storage/' . $plan->image_url) : null,
                    'client' => $plan->client,
                    'goal_type' => $plan->goal_type,
                    'duration_days' => $plan->duration_days,
                    'target_weight' => $plan->target_weight,
                    'status' => $plan->status,
                    'tags' => $plan->tags,
                    'meals' => $plan->meals->map(function($meal) {
                        return [
                            'id' => $meal->id,
                            'name' => $meal->title,
                            'type' => $meal->meal_type,
                            'description' => $meal->description,
                            'calories' => $meal->calories,
                            'protein' => $meal->protein,
                            'carbs' => $meal->carbs,
                            'fats' => $meal->fats,
                            'sort_order' => $meal->sort_order
                        ];
                    }),
                    'macros' => $plan->dailyMacros ? [
                        'protein' => $plan->dailyMacros->protein,
                        'carbs' => $plan->dailyMacros->carbs,
                        'fats' => $plan->dailyMacros->fats,
                        'total_calories' => $plan->dailyMacros->total_calories
                    ] : null,
                    'restrictions' => $plan->restrictions ? [
                        'vegetarian' => $plan->restrictions->vegetarian,
                        'vegan' => $plan->restrictions->vegan,
                        'gluten_free' => $plan->restrictions->gluten_free,
                        'dairy_free' => $plan->restrictions->dairy_free,
                        'keto' => $plan->restrictions->keto,
                        'paleo' => $plan->restrictions->paleo,
                        'custom_restrictions' => $plan->restrictions->custom_restrictions,
                        'notes' => $plan->restrictions->notes
                    ] : null,
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create comprehensive nutrition plan via trainer API: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'request_data' => $request->except(['image']), // Exclude image from logs
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create nutrition plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific nutrition plan
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            $plan = NutritionPlan::with([
                'client:id,name,email,profile_image',
                'meals' => function($query) {
                    $query->orderBy('sort_order');
                },
                'dailyMacros',
                'restrictions'
            ])->where('trainer_id', $trainer->id)
              ->findOrFail($id);
            
            // Calculate plan statistics
            $stats = [
                'total_meals' => $plan->meals->count(),
                'total_calories' => $plan->meals->sum('calories'),
                'avg_prep_time' => $plan->meals->avg('prep_time'),
                'meal_types' => $plan->meals->groupBy('meal_type')->map->count()
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Nutrition plan retrieved successfully',
                'data' => [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'description' => $plan->description,
                    'client' => $plan->client,
                    'goal_type' => $plan->goal_type,
                    'duration_days' => $plan->duration_days,
                    'duration_text' => $plan->duration_text,
                    'target_weight' => $plan->target_weight,
                    'status' => $plan->status,
                    'is_featured' => $plan->is_featured,
                    'media_url' => $plan->media_url ? asset('storage/' . $plan->media_url) : null,
                    'tags' => $plan->tags,
                    'meals' => $plan->meals->map(function($meal) {
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
                            'calories_per_serving' => $meal->calories,
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
                        'water' => $plan->dailyMacros->water,
                        'macro_distribution' => $plan->dailyMacros->macro_distribution,
                        'is_balanced' => $plan->dailyMacros->is_balanced
                    ] : null,
                    'restrictions' => $plan->restrictions ? [
                        'dietary_preferences' => $plan->restrictions->dietary_preferences,
                        'allergens' => $plan->restrictions->allergens,
                        'medical_restrictions' => $plan->restrictions->medical_restrictions,
                        'custom_restrictions' => $plan->restrictions->custom_restrictions,
                        'restrictions_summary' => $plan->restrictions->restrictions_summary,
                        'restriction_badges' => $plan->restrictions->restriction_badges
                    ] : null,
                    'statistics' => $stats,
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve nutrition plan via trainer API: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'plan_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Nutrition plan not found or access denied'
            ], 404);
        }
    }

    /**
     * Add a meal to a nutrition plan
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function addMeal(Request $request, int $planId): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            // Verify plan ownership
            $plan = NutritionPlan::where('trainer_id', $trainer->id)
                                ->findOrFail($planId);
            
            // Validation rules
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'meal_type' => 'required|in:breakfast,lunch,dinner,snack,pre_workout,post_workout',
                'ingredients' => 'nullable|string',
                'instructions' => 'nullable|string',
                'prep_time' => 'nullable|integer|min:0|max:480',
                'cook_time' => 'nullable|integer|min:0|max:480',
                'servings' => 'nullable|integer|min:1|max:20',
                'calories_per_serving' => 'nullable|numeric|min:0|max:2000',
                'protein_per_serving' => 'nullable|numeric|min:0|max:200',
                'carbs_per_serving' => 'nullable|numeric|min:0|max:300',
                'fats_per_serving' => 'nullable|numeric|min:0|max:100'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get next sort order
            $nextSortOrder = NutritionMeal::where('plan_id', $planId)->max('sort_order') + 1;
            
            // Create meal
            $meal = NutritionMeal::create([
                'plan_id' => $planId,
                'title' => $request->title,
                'description' => $request->description,
                'meal_type' => $request->meal_type,
                'ingredients' => $request->ingredients,
                'instructions' => $request->instructions,
                'prep_time' => $request->prep_time,
                'cook_time' => $request->cook_time,
                'servings' => $request->servings,
                'calories_per_serving' => $request->calories_per_serving,
                'protein_per_serving' => $request->protein_per_serving,
                'carbs_per_serving' => $request->carbs_per_serving,
                'fats_per_serving' => $request->fats_per_serving,
                'sort_order' => $nextSortOrder
            ]);
            
            // Log the creation
            Log::info('Meal added to nutrition plan via trainer API', [
                'trainer_id' => $trainer->id,
                'plan_id' => $planId,
                'meal_id' => $meal->id,
                'meal_title' => $meal->title
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Meal added successfully',
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
                    'total_time' => $meal->total_time,
                    'servings' => $meal->servings,
                    'calories_per_serving' => $meal->calories_per_serving,
                    'protein_per_serving' => $meal->protein_per_serving,
                    'carbs_per_serving' => $meal->carbs_per_serving,
                    'fats_per_serving' => $meal->fats_per_serving,
                    'total_macros' => $meal->total_macros,
                    'sort_order' => $meal->sort_order,
                    'created_at' => $meal->created_at
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to add meal via trainer API: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'plan_id' => $planId,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add meal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update macros for a nutrition plan
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function updateMacros(Request $request, int $planId): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            // Verify plan ownership
            $plan = NutritionPlan::where('trainer_id', $trainer->id)
                                ->findOrFail($planId);
            
            // Validation rules
            $validator = Validator::make($request->all(), [
                'protein' => 'required|numeric|min:0|max:500',
                'carbs' => 'required|numeric|min:0|max:800',
                'fats' => 'required|numeric|min:0|max:200',
                'total_calories' => 'required|numeric|min:0|max:5000',
                'fiber' => 'nullable|numeric|min:0|max:100',
                'sugar' => 'nullable|numeric|min:0|max:200',
                'sodium' => 'nullable|numeric|min:0|max:5000',
                'water' => 'nullable|numeric|min:0|max:10'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update or create daily macros
            $macros = NutritionMacro::updateOrCreate(
                [
                    'plan_id' => $planId,
                    'macro_type' => 'daily_target'
                ],
                [
                    'protein' => $request->protein,
                    'carbs' => $request->carbs,
                    'fats' => $request->fats,
                    'total_calories' => $request->total_calories,
                    'fiber' => $request->fiber,
                    'sugar' => $request->sugar,
                    'sodium' => $request->sodium,
                    'water' => $request->water
                ]
            );
            
            // Log the update
            Log::info('Macros updated for nutrition plan via trainer API', [
                'trainer_id' => $trainer->id,
                'plan_id' => $planId,
                'macros_id' => $macros->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Macros updated successfully',
                'data' => [
                    'protein' => $macros->protein,
                    'carbs' => $macros->carbs,
                    'fats' => $macros->fats,
                    'total_calories' => $macros->total_calories,
                    'fiber' => $macros->fiber,
                    'sugar' => $macros->sugar,
                    'sodium' => $macros->sodium,
                    'water' => $macros->water,
                    'macro_distribution' => $macros->macro_distribution,
                    'is_balanced' => $macros->is_balanced,
                    'updated_at' => $macros->updated_at
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update macros via trainer API: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'plan_id' => $planId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update macros',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update restrictions for a nutrition plan
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function updateRestrictions(Request $request, int $planId): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            // Verify plan ownership
            $plan = NutritionPlan::where('trainer_id', $trainer->id)
                                ->findOrFail($planId);
            
            // Validation rules for boolean restrictions
            $booleanFields = [
                'vegetarian', 'vegan', 'pescatarian', 'keto', 'paleo', 'mediterranean',
                'low_carb', 'low_fat', 'high_protein', 'gluten_free', 'dairy_free',
                'nut_free', 'soy_free', 'egg_free', 'shellfish_free', 'fish_free',
                'sesame_free', 'diabetic_friendly', 'heart_healthy', 'low_sodium', 'low_sugar'
            ];
            
            $rules = [];
            foreach ($booleanFields as $field) {
                $rules[$field] = 'nullable|boolean';
            }
            $rules['custom_restrictions'] = 'nullable|array';
            $rules['notes'] = 'nullable|string|max:1000';
            
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Prepare restriction data
            $restrictionData = ['plan_id' => $planId];
            foreach ($booleanFields as $field) {
                $restrictionData[$field] = $request->boolean($field);
            }
            $restrictionData['custom_restrictions'] = $request->custom_restrictions;
            $restrictionData['notes'] = $request->notes;
            
            // Update or create restrictions
            $restrictions = NutritionRestriction::updateOrCreate(
                ['plan_id' => $planId],
                $restrictionData
            );
            
            // Log the update
            Log::info('Restrictions updated for nutrition plan via trainer API', [
                'trainer_id' => $trainer->id,
                'plan_id' => $planId,
                'restrictions_id' => $restrictions->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Restrictions updated successfully',
                'data' => [
                    'dietary_preferences' => $restrictions->dietary_preferences,
                    'allergens' => $restrictions->allergens,
                    'medical_restrictions' => $restrictions->medical_restrictions,
                    'custom_restrictions' => $restrictions->custom_restrictions,
                    'restrictions_summary' => $restrictions->restrictions_summary,
                    'restriction_badges' => $restrictions->restriction_badges,
                    'notes' => $restrictions->notes,
                    'updated_at' => $restrictions->updated_at
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update restrictions via trainer API: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'plan_id' => $planId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update restrictions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign a nutrition plan to a client
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function assignPlan(Request $request): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            // Validation rules
            $validator = Validator::make($request->all(), [
                'client_id' => 'required|exists:users,id',
                'plan_id' => 'required|exists:nutrition_plans,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verify client role
            $client = User::where('id', $request->client_id)
                         ->where('role', 'client')
                         ->first();
                         
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }
            
            // Verify plan ownership or global plan
            $plan = NutritionPlan::where('id', $request->plan_id)
                                ->where(function($query) use ($trainer) {
                                    $query->where('trainer_id', $trainer->id)
                                          ->orWhere('is_global', true);
                                })
                                ->first();
                                
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not found or access denied'
                ], 404);
            }
            
            // Check if client already has this plan assigned
            $existingAssignment = NutritionPlan::where('client_id', $client->id)
                                              ->where('id', $plan->id)
                                              ->first();
                                              
            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan already assigned to this client'
                ], 409);
            }
            
            // If it's a global plan, create a copy for the client
            if ($plan->is_global) {
                $assignedPlan = $plan->replicate();
                $assignedPlan->is_global = false;
                $assignedPlan->trainer_id = $trainer->id;
                $assignedPlan->client_id = $client->id;
                $assignedPlan->save();
                
                // Copy meals if any
                foreach ($plan->meals as $meal) {
                    $newMeal = $meal->replicate();
                    $newMeal->plan_id = $assignedPlan->id;
                    $newMeal->save();
                }
                
                // Copy recommendations if any
                if ($plan->recommendations) {
                    $newRecommendation = $plan->recommendations->replicate();
                    $newRecommendation->plan_id = $assignedPlan->id;
                    $newRecommendation->save();
                }
                
                $planId = $assignedPlan->id;
            } else {
                // Assign existing plan to client
                $plan->client_id = $client->id;
                $plan->save();
                $planId = $plan->id;
            }
            
            Log::info('Nutrition plan assigned successfully', [
                'trainer_id' => $trainer->id,
                'client_id' => $client->id,
                'plan_id' => $planId,
                'is_global_copy' => $plan->is_global
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Plan assigned successfully',
                'data' => [
                    'plan_id' => $planId,
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to assign nutrition plan: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update macronutrient recommendations for a client's plan
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function updateRecommendations(Request $request, int $planId): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            // Verify plan ownership
            $plan = NutritionPlan::where('trainer_id', $trainer->id)
                                ->where('id', $planId)
                                ->first();
                                
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not found or access denied'
                ], 404);
            }
            
            // Validation rules
            $validator = Validator::make($request->all(), [
                'target_calories' => 'required|numeric|min:800|max:5000',
                'protein' => 'required|numeric|min:0|max:500',
                'carbs' => 'required|numeric|min:0|max:800',
                'fats' => 'required|numeric|min:0|max:300',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update or create recommendations
            $recommendations = NutritionRecommendation::updateOrCreate(
                ['plan_id' => $planId],
                [
                    'target_calories' => $request->target_calories,
                    'protein' => $request->protein,
                    'carbs' => $request->carbs,
                    'fats' => $request->fats,
                ]
            );
            
            Log::info('Nutrition recommendations updated successfully', [
                'trainer_id' => $trainer->id,
                'plan_id' => $planId,
                'recommendations' => $recommendations->toArray()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Recommendations updated successfully',
                'data' => [
                    'plan_id' => $planId,
                    'recommendations' => [
                        'target_calories' => $recommendations->target_calories,
                        'protein' => $recommendations->protein,
                        'carbs' => $recommendations->carbs,
                        'fats' => $recommendations->fats,
                        'macro_distribution' => $recommendations->macro_distribution,
                        'total_macro_calories' => $recommendations->total_macro_calories
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update nutrition recommendations: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'plan_id' => $planId,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * View client's food diary
     * 
     * @param Request $request
     * @param int $clientId
     * @return JsonResponse
     */
    public function viewClientFoodDiary(Request $request, int $clientId): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            // Verify client exists and has plans assigned by this trainer
            $client = User::where('id', $clientId)
                         ->where('role', 'client')
                         ->first();
                         
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }
            
            // Check if trainer has any plans assigned to this client
            $hasAssignedPlans = NutritionPlan::where('trainer_id', $trainer->id)
                                           ->where('client_id', $clientId)
                                           ->exists();
                                           
            if (!$hasAssignedPlans) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied - no plans assigned to this client'
                ], 403);
            }
            
            // Get date range from request (default to last 7 days)
            $startDate = $request->get('start_date', Carbon::now()->subDays(7)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            
            // Validate dates
            try {
                $startDate = Carbon::parse($startDate)->startOfDay();
                $endDate = Carbon::parse($endDate)->endOfDay();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format'
                ], 422);
            }
            
            // Get food diary entries
            $entries = FoodDiary::byClient($clientId)
                               ->byDateRange($startDate, $endDate)
                               ->with('meal')
                               ->orderBy('logged_at', 'desc')
                               ->get()
                               ->map(function($entry) {
                                   return [
                                       'id' => $entry->id,
                                       'meal_name' => $entry->meal_name,
                                       'calories' => $entry->calories,
                                       'protein' => $entry->protein,
                                       'carbs' => $entry->carbs,
                                       'fats' => $entry->fats,
                                       'meal_type' => $entry->meal_type,
                                       'logged_at' => $entry->logged_at,
                                       'formatted_date' => $entry->formatted_date,
                                       'formatted_time' => $entry->formatted_time,
                                       'meal' => $entry->meal ? [
                                           'id' => $entry->meal->id,
                                           'title' => $entry->meal->title,
                                           'meal_type' => $entry->meal->meal_type,
                                           'media_url' => $entry->meal->media_url ? asset('storage/' . $entry->meal->media_url) : null
                                       ] : null
                                   ];
                               });
            
            // Calculate daily summaries
            $dailySummaries = $entries->groupBy(function($entry) {
                return Carbon::parse($entry['logged_at'])->format('Y-m-d');
            })->map(function($dayEntries) {
                return [
                    'date' => $dayEntries->first()['formatted_date'],
                    'total_calories' => $dayEntries->sum('calories'),
                    'total_protein' => $dayEntries->sum('protein'),
                    'total_carbs' => $dayEntries->sum('carbs'),
                    'total_fats' => $dayEntries->sum('fats'),
                    'meal_count' => $dayEntries->count(),
                    'entries' => $dayEntries->values()
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'message' => 'Food diary retrieved successfully',
                'data' => [
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email
                    ],
                    'date_range' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d')
                    ],
                    'daily_summaries' => $dailySummaries,
                    'total_entries' => $entries->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve client food diary: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'client_id' => $clientId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve food diary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trainer's assigned clients
     * 
     * @return JsonResponse
     */
    public function getClients(): JsonResponse
    {
        try {
            $trainer = Auth::user();
            
            // Get clients that have plans assigned by this trainer
            $clients = User::where('role', 'client')
                          ->select('id', 'name', 'email', 'profile_image')
                          ->orderBy('name')
                          ->get()
                          ->map(function($client) {
                              return [
                                  'id' => $client->id,
                                  'name' => $client->name,
                                  'email' => $client->email,
                                  'profile_image' => $client->profile_image ? asset('storage/' . $client->profile_image) : null
                              ];
                          });
            
            return response()->json([
                'success' => true,
                'message' => 'Clients retrieved successfully',
                'data' => $clients
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve clients via trainer API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve clients'
            ], 500);
        }
    }
}