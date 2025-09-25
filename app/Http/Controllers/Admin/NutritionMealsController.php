<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NutritionPlan;
use App\Models\NutritionMeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * NutritionMealsController
 * 
 * Handles admin web-based CRUD operations for nutrition meals
 * Manages individual meals within nutrition plans
 * 
 * @package App\Http\Controllers\Admin
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionMealsController extends Controller
{
    /**
     * Display meals for a specific nutrition plan
     * 
     * @param int $planId
     * @return View
     */
    public function index(int $planId): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::with(['meals' => function($query) {
                $query->orderBy('sort_order');
            }])->findOrFail($planId);
            
            return view('admin.nutrition-plans.meals.index', compact('plan'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition meals: ' . $e->getMessage());
            return redirect()->route('admin.nutrition-plans.index')->with('error', 'Plan not found');
        }
    }

    /**
     * Show the form for creating a new meal
     * 
     * @param int $planId
     * @return View
     */
    public function create(int $planId): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($planId);
            
            // Get next sort order
            $nextSortOrder = NutritionMeal::where('plan_id', $planId)->max('sort_order') + 1;
            
            return view('admin.nutrition-plans.meals.create', compact('plan', 'nextSortOrder'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load meal creation form: ' . $e->getMessage());
            return redirect()->route('admin.nutrition-plans.show', $planId)->with('error', 'Plan not found');
        }
    }

    /**
     * Store a newly created meal
     * 
     * @param Request $request
     * @param int $planId
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request, int $planId)
    {
        try {
            $plan = NutritionPlan::findOrFail($planId);
            
            // Validation rules
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'meal_type' => 'required|in:breakfast,lunch,dinner,snack,pre_workout,post_workout',
                'ingredients' => 'nullable|string',
                'instructions' => 'nullable|string',
                'prep_time' => 'nullable|integer|min:0|max:480',
                'cook_time' => 'nullable|integer|min:0|max:480',
                'servings' => 'required|integer|min:1|max:20',
                'calories_per_serving' => 'nullable|numeric|min:0|max:2000',
                'protein_per_serving' => 'nullable|numeric|min:0|max:200',
                'carbs_per_serving' => 'nullable|numeric|min:0|max:300',
                'fats_per_serving' => 'nullable|numeric|min:0|max:100',
                'sort_order' => 'required|integer|min:0',
                'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                return back()->withErrors($validator)->withInput();
            }
            
            // Handle image upload
            $imageUrl = null;
            if ($request->hasFile('image_file')) {
                $imageUrl = $request->file('image_file')->store('nutrition-meals', 'public');
            }
            
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
                'sort_order' => $request->sort_order,
                'image_url' => $imageUrl
            ]);
            
            // Log the creation
            Log::info('Nutrition meal created successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_id' => $meal->id,
                'meal_title' => $meal->title
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Meal created successfully',
                    'meal' => $meal
                ]);
            }
            
            // Check if user wants to add another meal
            if ($request->has('add_another')) {
                return redirect()->route('admin.nutrition-plans.meals.create', $planId)
                               ->with('success', 'Meal created successfully! Add another meal below.');
            }
            
            return redirect()->route('admin.nutrition-plans.meals.index', $planId)
                           ->with('success', 'Meal created successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to create nutrition meal: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'request_data' => $request->except(['image_file']),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create meal: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create meal: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified meal
     * 
     * @param int $planId
     * @param int $id
     * @return View
     */
    public function show(int $planId, int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            
            return view('admin.nutrition-plans.meals.show', compact('plan', 'meal'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition meal details: ' . $e->getMessage());
            return redirect()->route('admin.nutrition-plans.meals.index', $planId)->with('error', 'Meal not found');
        }
    }

    /**
     * Show the form for editing the specified meal
     * 
     * @param int $planId
     * @param int $id
     * @return View
     */
    public function edit(int $planId, int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            
            return view('admin.nutrition-plans.meals.edit', compact('plan', 'meal'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load meal edit form: ' . $e->getMessage());
            return redirect()->route('admin.nutrition-plans.meals.index', $planId)->with('error', 'Meal not found');
        }
    }

    /**
     * Update the specified meal
     * 
     * @param Request $request
     * @param int $planId
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function update(Request $request, int $planId, int $id)
    {
        try {
            $plan = NutritionPlan::findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            
            // Validation rules
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'meal_type' => 'required|in:breakfast,lunch,dinner,snack,pre_workout,post_workout',
                'ingredients' => 'nullable|string',
                'instructions' => 'nullable|string',
                'prep_time' => 'nullable|integer|min:0|max:480',
                'cook_time' => 'nullable|integer|min:0|max:480',
                'servings' => 'required|integer|min:1|max:20',
                'calories_per_serving' => 'nullable|numeric|min:0|max:2000',
                'protein_per_serving' => 'nullable|numeric|min:0|max:200',
                'carbs_per_serving' => 'nullable|numeric|min:0|max:300',
                'fats_per_serving' => 'nullable|numeric|min:0|max:100',
                'sort_order' => 'required|integer|min:0',
                'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                return back()->withErrors($validator)->withInput();
            }
            
            // Handle image upload
            $imageUrl = $meal->image_url;
            if ($request->hasFile('image_file')) {
                // Delete old image
                if ($meal->image_url) {
                    Storage::disk('public')->delete($meal->image_url);
                }
                
                $imageUrl = $request->file('image_file')->store('nutrition-meals', 'public');
            }
            
            // Update meal
            $meal->update([
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
                'sort_order' => $request->sort_order,
                'image_url' => $imageUrl
            ]);
            
            // Log the update
            Log::info('Nutrition meal updated successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_id' => $meal->id,
                'changes' => $request->except(['image_file'])
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Meal updated successfully',
                    'meal' => $meal->fresh()
                ]);
            }
            
            return redirect()->route('admin.nutrition-plans.meals.show', [$planId, $meal->id])
                           ->with('success', 'Meal updated successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to update nutrition meal: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update meal: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update meal: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified meal
     * 
     * @param int $planId
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $planId, int $id): JsonResponse
    {
        try {
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            
            // Delete associated image file
            if ($meal->image_url) {
                Storage::disk('public')->delete($meal->image_url);
            }
            
            // Store meal info for logging
            $mealInfo = [
                'id' => $meal->id,
                'title' => $meal->title,
                'meal_type' => $meal->meal_type,
                'plan_id' => $planId
            ];
            
            // Delete the meal
            $meal->delete();
            
            // Log the deletion
            Log::info('Nutrition meal deleted successfully', [
                'admin_id' => Auth::id(),
                'deleted_meal' => $mealInfo
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Meal deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete nutrition meal: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete meal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder meals within a plan
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function reorder(Request $request, int $planId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'meal_orders' => 'required|array',
                'meal_orders.*.id' => 'required|integer|exists:nutrition_meals,id',
                'meal_orders.*.sort_order' => 'required|integer|min:0'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update sort orders
            foreach ($request->meal_orders as $mealOrder) {
                NutritionMeal::where('id', $mealOrder['id'])
                            ->where('plan_id', $planId)
                            ->update(['sort_order' => $mealOrder['sort_order']]);
            }
            
            // Log the reorder
            Log::info('Nutrition meals reordered successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_orders' => $request->meal_orders
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Meals reordered successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to reorder nutrition meals: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder meals'
            ], 500);
        }
    }

    /**
     * Delete image from meal
     * 
     * @param int $planId
     * @param int $id
     * @return JsonResponse
     */
    public function deleteImage(int $planId, int $id): JsonResponse
    {
        try {
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            
            if ($meal->image_url) {
                Storage::disk('public')->delete($meal->image_url);
                $meal->update(['image_url' => null]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No image found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete meal image: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }

    /**
     * Duplicate an existing meal within the same plan
     * 
     * @param int $planId
     * @param int $id
     * @return JsonResponse
     */
    public function duplicate(int $planId, int $id): JsonResponse
    {
        try {
            $originalMeal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            
            // Get the highest sort order for the plan
            $maxSortOrder = NutritionMeal::where('plan_id', $planId)->max('sort_order') ?? 0;
            
            // Create duplicate meal
            $duplicatedMeal = $originalMeal->replicate();
            $duplicatedMeal->title = $originalMeal->title . ' (Copy)';
            $duplicatedMeal->sort_order = $maxSortOrder + 1;
            $duplicatedMeal->save();
            
            // Log the duplication
            Log::info('Nutrition meal duplicated successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'original_meal_id' => $originalMeal->id,
                'duplicated_meal_id' => $duplicatedMeal->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Meal duplicated successfully',
                'meal' => $duplicatedMeal
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to duplicate nutrition meal: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate meal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy meals from global plans to current plan
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function copyFromGlobal(Request $request, int $planId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'meal_ids' => 'required|array',
                'meal_ids.*' => 'required|integer|exists:nutrition_meals,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $plan = NutritionPlan::findOrFail($planId);
            $copiedMeals = [];
            
            // Get the highest sort order for the plan
            $maxSortOrder = NutritionMeal::where('plan_id', $planId)->max('sort_order') ?? 0;
            
            foreach ($request->meal_ids as $mealId) {
                // Verify meal is from a global plan
                $globalMeal = NutritionMeal::whereHas('plan', function($query) {
                    $query->where('is_global', true);
                })->findOrFail($mealId);
                
                // Create copy of the meal
                $copiedMeal = $globalMeal->replicate();
                $copiedMeal->plan_id = $planId;
                $copiedMeal->sort_order = ++$maxSortOrder;
                $copiedMeal->save();
                
                $copiedMeals[] = $copiedMeal;
            }
            
            // Log the copy operation
            Log::info('Meals copied from global plans successfully', [
                'admin_id' => Auth::id(),
                'target_plan_id' => $planId,
                'copied_meal_ids' => collect($copiedMeals)->pluck('id')->toArray(),
                'source_meal_ids' => $request->meal_ids
            ]);
            
            return response()->json([
                'success' => true,
                'message' => count($copiedMeals) . ' meals copied successfully',
                'meals' => $copiedMeals
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to copy meals from global plans: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_ids' => $request->meal_ids ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy meals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available global meals for copying
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getGlobalMeals(Request $request): JsonResponse
    {
        try {
            $query = NutritionMeal::with(['plan:id,name,category'])
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
            $meals = $query->orderBy('title')
                          ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $meals
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get global meals: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load global meals'
            ], 500);
        }
    }

    /**
     * Bulk delete meals
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function bulkDelete(Request $request, int $planId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'meal_ids' => 'required|array',
                'meal_ids.*' => 'required|integer|exists:nutrition_meals,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $deletedMeals = [];
            
            foreach ($request->meal_ids as $mealId) {
                $meal = NutritionMeal::where('plan_id', $planId)->find($mealId);
                
                if ($meal) {
                    // Delete associated image file
                    if ($meal->image_url) {
                        Storage::disk('public')->delete($meal->image_url);
                    }
                    
                    $deletedMeals[] = [
                        'id' => $meal->id,
                        'title' => $meal->title,
                        'meal_type' => $meal->meal_type
                    ];
                    
                    $meal->delete();
                }
            }
            
            // Log the bulk deletion
            Log::info('Nutrition meals bulk deleted successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'deleted_meals' => $deletedMeals
            ]);
            
            return response()->json([
                'success' => true,
                'message' => count($deletedMeals) . ' meals deleted successfully',
                'deleted_count' => count($deletedMeals)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete nutrition meals: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_ids' => $request->meal_ids ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete meals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update meal macros based on new field names
     * 
     * @param Request $request
     * @param int $planId
     * @param int $id
     * @return JsonResponse
     */
    public function updateMacros(Request $request, int $planId, int $id): JsonResponse
    {
        try {
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'calories' => 'nullable|numeric|min:0|max:2000',
                'protein' => 'nullable|numeric|min:0|max:200',
                'carbs' => 'nullable|numeric|min:0|max:300',
                'fats' => 'nullable|numeric|min:0|max:100',
                'servings' => 'required|integer|min:1|max:20'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update meal macros
            $meal->update([
                'calories' => $request->calories,
                'protein' => $request->protein,
                'carbs' => $request->carbs,
                'fats' => $request->fats,
                'servings' => $request->servings
            ]);
            
            // Log the update
            Log::info('Nutrition meal macros updated successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_id' => $meal->id,
                'macros' => $request->only(['calories', 'protein', 'carbs', 'fats', 'servings'])
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Meal macros updated successfully',
                'meal' => $meal->fresh()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update nutrition meal macros: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'meal_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update meal macros: ' . $e->getMessage()
            ], 500);
        }
    }
}