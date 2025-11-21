<?php

namespace App\Http\Controllers\Trainer;

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

class NutritionRecipesController extends Controller
{
    public function index(int $planId): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::where(function($q){ $q->where('trainer_id', Auth::id())->orWhere('is_global', true);})->with(['recipes' => function($query){ $query->orderBy('sort_order'); }])->findOrFail($planId);
            return view('trainer.nutrition-plans.recipes.index', compact('plan'));
        } catch (\Exception $e) {
            Log::error('Failed to load trainer recipes: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.index')->with('error', 'Plan not found');
        }
    }

    public function create(int $planId): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $nextSortOrder = (int) NutritionRecipe::where('plan_id', $planId)->max('sort_order') + 1;
            return view('trainer.nutrition-plans.recipes.create', compact('plan', 'nextSortOrder'));
        } catch (\Exception $e) {
            Log::error('Failed to load trainer recipe create form: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.recipes.index', $planId)->with('error', 'Access denied or plan not found');
        }
    }

    public function store(Request $request, int $planId)
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);

            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
                'sort_order' => 'required|integer|min:0',
                'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()],422);} 
                return back()->withErrors($validator)->withInput();
            }

            $imageUrl = null;
            if ($request->hasFile('image_file')) {
                $imageUrl = $request->file('image_file')->store('nutrition-recipes', 'public');
            }

            $recipe = NutritionRecipe::create([
                'plan_id' => $planId,
                'title' => $request->title,
                'description' => $request->description,
                'sort_order' => $request->sort_order,
                'image_url' => $imageUrl
            ]);

            if ($request->ajax()) { return response()->json(['success'=>true,'message'=>'Recipe created successfully','recipe'=>$recipe]); }

            if ($request->has('add_another')) {
                return redirect()->route('trainer.nutrition-plans.recipes.create', $planId)->with('success', 'Recipe created successfully! Add another recipe below.');
            }
            return redirect()->route('trainer.nutrition-plans.recipes.index', $planId)->with('success', 'Recipe created successfully');
        } catch (\Exception $e) {
            Log::error('Trainer failed to create recipe: ' . $e->getMessage(), ['trainer_id'=>Auth::id(),'plan_id'=>$planId]);
            if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Failed to create recipe'],500);} 
            return back()->with('error','Failed to create recipe')->withInput();
        }
    }

    public function show(int $planId, int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::where(function($q){ $q->where('trainer_id', Auth::id())->orWhere('is_global', true);})->findOrFail($planId);
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            return view('trainer.nutrition-plans.recipes.show', compact('plan', 'recipe'));
        } catch (\Exception $e) {
            Log::error('Trainer failed to load recipe details: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.recipes.index', $planId)->with('error', 'Recipe not found');
        }
    }

    public function edit(int $planId, int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            return view('trainer.nutrition-plans.recipes.edit', compact('plan', 'recipe'));
        } catch (\Exception $e) {
            Log::error('Trainer failed to load recipe edit form: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.recipes.index', $planId)->with('error', 'Recipe not found or access denied');
        }
    }

    public function update(Request $request, int $planId, int $id)
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);

            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
                'sort_order' => 'required|integer|min:0',
                'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()],422);} 
                return back()->withErrors($validator)->withInput();
            }

            $imageUrl = $recipe->getRawOriginal('image_url');
            if ($request->hasFile('image_file')) {
                if ($recipe->getRawOriginal('image_url')) { Storage::disk('public')->delete($recipe->getRawOriginal('image_url')); }
                $imageUrl = $request->file('image_file')->store('nutrition-recipes', 'public');
            }

            $recipe->update([
                'title' => $request->title,
                'description' => $request->description,
                'sort_order' => $request->sort_order,
                'image_url' => $imageUrl
            ]);

            if ($request->ajax()) { return response()->json(['success'=>true,'message'=>'Recipe updated successfully','recipe'=>$recipe->fresh()]); }
            return redirect()->route('trainer.nutrition-plans.recipes.show', [$planId, $recipe->id])->with('success', 'Recipe updated successfully');
        } catch (\Exception $e) {
            Log::error('Trainer failed to update recipe: ' . $e->getMessage(), ['trainer_id'=>Auth::id(),'plan_id'=>$planId,'recipe_id'=>$id]);
            if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Failed to update recipe'],500);} 
            return back()->with('error','Failed to update recipe')->withInput();
        }
    }

    public function destroy(int $planId, int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            if ($recipe->getRawOriginal('image_url')) { Storage::disk('public')->delete($recipe->getRawOriginal('image_url')); }
            $recipe->delete();
            return response()->json(['success'=>true,'message'=>'Recipe deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Trainer failed to delete recipe: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to delete recipe'],500);
        }
    }

    public function reorder(Request $request, int $planId): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $orders = $request->get('recipe_orders');
            if (!is_array($orders)) { return response()->json(['success'=>false,'message'=>'Invalid order payload'],422);} 
            foreach ($orders as $item) {
                NutritionRecipe::where('plan_id', $planId)->where('id', $item['id'])->update(['sort_order' => (int) $item['sort_order']]);
            }
            return response()->json(['success'=>true,'message'=>'Recipes reordered successfully']);
        } catch (\Exception $e) {
            Log::error('Trainer failed to reorder recipes: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to reorder recipes'],500);
        }
    }

    public function deleteImage(int $planId, int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $recipe = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            if ($recipe->getRawOriginal('image_url')) {
                Storage::disk('public')->delete($recipe->getRawOriginal('image_url'));
                $recipe->update(['image_url' => null]);
                return response()->json(['success'=>true,'message'=>'Recipe image deleted successfully']);
            }
            return response()->json(['success'=>false,'message'=>'No image found'],404);
        } catch (\Exception $e) {
            Log::error('Trainer failed to delete recipe image: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to delete image'],500);
        }
    }

    public function duplicate(int $planId, int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $original = NutritionRecipe::where('plan_id', $planId)->findOrFail($id);
            $maxOrder = (int) (NutritionRecipe::where('plan_id', $planId)->max('sort_order') ?? 0);
            $copy = $original->replicate();
            $copy->title = $original->title . ' (Copy)';
            $copy->sort_order = $maxOrder + 1;
            $copy->save();
            return response()->json(['success'=>true,'message'=>'Recipe duplicated successfully','recipe'=>$copy]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to duplicate recipe: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to duplicate recipe'],500);
        }
    }

    public function bulkDelete(Request $request, int $planId): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $ids = $request->get('recipe_ids', []);
            if (!is_array($ids) || empty($ids)) { return response()->json(['success'=>false,'message'=>'No recipes selected'],422);} 
            $recipes = NutritionRecipe::where('plan_id', $planId)->whereIn('id', $ids)->get();
            $deleted = [];
            foreach ($recipes as $recipe) {
                if ($recipe->getRawOriginal('image_url')) { Storage::disk('public')->delete($recipe->getRawOriginal('image_url')); }
                $deleted[] = ['id'=>$recipe->id,'title'=>$recipe->title];
                $recipe->delete();
            }
            return response()->json(['success'=>true,'message'=>count($deleted) . ' recipes deleted successfully','deleted_count'=>count($deleted)]);
        } catch (\Exception $e) {
            Log::error('Trainer failed bulk delete recipes: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to delete recipes'],500);
        }
    }
}