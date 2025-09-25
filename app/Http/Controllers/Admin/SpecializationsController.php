<?php

/**
 * Admin Specializations Controller
 * 
 * Handles specialization CRUD operations in admin panel
 * Manages trainer specializations that can be assigned to trainers
 * Provides both web and AJAX responses for DataTables
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Admin
 * @category    Trainer Specializations
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 * @created     2025-01-19
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SpecializationsController extends Controller
{
    /**
     * Display a listing of specializations with their statistics
     * Supports AJAX DataTables requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Check if this is an AJAX DataTables request
            if ($request->ajax()) {
                return $this->getDataTablesData($request);
            }
            
            // Get filter parameters
            $status = $request->get('status', 'all');
            
            // Get specializations with trainer counts
            $query = Specialization::withCount('trainers');
            
            // Apply status filter
            if ($status === 'active') {
                $query->where('status', true);
            } elseif ($status === 'inactive') {
                $query->where('status', false);
            }
            
            $specializations = $query->orderBy('created_at', 'desc')->paginate(15);
            
            // Get statistics
            $stats = [
                'total' => Specialization::count(),
                'active' => Specialization::where('status', true)->count(),
                'inactive' => Specialization::where('status', false)->count(),
                'with_trainers' => Specialization::has('trainers')->count(),
            ];
            
            return view('admin.specializations.index', compact('specializations', 'stats', 'status'));
            
        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@index: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading specializations.');
        }
    }

    /**
     * Get DataTables formatted data for AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function getDataTablesData(Request $request): JsonResponse
    {
        try {
            $draw = $request->get('draw');
            $start = $request->get('start');
            $length = $request->get('length');
            $search = $request->get('search')['value'] ?? '';
            
            // Base query with trainer counts
            $query = Specialization::withCount('trainers');
            
            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }
            
            // Get total count before pagination
            $totalRecords = Specialization::count();
            $filteredRecords = $query->count();
            
            // Apply pagination
            $specializations = $query->orderBy('created_at', 'desc')
                                   ->skip($start)
                                   ->take($length)
                                   ->get();
            
            // Format data for DataTables
            $data = [];
            foreach ($specializations as $specialization) {
                $data[] = [
                    'id' => $specialization->id,
                    'name' => $specialization->name,
                    'description' => $specialization->description ? substr($specialization->description, 0, 30) . '...' : 'No description',
                    'status' => $specialization->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>',
                    'trainers_count' => $specialization->trainers_count,
                    'created_at' => $specialization->created_at->format('d/m/Y H:i'),
                    'actions' => $this->getActionButtons($specialization)
                ];
            }
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@getDataTablesData: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while loading data.'], 500);
        }
    }

    /**
     * Generate action buttons for DataTables
     * 
     * @param  \App\Models\Specialization  $specialization
     * @return string
     */
    private function getActionButtons(Specialization $specialization): string
    {
        $buttons = '';
        
        // Edit button
        $buttons .= '<a href="' . route('admin.specializations.edit', $specialization) . '" class="btn btn-sm btn-primary me-1" title="Edit">
                        <i class="ri-edit-2-line"></i>
                    </a>';
        
        // Status toggle button
        if ($specialization->status) {
            $buttons .= '<button type="button" class="btn btn-sm btn-warning me-1 toggle-status" 
                            data-id="' . $specialization->id . '" 
                            data-status="0" 
                            title="Deactivate">
                            <i class="ri-eye-off-line"></i>
                        </button>';
        } else {
            $buttons .= '<button type="button" class="btn btn-sm btn-success me-1 toggle-status" 
                            data-id="' . $specialization->id . '" 
                            data-status="1" 
                            title="Activate">
                            <i class="ri-eye-line"></i>
                        </button>';
        }
        
        // Delete button (only if no trainers assigned)
        if ($specialization->trainers_count == 0) {
            $buttons .= '<button type="button" class="btn btn-sm btn-danger delete-specialization" 
                            data-id="' . $specialization->id . '" 
                            title="Delete">
                            <i class="ri-delete-bin-line"></i>
                        </button>';
        }
        
        return $buttons;
    }

    /**
     * Show the form for creating a new specialization.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        try {
            return view('admin.specializations.create');
        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@create: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading the create form.');
        }
    }

    /**
     * Store a newly created specialization in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:specializations,name',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            // Create specialization
            $specialization = Specialization::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status
            ]);

            // Log the action
            Log::info('Specialization created', [
                'specialization_id' => $specialization->id,
                'name' => $specialization->name,
                'created_by' => Auth::id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Specialization created successfully.',
                    'specialization' => $specialization
                ]);
            }

            return redirect()->route('admin.specializations.index')
                           ->with('success', 'Specialization created successfully.');

        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@store: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while creating the specialization.'
                ], 500);
            }
            
            return back()->with('error', 'An error occurred while creating the specialization.')
                         ->withInput();
        }
    }

    /**
     * Display the specified specialization.
     * 
     * @param  \App\Models\Specialization  $specialization
     * @return \Illuminate\View\View
     */
    public function show(Specialization $specialization)
    {
        try {
            // Load trainers with this specialization
            $trainers = $specialization->trainers()
                                     ->select('id', 'name', 'email', 'phone', 'created_at')
                                     ->paginate(10);
            
            return view('admin.specializations.show', compact('specialization', 'trainers'));
            
        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@show: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading the specialization.');
        }
    }

    /**
     * Show the form for editing the specified specialization.
     * 
     * @param  \App\Models\Specialization  $specialization
     * @return \Illuminate\View\View
     */
    public function edit(Specialization $specialization)
    {
        try {
            return view('admin.specializations.edit', compact('specialization'));
        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@edit: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading the edit form.');
        }
    }

    /**
     * Update the specified specialization in storage.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Specialization  $specialization
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Specialization $specialization)
    { 
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('specializations', 'name')->ignore($specialization->id)
                ],
                'description' => 'nullable|string|max:1000',
                'status' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            // Update specialization
            $specialization->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status
            ]);

            // Log the action
            Log::info('Specialization updated', [
                'specialization_id' => $specialization->id,
                'name' => $specialization->name,
                'updated_by' => Auth::id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Specialization updated successfully.',
                    'specialization' => $specialization
                ]);
            }

            return redirect()->route('admin.specializations.index')
                           ->with('success', 'Specialization updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@update: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the specialization.'
                ], 500);
            }
            
            return back()->with('error', 'An error occurred while updating the specialization.')
                         ->withInput();
        }
    }

    /**
     * Remove the specified specialization from storage.
     * 
     * @param  \App\Models\Specialization  $specialization
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Specialization $specialization)
    {
        try {
            // Check if specialization has trainers assigned
            if ($specialization->trainers()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete specialization that has trainers assigned to it.'
                ], 422);
            }

            // Store name for logging
            $specializationName = $specialization->name;
            $specializationId = $specialization->id;

            // Delete specialization
            $specialization->delete();

            // Log the action
            Log::info('Specialization deleted', [
                'specialization_id' => $specializationId,
                'name' => $specializationName,
                'deleted_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Specialization deleted successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the specialization.'
            ], 500);
        }
    }

    /**
     * Toggle specialization status (active/inactive).
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Specialization  $specialization
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, Specialization $specialization)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $specialization->update(['status' => $request->status]);

            // Log the action
            Log::info('Specialization status toggled', [
                'specialization_id' => $specialization->id,
                'name' => $specialization->name,
                'new_status' => $request->status,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Specialization status updated successfully.',
                'status' => $specialization->status
            ]);

        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@toggleStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the status.'
            ], 500);
        }
    }

    /**
     * Get active specializations for select dropdown (AJAX).
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveSpecializations()
    {
        try {
            $specializations = Specialization::active()
                                           ->select('id', 'name')
                                           ->orderBy('name')
                                           ->get();

            return response()->json([
                'success' => true,
                'specializations' => $specializations
            ]);

        } catch (\Exception $e) {
            Log::error('Error in SpecializationsController@getActiveSpecializations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading specializations.'
            ], 500);
        }
    }
}