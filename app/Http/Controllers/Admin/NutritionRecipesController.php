<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NutritionPlan;
use App\Models\NutritionRecipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * NutritionRecipesController
 * 
 * Handles admin web-based CRUD operations for nutrition recipes
 * Manages individual recipes within nutrition plans
 * 
 * @package App\Http\Controllers\Admin
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionRecipesController extends Controller
{
    /**
     * Display recipes for a specific nutrition plan
     * 
     * @param int $planId
     * @return View|RedirectResponse
     */
    public function index(int $planId): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::with(['recipes' => function($query) {
                $query->orderBy('sort_order');
            }])->findOrFail($planId);
            
            return view('admin.nutrition-plans.recipes.index', compact('plan'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition recipes: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.nutrition-plans.index')->with('error', 'Plan not found');
        }
    }

    /**
     * Show the form for creating a new recipe
     * 
     * @param int $planId
     * @return View|RedirectResponse
     */
    public function create(int $planId): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($planId);
            
            // Get next sort order
            $nextSortOrder = NutritionRecipe::where('plan_id', $planId)->max('sort_order') + 1;
            
            return view('admin.nutrition-plans.recipes.create', compact('plan', 'nextSortOrder'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load recipe creation form: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.nutrition-plans.show', $planId)->with('error', 'Plan not found');
        }
    }

    /**
     * Store a newly created recipe
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
                'description' => 'nullable|string|max:2000',
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
                $imageUrl = $request->file('image_file')->store('nutrition-recipes', 'public');
            }
            
            // Create recipe
            $recipe = NutritionRecipe::create([
                'plan_id' => $planId,
                'title' => $request->title,
                'description' => $request->description,
                'sort_order' => $request->sort_order,
                'image_url' => $imageUrl
            ]);
            
            // Log the creation
            Log::info('Nutrition recipe created successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_id' => $recipe->id,
                'recipe_title' => $recipe->title
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recipe created successfully',
                    'recipe' => $recipe
                ]);
            }
            
            // Check if user wants to add another recipe
            if ($request->has('add_another')) {
                return redirect()->route('admin.nutrition-plans.recipes.create', $planId)
                               ->with('success', 'Recipe created successfully! Add another recipe below.');
            }
            
            return redirect()->route('admin.nutrition-plans.recipes.index', $planId)
                           ->with('success', 'Recipe created successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to create nutrition recipe: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'request_data' => $request->except(['image_file']),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create recipe: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create recipe: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified recipe
     * 
     * @param int $planId
     * @param int $id
     * @return View|RedirectResponse
     */
    public function show(int $planId, int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($planId);
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            
            return view('admin.nutrition-plans.recipes.show', compact('plan', 'recipe'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition recipe details: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.nutrition-plans.recipes.index', $planId)->with('error', 'Recipe not found');
        }
    }

    /**
     * Show the form for editing the specified recipe
     * 
     * @param int $planId
     * @param int $id
     * @return View|RedirectResponse
     */
    public function edit(int $planId, int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($planId);
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            
            return view('admin.nutrition-plans.recipes.edit', compact('plan', 'recipe'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load recipe edit form: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.nutrition-plans.recipes.index', $planId)->with('error', 'Recipe not found');
        }
    }

    /**
     * Update the specified recipe
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
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            
            // Validation rules
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
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
            $imageUrl = $recipe->getRawOriginal('image_url'); // Get raw value without accessor
            if ($request->hasFile('image_file')) {
                // Delete old image
                if ($recipe->getRawOriginal('image_url')) {
                    Storage::disk('public')->delete($recipe->getRawOriginal('image_url'));
                }
                
                $imageUrl = $request->file('image_file')->store('nutrition-recipes', 'public');
            }
            
            // Update recipe
            $recipe->update([
                'title' => $request->title,
                'description' => $request->description,
                'sort_order' => $request->sort_order,
                'image_url' => $imageUrl
            ]);
            
            // Log the update
            Log::info('Nutrition recipe updated successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_id' => $recipe->id,
                'changes' => $request->except(['image_file'])
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recipe updated successfully',
                    'recipe' => $recipe->fresh()
                ]);
            }
            
            return redirect()->route('admin.nutrition-plans.recipes.show', [$planId, $recipe->id])
                           ->with('success', 'Recipe updated successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to update nutrition recipe: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update recipe: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update recipe: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified recipe
     * 
     * @param int $planId
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $planId, int $id): JsonResponse
    {
        try {
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            
            // Delete associated image file
            if ($recipe->getRawOriginal('image_url')) {
                Storage::disk('public')->delete($recipe->getRawOriginal('image_url'));
            }
            
            // Store recipe info for logging
            $recipeInfo = [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'plan_id' => $planId
            ];
            
            // Delete the recipe
            $recipe->delete();
            
            // Log the deletion
            Log::info('Nutrition recipe deleted successfully', [
                'admin_id' => Auth::id(),
                'deleted_recipe' => $recipeInfo
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Recipe deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete nutrition recipe: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete recipe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder recipes within a plan
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function reorder(Request $request, int $planId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'recipe_orders' => 'required|array',
                'recipe_orders.*.id' => 'required|integer|exists:nutrition_recipes,id',
                'recipe_orders.*.sort_order' => 'required|integer|min:0'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Update sort orders
            foreach ($request->recipe_orders as $recipeOrder) {
                NutritionRecipe::where('id', $recipeOrder['id'])
                              ->where('plan_id', $planId)
                              ->update(['sort_order' => $recipeOrder['sort_order']]);
            }
            
            // Log the reorder
            Log::info('Nutrition recipes reordered successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_orders' => $request->recipe_orders
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Recipes reordered successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to reorder nutrition recipes: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder recipes'
            ], 500);
        }
    }

    /**
     * Delete image from recipe
     * 
     * @param int $planId
     * @param int $id
     * @return JsonResponse
     */
    public function deleteImage(int $planId, int $id): JsonResponse
    {
        try {
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            
            if ($recipe->getRawOriginal('image_url')) {
                Storage::disk('public')->delete($recipe->getRawOriginal('image_url'));
                $recipe->update(['image_url' => null]);
                
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
            Log::error('Failed to delete recipe image: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image'
            ], 500);
        }
    }

    /**
     * Duplicate an existing recipe within the same plan
     * 
     * @param int $planId
     * @param int $id
     * @return JsonResponse
     */
    public function duplicate(int $planId, int $id): JsonResponse
    {
        try {
            $originalRecipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            
            // Get the highest sort order for the plan
            $maxSortOrder = NutritionRecipe::where('plan_id', $planId)->max('sort_order') ?? 0;
            
            // Create duplicate recipe
            $duplicatedRecipe = $originalRecipe->replicate();
            $duplicatedRecipe->title = $originalRecipe->title . ' (Copy)';
            $duplicatedRecipe->sort_order = $maxSortOrder + 1;
            $duplicatedRecipe->save();
            
            // Log the duplication
            Log::info('Nutrition recipe duplicated successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'original_recipe_id' => $originalRecipe->id,
                'duplicated_recipe_id' => $duplicatedRecipe->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Recipe duplicated successfully',
                'recipe' => $duplicatedRecipe
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to duplicate nutrition recipe: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate recipe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete recipes
     * 
     * @param Request $request
     * @param int $planId
     * @return JsonResponse
     */
    public function bulkDelete(Request $request, int $planId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'recipe_ids' => 'required|array',
                'recipe_ids.*' => 'required|integer|exists:nutrition_recipes,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $deletedRecipes = [];
            
            foreach ($request->recipe_ids as $recipeId) {
                $recipe = NutritionRecipe::where('plan_id', $planId)->find($recipeId);
                
                if ($recipe) {
                    // Delete associated image file
                    if ($recipe->getRawOriginal('image_url')) {
                        Storage::disk('public')->delete($recipe->getRawOriginal('image_url'));
                    }
                    
                    $deletedRecipes[] = [
                        'id' => $recipe->id,
                        'title' => $recipe->title
                    ];
                    
                    $recipe->delete();
                }
            }
            
            // Log the bulk deletion
            Log::info('Nutrition recipes bulk deleted successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'deleted_recipes' => $deletedRecipes
            ]);
            
            return response()->json([
                'success' => true,
                'message' => count($deletedRecipes) . ' recipes deleted successfully',
                'deleted_count' => count($deletedRecipes)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete nutrition recipes: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $planId,
                'recipe_ids' => $request->recipe_ids ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete recipes: ' . $e->getMessage()
            ], 500);
        }
    }
}