<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Admin Trainees Controller
 * 
 * Handles complete CRUD operations for trainee (client role) management in admin panel
 * Provides specialized functionality for managing users with 'client' role
 * Supports both web and AJAX responses with DataTables integration
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Admin
 * @category    Trainee Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class TraineesController extends Controller
{
    /**
     * Display a listing of trainees (users with client role)
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
            
            // Build query for trainees only (client role)
            $query = User::with(['receivedTestimonials', 'goals'])
                        ->where('role', 'client');
            
            // Apply status filter
            if ($status !== 'all') {
                if ($status === 'active') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }
            
            // Get trainees with pagination
            $trainees = $query->latest()->paginate(20);
            
            // Get statistics for dashboard cards
            $stats = [
                'total_trainees' => User::where('role', 'client')->count(),
                'active_trainees' => User::where('role', 'client')->whereNotNull('email_verified_at')->count(),
                'inactive_trainees' => User::where('role', 'client')->whereNull('email_verified_at')->count(),
                'trainees_with_goals' => User::where('role', 'client')->whereHas('goals')->count(),
            ];
            
            return view('admin.trainees.index', compact('trainees', 'stats', 'status'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainees list: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to load trainees: ' . $e->getMessage());
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
            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search')['value'] ?? '';
            $status = $request->get('status', 'all');
            
            // Build query for trainees only
            $query = User::with(['receivedTestimonials', 'goals'])
                        ->where('role', 'client');
            
            // Apply status filter
            if ($status !== 'all') {
                if ($status === 'active') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }
            
            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            // Get total count before pagination
            $totalRecords = User::where('role', 'client')->count();
            $filteredRecords = $query->count();
            
            // Apply pagination
            $trainees = $query->skip($start)->take($length)->latest()->get();
            
            // Format data for DataTables
            $data = $trainees->map(function($trainee) {
                return [
                    'id' => $trainee->id,
                    'name' => $trainee->name,
                    'email' => $trainee->email,
                    'phone' => $trainee->phone ?? 'N/A',
                    'status' => $trainee->email_verified_at ? 'Active' : 'Inactive',
                    'profile_image' => $trainee->profile_image ? asset('storage/' . $trainee->profile_image) : null,
                    'goals_count' => $trainee->goals->count(),
                    'testimonials_count' => $trainee->receivedTestimonials->count(),
                    'created_at' => $trainee->created_at->format('d-m-Y H:i'),
                    'updated_at' => $trainee->updated_at->format('d-m-Y H:i'),
                ];
            });
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('DataTables request failed: ' . $e->getMessage());
            
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
     * Show the form for creating a new trainee
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        try {
            return view('admin.trainees.create');
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainee creation form: ' . $e->getMessage());
            return redirect()->route('admin.trainees.index')->with('error', 'Failed to load creation form');
        }
    }

    /**
     * Store a newly created trainee in storage
     * Supports both web and AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Define validation rules for trainee
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            // Validate input
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
            
            // Create new trainee with client role
            $traineeData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'client', // Force client role for trainees
                'email_verified_at' => now(), // Auto-verify admin created users
            ];
            
            $trainee = User::create($traineeData);
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $imagePath = $request->file('profile_image')->store('profile-images', 'public');
                $trainee->update(['profile_image' => $imagePath]);
            }
            
            // Log trainee creation
            Log::info('New trainee created by admin', [
                'admin_id' => Auth::id(),
                'trainee_id' => $trainee->id,
                'trainee_email' => $trainee->email
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trainee created successfully',
                    'trainee' => $trainee
                ]);
            }
            
            return redirect()->route('admin.trainees.index')
                           ->with('success', 'Trainee created successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to create trainee: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'request_data' => $request->except(['password', 'password_confirmation']),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create trainee: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create trainee: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified trainee
     * 
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            // Find trainee with client role only
            $trainee = User::with(['goals', 'receivedTestimonials.client', 'receivedTestimonials.trainer'])
                          ->where('role', 'client')
                          ->findOrFail($id);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'trainee' => $trainee
                ]);
            }
            
            return view('admin.trainees.show', compact('trainee'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainee details: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainee_id' => $id
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainee not found'
                ], 404);
            }
            
            return redirect()->route('admin.trainees.index')
                           ->with('error', 'Trainee not found');
        }
    }

    /**
     * Show the form for editing the specified trainee
     * 
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        try {
            // Find trainee with client role only
            $trainee = User::where('role', 'client')->findOrFail($id);
            
            return view('admin.trainees.edit', compact('trainee'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainee edit form: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainee_id' => $id
            ]);
            
            return redirect()->route('admin.trainees.index')
                           ->with('error', 'Trainee not found');
        }
    }

    /**
     * Update the specified trainee in storage
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Find trainee with client role only
            $trainee = User::where('role', 'client')->findOrFail($id);
            
            // Define validation rules
            $rules = [
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', Rule::unique('users')->ignore($trainee->id)],
                'password' => 'nullable|string|min:8|confirmed',
                'phone' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($trainee->id)],
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            // Validate input
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
            
            // Prepare update data
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ];
            
            // Update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }
            
            // Update trainee
            $trainee->update($updateData);
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($trainee->profile_image) {
                    Storage::disk('public')->delete($trainee->profile_image);
                }
                
                $imagePath = $request->file('profile_image')->store('profile-images', 'public');
                $trainee->update(['profile_image' => $imagePath]);
            }
            
            // Log trainee update
            Log::info('Trainee updated by admin', [
                'admin_id' => Auth::id(),
                'trainee_id' => $trainee->id,
                'trainee_email' => $trainee->email
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trainee updated successfully',
                    'trainee' => $trainee->fresh()
                ]);
            }
            
            return redirect()->route('admin.trainees.index')
                           ->with('success', 'Trainee updated successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to update trainee: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainee_id' => $id,
                'request_data' => $request->except(['password', 'password_confirmation']),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update trainee: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update trainee: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified trainee from storage
     * 
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Find trainee with client role only
            $trainee = User::where('role', 'client')->findOrFail($id);
            
            // Delete profile image if exists
            if ($trainee->profile_image) {
                Storage::disk('public')->delete($trainee->profile_image);
            }
            
            // Log trainee deletion
            Log::info('Trainee deleted by admin', [
                'admin_id' => Auth::id(),
                'trainee_id' => $trainee->id,
                'trainee_email' => $trainee->email
            ]);
            
            // Delete trainee
            $trainee->delete();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trainee deleted successfully'
                ]);
            }
            
            return redirect()->route('admin.trainees.index')
                           ->with('success', 'Trainee deleted successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to delete trainee: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainee_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete trainee: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete trainee: ' . $e->getMessage());
        }
    }

    /**
     * Toggle trainee status (active/inactive)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            // Find trainee with client role only
            $trainee = User::where('role', 'client')->findOrFail($id);
            
            // Toggle email verification status
            $newStatus = $trainee->email_verified_at ? null : now();
            $trainee->update(['email_verified_at' => $newStatus]);
            
            // Log status change
            Log::info('Trainee status toggled by admin', [
                'admin_id' => Auth::id(),
                'trainee_id' => $trainee->id,
                'new_status' => $newStatus ? 'active' : 'inactive'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Trainee status updated successfully',
                'status' => $newStatus ? 'Active' : 'Inactive'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle trainee status: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainee_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete trainee profile image
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Request $request, $id)
    {
        try {
            // Find trainee with client role only
            $trainee = User::where('role', 'client')->findOrFail($id);
            
            if ($trainee->profile_image) {
                // Delete image file
                Storage::disk('public')->delete($trainee->profile_image);
                
                // Update database
                $trainee->update(['profile_image' => null]);
                
                // Log image deletion
                Log::info('Trainee profile image deleted by admin', [
                    'admin_id' => Auth::id(),
                    'trainee_id' => $trainee->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Profile image deleted successfully'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No profile image found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete trainee profile image: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainee_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    public function subscriptions(Request $request, $id)
    {
        try {
            $trainee = User::where('role', 'client')->findOrFail($id);

            $subscriptions = \App\Models\TrainerSubscription::where('client_id', $trainee->id)
                ->with(['trainer:id,name,email,phone,profile_image,designation'])
                ->orderBy('subscribed_at', 'desc')
                ->paginate(20);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $subscriptions
                ]);
            }

            return view('admin.trainees.subscriptions', compact('trainee', 'subscriptions'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load trainee subscriptions: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load subscriptions'
                ], 500);
            }
            return redirect()->route('admin.trainees.index')->with('error', 'Failed to load subscriptions');
        }
    }
}