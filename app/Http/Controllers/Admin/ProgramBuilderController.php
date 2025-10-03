<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Week;
use App\Models\Day;
use App\Models\Circuit;
use App\Models\ProgramExercise;
use App\Models\ExerciseSet;
use App\Models\Workout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Program Builder Controller
 * 
 * Handles the complex program building interface with hierarchical structure:
 * Program → Week → Day → Circuit → Exercise → Sets
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    Workout Exercise Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ProgramBuilderController extends Controller
{
    /**
     * Show the program builder interface
     * 
     * @param  \App\Models\Program  $program
     * @return \Illuminate\View\View
     */
    public function show(Program $program): View
    {
        $program->load([
            'weeks.days.circuits.programExercises.workout',
            'weeks.days.circuits.programExercises.exerciseSets'
        ]);
        
        $workouts = Workout::where('is_active', true)->get();
        
        return view('admin.programs.builder', compact('program', 'workouts'));
    }

    /**
     * Add a new week to the program
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Program  $program
     * @return \Illuminate\Http\JsonResponse
     */
    public function addWeek(Request $request, Program $program): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'week_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $week = Week::create([
                'program_id' => $program->id,
                'week_number' => $request->week_number,
                'title' => $request->title,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Week added successfully',
                'week' => $week
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@addWeek: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the week'
            ], 500);
        }
    }

    /**
     * Add a new day to a week
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Week  $week
     * @return \Illuminate\Http\JsonResponse
     */
    public function addDay(Request $request, Week $week): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'day_number' => 'required|integer|min:1',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cool_down' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $day = Day::create([
                'week_id' => $week->id,
                'day_number' => $request->day_number,
                'title' => $request->title,
                'description' => $request->description,
                'cool_down' => $request->cool_down
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Day added successfully',
                'day' => $day
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@addDay: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the day'
            ], 500);
        }
    }

    /**
     * Add a new circuit to a day
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Day  $day
     * @return \Illuminate\Http\JsonResponse
     */
    public function addCircuit(Request $request, Day $day): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'circuit_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $circuit = Circuit::create([
                'day_id' => $day->id,
                'circuit_number' => $request->circuit_number,
                'title' => $request->title,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Circuit added successfully',
                'circuit' => $circuit
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@addCircuit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the circuit'
            ], 500);
        }
    }

    /**
     * Add an exercise to a circuit
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Circuit  $circuit
     * @return \Illuminate\Http\JsonResponse
     */
    public function addExercise(Request $request, Circuit $circuit): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'workout_id' => 'required|exists:workouts,id',
                'order' => 'required|integer|min:0',
                'tempo' => 'nullable|string|max:50',
                'rest_interval' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
                'sets' => 'required|array|min:1',
                'sets.*.set_number' => 'required|integer|min:1',
                'sets.*.reps' => 'nullable|integer|min:0',
                'sets.*.weight' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $programExercise = ProgramExercise::create([
                'circuit_id' => $circuit->id,
                'workout_id' => $request->workout_id,
                'order' => $request->order,
                'tempo' => $request->tempo,
                'rest_interval' => $request->rest_interval,
                'notes' => $request->notes
            ]);

            // Add exercise sets
            foreach ($request->sets as $setData) {
                ExerciseSet::create([
                    'program_exercise_id' => $programExercise->id,
                    'set_number' => $setData['set_number'],
                    'reps' => $setData['reps'],
                    'weight' => $setData['weight']
                ]);
            }

            DB::commit();

            $programExercise->load(['workout', 'exerciseSets']);

            return response()->json([
                'success' => true,
                'message' => 'Exercise added successfully',
                'program_exercise' => $programExercise
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@addExercise: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the exercise'
            ], 500);
        }
    }

    /**
     * Update exercise workout only
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProgramExercise  $programExercise
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateExerciseWorkout(Request $request, ProgramExercise $programExercise): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'workout_id' => 'required|exists:workouts,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $programExercise->update([
                'workout_id' => $request->workout_id
            ]);

            $programExercise->load(['workout', 'exerciseSets']);

            return response()->json([
                'success' => true,
                'message' => 'Exercise workout updated successfully',
                'program_exercise' => $programExercise
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@updateExerciseWorkout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the exercise workout'
            ], 500);
        }
    }

    /**
     * Update an exercise in a circuit
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProgramExercise  $programExercise
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateExercise(Request $request, ProgramExercise $programExercise): JsonResponse
    {
        try {
            // Debug: Log the program exercise ID to ensure it's not null
            Log::info('UpdateExercise called with ProgramExercise ID: ' . $programExercise->id);
            
            $validator = Validator::make($request->all(), [
                'tempo' => 'nullable|string|max:50',
                'rest_interval' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
                'sets' => 'required|array|min:1',
                'sets.*.set_number' => 'required|integer|min:1',
                'sets.*.reps' => 'nullable|integer|min:0',
                'sets.*.weight' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ensure the program exercise exists and has a valid ID
            if (!$programExercise || !$programExercise->id) {
                Log::error('ProgramExercise is null or has no ID in updateExercise method');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid exercise reference'
                ], 400);
            }

            DB::beginTransaction();

            $programExercise->update([
                'tempo' => $request->tempo,
                'rest_interval' => $request->rest_interval,
                'notes' => $request->notes
            ]);

            // Delete existing sets and recreate
            $programExercise->exerciseSets()->delete();

            foreach ($request->sets as $setData) {
                // Debug: Log the program exercise ID before creating sets
                Log::info('Creating ExerciseSet with program_exercise_id: ' . $programExercise->id);
                
                ExerciseSet::create([
                    'program_exercise_id' => $programExercise->id,
                    'set_number' => $setData['set_number'],
                    'reps' => $setData['reps'],
                    'weight' => $setData['weight']
                ]);
            }

            DB::commit();

            $programExercise->load(['workout', 'exerciseSets']);

            return response()->json([
                'success' => true,
                'message' => 'Exercise updated successfully',
                'program_exercise' => $programExercise
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@updateExercise: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the exercise: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove an exercise from a circuit
     * 
     * @param  \App\Models\ProgramExercise  $programExercise
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeExercise(ProgramExercise $programExercise): JsonResponse
    {
        try {
            $programExercise->delete();

            return response()->json([
                'success' => true,
                'message' => 'Exercise removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@removeExercise: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the exercise'
            ], 500);
        }
    }

    /**
     * Remove a circuit from a day
     * 
     * @param  \App\Models\Circuit  $circuit
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCircuit(Circuit $circuit): JsonResponse
    {
        try {
            $circuit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Circuit removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@removeCircuit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the circuit'
            ], 500);
        }
    }

    /**
     * Remove a day from a week
     * 
     * @param  \App\Models\Day  $day
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeDay(Day $day): JsonResponse
    {
        try {
            $day->delete();

            return response()->json([
                'success' => true,
                'message' => 'Day removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@removeDay: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the day'
            ], 500);
        }
    }

    /**
     * Remove a week from a program
     * 
     * @param  \App\Models\Week  $week
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeWeek(Week $week): JsonResponse
    {
        try {
            $week->delete();

            return response()->json([
                'success' => true,
                'message' => 'Week removed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@removeWeek: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the week'
            ], 500);
        }
    }

    /**
     * Show the form for editing a week
     * 
     * @param  \App\Models\Week  $week
     * @return \Illuminate\Http\JsonResponse
     */
    public function editWeek(Week $week): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'week' => $week
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@editWeek: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the week'
            ], 500);
        }
    }

    /**
     * Update a week
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Week  $week
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateWeek(Request $request, Week $week): JsonResponse
    {
        $request->validate([
            'week_number' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $week->update([
                'week_number' => $request->week_number,
                'title' => $request->title,
                'description' => $request->description
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Week updated successfully',
                'week' => $week
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@updateWeek: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the week'
            ], 500);
        }
    }

    /**
     * Duplicate a week with all its nested data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Week  $week
     * @return \Illuminate\Http\JsonResponse
     */
    public function duplicateWeek(Request $request, Week $week): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'week_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if week number already exists in the same program
            $existingWeek = Week::where('program_id', $week->program_id)
                                ->where('week_number', $request->week_number)
                                ->first();

            if ($existingWeek) {
                return response()->json([
                    'success' => false,
                    'message' => 'Week number already exists in this program',
                    'errors' => ['week_number' => ['Week number already exists']]
                ], 422);
            }

            DB::beginTransaction();

            // Load the original week with all its nested relationships
            $originalWeek = Week::with([
                'days.circuits.programExercises.exerciseSets'
            ])->find($week->id);

            // Create the new week
            $newWeek = Week::create([
                'program_id' => $originalWeek->program_id,
                'week_number' => $request->week_number,
                'title' => $request->title ?: $originalWeek->title,
                'description' => $request->description ?: $originalWeek->description
            ]);

            // Duplicate all days in the week
            foreach ($originalWeek->days as $originalDay) {
                $newDay = Day::create([
                    'week_id' => $newWeek->id,
                    'day_number' => $originalDay->day_number,
                    'title' => $originalDay->title,
                    'description' => $originalDay->description,
                    'cool_down' => $originalDay->cool_down
                ]);

                // Duplicate all circuits in each day
                foreach ($originalDay->circuits as $originalCircuit) {
                    $newCircuit = Circuit::create([
                        'day_id' => $newDay->id,
                        'circuit_number' => $originalCircuit->circuit_number,
                        'title' => $originalCircuit->title,
                        'description' => $originalCircuit->description
                    ]);

                    // Duplicate all exercises in each circuit
                    foreach ($originalCircuit->programExercises as $originalExercise) {
                        $newExercise = ProgramExercise::create([
                            'circuit_id' => $newCircuit->id,
                            'workout_id' => $originalExercise->workout_id,
                            'order' => $originalExercise->order,
                            'tempo' => $originalExercise->tempo,
                            'rest_interval' => $originalExercise->rest_interval,
                            'notes' => $originalExercise->notes
                        ]);

                        // Duplicate all sets for each exercise
                        foreach ($originalExercise->exerciseSets as $originalSet) {
                            ExerciseSet::create([
                                'program_exercise_id' => $newExercise->id,
                                'set_number' => $originalSet->set_number,
                                'reps' => $originalSet->reps,
                                'weight' => $originalSet->weight
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // Log the successful duplication
            Log::info('Week duplicated successfully', [
                'original_week_id' => $originalWeek->id,
                'new_week_id' => $newWeek->id,
                'program_id' => $originalWeek->program_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Week duplicated successfully',
                'week' => $newWeek
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@duplicateWeek: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while duplicating the week'
            ], 500);
        }
    }

    /**
     * Show the form for editing a day
     * 
     * @param  \App\Models\Day  $day
     * @return \Illuminate\Http\JsonResponse
     */
    public function editDay(Day $day): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'day' => $day
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@editDay: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the day'
            ], 500);
        }
    }

    /**
     * Update a day
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Day  $day
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateDay(Request $request, Day $day): JsonResponse
    {
        $request->validate([
            'day_number' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cool_down' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $day->update([
                'day_number' => $request->day_number,
                'title' => $request->title,
                'description' => $request->description,
                'cool_down' => $request->cool_down
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Day updated successfully',
                'day' => $day
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@updateDay: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the day'
            ], 500);
        }
    }

    /**
     * Show the form for editing a circuit
     * 
     * @param  \App\Models\Circuit  $circuit
     * @return \Illuminate\Http\JsonResponse
     */
    public function editCircuit(Circuit $circuit): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'circuit' => $circuit
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@editCircuit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the circuit'
            ], 500);
        }
    }

    /**
     * Update a circuit
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Circuit  $circuit
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCircuit(Request $request, Circuit $circuit): JsonResponse
    {
        $request->validate([
            'circuit_number' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $circuit->update([
                'circuit_number' => $request->circuit_number,
                'title' => $request->title,
                'description' => $request->description
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Circuit updated successfully',
                'circuit' => $circuit
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@updateCircuit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the circuit'
            ], 500);
        }
    }

    /**
     * Show the form for editing an exercise
     * 
     * @param  \App\Models\ProgramExercise  $exercise
     * @return \Illuminate\Http\JsonResponse
     */
    public function editExercise(ProgramExercise $exercise): JsonResponse
    {
        try {
            $exercise->load(['workout', 'exerciseSets']);
            
            return response()->json([
                'success' => true,
                'exercise' => $exercise
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@editExercise: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the exercise'
            ], 500);
        }
    }

    /**
     * Get exercise sets for management
     * 
     * @param  \App\Models\ProgramExercise  $exercise
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExerciseSets(ProgramExercise $exercise): JsonResponse
    {
        try {
            $exercise->load(['workout', 'exerciseSets']);
            
            return response()->json([
                'success' => true,
                'exercise' => $exercise
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@getExerciseSets: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the exercise sets'
            ], 500);
        }
    }

    /**
     * Manage sets for a specific exercise
     * 
     * Returns exercise data with its sets for the sets management modal
     * 
     * @param  \App\Models\ProgramExercise  $programExercise
     * @return \Illuminate\Http\JsonResponse
     */
    public function manageSets(ProgramExercise $programExercise): JsonResponse
    {
        try {
            // Load the exercise with its workout and exercise sets
            $programExercise->load(['workout', 'exerciseSets' => function($query) {
                $query->orderBy('set_number');
            }]);

            return response()->json([
                'success' => true,
                'exercise' => $programExercise
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@manageSets: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading exercise sets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update exercise sets
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProgramExercise  $exercise
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateExerciseSets(Request $request, ProgramExercise $exercise): JsonResponse
    {
        $request->validate([
            'sets' => 'required|array|min:1',
            'sets.*.set_number' => 'required|integer|min:1',
            'sets.*.reps' => 'nullable|integer|min:0',
            'sets.*.weight' => 'nullable|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            // Delete existing sets
            $exercise->exerciseSets()->delete();

            // Create new sets
            foreach ($request->sets as $setData) {
                ExerciseSet::create([
                    'program_exercise_id' => $exercise->id,
                    'set_number' => $setData['set_number'],
                    'reps' => $setData['reps'] ?? null,
                    'weight' => $setData['weight'] ?? null
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exercise sets updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@updateExerciseSets: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the exercise sets'
            ], 500);
        }
    }

    /**
     * Reorder weeks within a program
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Program  $program
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderWeeks(Request $request, Program $program): JsonResponse
    {
        $request->validate([
            'weeks' => 'required|array',
            'weeks.*.id' => 'required|exists:weeks,id',
            'weeks.*.week_number' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->weeks as $weekData) {
                Week::where('id', $weekData['id'])
                    ->where('program_id', $program->id)
                    ->update(['week_number' => $weekData['week_number']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Weeks reordered successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@reorderWeeks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while reordering weeks'
            ], 500);
        }
    }

    /**
     * Reorder days within a week
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Week  $week
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderDays(Request $request, Week $week): JsonResponse
    {
        $request->validate([
            'days' => 'required|array',
            'days.*.id' => 'required|exists:days,id',
            'days.*.day_number' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->days as $dayData) {
                Day::where('id', $dayData['id'])
                    ->where('week_id', $week->id)
                    ->update(['day_number' => $dayData['day_number']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Days reordered successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@reorderDays: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while reordering days'
            ], 500);
        }
    }

    /**
     * Reorder circuits within a day
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Day  $day
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderCircuits(Request $request, Day $day): JsonResponse
    {
        $request->validate([
            'circuits' => 'required|array',
            'circuits.*.id' => 'required|exists:circuits,id',
            'circuits.*.circuit_number' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->circuits as $circuitData) {
                Circuit::where('id', $circuitData['id'])
                    ->where('day_id', $day->id)
                    ->update(['circuit_number' => $circuitData['circuit_number']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Circuits reordered successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@reorderCircuits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while reordering circuits'
            ], 500);
        }
    }

    /**
     * Reorder exercises within a circuit
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Circuit  $circuit
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderExercises(Request $request, Circuit $circuit): JsonResponse
    {
        $request->validate([
            'exercises' => 'required|array',
            'exercises.*.id' => 'required|exists:program_exercises,id',
            'exercises.*.order' => 'required|integer|min:0'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->exercises as $exerciseData) {
                ProgramExercise::where('id', $exerciseData['id'])
                    ->where('circuit_id', $circuit->id)
                    ->update(['order' => $exerciseData['order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exercises reordered successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@reorderExercises: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while reordering exercises'
            ], 500);
        }
    }
}