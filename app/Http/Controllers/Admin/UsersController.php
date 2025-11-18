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
 * Admin Users Controller
 * 
 * Handles complete CRUD operations for user management in admin panel
 * Supports role-based filtering for trainers and clients
 * Provides both web and AJAX responses
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Admin
 * @category    User Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class UsersController extends Controller
{
    /**
     * Display a listing of users with optional role filtering
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
            $role = $request->get('role', 'all');
            $status = $request->get('status', 'all');
            
            // Build query with filters
            $query = User::with(['receivedTestimonials', 'certifications', 'goals']);
            
            // Apply role filter
            if ($role !== 'all') {
                $query->where('role', $role);
            }
            
            // Get users with pagination
            $users = $query->latest()->paginate(20);
            
            // Get statistics for dashboard cards
            $stats = [
                'total_users' => User::count(),
                'total_trainers' => User::where('role', 'trainer')->count(),
                'total_clients' => User::where('role', 'client')->count(),
                'total_admins' => User::where('role', 'admin')->count(),
                'active_users' => User::whereNotNull('email_verified_at')->count(),
            ];
            
            return view('admin.users.index', compact('users', 'stats', 'role', 'status'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load users list: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to load users: ' . $e->getMessage());
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
            $role = $request->get('role', 'all');
            $status = $request->get('status', 'all');
            
            // Build query
            $query = User::with(['receivedTestimonials', 'certifications']);
            
            // Apply role filter
            if ($role !== 'all') {
                $query->where('role', $role);
            }
            
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
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('role', 'like', "%{$search}%");
                });
            }
            
            // Get total count before pagination
            $totalRecords = User::count();
            $filteredRecords = $query->count();
            
            // Apply pagination
            $users = $query->skip($start)->take($length)->latest()->get();
            
            // Format data for DataTables
            $data = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? 'N/A',
                    'role' => ucfirst($user->role),
                    'status' => $user->email_verified_at ? 'Active' : 'Inactive',
                    'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                    'created_at' => $user->created_at->format('d-m-Y H:i'),
                    'updated_at' => $user->updated_at->format('d-m-Y H:i'),
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
     * Show the form for creating a new user
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        try {
            $roles = ['client', 'trainer', 'admin'];
            return view('admin.users.create', compact('roles'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load user creation form: ' . $e->getMessage());
            return redirect()->route('admin.users.index')->with('error', 'Failed to load creation form');
        }
    }

    /**
     * Store a newly created user in storage
     * Supports both web and AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Define validation rules based on role
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'role' => 'required|in:client,trainer,admin',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            // Add trainer-specific validation rules
            if ($request->role === 'trainer') {
                $rules = array_merge($rules, [
                    'designation' => 'nullable|string|max:255',
                    'experience' => 'nullable|integer|min:0|max:50',
                    'about' => 'nullable|string|max:1000',
                    'training_philosophy' => 'nullable|string|max:1000'
                ]);
            }
            
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
            
            // Create new user
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => $request->role,
                'email_verified_at' => now(), // Auto-verify admin created users
            ];
            
            // Add trainer-specific fields
            if ($request->role === 'trainer') {
                $userData = array_merge($userData, [
                    'designation' => $request->designation,
                    'experience' => $request->experience,
                    'about' => $request->about,
                    'training_philosophy' => $request->training_philosophy
                ]);
            }
            
            $user = User::create($userData);
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $imagePath = $request->file('profile_image')->store('profile-images', 'public');
                $user->update(['profile_image' => $imagePath]);
            }

            // Handle business logo upload (optional)
            if ($request->hasFile('business_logo')) {
                $logoPath = $request->file('business_logo')->store('business-logos', 'public');
                $user->update(['business_logo' => $logoPath]);
            }
            
            // Log user creation
            Log::info('New user created by admin', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully',
                    'user' => $user
                ]);
            }
            
            return redirect()->route('admin.users.index')
                           ->with('success', 'User created successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to create user: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'request_data' => $request->except(['password', 'password_confirmation']),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create user: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified user
     * 
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = User::with([
                'receivedTestimonials.client',
                'writtenTestimonials.trainer',
                'certifications',
                'goals',
                'workouts'
            ])->findOrFail($id);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'user' => $user
                ]);
            }
            
            return view('admin.users.show', compact('user'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load user details: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            return redirect()->route('admin.users.index')->with('error', 'User not found');
        }
    }

    /**
     * Show the form for editing the specified user
     * 
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        try {
            $user = User::findOrFail($id);
            $roles = ['client', 'trainer', 'admin'];
            
            return view('admin.users.edit', compact('user', 'roles'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load user edit form: ' . $e->getMessage());
            return redirect()->route('admin.users.index')->with('error', 'User not found');
        }
    }

    /**
     * Update the specified user in storage
     * Supports both web and AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Define validation rules
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
                'role' => 'required|in:client,trainer,admin',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            // Add password validation if provided
            if ($request->filled('password')) {
                $rules['password'] = 'string|min:8|confirmed';
            }
            
            // Add trainer-specific validation rules
            if ($request->role === 'trainer') {
                $rules = array_merge($rules, [
                    'designation' => 'nullable|string|max:255',
                    'experience' => 'nullable|integer|min:0|max:50',
                    'about' => 'nullable|string|max:1000',
                    'training_philosophy' => 'nullable|string|max:1000'
                ]);
            }
            
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
            
            // Update user data
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role
            ];
            
            // Update password if provided
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            
            // Add trainer-specific fields
            if ($request->role === 'trainer') {
                $userData = array_merge($userData, [
                    'designation' => $request->designation,
                    'experience' => $request->experience,
                    'about' => $request->about,
                    'training_philosophy' => $request->training_philosophy
                ]);
            } else {
                // Clear trainer fields if role changed from trainer
                $userData = array_merge($userData, [
                    'designation' => null,
                    'experience' => null,
                    'about' => null,
                    'training_philosophy' => null
                ]);
            }
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old image
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                
                $imagePath = $request->file('profile_image')->store('profile-images', 'public');
                $userData['profile_image'] = $imagePath;
            }

            // Handle business logo upload
            if ($request->hasFile('business_logo')) {
                if ($user->business_logo && Storage::disk('public')->exists($user->business_logo)) {
                    Storage::disk('public')->delete($user->business_logo);
                }
                $logoPath = $request->file('business_logo')->store('business-logos', 'public');
                $userData['business_logo'] = $logoPath;
            }
            
            $user->update($userData);
            
            // Log user update
            Log::info('User updated by admin', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'user_email' => $user->email,
                'changes' => $request->except(['password', 'password_confirmation'])
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'user' => $user->fresh()
                ]);
            }
            
            return redirect()->route('admin.users.index')
                           ->with('success', 'User updated successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to update user: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update user: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified user from storage
     * Supports both web and AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent admin from deleting themselves
            if ($user->id === Auth::id()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You cannot delete your own account'
                    ], 403);
                }
                return back()->with('error', 'You cannot delete your own account');
            }
            
            // Delete profile image if exists
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            // Log user deletion before deleting
            Log::info('User deleted by admin', [
                'admin_id' => Auth::id(),
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $user->email,
                'deleted_user_role' => $user->role
            ]);
            
            $user->delete();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            }
            
            return redirect()->route('admin.users.index')
                           ->with('success', 'User deleted successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to delete user: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete user: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
    
    /**
     * Toggle user status (activate/deactivate)
     * AJAX only endpoint
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Toggle email verification status
            $user->email_verified_at = $user->email_verified_at ? null : now();
            $user->save();
            
            Log::info('User status toggled by admin', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'new_status' => $user->email_verified_at ? 'active' : 'inactive'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'status' => $user->email_verified_at ? 'active' : 'inactive'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle user status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status'
            ], 500);
        }
    }
    
    /**
     * Delete user profile image
     * AJAX only endpoint
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
                $user->update(['profile_image' => null]);
                
                Log::info('User profile image deleted by admin', [
                    'admin_id' => Auth::id(),
                    'user_id' => $user->id
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
            Log::error('Failed to delete user profile image: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete profile image'
            ], 500);
        }
    }
}