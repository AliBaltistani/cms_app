<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Goal;
use App\Models\Workout;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ClientDashboardController
 * 
 * Handles client dashboard functionality and personal progress tracking
 */
class ClientDashboardController extends Controller
{
    /**
     * Display the client dashboard.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Get client-specific statistics
            $stats = [
                'total_goals' => Goal::where('user_id', $user->id)->count(),
                'completed_goals' => Goal::where('user_id', $user->id)->where('status', 'completed')->count(),
                'active_goals' => Goal::where('user_id', $user->id)->where('status', 'active')->count(),
                'total_testimonials' => Testimonial::where('client_id', $user->id)->count(),
                'recent_goals' => Goal::where('user_id', $user->id)->latest()->take(5)->get(),
                'recent_testimonials' => Testimonial::where('client_id', $user->id)
                    ->with('trainer')
                    ->latest()
                    ->take(3)
                    ->get(),
                'recommended_trainers' => User::where('role', 'trainer')
                    ->with(['receivedTestimonials', 'certifications'])
                    ->withCount('receivedTestimonials')
                    ->orderBy('received_testimonials_count', 'desc')
                    ->take(3)
                    ->get()
            ];
            
            return view('client.dashboard', compact('stats'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load dashboard: ' . $e->getMessage());
        }
    }
    
    /**
     * Display client's goals.
     * 
     * @return \Illuminate\View\View
     */
    public function goals()
    {
        try {
            $user = Auth::user();
            $goals = Goal::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            return view('client.goals.index', compact('goals'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load goals: ' . $e->getMessage());
        }
    }
    
    /**
     * Display client's testimonials.
     * 
     * @return \Illuminate\View\View
     */
    public function testimonials()
    {
        try {
            $user = Auth::user();
            $testimonials = Testimonial::where('client_id', $user->id)
                ->with('trainer')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            return view('client.testimonials.index', compact('testimonials'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load testimonials: ' . $e->getMessage());
        }
    }
    
    /**
     * Display available trainers for the client.
     * 
     * @return \Illuminate\View\View
     */
    public function trainers()
    {
        try {
            $trainers = User::where('role', 'trainer')
                ->with(['certifications', 'receivedTestimonials'])
                ->withCount('receivedTestimonials')
                ->paginate(12);
            
            return view('client.trainers.index', compact('trainers'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load trainers: ' . $e->getMessage());
        }
    }
}
