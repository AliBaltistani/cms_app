<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutExerciseSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * WorkoutExerciseSetController
 * 
 * Handles CRUD operations for workout exercise sets
 * Manages individual sets within workout exercises
 * 
 * @package     Laravel CMS
 * @subpackage  Controllers/Admin
 * @category    Workout Management
 * @author      System Developer
 * @since       1.0.0
 */
class WorkoutExerciseSetController extends Controller
{
    /**
     * Constructor - Initialize required resources
     */
    public function __construct()
    {
        // Apply admin middleware for all methods
        $this->middleware('admin');
    }

    /**
     * Display a listing of exercise sets
     * 
     * @param  int $workoutId
     * @param  int $exerciseId
     * @return \Illuminate\Http\Response
     */
    public function index($workoutId, $exerciseId)
    {
        try {
            $workout = Workout::findOrFail($workoutId);
            $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
                ->findOrFail($exerciseId);
            
            $sets = WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                ->orderBy('set_number')
                ->paginate(20);

            return view('admin.workouts.exercises.sets.index', compact('workout', 'workoutExercise', 'sets'));
        } catch (\Exception $e) {
            Log::error('Failed to load exercise sets: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load exercise sets.');
        }
    }

    /**
     * Show the form for creating a new exercise set
     * 
     * @param  int $workoutId
     * @param  int $exerciseId
     * @return \Illuminate\Http\Response
     */
    public function create($workoutId, $exerciseId)
    {
        try {
            $workout = Workout::findOrFail($workoutId);
            $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
                ->findOrFail($exerciseId);
            
            // Get next set number
            $nextSetNumber = WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                ->max('set_number') + 1;

            return view('admin.workouts.exercises.sets.create', compact('workout', 'workoutExercise', 'nextSetNumber'));
        } catch (\Exception $e) {
            Log::error('Failed to load create set form: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load create form.');
        }
    }

    /**
     * Store a newly created exercise set in storage
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  int $workoutId
     * @param  int $exerciseId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $workoutId, $exerciseId)
    {
        // Validate input data
        $validator = Validator::make($request->all(), [
            'set_number' => 'required|integer|min:1',
            'reps' => 'nullable|integer|min:1',
            'weight' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'rest_time' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $workout = Workout::findOrFail($workoutId);
            $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
                ->findOrFail($exerciseId);

            DB::beginTransaction();

            // Check if set number already exists
            $existingSet = WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                ->where('set_number', $request->set_number)
                ->first();

            if ($existingSet) {
                // Shift existing sets up
                WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                    ->where('set_number', '>=', $request->set_number)
                    ->increment('set_number');
            }

            // Create new set
            $set = WorkoutExerciseSet::create([
                'workout_exercise_id' => $exerciseId,
                'set_number' => $request->set_number,
                'reps' => $request->reps,
                'weight' => $request->weight,
                'duration' => $request->duration,
                'rest_time' => $request->rest_time,
                'notes' => $request->notes,
                'is_completed' => false,
            ]);

            DB::commit();

            Log::info('Exercise set created successfully', [
                'set_id' => $set->id,
                'workout_id' => $workoutId,
                'exercise_id' => $exerciseId,
                'user_id' => Auth()->id
            ]);

            return redirect()->route('workout-exercises.show', [$workoutId, $exerciseId])
                ->with('success', 'Exercise set created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create exercise set: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create exercise set.')
                ->withInput();
        }
    }

    /**
     * Display the specified exercise set
     * 
     * @param  int $workoutId
     * @param  int $exerciseId
     * @param  int $setId
     * @return \Illuminate\Http\Response
     */
    public function show($workoutId, $exerciseId, $setId)
    {
        try {
            $workout = Workout::findOrFail($workoutId);
            $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
                ->findOrFail($exerciseId);
            $set = WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                ->findOrFail($setId);

            return view('admin.workouts.exercises.sets.show', compact('workout', 'workoutExercise', 'set'));
        } catch (\Exception $e) {
            Log::error('Failed to load exercise set: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Exercise set not found.');
        }
    }

    /**
     * Show the form for editing the specified exercise set
     * 
     * @param  int $workoutId
     * @param  int $exerciseId
     * @param  int $setId
     * @return \Illuminate\Http\Response
     */
    public function edit($workoutId, $exerciseId, $setId)
    {
        try {
            $workout = Workout::findOrFail($workoutId);
            $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
                ->findOrFail($exerciseId);
            $set = WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                ->findOrFail($setId);

            return view('admin.workouts.exercises.sets.edit', compact('workout', 'workoutExercise', 'set'));
        } catch (\Exception $e) {
            Log::error('Failed to load edit set form: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load edit form.');
        }
    }

    /**
     * Update the specified exercise set in storage
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  int $workoutId
     * @param  int $exerciseId
     * @param  int $setId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $workoutId, $exerciseId, $setId)
    {
        // Validate input data
        $validator = Validator::make($request->all(), [
            'set_number' => 'required|integer|min:1',
            'reps' => 'nullable|integer|min:1',
            'weight' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:1',
            'rest_time' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $workout = Workout::findOrFail($workoutId);
            $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
                ->findOrFail($exerciseId);
            $set = WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                ->findOrFail($setId);

            DB::beginTransaction();

            // Handle set number change
            if ($set->set_number != $request->set_number) {
                $oldSetNumber = $set->set_number;
                $newSetNumber = $request->set_number;

                // Check if new set number already exists
                $existingSet = WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                    ->where('set_number', $newSetNumber)
                    ->where('id', '!=', $setId)
                    ->first();

                if ($existingSet) {
                    if ($newSetNumber > $oldSetNumber) {
                        // Moving down - shift sets between old and new position up
                        WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                            ->where('set_number', '>', $oldSetNumber)
                            ->where('set_number', '<=', $newSetNumber)
                            ->decrement('set_number');
                    } else {
                        // Moving up - shift sets between new and old position down
                        WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                            ->where('set_number', '>=', $newSetNumber)
                            ->where('set_number', '<', $oldSetNumber)
                            ->increment('set_number');
                    }
                }
            }

            // Update set data
            $set->update([
                'set_number' => $request->set_number,
                'reps' => $request->reps,
                'weight' => $request->weight,
                'duration' => $request->duration,
                'rest_time' => $request->rest_time,
                'notes' => $request->notes,
            ]);

            DB::commit();

            Log::info('Exercise set updated successfully', [
                'set_id' => $set->id,
                'workout_id' => $workoutId,
                'exercise_id' => $exerciseId,
                'user_id' => Auth()->id
            ]);

            return redirect()->route('workout-exercises.show', [$workoutId, $exerciseId])
                ->with('success', 'Exercise set updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update exercise set: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update exercise set.')
                ->withInput();
        }
    }

    /**
     * Remove the specified exercise set from storage
     * 
     * @param  int $workoutId
     * @param  int $exerciseId
     * @param  int $setId
     * @return \Illuminate\Http\Response
     */
    public function destroy($workoutId, $exerciseId, $setId)
    {
        try {
            $workout = Workout::findOrFail($workoutId);
            $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
                ->findOrFail($exerciseId);
            $set = WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                ->findOrFail($setId);

            DB::beginTransaction();

            $setNumber = $set->set_number;

            // Delete the set
            $set->delete();

            // Reorder remaining sets
            WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                ->where('set_number', '>', $setNumber)
                ->decrement('set_number');

            DB::commit();

            Log::info('Exercise set deleted successfully', [
                'set_id' => $setId,
                'workout_id' => $workoutId,
                'exercise_id' => $exerciseId,
                'user_id' => Auth()->id
            ]);

            return redirect()->route('workout-exercises.show', [$workoutId, $exerciseId])
                ->with('success', 'Exercise set deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete exercise set: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete exercise set.');
        }
    }

    /**
     * Toggle the completion status of an exercise set
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  int $workoutId
     * @param  int $exerciseId
     * @param  int $setId
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $workoutId, $exerciseId, $setId)
    {
        try {
            $workout = Workout::findOrFail($workoutId);
            $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
                ->findOrFail($exerciseId);
            $set = WorkoutExerciseSet::where('workout_exercise_id', $exerciseId)
                ->findOrFail($setId);

            // Toggle completion status
            $set->is_completed = !$set->is_completed;
            $set->save();

            Log::info('Exercise set status toggled', [
                'set_id' => $set->id,
                'new_status' => $set->is_completed,
                'workout_id' => $workoutId,
                'exercise_id' => $exerciseId,
                'user_id' => Auth()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Set status updated successfully.',
                'is_completed' => $set->is_completed
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle set status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update set status.'
            ], 500);
        }
    }
}