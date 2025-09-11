<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboards Controller
 * 
 * Handles dashboard functionality for authenticated users
 * Uses master.blade.php layout as specified in requirements
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    Dashboard
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */
class DashboardsController extends Controller
{
    /**
     * Create a new controller instance.
     * Apply authentication middleware to ensure only logged-in users can access
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     * This is where users are redirected after successful login
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();
        
        // Log dashboard access for security audit
        \Illuminate\Support\Facades\Log::info('User accessed dashboard', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Prepare dashboard data
        $dashboardData = [
            'user' => $user,
            'login_time' => session('login_time', now()),
            'total_users' => \App\Models\User::count(),
            'user_since' => $user->created_at->format('F Y'),
        ];

        return view('pages.dashboards.index', $dashboardData);
    }
}
