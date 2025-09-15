<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCertification;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                'profile_completion' => $this->calculateProfileCompletion($user)
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
