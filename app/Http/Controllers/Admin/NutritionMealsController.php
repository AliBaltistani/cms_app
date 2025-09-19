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
}