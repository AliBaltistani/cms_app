<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NutritionPlan;
use App\Models\NutritionMeal;
use App\Models\NutritionMacro;
use App\Models\NutritionRestriction;
use App\Models\NutritionRecommendation;
use App\Models\FoodDiary;
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
use Illuminate\Support\Facades\Redirect;
use App\Support\UnitConverter;
/**
 * NutritionPlansController
 * 
 * Handles admin web-based CRUD operations for nutrition plans
 * Provides full management capabilities for nutrition plans, meals, macros, and restrictions
 * 
 * @package App\Http\Controllers\Admin
 * @author Go Globe CMS Team
 * @since 1.0.0
 */
class NutritionPlansController extends Controller
{
    /**
     * Display a listing of nutrition plans with DataTables support
     * 
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Handle AJAX DataTables request
            if ($request->ajax()) {
                Log::info('AJAX DataTables request received', [
                    'admin_id' => Auth::id(),
                    'request_data' => $request->all()
                ]);
                return $this->getDataTablesData($request);
            }
            
            // Get statistics for dashboard cards
            $stats = [
                'total_plans' => NutritionPlan::count(),
                'active_plans' => NutritionPlan::where('status', 'active')->count(),
                'global_plans' => NutritionPlan::where('is_global', true)->count(),
                'plans_with_clients' => NutritionPlan::whereNotNull('client_id')->count(),
            ];
            
            // Get filter options
            $trainers = User::where('role', 'trainer')
                          ->select('id', 'name')
                          ->orderBy('name')
                          ->get();
            
            $clients = User::where('role', 'client')
                         ->select('id', 'name')
                         ->orderBy('name')
                         ->get();
            
            return view('admin.nutrition-plans.index', compact('stats', 'trainers', 'clients'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition plans list: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to load nutrition plans: ' . $e->getMessage());
        }
    }

    /**
     * Process DataTables AJAX request
     * 
     * @param Request $request
     * @return JsonResponse
     */
    private function getDataTablesData(Request $request): JsonResponse
    {
        try {
            $draw = $request->get('draw');
            $start = $request->get('start');
            $length = $request->get('length');
            $search = $request->get('search')['value'] ?? '';
            
            // Get filter parameters
            $statusFilter = $request->get('status_filter');
            $trainerFilter = $request->get('trainer_filter');
            $clientFilter = $request->get('client_filter');
            $globalFilter = $request->get('global_filter');
            
            // Build query
            $query = NutritionPlan::with(['trainer:id,name', 'client:id,name', 'meals', 'restrictions'])
                                 ->select('nutrition_plans.*');
            
            // Apply filters
            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            
            if ($trainerFilter) {
                $query->where('trainer_id', $trainerFilter);
            }
            
            if ($clientFilter) {
                $query->where('client_id', $clientFilter);
            }
            
            if ($globalFilter !== null) {
                $query->where('is_global', $globalFilter === '1');
            }
            
            // Apply search
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('plan_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('goal_type', 'like', "%{$search}%")
                      ->orWhereHas('trainer', function($trainerQuery) use ($search) {
                          $trainerQuery->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('client', function($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }
            
            // Get total count before pagination
            $totalRecords = NutritionPlan::count();
            $filteredRecords = $query->count();
            
            // Apply pagination and ordering
            $plans = $query->orderBy('created_at', 'desc')
                          ->skip($start)
                          ->take($length)
                          ->get();
            
            // Format data for DataTables
            $data = [];
            foreach ($plans as $plan) {
                $data[] = [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'trainer' => $plan->trainer ? $plan->trainer->name : 'Admin',
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
            
            $response = [
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ];
            
            Log::info('DataTables response prepared', [
                'admin_id' => Auth::id(),
                'total_records' => $totalRecords,
                'filtered_records' => $filteredRecords,
                'data_count' => count($data)
            ]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Nutrition plans DataTables request failed: ' . $e->getMessage());
            
            return response()->json([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Failed to load data'
            ], 500);
        }
    }

    /**
     * Generate action buttons for DataTables
     * 
     * @param NutritionPlan $plan
     * @return string
     */
    private function generateActionButtons(NutritionPlan $plan): string
    {
        $buttons = [];
        
        // View button
        $buttons[] = '<a href="' . route('admin.nutrition-plans.show', $plan->id) . '" class="btn btn-sm btn-info btn-wave" title="View Details">' .
                    '<i class="ri-eye-line"></i></a>';
        
        // Edit button
        $buttons[] = '<a href="' . route('admin.nutrition-plans.edit', $plan->id) . '" class="btn btn-sm btn-success btn-wave" title="Edit Plan">' .
                    '<i class="ri-edit-2-line"></i></a>';
        
        // Duplicate button
        $buttons[] = '<button type="button" class="btn btn-sm btn-warning btn-wave" onclick="duplicatePlan(' . $plan->id . ')" title="Duplicate Plan">' .
                    '<i class="ri-file-copy-line"></i></button>';
        
        // Status toggle button
        $statusClass = $plan->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success';
        $statusIcon = $plan->status === 'active' ? 'ri-pause-line' : 'ri-play-line';
        $statusTitle = $plan->status === 'active' ? 'Deactivate' : 'Activate';
        
        $buttons[] = '<button type="button" class="btn btn-sm ' . $statusClass . ' btn-wave" onclick="toggleStatus(' . $plan->id . ')" title="' . $statusTitle . '">' .
                    '<i class="' . $statusIcon . '"></i></button>';
        
        // Delete button
        $buttons[] = '<button type="button" class="btn btn-sm btn-danger btn-wave" onclick="deletePlan(' . $plan->id . ')" title="Delete Plan">' .
                    '<i class="ri-delete-bin-5-line"></i></button>';
        
        return '<div class="btn-group" role="group">' . implode('', $buttons) . '</div>';
    }

    /**
     * Show the form for creating a new nutrition plan
     * 
     * @return View
     */
    public function create(): RedirectResponse|View
    {
        try {
            // Get trainers and clients for dropdowns
            $trainers = User::where('role', 'trainer')
                          ->select('id', 'name', 'email')
                          ->orderBy('name')
                          ->get();
            
            $clients = User::where('role', 'client')
                         ->select('id', 'name', 'email')
                         ->orderBy('name')
                         ->get();
            
            // Get active goals for goal type dropdown
            $goals = Goal::where('status', 1)
                        ->select('id', 'name')
                        ->orderBy('name')
                        ->get();
            
            return view('admin.nutrition-plans.create', compact('trainers', 'clients', 'goals'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition plan creation form: ' . $e->getMessage());
            return redirect()->route('admin.nutrition-plans.index')->with('error', 'Failed to load creation form');
        }
    }

    /**
     * Store a newly created nutrition plan
     * 
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Get valid goal types from the goals table
            $validGoalTypes = Goal::where('status', 1)
                                 ->pluck('name')
                                 ->map(function($name) {
                                     return strtolower(str_replace(' ', '_', $name));
                                 })
                                 ->toArray();
            
            // Validation rules
            $rules = [
                'plan_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'trainer_id' => 'nullable|exists:users,id',
                'client_id' => 'nullable|exists:users,id',
                'goal_type' => 'nullable|string|in:' . implode(',', $validGoalTypes),
                'duration_days' => 'nullable|integer|min:1|max:365',
                'target_weight' => 'nullable|numeric|min:30|max:1100',
                'status' => 'required|in:active,inactive,draft',
                'is_global' => 'boolean',
                'is_featured' => 'boolean',
                'tags' => 'nullable|array',
                'media_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                // Log validation errors for debugging
                Log::error('Nutrition plan validation failed', [
                    'admin_id' => Auth::id(),
                    'validation_errors' => $validator->errors()->toArray(),
                    'request_data' => $request->except(['media_file', '_token'])
                ]);
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                return back()->withErrors($validator)->withInput();
            }
            
            // Handle media upload
            $mediaUrl = null;
            if ($request->hasFile('media_file')) {
                $mediaUrl = $request->file('media_file')->store('nutrition-plans', 'public');
            }
            
            // Create nutrition plan
            $plan = NutritionPlan::create([
                'plan_name' => $request->plan_name,
                'description' => $request->description,
                'category' => $request->category,
                'trainer_id' => $request->trainer_id,
                'client_id' => $request->client_id,
                'goal_type' => $request->goal_type,
                'duration_days' => $request->duration_days,
                'target_weight' => UnitConverter::lbsToKg($request->target_weight !== null ? (float) $request->target_weight : null),
                'status' => $request->status,
                'is_global' => $request->boolean('is_global'),
                'is_featured' => $request->boolean('is_featured'),
                'tags' => $request->tags,
                'image_url' => $mediaUrl
            ]);
            
            // Create daily macros if provided
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
            
            // Create dietary restrictions if provided
            $restrictionFields = [
                'vegetarian', 'vegan', 'pescatarian', 'keto', 'paleo', 'mediterranean',
                'low_carb', 'low_fat', 'high_protein', 'gluten_free', 'dairy_free',
                'nut_free', 'soy_free', 'egg_free', 'shellfish_free', 'fish_free',
                'sesame_free', 'diabetic_friendly', 'heart_healthy', 'low_sodium', 'low_sugar'
            ];
            
            $hasRestrictions = false;
            $restrictionData = ['plan_id' => $plan->id];
            
            foreach ($restrictionFields as $field) {
                $restrictionData[$field] = $request->boolean($field);
                if ($request->boolean($field)) {
                    $hasRestrictions = true;
                }
            }
            
            if ($hasRestrictions) {
                NutritionRestriction::create($restrictionData);
            }
            
            // Log the creation
            Log::info('Nutrition plan created successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $plan->id,
                'plan_name' => $plan->plan_name
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nutrition plan created successfully',
                    'plan' => $plan
                ]);
            }
            
            return redirect()->route('admin.nutrition-plans.show', $plan->id)
                           ->with('success', 'Nutrition plan created successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to create nutrition plan: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'request_data' => $request->except(['media_file']),
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create nutrition plan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create nutrition plan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified nutrition plan
     * 
     * @param int $id
     * @return View
     */
    public function show(int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::with([
                'trainer:id,name,email,profile_image',
                'client:id,name,email,profile_image',
                'meals' => function($query) {
                    $query->orderBy('sort_order');
                },
                'recipes' => function($query) {
                    $query->orderBy('sort_order');
                },
                'dailyMacros',
                'restrictions'
            ])->findOrFail($id);
            
            // Calculate plan statistics
            $stats = [
                'total_meals' => $plan->meals->count(),
                'total_recipes' => $plan->recipes->count(),
                'total_calories' => $plan->meals->sum('calories_per_serving'),
                'avg_prep_time' => $plan->meals->avg('prep_time'),
                'meal_types' => $plan->meals->groupBy('meal_type')->map->count()
            ];
            
            return view('admin.nutrition-plans.show', compact('plan', 'stats'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition plan details: ' . $e->getMessage());
            return redirect()->route('admin.nutrition-plans.index')->with('error', 'Nutrition plan not found');
        }
    }

    /**
     * Show the form for editing the specified nutrition plan
     * 
     * @param int $id
     * @return View
     */
    public function edit(int $id): View|RedirectResponse
    {
        try {
            $plan = NutritionPlan::with(['restrictions'])->findOrFail($id);
            
            // Get trainers and clients for dropdowns
            $trainers = User::where('role', 'trainer')
                          ->select('id', 'name', 'email')
                          ->orderBy('name')
                          ->get();
            
            $clients = User::where('role', 'client')
                         ->select('id', 'name', 'email')
                         ->orderBy('name')
                         ->get();
            
            // Get active goals for goal type dropdown
            $goals = Goal::where('status', 1)
                        ->select('id', 'name')
                        ->orderBy('name')
                        ->get();
            
            return view('admin.nutrition-plans.edit', compact('plan', 'trainers', 'clients', 'goals'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition plan edit form: ' . $e->getMessage());
            return redirect()->route('admin.nutrition-plans.index')->with('error', 'Nutrition plan not found');
        }
    }

    /**
     * Update the specified nutrition plan
     * 
     * @param Request $request
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function update(Request $request, int $id)
    {
        try {
            $plan = NutritionPlan::findOrFail($id);
            
            // Get valid goal types from the goals table
            $validGoalTypes = Goal::where('status', 1)
                                 ->pluck('name')
                                 ->map(function($name) {
                                     return strtolower(str_replace(' ', '_', $name));
                                 })
                                 ->toArray();
            
            // Validation rules
            $rules = [
                'plan_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'trainer_id' => 'nullable|exists:users,id',
                'client_id' => 'nullable|exists:users,id',
                'goal_type' => 'nullable|string|in:' . implode(',', $validGoalTypes),
                'duration_days' => 'nullable|integer|min:1|max:365',
                'target_weight' => 'nullable|numeric|min:30|max:1100',
                'status' => 'required|in:active,inactive,draft',
                'is_global' => 'boolean',
                'is_featured' => 'boolean',
                'tags' => 'nullable|array',
                'media_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                // Log validation errors for debugging
                Log::error('Nutrition plan update validation failed', [
                    'admin_id' => Auth::id(),
                    'plan_id' => $id,
                    'validation_errors' => $validator->errors()->toArray(),
                    'request_data' => $request->except(['media_file', '_token'])
                ]);
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                return back()->withErrors($validator)->withInput();
            }
            
            // Handle media upload
            $mediaUrl = $plan->image_url;
            if ($request->hasFile('media_file')) {
                // Delete old media file
                if ($plan->image_url) {
                    Storage::disk('public')->delete($plan->image_url);
                }
                
                $mediaUrl = $request->file('media_file')->store('nutrition-plans', 'public');
            }
            
            // Update nutrition plan
            $plan->update([
                'plan_name' => $request->plan_name,
                'description' => $request->description,
                'category' => $request->category,
                'trainer_id' => $request->trainer_id,
                'client_id' => $request->client_id,
                'goal_type' => $request->goal_type,
                'duration_days' => $request->duration_days,
                'target_weight' => UnitConverter::lbsToKg($request->target_weight !== null ? (float) $request->target_weight : null),
                'status' => $request->status,
                'is_global' => $request->boolean('is_global'),
                'is_featured' => $request->boolean('is_featured'),
                'tags' => $request->tags,
                'image_url' => $mediaUrl
            ]);
            
            // Update or create daily macros if provided
            if ($request->filled(['protein', 'carbs', 'fats', 'total_calories'])) {
                NutritionMacro::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'macro_type' => 'daily_target'
                    ],
                    [
                        'protein' => $request->protein ?? 0,
                        'carbs' => $request->carbs ?? 0,
                        'fats' => $request->fats ?? 0,
                        'total_calories' => $request->total_calories ?? 0
                    ]
                );
            }
            
            // Update or create dietary restrictions
            $restrictionFields = [
                'vegetarian', 'vegan', 'pescatarian', 'keto', 'paleo', 'mediterranean',
                'low_carb', 'low_fat', 'high_protein', 'gluten_free', 'dairy_free',
                'nut_free', 'soy_free', 'egg_free', 'shellfish_free', 'fish_free',
                'sesame_free', 'diabetic_friendly', 'heart_healthy', 'low_sodium', 'low_sugar'
            ];
            
            $restrictionData = ['plan_id' => $plan->id];
            
            foreach ($restrictionFields as $field) {
                $restrictionData[$field] = $request->boolean($field);
            }
            
            NutritionRestriction::updateOrCreate(
                ['plan_id' => $plan->id],
                $restrictionData
            );
            
            // Log the update
            Log::info('Nutrition plan updated successfully', [
                'admin_id' => Auth::id(),
                'plan_id' => $plan->id,
                'changes' => $request->except(['media_file'])
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nutrition plan updated successfully',
                    'plan' => $plan->fresh()
                ]);
            }
            
            return redirect()->route('admin.nutrition-plans.show', $plan->id)
                           ->with('success', 'Nutrition plan updated successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to update nutrition plan: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $id,
                'request_data' => $request->except(['media_file', '_token']),
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update nutrition plan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update nutrition plan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified nutrition plan
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($id);
            
            // Delete associated media file
            if ($plan->media_url) {
                Storage::disk('public')->delete($plan->media_url);
            }
            
            // Store plan info for logging
            $planInfo = [
                'id' => $plan->id,
                'name' => $plan->plan_name,
                'trainer_id' => $plan->trainer_id,
                'client_id' => $plan->client_id
            ];
            
            // Delete the plan (cascade will handle related records)
            $plan->delete();
            
            // Log the deletion
            Log::info('Nutrition plan deleted successfully', [
                'admin_id' => Auth::id(),
                'deleted_plan' => $planInfo
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Nutrition plan deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete nutrition plan: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete nutrition plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle nutrition plan status
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($id);
            
            $newStatus = $plan->status === 'active' ? 'inactive' : 'active';
            $plan->update(['status' => $newStatus]);
            
            // Log the status change
            Log::info('Nutrition plan status toggled', [
                'admin_id' => Auth::id(),
                'plan_id' => $plan->id,
                'old_status' => $plan->status === 'active' ? 'inactive' : 'active',
                'new_status' => $newStatus
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Plan status updated successfully',
                'new_status' => $newStatus
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle nutrition plan status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan status'
            ], 500);
        }
    }

    /**
     * Duplicate a nutrition plan
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function duplicate(int $id): JsonResponse
    {
        try {
            $originalPlan = NutritionPlan::with(['meals', 'dailyMacros', 'restrictions'])->findOrFail($id);
            
            // Create duplicate plan
            $duplicatePlan = $originalPlan->replicate();
            $duplicatePlan->plan_name = $originalPlan->plan_name . ' (Copy)';
            $duplicatePlan->status = 'draft';
            $duplicatePlan->client_id = null; // Remove client assignment
            $duplicatePlan->save();
            
            // Duplicate meals
            foreach ($originalPlan->meals as $meal) {
                $duplicateMeal = $meal->replicate();
                $duplicateMeal->plan_id = $duplicatePlan->id;
                $duplicateMeal->save();
            }
            
            // Duplicate macros
            if ($originalPlan->dailyMacros) {
                $duplicateMacros = $originalPlan->dailyMacros->replicate();
                $duplicateMacros->plan_id = $duplicatePlan->id;
                $duplicateMacros->save();
            }
            
            // Duplicate restrictions
            if ($originalPlan->restrictions) {
                $duplicateRestrictions = $originalPlan->restrictions->replicate();
                $duplicateRestrictions->plan_id = $duplicatePlan->id;
                $duplicateRestrictions->save();
            }
            
            // Log the duplication
            Log::info('Nutrition plan duplicated successfully', [
                'admin_id' => Auth::id(),
                'original_plan_id' => $originalPlan->id,
                'duplicate_plan_id' => $duplicatePlan->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Plan duplicated successfully',
                'duplicate_plan' => $duplicatePlan
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to duplicate nutrition plan: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate plan'
            ], 500);
        }
    }

    /**
     * Delete media file from nutrition plan
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function deleteMedia(int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($id);
            
            if ($plan->media_url) {
                Storage::disk('public')->delete($plan->media_url);
                $plan->update(['media_url' => null]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Media file deleted successfully'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No media file found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete nutrition plan media: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media file'
            ], 500);
        }
    }

    /**
     * Manage nutrition recommendations for a plan
     * 
     * @param int $id
     * @return View
     */
    public function recommendations(int $id)
    {
        try {
            $plan = NutritionPlan::with(['client', 'trainer', 'recommendations'])->findOrFail($id);
            
            return view('admin.nutrition-plans.recommendations', compact('plan'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition plan recommendations: ' . $e->getMessage());
            return redirect()->route('admin.nutrition-plans.index')->with('error', 'Nutrition plan not found');
        }
    }

    /**
     * Update nutrition recommendations for a plan
     * 
     * @param Request $request
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function updateRecommendations(Request $request, int $id)
    {
        try {
            $plan = NutritionPlan::findOrFail($id);
            
            // Validation rules
            $rules = [
                'target_calories' => 'required|numeric|min:800|max:5000',
                'protein' => 'required|numeric|min:0|max:500',
                'carbs' => 'required|numeric|min:0|max:800',
                'fats' => 'required|numeric|min:0|max:300'
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
            
            // Update or create recommendations
            NutritionRecommendation::updateOrCreate(
                ['plan_id' => $plan->id],
                [
                    'target_calories' => $request->target_calories,
                    'protein' => $request->protein,
                    'carbs' => $request->carbs,
                    'fats' => $request->fats
                ]
            );
            
            // Log the update
            Log::info('Nutrition recommendations updated', [
                'admin_id' => Auth::id(),
                'plan_id' => $plan->id,
                'recommendations' => $request->only(['target_calories', 'protein', 'carbs', 'fats'])
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nutrition recommendations updated successfully'
                ]);
            }
            
            return redirect()->route('admin.nutrition-plans.recommendations', $plan->id)
                           ->with('success', 'Nutrition recommendations updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to update nutrition recommendations: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update recommendations'
                ], 500);
            }
            
            return back()->with('error', 'Failed to update recommendations');
        }
    }

    /**
     * View client food diary entries for a plan
     * 
     * @param int $id
     * @param Request $request
     * @return View
     */
    public function foodDiary(int $id, Request $request)
    {
        try {
            $plan = NutritionPlan::with(['client', 'trainer'])->findOrFail($id);
            
            if (!$plan->client_id) {
                return redirect()->route('admin.nutrition-plans.index')
                               ->with('error', 'This plan is not assigned to a specific client');
            }
            
            // Get date range from request or default to last 7 days
            $startDate = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            
            // Get food diary entries
            $foodDiaryEntries = FoodDiary::where('client_id', $plan->client_id)
                                        ->whereBetween('logged_at', [$startDate, $endDate])
                                        ->with('meal')
                                        ->orderBy('logged_at', 'desc')
                                        ->get();
            
            // Group entries by date
            $entriesByDate = $foodDiaryEntries->groupBy(function($entry) {
                return $entry->logged_at->format('Y-m-d');
            });
            
            // Calculate daily summaries
            $dailySummaries = [];
            foreach ($entriesByDate as $date => $entries) {
                $dailySummaries[$date] = [
                    'total_calories' => $entries->sum('calories'),
                    'total_protein' => $entries->sum('protein'),
                    'total_carbs' => $entries->sum('carbs'),
                    'total_fats' => $entries->sum('fats'),
                    'entry_count' => $entries->count()
                ];
            }
            
            return view('admin.nutrition-plans.food-diary', compact(
                'plan', 
                'foodDiaryEntries', 
                'entriesByDate', 
                'dailySummaries', 
                'startDate', 
                'endDate'
            ));
            
        } catch (\Exception $e) {
            Log::error('Failed to load food diary: ' . $e->getMessage());
            return redirect()->route('admin.nutrition-plans.index')->with('error', 'Nutrition plan not found');
        }
    }

    /**
     * Get available categories for nutrition plans
     * 
     * @return JsonResponse
     */
    public function getCategories()
    {
        try {
            $categories = NutritionPlan::getCategories();
            
            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get nutrition plan categories: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load categories'
            ], 500);
        }
    }

    /**
     * Show nutrition calculator interface
     * 
     * @param int $id Plan ID
     * @return View|RedirectResponse
     */
    public function calculator(int $id)
    {
        try {
            $plan = NutritionPlan::with(['client', 'trainer', 'recommendations'])->findOrFail($id);
            $calculatorService = new NutritionCalculatorService();
            
            // Get available options for calculator
            $activityLevels = $calculatorService->getActivityLevels();
            $goalTypes = $calculatorService->getGoalTypes();
            
            return view('admin.nutrition-plans.calculator', compact('plan', 'activityLevels', 'goalTypes'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load nutrition calculator: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $id
            ]);
            
            return redirect()->route('admin.nutrition-plans.index')->with('error', 'Plan not found');
        }
    }

    /**
     * Calculate nutrition recommendations via AJAX
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateNutrition(Request $request): JsonResponse
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'weight' => 'required|numeric|min:1|max:1100',
                'height' => 'required|numeric|min:1|max:300',
                'age' => 'required|integer|min:1|max:120',
                'gender' => 'required|in:male,female',
                'activity_level' => 'required|string',
                'goal_type' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculate nutrition
            $calculatorService = new NutritionCalculatorService();
            $userData = $request->only(['weight', 'height', 'age', 'gender', 'activity_level', 'goal_type']);
            $calculations = $calculatorService->calculateNutrition($userData);

            return response()->json([
                'success' => true,
                'message' => 'Nutrition calculated successfully',
                'data' => $calculations
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to calculate nutrition: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate nutrition: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save calculated nutrition recommendations to plan
     * 
     * @param Request $request
     * @param int $id Plan ID
     * @return JsonResponse
     */
    public function saveCalculatedNutrition(Request $request, int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::findOrFail($id);

            // Validation
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

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update or create recommendations
            $recommendationData = [
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

            if ($plan->recommendations) {
                $plan->recommendations->update($recommendationData);
                $message = 'Nutrition recommendations updated successfully';
            } else {
                NutritionRecommendation::create($recommendationData);
                $message = 'Nutrition recommendations saved successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save calculated nutrition: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save nutrition recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get calculator data for existing plan
     * 
     * @param int $id Plan ID
     * @return JsonResponse
     */
    public function getCalculatorData(int $id): JsonResponse
    {
        try {
            $plan = NutritionPlan::with(['client', 'recommendations'])->findOrFail($id);
            $calculatorService = new NutritionCalculatorService();

            $data = [
                'plan' => [
                    'id' => $plan->id,
                    'plan_name' => $plan->plan_name,
                    'goal_type' => $plan->goal_type,
                    'client' => $plan->client ? [
                        'id' => $plan->client->id,
                        'name' => $plan->client->name,
                        'email' => $plan->client->email
                    ] : null
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

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get calculator data: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'plan_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load calculator data'
            ], 500);
        }
    }
}