<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutVideoRequest;
use App\Http\Requests\UpdateWorkoutVideoRequest;
use App\Models\Workout;
use App\Models\WorkoutVideo;
use App\Services\WorkoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Illuminate\Support\Facades\Auth;

class WorkoutVideoController extends Controller
{
    public function __construct(
        private WorkoutService $workoutService
    ) {}

    /**
     * Display videos for a specific workout
     */
    public function index(Workout $workout): View
    {
        $videos = $workout->videos()->orderBy('order')->get();
        
        return view('workouts.videos.index', compact('workout', 'videos'));
    }

    /**
     * Show the form for creating a new video
     */
    public function create(Workout $workout): View
    {
        $user = Auth::user();
        return view('workouts.videos.create', compact('workout', 'user'));
    }

    /**
     * Store a newly created video
     */
    public function store(StoreWorkoutVideoRequest $request, Workout $workout): RedirectResponse
    {
        try {
            $videoData = $request->validated();
            $video = $this->workoutService->addVideoToWorkout($workout, $videoData);
            
            return redirect()
                ->route('workouts.show', $workout->id)
                ->with('success', 'Video added successfully');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to add video: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified video
     */
    public function show(Workout $workout, WorkoutVideo $video): View
    {
        // Ensure video belongs to workout
        if ($video->workout_id !== $workout->id) {
            abort(404, 'Video not found in this workout');
        }

        return view('workouts.videos.show', compact('workout', 'video'));
    }

    /**
     * Show the form for editing the specified video
     */
    public function edit(Workout $workout, WorkoutVideo $video): View
    {
        // Ensure video belongs to workout
        if ($video->workout_id !== $workout->id) {
            abort(404, 'Video not found in this workout');
        }

        return view('workouts.videos.edit', compact('workout', 'video'));
    }

    /**
     * Update the specified video
     */
    public function update(UpdateWorkoutVideoRequest $request, Workout $workout, WorkoutVideo $video): RedirectResponse
    {
        // Ensure video belongs to workout
        if ($video->workout_id !== $workout->id) {
            abort(404, 'Video not found in this workout');
        }

        try {
            $updatedVideo = $this->workoutService->updateWorkoutVideo($video, $request->validated());
            
            return redirect()
                ->route('workouts.videos.show', [$workout, $updatedVideo])
                ->with('success', 'Video updated successfully');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to update video: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified video
     */
    public function destroy(Workout $workout, WorkoutVideo $video): RedirectResponse
    {
        // Ensure video belongs to workout
        if ($video->workout_id !== $workout->id) {
            abort(404, 'Video not found in this workout');
        }

        try {
            $this->workoutService->deleteWorkoutVideo($video);
            
            return redirect()
                ->route('workouts.videos.index', $workout)
                ->with('success', 'Video deleted successfully');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete video: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the reorder form
     */
    public function reorderForm(Workout $workout): View
    {
        $videos = $workout->videos()->orderBy('order')->get();
        
        return view('workouts.videos.reorder', compact('workout', 'videos'));
    }

    /**
     * Reorder workout videos
     */
    public function reorder(Request $request, Workout $workout): RedirectResponse
    {
        $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'integer|exists:workout_videos,id'
        ]);

        try {
            // Verify all videos belong to this workout
            $videoIds = $request->video_ids;
            $workoutVideoIds = $workout->videos()->pluck('id')->toArray();
            
            $invalidIds = array_diff($videoIds, $workoutVideoIds);
            if (!empty($invalidIds)) {
                return back()
                    ->withErrors(['error' => 'Some videos do not belong to this workout'])
                    ->withInput();
            }

            $workout->reorderVideos($videoIds);
            
            return redirect()
                ->route('workouts.videos.index', $workout)
                ->with('success', 'Videos reordered successfully');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to reorder videos: ' . $e->getMessage()])
                ->withInput();
        }
    }
}