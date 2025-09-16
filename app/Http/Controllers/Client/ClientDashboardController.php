<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestimonialRequest;
use App\Models\User;
use App\Models\Goal;
use App\Models\Workout;
use App\Models\Testimonial;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            
            echo $e->getMessage();
            die;
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
    
    /**
     * Store a new testimonial for a trainer.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeTestimonial(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Validate that user is a client
            if ($user->role !== 'client') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only clients can write testimonials.'
                ], 403);
            }
            
            // Validate request data
            $validatedData = $request->validate([
                'trainer_id' => 'required|exists:users,id',
                'name' => 'required|string|max:255',
                'rate' => 'required|integer|min:1|max:5',
                'comments' => 'required|string|min:10|max:1000',
                'date' => 'nullable|date'
            ]);
            
            // Verify trainer exists and is actually a trainer
            $trainer = User::where('id', $validatedData['trainer_id'])
                ->where('role', 'trainer')
                ->first();
                
            if (!$trainer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected trainer not found.'
                ], 404);
            }
            
            // Allow multiple reviews for the same trainer
            // Removed duplicate check to enable multiple testimonials
            
            DB::beginTransaction();
            
            // Create testimonial
            $testimonialData = [
                'trainer_id' => $validatedData['trainer_id'],
                'client_id' => $user->id,
                'name' => $validatedData['name'],
                'rate' => $validatedData['rate'],
                'comments' => $validatedData['comments'],
                'date' => $validatedData['date'] ?? now()->format('Y-m-d'),
                'likes' => 0,
                'dislikes' => 0
            ];
            
            $testimonial = Testimonial::create($testimonialData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully!',
                'data' => $testimonial->load('trainer')
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific testimonial.
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showTestimonial(string $id)
    {
        try {
            $user = Auth::user();
            
            $testimonial = Testimonial::where('id', $id)
                ->where('client_id', $user->id)
                ->with('trainer')
                ->first();
            
            if (!$testimonial) {
                return response()->json([
                    'success' => false,
                    'message' => 'Testimonial not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Testimonial retrieved successfully',
                'data' => $testimonial
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve testimonial',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update a specific testimonial.
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTestimonial(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            
            $testimonial = Testimonial::where('id', $id)
                ->where('client_id', $user->id)
                ->first();
            
            if (!$testimonial) {
                return response()->json([
                    'success' => false,
                    'message' => 'Testimonial not found'
                ], 404);
            }
            
            // Validate request data
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'rate' => 'required|integer|min:1|max:5',
                'comments' => 'required|string|min:10|max:1000',
                'date' => 'nullable|date'
            ]);
            
            DB::beginTransaction();
            
            $testimonial->update([
                'name' => $validatedData['name'],
                'rate' => $validatedData['rate'],
                'comments' => $validatedData['comments'],
                'date' => $validatedData['date'] ?? $testimonial->date
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully',
                'data' => $testimonial->fresh()->load('trainer')
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a specific testimonial.
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyTestimonial(string $id)
    {
        try {
            $user = Auth::user();
            
            $testimonial = Testimonial::where('id', $id)
                ->where('client_id', $user->id)
                ->first();
            
            if (!$testimonial) {
                return response()->json([
                    'success' => false,
                    'message' => 'Testimonial not found'
                ], 404);
            }
            
            DB::beginTransaction();
            
            $testimonial->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
