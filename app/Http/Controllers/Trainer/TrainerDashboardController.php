<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificationRequest;
use App\Models\UserCertification;
use App\Models\Testimonial;
use App\Models\TestimonialLikesDislike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * TrainerDashboardController
 * 
 * Handles trainer dashboard functionality and profile management
 */
class TrainerDashboardController extends Controller
{
    /**
     * Display the trainer dashboard.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Get trainer-specific statistics
            $stats = [
                'total_certifications' => UserCertification::where('user_id', $user->id)->count(),
                'total_testimonials' => Testimonial::where('trainer_id', $user->id)->count(),
                'average_rating' => Testimonial::where('trainer_id', $user->id)->avg('rate') ?: 0,
                'total_likes' => Testimonial::where('trainer_id', $user->id)->sum('likes'),
                'recent_testimonials' => Testimonial::where('trainer_id', $user->id)
                    ->with('client')
                    ->latest()
                    ->take(5)
                    ->get(),
                'recent_certifications' => UserCertification::where('user_id', $user->id)
                    ->latest()
                    ->take(3)
                    ->get(),
                'profile_completion' => ''
            ];
            
            return view('trainer.dashboard', compact('stats'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load dashboard: ' . $e->getMessage());
        }
    }
    
    /**
     * Display trainer's certifications.
     * 
     * @return \Illuminate\View\View
     */
    public function certifications()
    {
        try {
            $user = Auth::user();
            $certifications = UserCertification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            return view('trainer.certifications.index', compact('certifications'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load certifications: ' . $e->getMessage());
        }
    }
    
    /**
     * Store a new certification for the authenticated trainer.
     * 
     * @param \App\Http\Requests\StoreCertificationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCertification(StoreCertificationRequest $request)
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'trainer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only trainers can add certifications.'
                ], 403);
            }
            
            DB::beginTransaction();
            
            $data = $request->validated();
            $data['user_id'] = $user->id;
            
            // Handle file upload if present
            if ($request->hasFile('doc')) {
                $file = $request->file('doc');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('certifications', $filename, 'public');
                $data['doc'] = $path;
            }
            
            $certification = UserCertification::create($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Certification added successfully',
                'data' => $certification
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific certification.
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCertification(string $id)
    {
        try {
            $user = Auth::user();
            
            $certification = UserCertification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$certification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certification not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Certification retrieved successfully',
                'data' => $certification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update a specific certification.
     * 
     * @param \App\Http\Requests\StoreCertificationRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCertification(StoreCertificationRequest $request, string $id)
    {
        try {
            $user = Auth::user();
            
            $certification = UserCertification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$certification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certification not found'
                ], 404);
            }
            
            DB::beginTransaction();
            
            $data = $request->validated();
            
            // Handle file upload if present
            if ($request->hasFile('doc')) {
                // Delete old file if exists
                if ($certification->doc && Storage::disk('public')->exists($certification->doc)) {
                    Storage::disk('public')->delete($certification->doc);
                }
                
                $file = $request->file('doc');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('certifications', $filename, 'public');
                $data['doc'] = $path;
            }
            
            $certification->update($data);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Certification updated successfully',
                'data' => $certification->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a specific certification.
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyCertification(string $id)
    {
        try {
            $user = Auth::user();
            
            $certification = UserCertification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$certification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certification not found'
                ], 404);
            }
            
            DB::beginTransaction();
            
            // Delete associated file if exists
            if ($certification->doc && Storage::disk('public')->exists($certification->doc)) {
                Storage::disk('public')->delete($certification->doc);
            }
            
            $certification->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Certification deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete certification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display trainer's testimonials.
     * 
     * @return \Illuminate\View\View
     */
    public function testimonials()
    {
        try {
            $user = Auth::user();
            $testimonials = Testimonial::where('trainer_id', $user->id)
                ->with('client')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            return view('trainer.testimonials.index', compact('testimonials'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load testimonials: ' . $e->getMessage());
        }
    }
    
    /**
     * Display trainer profile management.
     * 
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            
            return view('trainer.profile.index', compact('user'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load profile: ' . $e->getMessage());
        }
    }
    
    /**
     * Like a testimonial.
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function likeTestimonial(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $testimonial = Testimonial::findOrFail($id);
            
            DB::beginTransaction();
            
            // Find or create reaction record
            $reaction = TestimonialLikesDislike::firstOrCreate(
                [
                    'testimonial_id' => $id,
                    'user_id' => $user->id
                ],
                [
                    'like' => false,
                    'dislike' => false
                ]
            );
            
            $previousLike = $reaction->like;
            $previousDislike = $reaction->dislike;
            
            // Toggle like
            $reaction->setLike();
            
            // Update testimonial counters
            if (!$previousLike) {
                $testimonial->incrementLikes();
            }
            
            if ($previousDislike) {
                $testimonial->decrementDislikes();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Testimonial liked successfully',
                'data' => [
                    'likes' => $testimonial->fresh()->likes,
                    'dislikes' => $testimonial->fresh()->dislikes
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to like testimonial',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Dislike a testimonial.
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function dislikeTestimonial(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $testimonial = Testimonial::findOrFail($id);
            
            DB::beginTransaction();
            
            // Find or create reaction record
            $reaction = TestimonialLikesDislike::firstOrCreate(
                [
                    'testimonial_id' => $id,
                    'user_id' => $user->id
                ],
                [
                    'like' => false,
                    'dislike' => false
                ]
            );
            
            $previousLike = $reaction->like;
            $previousDislike = $reaction->dislike;
            
            // Toggle dislike
            $reaction->setDislike();
            
            // Update testimonial counters
            if (!$previousDislike) {
                $testimonial->incrementDislikes();
            }
            
            if ($previousLike) {
                $testimonial->decrementLikes();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Testimonial disliked successfully',
                'data' => [
                    'likes' => $testimonial->fresh()->likes,
                    'dislikes' => $testimonial->fresh()->dislikes
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to dislike testimonial',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display Google Calendar management interface for trainers.
     * 
     * @return \Illuminate\View\View
     */
    public function googleCalendar()
    {
        try {
            $user = Auth::user();
            
            // Check if trainer has Google Calendar connected
            $isConnected = !empty($user->google_token);
            $connectedEmail = null;
            
            if ($isConnected) {
                try {
                    // Initialize Google Client to get user info
                    $googleController = new \App\Http\Controllers\GoogleController();
                    $connectionStatus = $googleController->getTrainerConnectionStatus($user);
                    
                    $isConnected = $connectionStatus['connected'];
                    $connectedEmail = $connectionStatus['email'];
                } catch (\Exception $e) {
                    \Log::warning('Failed to verify Google Calendar connection: ' . $e->getMessage());
                    $isConnected = false;
                }
            }
            
            return view('trainer.google-calendar.index', compact('isConnected', 'connectedEmail'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load Google Calendar settings: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate profile completion percentage.
     * 
     * @param User $user
     * @return int
     */
    private function calculateProfileCompletion(User $user): int
    {
        $fields = [
            'name' => !empty($user->name),
            'email' => !empty($user->email),
            'phone' => !empty($user->phone),
            'designation' => !empty($user->designation),
            'experience' => !empty($user->experience),
            'about' => !empty($user->about),
            'training_philosophy' => !empty($user->training_philosophy),
            'profile_image' => !empty($user->profile_image),
            'certifications' => $user->certifications()->count() > 0
        ];
        
        $completedFields = array_filter($fields);
        
        return round((count($completedFields) / count($fields)) * 100);
    }
}
