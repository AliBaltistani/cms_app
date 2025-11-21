<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\NutritionPlan;
use App\Models\NutritionMeal;
use App\Models\NutritionMacro;
use App\Models\NutritionRestriction;
use App\Models\NutritionRecommendation;
use App\Models\User;
use App\Models\Goal;
use App\Services\NutritionCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Support\UnitConverter;

class NutritionPlansController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                return $this->getDataTablesData($request);
            }

            $trainerId = Auth::id();

            $stats = [
                'total_plans' => NutritionPlan::where(function($q) use ($trainerId) {
                    $q->where('trainer_id', $trainerId)->orWhere('is_global', true);
                })->count(),
                'active_plans' => NutritionPlan::where(function($q) use ($trainerId) {
                    $q->where('trainer_id', $trainerId)->orWhere('is_global', true);
                })->where('status', 'active')->count(),
                'global_plans' => NutritionPlan::where('is_global', true)->count(),
                'plans_with_clients' => NutritionPlan::where('trainer_id', $trainerId)->whereNotNull('client_id')->count(),
            ];

            return view('trainer.nutrition-plans.index', compact('stats'));
        } catch (\Exception $e) {
            Log::error('Failed to load trainer nutrition plans: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
            ]);
            return redirect()->back()->with('error', 'Failed to load nutrition plans');
        }
    }

    private function getDataTablesData(Request $request): JsonResponse
    {
        try {
            $draw = $request->get('draw');
            $start = $request->get('start');
            $length = $request->get('length');
            $search = $request->get('search')['value'] ?? '';
            $statusFilter = $request->get('status_filter');
            $globalFilter = $request->get('global_filter');

            $trainerId = Auth::id();

            $query = NutritionPlan::with(['trainer:id,name', 'client:id,name', 'meals', 'restrictions'])
                ->select('nutrition_plans.*')
                ->where(function($q) use ($trainerId) {
                    $q->where('trainer_id', $trainerId)->orWhere('is_global', true);
                });

            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }

            if ($globalFilter !== null && $globalFilter !== '') {
                $query->where('is_global', $globalFilter === '1');
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('plan_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('goal_type', 'like', "%{$search}%")
                      ->orWhereHas('client', function($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $totalRecords = NutritionPlan::where(function($q) use ($trainerId) {
                $q->where('trainer_id', $trainerId)->orWhere('is_global', true);
            })->count();

            $filteredRecords = $query->count();

            $plans = $query->orderBy('created_at', 'desc')
                ->skip($start)
                ->take($length)
                ->get();

            $data = [];
            foreach ($plans as $plan) {
                $data[] = [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'trainer' => $plan->trainer ? $plan->trainer->name : null,
                    'client' => $plan->client ? $plan->client->name : 'Unassigned',
                    'goal_type' => $plan->goal_type ? ucfirst(str_replace('_', ' ', $plan->goal_type)) : 'N/A',
                    'meals_count' => $plan->meals->count(),
                    'duration' => $plan->duration_text,
                    'status' => $plan->status,
                    'is_global' => $plan->is_global,
                    'is_featured' => $plan->is_featured,
                    'restrictions_summary' => $plan->restrictions ? $plan->restrictions->restrictions_summary : 'None',
                    'created_at' => $plan->created_at->format('d-m-Y H:i'),
                    'actions' => $this->generateActionButtons($plan)
                ];
            }

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Trainer nutrition plans DataTables failed: ' . $e->getMessage());
            return response()->json([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load data'
            ], 500);
        }
    }

    private function generateActionButtons(NutritionPlan $plan): string
    {
        $buttons = [];

        $buttons[] = '<a href="' . route('trainer.nutrition-plans.show', $plan->id) . '" class="btn btn-sm btn-info btn-wave" title="View Details"><i class="ri-eye-line"></i></a>';

        $canEdit = ($plan->trainer_id === Auth::id()) && !$plan->is_global;
        if ($canEdit) {
            $buttons[] = '<a href="' . route('trainer.nutrition-plans.edit', $plan->id) . '" class="btn btn-sm btn-success btn-wave" title="Edit Plan"><i class="ri-edit-2-line"></i></a>';

            $statusClass = $plan->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success';
            $statusIcon = $plan->status === 'active' ? 'ri-pause-line' : 'ri-play-line';
            $statusTitle = $plan->status === 'active' ? 'Deactivate' : 'Activate';
            $buttons[] = '<button type="button" class="btn btn-sm ' . $statusClass . ' btn-wave" onclick="toggleStatus(' . $plan->id . ')" title="' . $statusTitle . '"><i class="' . $statusIcon . '"></i></button>';

            $buttons[] = '<button type="button" class="btn btn-sm btn-danger btn-wave" onclick="deletePlan(' . $plan->id . ')" title="Delete Plan"><i class="ri-delete-bin-5-line"></i></button>';
        }

        $buttons[] = '<button type="button" class="btn btn-sm btn-warning btn-wave" onclick="duplicatePlan(' . $plan->id . ')" title="Duplicate Plan"><i class="ri-file-copy-line"></i></button>';

        return '<div class="btn-group" role="group">' . implode('', $buttons) . '</div>';
    }

    public function create(): RedirectResponse|View
    {
        try {
            $clients = User::where('role', 'client')->select('id', 'name', 'email')->orderBy('name')->get();
            $goals = Goal::where('status', 1)->select('id', 'name')->orderBy('name')->get();
            return view('trainer.nutrition-plans.create', compact('clients', 'goals'));
        } catch (\Exception $e) {
            Log::error('Failed to load trainer nutrition plan creation: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.index')->with('error', 'Failed to load creation form');
        }
    }

    public function store(Request $request)
    {
        try {
            $validGoalTypes = Goal::where('status', 1)
                ->pluck('name')
                ->map(function($name) { return strtolower(str_replace(' ', '_', $name)); })
                ->toArray();

            $rules = [
                'plan_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'client_id' => 'nullable|exists:users,id',
                'goal_type' => 'nullable|string|in:' . implode(',', $validGoalTypes),
                'duration_days' => 'nullable|integer|min:1|max:365',
                'target_weight' => 'nullable|numeric|min:30|max:1100',
                'status' => 'required|in:active,inactive,draft',
                'is_featured' => 'boolean',
                'tags' => 'nullable|array',
                'media_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            $mediaUrl = null;
            if ($request->hasFile('media_file')) {
                $mediaUrl = $request->file('media_file')->store('nutrition-plans', 'public');
            }

            $plan = NutritionPlan::create([
                'plan_name' => $request->plan_name,
                'description' => $request->description,
                'category' => $request->category,
                'trainer_id' => Auth::id(),
                'client_id' => $request->client_id,
                'goal_type' => $request->goal_type,
                'duration_days' => $request->duration_days,
                'target_weight' => UnitConverter::lbsToKg($request->target_weight !== null ? (float) $request->target_weight : null),
                'status' => $request->status,
                'is_global' => false,
                'is_featured' => $request->boolean('is_featured'),
                'tags' => $request->tags,
                'image_url' => $mediaUrl
            ]);

            if ($request->filled(['protein', 'carbs', 'fats', 'total_calories'])) {
                NutritionMacro::create([
                    'plan_id' => $plan->id,
                    'protein' => $request->protein ?? 0,
                    'carbs' => $request->carbs ?? 0,
                    'fats' => $request->fats ?? 0,
                    'total_calories' => $request->total_calories ?? 0,
                    'macro_type' => 'daily_target'
                ]);
            }

            $restrictionFields = [
                'vegetarian','vegan','pescatarian','keto','paleo','mediterranean','low_carb','low_fat','high_protein','gluten_free','dairy_free','nut_free','soy_free','egg_free','shellfish_free','fish_free','sesame_free','diabetic_friendly','heart_healthy','low_sodium','low_sugar'
            ];

            $hasRestrictions = false;
            $restrictionData = ['plan_id' => $plan->id];
            foreach ($restrictionFields as $field) {
                $restrictionData[$field] = $request->boolean($field);
                if ($request->boolean($field)) { $hasRestrictions = true; }
            }
            if ($hasRestrictions) { NutritionRestriction::create($restrictionData); }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Nutrition plan created successfully', 'plan' => $plan]);
            }
            return redirect()->route('trainer.nutrition-plans.show', $plan->id)->with('success', 'Nutrition plan created successfully');
        } catch (\Exception $e) {
            Log::error('Trainer failed to create nutrition plan: ' . $e->getMessage(), ['trainer_id' => Auth::id()]);
            if ($request->ajax()) { return response()->json(['success' => false, 'message' => 'Failed to create nutrition plan'], 500); }
            return back()->with('error', 'Failed to create nutrition plan')->withInput();
        }
    }

    public function show(int $id): View|RedirectResponse
    {
        try {
            $trainerId = Auth::id();
            $plan = NutritionPlan::with(['trainer:id,name,email,profile_image','client:id,name,email,profile_image','meals' => function($q){$q->orderBy('sort_order');},'recipes' => function($q){$q->orderBy('sort_order');},'dailyMacros','restrictions'])
                ->where(function($q) use ($trainerId) { $q->where('trainer_id', $trainerId)->orWhere('is_global', true); })
                ->findOrFail($id);

            $stats = [
                'total_meals' => $plan->meals->count(),
                'total_recipes' => $plan->recipes->count(),
                'total_calories' => $plan->meals->sum('calories_per_serving'),
                'avg_prep_time' => $plan->meals->avg('prep_time'),
                'meal_types' => $plan->meals->groupBy('meal_type')->map->count()
            ];

            return view('trainer.nutrition-plans.show', compact('plan', 'stats'));
        } catch (\Exception $e) {
            Log::error('Trainer failed to load nutrition plan: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.index')->with('error', 'Nutrition plan not found');
        }
    }

    public function edit(int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::with(['restrictions'])->where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($id);
            $clients = User::where('role', 'client')->select('id', 'name', 'email')->orderBy('name')->get();
            $goals = Goal::where('status', 1)->select('id', 'name')->orderBy('name')->get();
            return view('trainer.nutrition-plans.edit', compact('plan', 'clients', 'goals'));
        } catch (\Exception $e) {
            Log::error('Trainer failed to load nutrition plan edit form: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.index')->with('error', 'Nutrition plan not found or access denied');
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($id);

            $validGoalTypes = Goal::where('status', 1)->pluck('name')->map(function($name){return strtolower(str_replace(' ', '_', $name));})->toArray();
            $rules = [
                'plan_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'client_id' => 'nullable|exists:users,id',
                'goal_type' => 'nullable|string|in:' . implode(',', $validGoalTypes),
                'duration_days' => 'nullable|integer|min:1|max:365',
                'target_weight' => 'nullable|numeric|min:30|max:1100',
                'status' => 'required|in:active,inactive,draft',
                'is_featured' => 'boolean',
                'tags' => 'nullable|array',
                'media_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()],422);} 
                return back()->withErrors($validator)->withInput();
            }

            $mediaUrl = $plan->image_url;
            if ($request->hasFile('media_file')) {
                if ($plan->image_url) { Storage::disk('public')->delete($plan->image_url); }
                $mediaUrl = $request->file('media_file')->store('nutrition-plans', 'public');
            }

            $plan->update([
                'plan_name' => $request->plan_name,
                'description' => $request->description,
                'category' => $request->category,
                'client_id' => $request->client_id,
                'goal_type' => $request->goal_type,
                'duration_days' => $request->duration_days,
                'target_weight' => UnitConverter::lbsToKg($request->target_weight !== null ? (float) $request->target_weight : null),
                'status' => $request->status,
                'is_featured' => $request->boolean('is_featured'),
                'tags' => $request->tags,
                'image_url' => $mediaUrl
            ]);

            if ($request->filled(['protein','carbs','fats','total_calories'])) {
                NutritionMacro::updateOrCreate(
                    ['plan_id' => $plan->id, 'macro_type' => 'daily_target'],
                    [
                        'protein' => $request->protein ?? 0,
                        'carbs' => $request->carbs ?? 0,
                        'fats' => $request->fats ?? 0,
                        'total_calories' => $request->total_calories ?? 0
                    ]
                );
            }

            $restrictionFields = ['vegetarian','vegan','pescatarian','keto','paleo','mediterranean','low_carb','low_fat','high_protein','gluten_free','dairy_free','nut_free','soy_free','egg_free','shellfish_free','fish_free','sesame_free','diabetic_friendly','heart_healthy','low_sodium','low_sugar'];
            $restrictionData = ['plan_id' => $plan->id];
            foreach ($restrictionFields as $field) { $restrictionData[$field] = $request->boolean($field); }
            NutritionRestriction::updateOrCreate(['plan_id' => $plan->id], $restrictionData);

            if ($request->ajax()) { return response()->json(['success'=>true,'message'=>'Nutrition plan updated successfully','plan'=>$plan->fresh()]); }
            return redirect()->route('trainer.nutrition-plans.show', $plan->id)->with('success', 'Nutrition plan updated successfully');
        } catch (\Exception $e) {
            Log::error('Trainer failed to update nutrition plan: ' . $e->getMessage(), ['trainer_id'=>Auth::id(),'plan_id'=>$id]);
            if ($request->ajax()) { return response()->json(['success'=>false,'message'=>'Failed to update nutrition plan'],500);} 
            return back()->with('error','Failed to update nutrition plan')->withInput();
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($id);
            if ($plan->image_url) { Storage::disk('public')->delete($plan->image_url); }
            $plan->delete();
            return response()->json(['success' => true, 'message' => 'Nutrition plan deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Trainer failed to delete nutrition plan: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete nutrition plan'], 500);
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->where('is_global', false)->findOrFail($id);
            $newStatus = $plan->status === 'active' ? 'inactive' : 'active';
            $plan->update(['status' => $newStatus]);
            return response()->json(['success' => true, 'message' => 'Plan status updated successfully', 'new_status' => $newStatus]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to toggle plan status: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update plan status'], 500);
        }
    }

    public function duplicate(int $id): JsonResponse
    {
        try {
            $accessiblePlan = NutritionPlan::with(['meals','dailyMacros','restrictions'])
                ->where(function($q){ $q->where('trainer_id', Auth::id())->orWhere('is_global', true); })
                ->findOrFail($id);

            $duplicate = $accessiblePlan->replicate();
            $duplicate->plan_name = $accessiblePlan->plan_name . ' (Copy)';
            $duplicate->status = 'draft';
            $duplicate->client_id = null;
            $duplicate->trainer_id = Auth::id();
            $duplicate->is_global = false;
            $duplicate->save();

            foreach ($accessiblePlan->meals as $meal) {
                $copyMeal = $meal->replicate();
                $copyMeal->plan_id = $duplicate->id;
                $copyMeal->save();
            }
            if ($accessiblePlan->dailyMacros) {
                $copyMacros = $accessiblePlan->dailyMacros->replicate();
                $copyMacros->plan_id = $duplicate->id;
                $copyMacros->save();
            }
            if ($accessiblePlan->restrictions) {
                $copyRestr = $accessiblePlan->restrictions->replicate();
                $copyRestr->plan_id = $duplicate->id;
                $copyRestr->save();
            }

            return response()->json(['success' => true, 'message' => 'Plan duplicated successfully', 'duplicate_plan' => $duplicate]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to duplicate plan: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to duplicate plan'], 500);
        }
    }

    public function deleteMedia(int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->findOrFail($id);
            if ($plan->image_url) {
                Storage::disk('public')->delete($plan->image_url);
                $plan->update(['image_url' => null]);
                return response()->json(['success' => true, 'message' => 'Media file deleted successfully']);
            }
            return response()->json(['success' => false, 'message' => 'No media file found'], 404);
        } catch (\Exception $e) {
            Log::error('Trainer failed to delete nutrition media: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete media'], 500);
        }
    }

    public function getCategories()
    {
        try {
            $categories = NutritionPlan::getCategories();
            return response()->json(['success' => true, 'categories' => $categories]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to get categories: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load categories'], 500);
        }
    }

    public function calculator(int $id)
    {
        try {
            $trainerId = Auth::id();
            $plan = NutritionPlan::with(['client','trainer','recommendations'])
                ->where(function($q) use ($trainerId){ $q->where('trainer_id',$trainerId)->orWhere('is_global', true);} )
                ->findOrFail($id);
            $calculatorService = new NutritionCalculatorService();
            $activityLevels = $calculatorService->getActivityLevels();
            $goalTypes = $calculatorService->getGoalTypes();
            return view('trainer.nutrition-plans.calculator', compact('plan','activityLevels','goalTypes'));
        } catch (\Exception $e) {
            Log::error('Trainer failed to load calculator: ' . $e->getMessage());
            return redirect()->route('trainer.nutrition-plans.index')->with('error','Plan not found');
        }
    }

    public function calculateNutrition(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'weight' => 'required|numeric|min:1|max:1100',
                'height' => 'required|numeric|min:1|max:300',
                'age' => 'required|integer|min:1|max:120',
                'gender' => 'required|in:male,female',
                'activity_level' => 'required|string',
                'goal_type' => 'required|string'
            ]);
            if ($validator->fails()) { return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()],422);} 
            $calculatorService = new NutritionCalculatorService();
            $data = $request->only(['weight','height','age','gender','activity_level','goal_type']);
            $calculations = $calculatorService->calculateNutrition($data);
            return response()->json(['success'=>true,'message'=>'Nutrition calculated successfully','data'=>$calculations]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to calculate nutrition: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to calculate nutrition'],500);
        }
    }

    public function saveCalculatedNutrition(Request $request, int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::where('trainer_id', Auth::id())->findOrFail($id);
            $validator = Validator::make($request->all(), [
                'target_calories' => 'required|numeric|min:500|max:5000',
                'protein' => 'required|numeric|min:10|max:500',
                'carbs' => 'required|numeric|min:10|max:800',
                'fats' => 'required|numeric|min:10|max:300',
                'bmr' => 'required|numeric|min:500|max:4000',
                'tdee' => 'required|numeric|min:500|max:5000',
                'activity_level' => 'required|string',
                'macro_distribution' => 'required|array'
            ]);
            if ($validator->fails()) { return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$validator->errors()],422);} 

            $data = [
                'plan_id' => $plan->id,
                'target_calories' => $request->target_calories,
                'protein' => $request->protein,
                'carbs' => $request->carbs,
                'fats' => $request->fats,
                'bmr' => $request->bmr,
                'tdee' => $request->tdee,
                'activity_level' => $request->activity_level,
                'calculation_method' => 'mifflin_st_jeor',
                'macro_distribution' => json_encode($request->macro_distribution)
            ];

            if ($plan->recommendations) { $plan->recommendations->update($data); $message = 'Nutrition recommendations updated successfully'; }
            else { NutritionRecommendation::create($data); $message = 'Nutrition recommendations saved successfully'; }

            return response()->json(['success'=>true,'message'=>$message]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to save calculated nutrition: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to save nutrition recommendations'],500);
        }
    }

    public function getCalculatorData(int $id): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $plan = NutritionPlan::with(['client','recommendations'])
                ->where(function($q) use ($trainerId){ $q->where('trainer_id',$trainerId)->orWhere('is_global', true);} )
                ->findOrFail($id);
            $calculatorService = new NutritionCalculatorService();
            $data = [
                'plan' => [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'goal_type' => $plan->goal_type,
                    'client' => $plan->client ? ['id'=>$plan->client->id,'name'=>$plan->client->name,'email'=>$plan->client->email] : null
                ],
                'recommendations' => $plan->recommendations ? [
                    'target_calories' => $plan->recommendations->target_calories,
                    'protein' => $plan->recommendations->protein,
                    'carbs' => $plan->recommendations->carbs,
                    'fats' => $plan->recommendations->fats,
                    'bmr' => $plan->recommendations->bmr,
                    'tdee' => $plan->recommendations->tdee,
                    'activity_level' => $plan->recommendations->activity_level,
                    'macro_distribution' => json_decode($plan->recommendations->macro_distribution, true)
                ] : null,
                'activity_levels' => array_values($calculatorService->getActivityLevels()),
                'goal_types' => array_values($calculatorService->getGoalTypes())
            ];
            return response()->json(['success'=>true,'data'=>$data]);
        } catch (\Exception $e) {
            Log::error('Trainer failed to get calculator data: ' . $e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to load calculator data'],500);
        }
    }
}