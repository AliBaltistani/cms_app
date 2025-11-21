<?php

namespace App\Http\Controllers\Trainer;

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

class NutritionMealsController extends Controller
{
    public function index(int $planId): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->orWhere('is_global', true)->findOrFail($planId);
            $meals = NutritionMeal::where('plan_id', $planId)->ordered()->get();
            return view('trainer.nutrition-plans.meals.index', compact('plan', 'meals'));
        } catch (\Exception $e) {
            Log::error('Failed to load trainer meals: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.index')->with('error', 'Plan not found');
        }
    }

    public function create(int $planId): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $nextOrder = (int) NutritionMeal::where('plan_id', $planId)->max('sort_order') + 1;
            return view('trainer.nutrition-plans.meals.create', compact('plan', 'nextOrder'));
        } catch (\Exception $e) {
            Log::error('Failed to load trainer meal create form: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.meals.index', $planId)->with('error', 'Access denied or plan not found');
        }
    }

    public function store(Request $request, int $planId)
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);

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
                if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()],422);} 
                return back()->withErrors($validator)->withInput();
            }

            $mediaUrl = null;
            if ($request->hasFile('image_file')) {
                $mediaUrl = $request->file('image_file')->store('nutrition-meals', 'public');
            }

            $meal = new NutritionMeal();
            $meal->plan_id = $planId;
            $meal->title = $request->title;
            $meal->description = $request->description;
            $meal->meal_type = $request->meal_type;
            $meal->ingredients = $request->ingredients;
            $meal->instructions = $request->instructions;
            $meal->prep_time = $request->prep_time;
            $meal->cook_time = $request->cook_time;
            $meal->servings = $request->servings;
            $meal->calories = $request->calories_per_serving;
            $meal->protein = $request->protein_per_serving;
            $meal->carbs = $request->carbs_per_serving;
            $meal->fats = $request->fats_per_serving;
            $meal->sort_order = $request->sort_order;
            $meal->media_url = $mediaUrl;
            $meal->save();

            if ($request->ajax()) { return response()->json(['success'=>true,'message'=>'Meal created successfully','meal'=>$meal]); }

            if ($request->has('add_another')) {
                return redirect()->route('trainer.nutrition-plans.meals.create', $planId)->with('success', 'Meal created successfully! Add another meal below.');
            }
            return redirect()->route('trainer.nutrition-plans.meals.index', $planId)->with('success', 'Meal created successfully');
        } catch (\Exception $e) {
            Log::error('Trainer failed to create meal: ' . $e->getMessage(), ['trainer_id'=>Auth::id(),'plan_id'=>$planId]);
            if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Failed to create meal'],500);} 
            return back()->with('error','Failed to create meal')->withInput();
        }
    }

    public function show(int $planId, int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::where(function($q){ $q->where('trainer_id', Auth::id())->orWhere('is_global', true);})->findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            return view('trainer.nutrition-plans.meals.show', compact('plan', 'meal'));
        } catch (\Exception $e) {
            Log::error('Trainer failed to load meal details: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.meals.index', $planId)->with('error', 'Meal not found');
        }
    }

    public function edit(int $planId, int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            return view('trainer.nutrition-plans.meals.edit', compact('plan', 'meal'));
        } catch (\Exception $e) {
            Log::error('Trainer failed to load meal edit form: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.meals.index', $planId)->with('error', 'Meal not found or access denied');
        }
    }

    public function update(Request $request, int $planId, int $id)
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);

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
                if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()],422);} 
                return back()->withErrors($validator)->withInput();
            }

            if ($request->hasFile('image_file')) {
                if ($meal->media_url) { Storage::disk('public')->delete($meal->media_url); }
                $meal->media_url = $request->file('image_file')->store('nutrition-meals', 'public');
            }

            $meal->title = $request->title;
            $meal->description = $request->description;
            $meal->meal_type = $request->meal_type;
            $meal->ingredients = $request->ingredients;
            $meal->instructions = $request->instructions;
            $meal->prep_time = $request->prep_time;
            $meal->cook_time = $request->cook_time;
            $meal->servings = $request->servings;
            $meal->calories = $request->calories_per_serving;
            $meal->protein = $request->protein_per_serving;
            $meal->carbs = $request->carbs_per_serving;
            $meal->fats = $request->fats_per_serving;
            $meal->sort_order = $request->sort_order;
            $meal->save();

            if ($request->ajax()) { return response()->json(['success'=>true,'message'=>'Meal updated successfully','meal'=>$meal]); }
            return redirect()->route('trainer.nutrition-plans.meals.index', $planId)->with('success', 'Meal updated successfully');
        } catch (\Exception $e) {
            Log::error('Trainer failed to update meal: ' . $e->getMessage(), ['trainer_id'=>Auth::id(),'plan_id'=>$planId,'meal_id'=>$id]);
            if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Failed to update meal'],500);} 
            return back()->with('error','Failed to update meal')->withInput();
        }
    }

    public function destroy(int $planId, int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            if ($meal->media_url) { Storage::disk('public')->delete($meal->media_url); }
            $meal->delete();
            return response()->json(['success'=>true,'message'=>'Meal deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Trainer failed to delete meal: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to delete meal'],500);
        }
    }

    public function reorder(Request $request, int $planId): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $order = $request->get('order');
            if (!is_array($order)) { return response()->json(['success'=>false,'message'=>'Invalid order payload'],422);} 
            foreach ($order as $item) {
                NutritionMeal::where('plan_id', $planId)->where('id', $item['id'])->update(['sort_order' => (int) $item['sort_order']]);
            }
            return response()->json(['success'=>true,'message'=>'Meals reordered successfully']);
        } catch (\Exception $e) {
            Log::error('Trainer failed to reorder meals: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to reorder meals'],500);
        }
    }

    public function deleteImage(int $planId, int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            if ($meal->media_url) {
                Storage::disk('public')->delete($meal->media_url);
                $meal->media_url = null;
                $meal->save();
                return response()->json(['success'=>true,'message'=>'Meal image deleted successfully']);
            }
            return response()->json(['success'=>false,'message'=>'No image found'],404);
        } catch (\Exception $e) {
            Log::error('Trainer failed to delete meal image: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to delete image'],500);
        }
    }

    public function duplicate(int $planId, int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            $copy = $meal->replicate();
            $copy->title = $meal->title . ' (Copy)';
            $copy->sort_order = (int) NutritionMeal::where('plan_id', $planId)->max('sort_order') + 1;
            $copy->save();
            return response()->json(['success'=>true,'message'=>'Meal duplicated successfully','meal'=>$copy]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to duplicate meal: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to duplicate meal'],500);
        }
    }

    public function updateMacros(Request $request, int $planId, int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $meal = NutritionMeal::where('plan_id', $planId)->findOrFail($id);
            $validator = Validator::make($request->all(), [
                'calories_per_serving' => 'nullable|numeric|min:0|max:2000',
                'protein_per_serving' => 'nullable|numeric|min:0|max:200',
                'carbs_per_serving' => 'nullable|numeric|min:0|max:300',
                'fats_per_serving' => 'nullable|numeric|min:0|max:100'
            ]);
            if ($validator->fails()) { return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()],422);} 
            $meal->calories = $request->calories_per_serving;
            $meal->protein = $request->protein_per_serving;
            $meal->carbs = $request->carbs_per_serving;
            $meal->fats = $request->fats_per_serving;
            $meal->save();
            return response()->json(['success'=>true,'message'=>'Meal macros updated successfully']);
        } catch (\Exception $e) {
            Log::error('Trainer failed to update meal macros: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to update macros'],500);
        }
    }

    public function copyFromGlobal(Request $request, int $planId): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $globalMealId = $request->get('global_meal_id');
            $globalMeal = NutritionMeal::findOrFail($globalMealId);
            $sourcePlan = NutritionPlan::findOrFail($globalMeal->plan_id);
            if (!$sourcePlan->is_global) { return response()->json(['success'=>false,'message'=>'Source meal is not from a global plan'],422);} 
            $copy = $globalMeal->replicate();
            $copy->plan_id = $planId;
            $copy->media_url = null;
            $copy->sort_order = (int) NutritionMeal::where('plan_id', $planId)->max('sort_order') + 1;
            $copy->save();
            return response()->json(['success'=>true,'message'=>'Meal copied from global successfully','meal'=>$copy]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to copy meal from global: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to copy meal'],500);
        }
    }

    public function getGlobalMeals(int $planId): JsonResponse
    {
        try {
            $globalPlans = NutritionPlan::where('is_global', true)->pluck('id');
            $meals = NutritionMeal::whereIn('plan_id', $globalPlans)->ordered()->get();
            return response()->json(['success'=>true,'meals'=>$meals]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to load global meals: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to load global meals'],500);
        }
    }

    public function bulkDelete(Request $request, int $planId): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($planId);
            $ids = $request->get('ids', []);
            if (!is_array($ids) || empty($ids)) { return response()->json(['success'=>false,'message'=>'No meals selected'],422);} 
            $meals = NutritionMeal::where('plan_id', $planId)->whereIn('id', $ids)->get();
            foreach ($meals as $meal) { if ($meal->media_url) { Storage::disk('public')->delete($meal->media_url); } $meal->delete(); }
            return response()->json(['success'=>true,'message'=>'Selected meals deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Trainer failed bulk delete meals: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to delete selected meals'],500);
        }
    }
}