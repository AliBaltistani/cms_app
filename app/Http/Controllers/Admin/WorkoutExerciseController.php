<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutExerciseSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkoutExerciseController extends Controller
{
    /**
     * Display a listing of the workout exercises.
     */
    public function index(Workout $workout)
    {
        $exercises = $workout->workoutExercises()
            ->with(['exercise', 'exerciseSets'])
            ->ordered()
            ->get();

        return view('admin.workouts.exercises.index', compact('workout', 'exercises'));
    }

    /**
     * Show the form for creating a new workout exercise.
     */
    public function create(Workout $workout)
    {
        $exercises = Workout::where('id', '!=', $workout->id)
            ->orderBy('name')
            ->get();

        // Get the next order number for the new exercise
        $nextOrder = $workout->workoutExercises()->max('order') + 1;

        return view('admin.workouts.exercises.create', compact('workout', 'exercises', 'nextOrder'));
    }

    /**
     * Store a newly created workout exercise in storage.
     */
    public function store(Request $request, Workout $workout)
    {
        $validator = Validator::make($request->all(), [
            'exercise_id' => 'required|exists:workouts,id',
            'sets' => 'nullable|integer|min:1|max:10',
            'reps' => 'nullable|integer|min:1|max:100',
            'weight' => 'nullable|numeric|min:0|max:1000',
            'duration' => 'nullable|integer|min:1|max:3600',
            'rest_interval' => 'nullable|string|max:50',
            'tempo' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'order' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $weightKg = isset($data['weight']) && $data['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$data['weight']) : null;
            $data['weight'] = $weightKg;
            $workoutExercise = $workout->workoutExercises()->create($data);

            // Create default sets if sets count is provided
            if ($request->sets) {
                for ($i = 1; $i <= $request->sets; $i++) {
                    $workoutExercise->exerciseSets()->create([
                        'set_number' => $i,
                        'reps' => $request->reps,
                        'weight' => $weightKg,
                        'duration' => $request->duration,
                    ]);
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Exercise added successfully',
                    'exercise' => $workoutExercise->load(['exercise', 'exerciseSets'])
                ]);
            }

            return redirect()->route('workouts.exercises.index', $workout)
                ->with('success', 'Exercise added successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add exercise: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Failed to add exercise: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified workout exercise.
     */
    public function show(Workout $workout, WorkoutExercise $exercise)
    {
        $exercise->load(['exercise', 'exerciseSets']);
        
        return view('admin.workouts.exercises.show', compact('workout', 'exercise'));
    }

    /**
     * Show the form for editing the specified workout exercise.
     */
    public function edit(Workout $workout, WorkoutExercise $exercise)
    {
        $availableExercises = Workout::where('id', '!=', $workout->id)
            ->orderBy('name')
            ->get();

        $exercise->load(['exercise', 'exerciseSets']);

        // Pass the exercise as workoutExercise to match the view expectation
        $workoutExercise = $exercise;
        
        // Pass availableExercises as exercises to match the view expectation
        $exercises = $availableExercises;

        return view('admin.workouts.exercises.edit', compact('workout', 'workoutExercise', 'exercises'));
    }

    /**
     * Update the specified workout exercise in storage.
     */
    public function update(Request $request, Workout $workout, WorkoutExercise $exercise)
    {
        $validator = Validator::make($request->all(), [
            'exercise_id' => 'required|exists:workouts,id',
            'sets' => 'nullable|integer|min:1|max:10',
            'reps' => 'nullable|integer|min:1|max:100',
            'weight' => 'nullable|numeric|min:0|max:1000',
            'duration' => 'nullable|integer|min:1|max:3600',
            'rest_interval' => 'nullable|string|max:50',
            'tempo' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
            'order' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $weightKg = isset($data['weight']) && $data['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$data['weight']) : null;
            $data['weight'] = $weightKg;
            $exercise->update($data);

            // Update sets if sets count changed
            if ($request->has('sets') && $request->sets != $exercise->exerciseSets->count()) {
                // Remove existing sets
                $exercise->exerciseSets()->delete();
                
                // Create new sets
                for ($i = 1; $i <= $request->sets; $i++) {
                    $exercise->exerciseSets()->create([
                        'set_number' => $i,
                        'reps' => $request->reps,
                        'weight' => $weightKg,
                        'duration' => $request->duration,
                    ]);
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Exercise updated successfully',
                    'exercise' => $exercise->load(['exercise', 'exerciseSets'])
                ]);
            }

            return redirect()->route('workouts.exercises.index', $workout)
                ->with('success', 'Exercise updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update exercise: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Failed to update exercise: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified workout exercise from storage.
     */
    public function destroy(Workout $workout, WorkoutExercise $exercise)
    {
        try {
            $exercise->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Exercise removed successfully'
                ]);
            }

            return redirect()->route('workouts.exercises.index', $workout)
                ->with('success', 'Exercise removed successfully');

        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove exercise: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to remove exercise: ' . $e->getMessage());
        }
    }

    /**
     * Update exercise order.
     */
    public function updateOrder(Request $request, Workout $workout)
    {
        $validator = Validator::make($request->all(), [
            'exercises' => 'required|array',
            'exercises.*.id' => 'required|exists:workout_exercises,id',
            'exercises.*.order' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->exercises as $exerciseData) {
                WorkoutExercise::where('id', $exerciseData['id'])
                    ->where('workout_id', $workout->id)
                    ->update(['order' => $exerciseData['order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exercise order updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exercise order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update exercise set data.
     */
    public function updateSet(Request $request, Workout $workout, WorkoutExercise $exercise)
    {
        $validator = Validator::make($request->all(), [
            'set_number' => 'required|integer|min:1|max:10',
            'field' => 'required|in:reps,weight,duration,rest_time,notes',
            'value' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $set = $exercise->exerciseSets()
                ->where('set_number', $request->set_number)
                ->first();

            if (!$set) {
                $set = $exercise->exerciseSets()->create([
                    'set_number' => $request->set_number,
                ]);
            }

            $field = $request->field;
            $value = $request->value;
            if ($field === 'weight' && $value !== null && $value !== '') {
                $value = \App\Support\UnitConverter::lbsToKg((float)$value);
            }
            $set->update([$field => $value]);

            return response()->json([
                'success' => true,
                'message' => 'Set updated successfully',
                'set' => $set
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update set: ' . $e->getMessage()
            ], 500);
        }
    }
}