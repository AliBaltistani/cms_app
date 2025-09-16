<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\User;
use App\Models\UserCertification;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Admin User API Controller
 * 
 * Handles user management operations via API for admin users
 * Provides complete CRUD operations with role-based filtering
 * All endpoints require admin authentication
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Api
 * @category    User Management API
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class AdminUserController extends ApiBaseController
{
    /**
     * Get all users with optional role filtering and pagination
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validator = Validator::make($request->all(), [
                'role' => 'nullable|in:admin,trainer,client',
                'status' => 'nullable|in:active,inactive',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
                'sort_by' => 'nullable|in:id,name,email,role,created_at',
                'sort_order' => 'nullable|in:asc,desc'
            ]);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            // Build query
            $query = User::with(['receivedTestimonials', 'certifications', 'goals', 'workouts']);
            
            // Apply role filter
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }
            
            // Apply status filter
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }
            
            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('designation', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Get paginated results
            $perPage = $request->get('per_page', 20);
            $users = $query->paginate($perPage);
            
            // Transform user data
            $transformedUsers = $users->getCollection()->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'status' => $user->email_verified_at ? 'active' : 'inactive',
                    'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                    'designation' => $user->designation,
                    'experience' => $user->experience,
                    'about' => $user->about,
                    'training_philosophy' => $user->training_philosophy,
                    'certifications_count' => $user->certifications->count(),
                    'testimonials_count' => $user->receivedTestimonials->count(),
                    'goals_count' => $user->goals->count(),
                    'workouts_count' => $user->workouts->count(),
                    'average_rating' => $user->receivedTestimonials->avg('rate') ?? 0,
                    'total_likes' => $user->receivedTestimonials->sum('likes'),
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at->toISOString(),
                    'updated_at' => $user->updated_at->toISOString()
                ];
            });
            
            // Prepare response data
            $responseData = [
                'users' => $transformedUsers,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem()
                ],
                'statistics' => [
                    'total_users' => User::count(),
                    'total_trainers' => User::where('role', 'trainer')->count(),
                    'total_clients' => User::where('role', 'client')->count(),
                    'total_admins' => User::where('role', 'admin')->count(),
                    'active_users' => User::whereNotNull('email_verified_at')->count()
                ]
            ];
            
            return $this->sendResponse($responseData, 'Users retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve users: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'request_params' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to retrieve users', ['error' => 'Unable to fetch users'], 500);
        }
    }
    
    /**
     * Create a new user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Define validation rules based on role
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'role' => 'required|in:client,trainer,admin',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            // Add trainer-specific validation rules
            if ($request->role === 'trainer') {
                $rules = array_merge($rules, [
                    'designation' => 'required|string|max:255',
                    'experience' => 'required|integer|min:0|max:50',
                    'about' => 'required|string|max:1000',
                    'training_philosophy' => 'nullable|string|max:1000'
                ]);
            }
            
            // Validate input
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
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
            
            // Load relationships for response
            $user->load(['receivedTestimonials', 'certifications', 'goals', 'workouts']);
            
            // Transform user data for response
            $transformedUser = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->email_verified_at ? 'active' : 'inactive',
                'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                'designation' => $user->designation,
                'experience' => $user->experience,
                'about' => $user->about,
                'training_philosophy' => $user->training_philosophy,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString()
            ];
            
            // Log user creation
            Log::info('New user created via API by admin', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role
            ]);
            
            return $this->sendResponse($transformedUser, 'User created successfully', 201);
            
        } catch (\Exception $e) {
            Log::error('Failed to create user via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'request_data' => $request->except(['password', 'password_confirmation']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to create user', ['error' => 'Unable to create user'], 500);
        }
    }
    
    /**
     * Get a specific user by ID
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::with([
                'receivedTestimonials.client',
                'writtenTestimonials.trainer',
                'certifications',
                'goals',
                'workouts'
            ])->find($id);
            
            if (!$user) {
                return $this->sendError('User not found', ['error' => 'User does not exist'], 404);
            }
            
            // Transform user data with detailed relationships
            $transformedUser = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->email_verified_at ? 'active' : 'inactive',
                'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                'designation' => $user->designation,
                'experience' => $user->experience,
                'about' => $user->about,
                'training_philosophy' => $user->training_philosophy,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
                'statistics' => [
                    'certifications_count' => $user->certifications->count(),
                    'received_testimonials_count' => $user->receivedTestimonials->count(),
                    'written_testimonials_count' => $user->writtenTestimonials->count(),
                    'goals_count' => $user->goals->count(),
                    'workouts_count' => $user->workouts->count(),
                    'average_rating' => $user->receivedTestimonials->avg('rate') ?? 0,
                    'total_likes' => $user->receivedTestimonials->sum('likes')
                ],
                'certifications' => $user->certifications->map(function($cert) {
                    return [
                        'id' => $cert->id,
                        'name' => $cert->name,
                        'issuing_organization' => $cert->issuing_organization,
                        'issue_date' => $cert->issue_date,
                        'expiry_date' => $cert->expiry_date,
                        'credential_id' => $cert->credential_id,
                        'credential_url' => $cert->credential_url
                    ];
                }),
                'recent_testimonials' => $user->receivedTestimonials->take(5)->map(function($testimonial) {
                    return [
                        'id' => $testimonial->id,
                        'content' => $testimonial->content,
                        'rate' => $testimonial->rate,
                        'likes' => $testimonial->likes,
                        'client' => [
                            'id' => $testimonial->client->id,
                            'name' => $testimonial->client->name,
                            'profile_image' => $testimonial->client->profile_image ? asset('storage/' . $testimonial->client->profile_image) : null
                        ],
                        'created_at' => $testimonial->created_at->toISOString()
                    ];
                }),
                'recent_goals' => $user->goals->take(5)->map(function($goal) {
                    return [
                        'id' => $goal->id,
                        'name' => $goal->name,
                        'status' => $goal->status,
                        'created_at' => $goal->created_at->toISOString()
                    ];
                })
            ];
            
            return $this->sendResponse($transformedUser, 'User retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to retrieve user', ['error' => 'Unable to fetch user details'], 500);
        }
    }
    
    /**
     * Update a specific user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return $this->sendError('User not found', ['error' => 'User does not exist'], 404);
            }
            
            // Define validation rules
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
                'role' => 'required|in:client,trainer,admin',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            // Add password validation if provided
            if ($request->filled('password')) {
                $rules['password'] = 'string|min:8|confirmed';
            }
            
            // Add trainer-specific validation rules
            if ($request->role === 'trainer') {
                $rules = array_merge($rules, [
                    'designation' => 'required|string|max:255',
                    'experience' => 'required|integer|min:0|max:50',
                    'about' => 'required|string|max:1000',
                    'training_philosophy' => 'nullable|string|max:1000'
                ]);
            }
            
            // Validate input
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
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
            
            $user->update($userData);
            
            // Load relationships for response
            $user->load(['receivedTestimonials', 'certifications', 'goals', 'workouts']);
            
            // Transform user data for response
            $transformedUser = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->email_verified_at ? 'active' : 'inactive',
                'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                'designation' => $user->designation,
                'experience' => $user->experience,
                'about' => $user->about,
                'training_philosophy' => $user->training_philosophy,
                'updated_at' => $user->updated_at->toISOString()
            ];
            
            // Log user update
            Log::info('User updated via API by admin', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'user_email' => $user->email,
                'changes' => $request->except(['password', 'password_confirmation'])
            ]);
            
            return $this->sendResponse($transformedUser, 'User updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to update user via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to update user', ['error' => 'Unable to update user'], 500);
        }
    }
    
    /**
     * Delete a specific user
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return $this->sendError('User not found', ['error' => 'User does not exist'], 404);
            }
            
            // Prevent admin from deleting themselves
            if ($user->id === Auth::id()) {
                return $this->sendError('Cannot delete own account', ['error' => 'You cannot delete your own account'], 403);
            }
            
            // Delete profile image if exists
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            // Log user deletion before deleting
            Log::info('User deleted via API by admin', [
                'admin_id' => Auth::id(),
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $user->email,
                'deleted_user_role' => $user->role
            ]);
            
            $user->delete();
            
            return $this->sendResponse([], 'User deleted successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to delete user via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to delete user', ['error' => 'Unable to delete user'], 500);
        }
    }
    
    /**
     * Toggle user status (activate/deactivate)
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return $this->sendError('User not found', ['error' => 'User does not exist'], 404);
            }
            
            // Toggle email verification status
            $user->email_verified_at = $user->email_verified_at ? null : now();
            $user->save();
            
            Log::info('User status toggled via API by admin', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'new_status' => $user->email_verified_at ? 'active' : 'inactive'
            ]);
            
            $responseData = [
                'user_id' => $user->id,
                'status' => $user->email_verified_at ? 'active' : 'inactive',
                'email_verified_at' => $user->email_verified_at
            ];
            
            return $this->sendResponse($responseData, 'User status updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle user status via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to update user status', ['error' => 'Unable to toggle user status'], 500);
        }
    }
    
    /**
     * Delete user profile image
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage($id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return $this->sendError('User not found', ['error' => 'User does not exist'], 404);
            }
            
            if (!$user->profile_image) {
                return $this->sendError('No profile image found', ['error' => 'User does not have a profile image'], 404);
            }
            
            if (Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            $user->update(['profile_image' => null]);
            
            Log::info('User profile image deleted via API by admin', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id
            ]);
            
            return $this->sendResponse(['user_id' => $user->id], 'Profile image deleted successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to delete user profile image via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'user_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to delete profile image', ['error' => 'Unable to delete profile image'], 500);
        }
    }
    
    /**
     * Get user statistics and analytics
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::whereNotNull('email_verified_at')->count(),
                    'inactive' => User::whereNull('email_verified_at')->count(),
                    'by_role' => [
                        'admin' => User::where('role', 'admin')->count(),
                        'trainer' => User::where('role', 'trainer')->count(),
                        'client' => User::where('role', 'client')->count()
                    ],
                    'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
                    'growth_rate' => $this->calculateGrowthRate()
                ],
                'trainers' => [
                    'total' => User::where('role', 'trainer')->count(),
                    'active' => User::where('role', 'trainer')->whereNotNull('email_verified_at')->count(),
                    'with_certifications' => User::where('role', 'trainer')->has('certifications')->count(),
                    'average_experience' => User::where('role', 'trainer')->avg('experience') ?? 0,
                    'average_rating' => Testimonial::avg('rate') ?? 0
                ],
                'clients' => [
                    'total' => User::where('role', 'client')->count(),
                    'active' => User::where('role', 'client')->whereNotNull('email_verified_at')->count(),
                    'with_goals' => User::where('role', 'client')->has('goals')->count(),
                    'with_testimonials' => User::where('role', 'client')->has('writtenTestimonials')->count()
                ],
                'content' => [
                    'total_certifications' => UserCertification::count(),
                    'total_testimonials' => Testimonial::count(),
                    'average_testimonial_rating' => Testimonial::avg('rate') ?? 0,
                    'total_testimonial_likes' => Testimonial::sum('likes')
                ]
            ];
            
            return $this->sendResponse($stats, 'Statistics retrieved successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to retrieve statistics via API: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Failed to retrieve statistics', ['error' => 'Unable to fetch statistics'], 500);
        }
    }
    
    /**
     * Calculate user growth rate
     * 
     * @return float
     */
    private function calculateGrowthRate(): float
    {
        $currentMonth = User::whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year)
                           ->count();
        
        $previousMonth = User::whereMonth('created_at', now()->subMonth()->month)
                            ->whereYear('created_at', now()->subMonth()->year)
                            ->count();
        
        if ($previousMonth == 0) {
            return $currentMonth > 0 ? 100 : 0;
        }
        
        return round((($currentMonth - $previousMonth) / $previousMonth) * 100, 2);
    }
}