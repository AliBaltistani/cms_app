<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Requests\UpdateWorkoutRequest;
use App\Models\Workout;
use App\Services\WorkoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;


class WorkoutController extends Controller
{
    public function __construct(
        private WorkoutService $workoutService
    ) {}

    /**
     * Display a listing of workouts
     */
    public function index(Request $request): View
    {
        $query = Workout::query();

        // Apply filters
        

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Include videos if requested
        if ($request->boolean('include_videos')) {
            $query->withVideos();
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $workouts = $query->paginate($request->get('per_page', 15));

        // Get filter options for the view

        $user = Auth::user();

        return view('workouts.index', compact('workouts', 'user'));
    }

    /**
     * Show the form for creating a new workout
     */
    public function create(): View
    {
        $user = Auth::user();
        return view('workouts.create', compact('user'));
    }

    /**
     * Store a newly created workout
     */
    public function store(StoreWorkoutRequest $request): RedirectResponse
    {
        try {
            $workout = $this->workoutService->createWorkout($request->validated());
            
            return redirect()
                ->route('workouts.show', $workout)
                ->with('success', 'Workout created successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create workout: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified workout
     */
    public function show(Workout $workout): View
    {
        $workout->load('videos');

        $user = Auth::user();

        return view('workouts.show', compact('workout', 'user'));
    }

    /**
     * Show the form for editing the specified workout
     */
    public function edit(Workout $workout): View
    {
        $user = Auth::user();
        return view('workouts.edit', compact('workout', 'user'));
    }

    /**
     * Update the specified workout
     */
    public function update(UpdateWorkoutRequest $request, Workout $workout): RedirectResponse
    {
        try {
            $updatedWorkout = $this->workoutService->updateWorkout($workout, $request->validated());
            
            return redirect()
                ->route('workouts.show', $updatedWorkout)
                ->with('success', 'Workout updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update workout: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified workout
     */
    public function destroy(Workout $workout): RedirectResponse
    {
        try {
            $this->workoutService->deleteWorkout($workout);
            
            return redirect()
                ->route('workouts.index')
                ->with('success', 'Workout deleted successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete workout: ' . $e->getMessage());
        }
    }

    /**
     * Get workout statistics
     */
    public function statistics(): View
    {
        $stats = [
            'total_workouts' => Workout::count(),
            'active_workouts' => Workout::active()->count(),
            'total_videos' => \App\Models\WorkoutVideo::count(),
            'by_difficulty' => Workout::selectRaw('difficulty, COUNT(*) as count')
                ->groupBy('difficulty')
                ->pluck('count', 'difficulty'),
            'by_category' => Workout::selectRaw('category, COUNT(*) as count')
                ->whereNotNull('category')
                ->groupBy('category')
                ->pluck('count', 'category'),
            'average_duration' => round(Workout::avg('duration'), 2),
        ];

        return view('workouts.statistics', compact('stats'));
    }

    /**
     * Toggle workout active status
     */
    public function toggleStatus(Workout $workout): RedirectResponse
    {
        $workout->update(['is_active' => !$workout->is_active]);

        $status = $workout->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Workout {$status} successfully");
    }

    /**
     * Duplicate a workout
     */
    public function duplicate(Workout $workout): RedirectResponse
    {
        try {
            $duplicatedWorkout = $this->workoutService->duplicateWorkout($workout);
            
            return redirect()
                ->route('workouts.show', $duplicatedWorkout)
                ->with('success', 'Workout duplicated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to duplicate workout: ' . $e->getMessage());
        }
    }
}