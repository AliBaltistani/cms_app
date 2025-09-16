<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Goal;
use App\Models\Workout;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AdminDashboardController
 * 
 * Handles admin dashboard functionality and system overview
 */
class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            // Get system statistics
            $stats = [
                'total_users' => User::count(),
                'total_trainers' => User::where('role', 'trainer')->count(),
                'total_clients' => User::where('role', 'client')->count(),
                'total_admins' => User::where('role', 'admin')->count(),
                'total_goals' => Goal::count(),
                'total_workouts' => Workout::count(),
                'total_testimonials' => Testimonial::count(),
                'recent_users' => User::latest()->take(5)->get(),
                'recent_testimonials' => Testimonial::with(['trainer', 'client'])->latest()->take(5)->get()
            ];
            
            return view('admin.dashboard', compact('stats'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load dashboard: ' . $e->getMessage());
        }
    }
    
    /**
     * Display system users management.
     * 
     * @return \Illuminate\View\View
     */
    public function users()
    {
        try {
            $users = User::with(['receivedTestimonials', 'certifications'])
                ->paginate(20);
            
            return view('admin.users.index', compact('users'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load users: ' . $e->getMessage());
        }
    }
    
    /**
     * Display system reports and analytics.
     * 
     * @return \Illuminate\View\View
     */
    public function reports()
    {
        try {
            $reports = [
                'user_growth' => User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'role_distribution' => User::selectRaw('role, COUNT(*) as count')
                    ->groupBy('role')
                    ->get(),
                'testimonial_stats' => Testimonial::selectRaw('AVG(rate) as avg_rating, COUNT(*) as total_reviews')
                    ->first()
            ];
            
            return view('admin.reports', compact('reports'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load reports: ' . $e->getMessage());
        }
    }
}
