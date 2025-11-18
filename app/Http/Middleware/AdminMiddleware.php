<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminMiddleware
 * 
 * Ensures only users with admin role can access admin routes
 */
class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Check if the authenticated user has admin role
     * Redirect to appropriate dashboard if not authorized
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            // Return JSON response for API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Please login to access this area.'
                ], 401);
            }
            return redirect()->route('login')->with('error', 'Please login to access this area.');
        }
        
        $user = Auth::user();
        
        // Check if user has admin role
        if ($user->role !== 'admin') {
            // Return JSON response for API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access Denied',
                    'error' => 'Admin access required. Your role: ' . $user->role
                ], 403);
            }
            // Redirect to main dashboard which will handle role-based routing
            return redirect()->route('dashboard')
                ->with('error', 'Access denied. Admin privileges required.');
        }
        
        return $next($request);
    }
}
