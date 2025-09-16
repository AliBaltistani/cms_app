<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ClientMiddleware
 * 
 * Ensures only users with client role can access client routes
 */
class ClientMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Check if the authenticated user has client role
     * Return JSON response for API requests or redirect for web requests
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
        
        // Check if user has client role
        if ($user->role !== 'client') {
            // Return JSON response for API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access Denied',
                    'error' => 'Client access required. Your role: ' . $user->role
                ], 403);
            }
            
            // Redirect to main dashboard which will handle role-based routing
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied. Client access required.');
        }
        
        return $next($request);
    }
}
