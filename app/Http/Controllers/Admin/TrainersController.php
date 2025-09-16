<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
 * Admin Trainers Controller
 * 
 * Handles trainer-specific CRUD operations in admin panel
 * Manages trainer profiles, certifications, and testimonials
 * Provides both web and AJAX responses
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers\Admin
 * @category    Trainer Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class TrainersController extends Controller
{
    /**
     * Display a listing of trainers with their statistics
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
            $experience = $request->get('experience', 'all');
            
            // Build query for trainers only
            $query = User::where('role', 'trainer')
                        ->with(['receivedTestimonials', 'certifications']);
            
            // Apply status filter
            if ($status === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($status === 'inactive') {
                $query->whereNull('email_verified_at');
            }
            
            // Apply experience filter
            if ($experience !== 'all') {
                switch ($experience) {
                    case 'beginner':
                        $query->where('experience', '<=', 2);
                        break;
                    case 'intermediate':
                        $query->whereBetween('experience', [3, 7]);
                        break;
                    case 'expert':
                        $query->where('experience', '>=', 8);
                        break;
                }
            }
            
            // Get trainers with pagination
            $trainers = $query->latest()->paginate(20);
            
            // Get statistics for dashboard cards
            $stats = [
                'total_trainers' => User::where('role', 'trainer')->count(),
                'active_trainers' => User::where('role', 'trainer')->whereNotNull('email_verified_at')->count(),
                'inactive_trainers' => User::where('role', 'trainer')->whereNull('email_verified_at')->count(),
                'total_certifications' => UserCertification::count(),
                'total_testimonials' => Testimonial::count(),
                'avg_experience' => User::where('role', 'trainer')->avg('experience') ?? 0,
                'avg_rating' => Testimonial::avg('rate') ?? 0,
            ];
            
            return view('admin.trainers.index', compact('trainers', 'stats', 'status', 'experience'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainers list: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to load trainers: ' . $e->getMessage());
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
            $experience = $request->get('experience', 'all');
            
            // Build query for trainers only
            $query = User::where('role', 'trainer')
                        ->with(['receivedTestimonials', 'certifications']);
            
            // Apply status filter
            if ($status === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($status === 'inactive') {
                $query->whereNull('email_verified_at');
            }
            
            // Apply experience filter
            if ($experience !== 'all') {
                switch ($experience) {
                    case 'beginner':
                        $query->where('experience', '<=', 2);
                        break;
                    case 'intermediate':
                        $query->whereBetween('experience', [3, 7]);
                        break;
                    case 'expert':
                        $query->where('experience', '>=', 8);
                        break;
                }
            }
            
            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('designation', 'like', "%{$search}%")
                      ->orWhere('about', 'like', "%{$search}%");
                });
            }
            
            // Get total count before pagination
            $totalRecords = User::where('role', 'trainer')->count();
            $filteredRecords = $query->count();
            
            // Apply pagination
            $trainers = $query->skip($start)->take($length)->latest()->get();
            
            // Format data for DataTables
            $data = $trainers->map(function($trainer) {
                $avgRating = $trainer->receivedTestimonials->avg('rate') ?? 0;
                
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email,
                    'phone' => $trainer->phone ?? 'N/A',
                    'designation' => $trainer->designation ?? 'N/A',
                    'experience' => $trainer->experience ? $trainer->experience . ' years' : 'N/A',
                    'status' => $trainer->email_verified_at ? 'Active' : 'Inactive',
                    'profile_image' => $trainer->profile_image ? asset('storage/' . $trainer->profile_image) : null,
                    'certifications_count' => $trainer->certifications->count(),
                    'testimonials_count' => $trainer->receivedTestimonials->count(),
                    'average_rating' => round($avgRating, 1),
                    'total_likes' => $trainer->receivedTestimonials->sum('likes'),
                    'created_at' => $trainer->created_at->format('d-m-Y H:i'),
                    'updated_at' => $trainer->updated_at->format('d-m-Y H:i'),
                ];
            });
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Trainers DataTables request failed: ' . $e->getMessage());
            
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
     * Show the form for creating a new trainer
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        try {
            return view('admin.trainers.create');
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainer creation form: ' . $e->getMessage());
            return redirect()->route('admin.trainers.index')->with('error', 'Failed to load creation form');
        }
    }

    /**
     * Store a newly created trainer in storage
     * Supports both web and AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Define validation rules for trainer
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'designation' => 'required|string|max:255',
                'experience' => 'required|integer|min:0|max:50',
                'about' => 'required|string|max:1000',
                'training_philosophy' => 'nullable|string|max:1000',
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
            
            // Create new trainer
            $trainerData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'trainer',
                'designation' => $request->designation,
                'experience' => $request->experience,
                'about' => $request->about,
                'training_philosophy' => $request->training_philosophy,
                'email_verified_at' => now(), // Auto-verify admin created trainers
            ];
            
            $trainer = User::create($trainerData);
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $imagePath = $request->file('profile_image')->store('profile-images', 'public');
                $trainer->update(['profile_image' => $imagePath]);
            }
            
            // Log trainer creation
            Log::info('New trainer created by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'trainer_email' => $trainer->email
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trainer created successfully',
                    'trainer' => $trainer
                ]);
            }
            
            return redirect()->route('admin.trainers.index')
                           ->with('success', 'Trainer created successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to create trainer: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'request_data' => $request->except(['password', 'password_confirmation']),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create trainer: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create trainer: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified trainer with detailed information
     * 
     * @param  int  $id
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $trainer = User::where('role', 'trainer')
                          ->with([
                              'receivedTestimonials.client',
                              'certifications',
                              'workouts'
                          ])
                          ->findOrFail($id);
            
            // Calculate trainer statistics
            $stats = [
                'total_testimonials' => $trainer->receivedTestimonials->count(),
                'average_rating' => $trainer->receivedTestimonials->avg('rate') ?? 0,
                'total_likes' => $trainer->receivedTestimonials->sum('likes'),
                'total_certifications' => $trainer->certifications->count(),
                'total_workouts' => $trainer->workouts->count(),
                'recent_testimonials' => $trainer->receivedTestimonials->take(5),
                'recent_certifications' => $trainer->certifications->take(3)
            ];
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'trainer' => $trainer,
                    'stats' => $stats
                ]);
            }
            
            return view('admin.trainers.show', compact('trainer', 'stats'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainer details: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainer not found'
                ], 404);
            }
            
            return redirect()->route('admin.trainers.index')->with('error', 'Trainer not found');
        }
    }

    /**
     * Show the form for editing the specified trainer
     * 
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        try {
            $trainer = User::where('role', 'trainer')->findOrFail($id);
            
            return view('admin.trainers.edit', compact('trainer'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainer edit form: ' . $e->getMessage());
            return redirect()->route('admin.trainers.index')->with('error', 'Trainer not found');
        }
    }

    /**
     * Update the specified trainer in storage
     * Supports both web and AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $trainer = User::where('role', 'trainer')->findOrFail($id);
            
            // Define validation rules for trainer update
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $trainer->id,
                'phone' => 'nullable|string|max:20|unique:users,phone,' . $trainer->id,
                'designation' => 'required|string|max:255',
                'experience' => 'required|integer|min:0|max:50',
                'about' => 'required|string|max:1000',
                'training_philosophy' => 'nullable|string|max:1000',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];
            
            // Add password validation if provided
            if ($request->filled('password')) {
                $rules['password'] = 'string|min:8|confirmed';
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
            
            // Update trainer data
            $trainerData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'designation' => $request->designation,
                'experience' => $request->experience,
                'about' => $request->about,
                'training_philosophy' => $request->training_philosophy
            ];
            
            // Update password if provided
            if ($request->filled('password')) {
                $trainerData['password'] = Hash::make($request->password);
            }
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old image
                if ($trainer->profile_image && Storage::disk('public')->exists($trainer->profile_image)) {
                    Storage::disk('public')->delete($trainer->profile_image);
                }
                
                $imagePath = $request->file('profile_image')->store('profile-images', 'public');
                $trainerData['profile_image'] = $imagePath;
            }
            
            $trainer->update($trainerData);
            
            // Log trainer update
            Log::info('Trainer updated by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'trainer_email' => $trainer->email,
                'changes' => $request->except(['password', 'password_confirmation'])
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trainer updated successfully',
                    'trainer' => $trainer->fresh()
                ]);
            }
            
            return redirect()->route('admin.trainers.index')
                           ->with('success', 'Trainer updated successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to update trainer: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update trainer: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update trainer: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified trainer from storage
     * Supports both web and AJAX requests
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $trainer = User::where('role', 'trainer')->findOrFail($id);
            
            // Delete profile image if exists
            if ($trainer->profile_image && Storage::disk('public')->exists($trainer->profile_image)) {
                Storage::disk('public')->delete($trainer->profile_image);
            }
            
            // Log trainer deletion before deleting
            Log::info('Trainer deleted by admin', [
                'admin_id' => Auth::id(),
                'deleted_trainer_id' => $trainer->id,
                'deleted_trainer_email' => $trainer->email
            ]);
            
            $trainer->delete();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trainer deleted successfully'
                ]);
            }
            
            return redirect()->route('admin.trainers.index')
                           ->with('success', 'Trainer deleted successfully');
                           
        } catch (\Exception $e) {
            Log::error('Failed to delete trainer: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trainer_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete trainer: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete trainer: ' . $e->getMessage());
        }
    }
    
    /**
     * Manage trainer certifications
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $trainerId
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function certifications(Request $request, $trainerId)
    {
        try {
            $trainer = User::where('role', 'trainer')
                          ->with('certifications')
                          ->findOrFail($trainerId);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'certifications' => $trainer->certifications
                ]);
            }
            
            return view('admin.trainers.certifications', compact('trainer'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainer certifications: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainer not found'
                ], 404);
            }
            
            return redirect()->route('admin.trainers.index')->with('error', 'Trainer not found');
        }
    }
    
    /**
     * Store a new certification for trainer
     * AJAX only endpoint
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $trainerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCertification(Request $request, $trainerId): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->findOrFail($trainerId);
            
            // Validate certification data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'issuing_organization' => 'required|string|max:255',
                'issue_date' => 'required|date',
                'expiry_date' => 'nullable|date|after:issue_date',
                'credential_id' => 'nullable|string|max:255',
                'credential_url' => 'nullable|url|max:500'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Create certification
            $certification = UserCertification::create([
                'user_id' => $trainer->id,
                'name' => $request->name,
                'issuing_organization' => $request->issuing_organization,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'credential_id' => $request->credential_id,
                'credential_url' => $request->credential_url
            ]);
            
            Log::info('Certification added to trainer by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'certification_id' => $certification->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Certification added successfully',
                'certification' => $certification
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to add certification: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add certification'
            ], 500);
        }
    }
    
    /**
     * Delete trainer certification
     * AJAX only endpoint
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $trainerId
     * @param  int  $certificationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCertification(Request $request, $trainerId, $certificationId): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->findOrFail($trainerId);
            $certification = UserCertification::where('user_id', $trainer->id)
                                            ->findOrFail($certificationId);
            
            Log::info('Certification deleted by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'certification_id' => $certification->id
            ]);
            
            $certification->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Certification deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to delete certification: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete certification'
            ], 500);
        }
    }
    
    /**
     * Get trainer testimonials with statistics
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $trainerId
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function testimonials(Request $request, $trainerId)
    {
        try {
            $trainer = User::where('role', 'trainer')
                          ->with(['receivedTestimonials.client'])
                          ->findOrFail($trainerId);
            
            $testimonialStats = [
                'total_testimonials' => $trainer->receivedTestimonials->count(),
                'average_rating' => $trainer->receivedTestimonials->avg('rate') ?? 0,
                'total_likes' => $trainer->receivedTestimonials->sum('likes'),
                'rating_distribution' => $trainer->receivedTestimonials->groupBy('rate')
                    ->map(function($group) {
                        return $group->count();
                    })->toArray()
            ];
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'testimonials' => $trainer->receivedTestimonials,
                    'stats' => $testimonialStats
                ]);
            }
            
            return view('admin.trainers.testimonials', compact('trainer', 'testimonialStats'));
            
        } catch (\Exception $e) {
            Log::error('Failed to load trainer testimonials: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trainer not found'
                ], 404);
            }
            
            return redirect()->route('admin.trainers.index')->with('error', 'Trainer not found');
        }
    }
    
    /**
     * Toggle trainer status (activate/deactivate)
     * AJAX only endpoint
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $trainer = User::where('role', 'trainer')->findOrFail($id);
            
            // Toggle email verification status
            $trainer->email_verified_at = $trainer->email_verified_at ? null : now();
            $trainer->save();
            
            Log::info('Trainer status toggled by admin', [
                'admin_id' => Auth::id(),
                'trainer_id' => $trainer->id,
                'new_status' => $trainer->email_verified_at ? 'active' : 'inactive'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Trainer status updated successfully',
                'status' => $trainer->email_verified_at ? 'active' : 'inactive'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle trainer status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update trainer status'
            ], 500);
        }
    }
}