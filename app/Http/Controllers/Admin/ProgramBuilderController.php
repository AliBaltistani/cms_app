<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgramBuilder\StoreWeekRequest;
use App\Http\Requests\ProgramBuilder\StoreDayRequest;
use App\Http\Requests\ProgramBuilder\StoreCircuitRequest;
use App\Http\Requests\ProgramBuilder\StoreExerciseRequest;
use App\Http\Requests\ProgramBuilder\UpdateExerciseRequest;
use App\Http\Requests\ProgramBuilder\UpdateExerciseWorkoutRequest;
use App\Models\Program;
use App\Models\Week;
use App\Models\Day;
use App\Models\Circuit;
use App\Models\ProgramExercise;
use App\Models\ExerciseSet;
use App\Models\Workout;
use App\Models\ProgramColumnConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

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
    private function programIdFrom($model): ?int
    {
        if ($model instanceof Program) { return (int) $model->id; }
        if ($model instanceof Week) { return (int) $model->program_id; }
        if ($model instanceof Day) { return (int) (Week::where('id', $model->week_id)->value('program_id')); }
        if ($model instanceof Circuit) {
            $dayId = $model->day_id;
            $weekId = Day::where('id', $dayId)->value('week_id');
            return (int) (Week::where('id', $weekId)->value('program_id'));
        }
        if ($model instanceof ProgramExercise) {
            $circuitId = $model->circuit_id;
            $dayId = Circuit::where('id', $circuitId)->value('day_id');
            $weekId = Day::where('id', $dayId)->value('week_id');
            return (int) (Week::where('id', $weekId)->value('program_id'));
        }
        return null;
    }

    private function ensureTrainerOwnsModel($model): void
    {
        $user = Auth::user();
        if (!request()->is('trainer/*')) { return; }
        $programId = $this->programIdFrom($model);
        if (!$programId) { abort(403); }
        $trainerId = Program::where('id', $programId)->value('trainer_id');
        if (!$user || (int) $trainerId !== (int) $user->id) { abort(403); }
    }
    /**
     * Return default column configuration structure.
     * Mirrors the client default to ensure consistency when initializing.
     */
    private function defaultColumnConfig(): array
    {
        return [
            [ 'id' => 'exercise', 'name' => 'Exercise', 'width' => '25%', 'type' => 'text', 'required' => true ],
            [ 'id' => 'set1',    'name' => 'Set 1 - rep / w',  'width' => '12%', 'type' => 'text', 'required' => false ],
            [ 'id' => 'set2',    'name' => 'Set 2 - rep / w',  'width' => '12%', 'type' => 'text', 'required' => false ],
            [ 'id' => 'set3',    'name' => 'Set 3 - rep / w',  'width' => '12%', 'type' => 'text', 'required' => false ],
            [ 'id' => 'set4',    'name' => 'Set 4 - reps / w', 'width' => '12%', 'type' => 'text', 'required' => false ],
            [ 'id' => 'set5',    'name' => 'Set 5 - reps / w', 'width' => '12%', 'type' => 'text', 'required' => false ],
            [ 'id' => 'notes',   'name' => 'Notes',            'width' => '15%', 'type' => 'text', 'required' => false ],
        ];
    }

    /**
     * Get the program's column configuration.
     *
     * @param  Program $program The Program instance resolved from route.
     * @return JsonResponse     Returns JSON with success flag and columns array.
     * @throws \Exception      On unexpected retrieval errors (logged internally).
     */
    public function getColumnConfig(Program $program): JsonResponse
    {
        $this->ensureTrainerOwnsModel($program);
        try {
            $config = ProgramColumnConfig::where('program_id', $program->id)->first();

            if (!$config) {
                // Initialize with defaults
                $config = ProgramColumnConfig::create([
                    'program_id' => $program->id,
                    'columns' => $this->defaultColumnConfig(),
                ]);
            }

            return response()->json([
                'success' => true,
                'columns' => $config->columns,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@getColumnConfig: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load program column configuration',
            ], 500);
        }
    }

    /**
     * Update the program's column configuration.
     *
     * @param  Request $request The incoming request containing the columns array.
     * @param  Program $program The Program instance resolved from route.
     * @return JsonResponse     Returns JSON with success flag and saved columns array.
     * @throws \Exception      On unexpected save errors (logged internally).
     */
    public function updateColumnConfig(Request $request, Program $program): JsonResponse
    {
        $this->ensureTrainerOwnsModel($program);
        try {
            $validator = Validator::make($request->all(), [
                'columns' => 'required|array|min:1',
                'columns.*.id' => 'required|string|max:100',
                'columns.*.name' => 'required|string|max:255',
                'columns.*.width' => 'required|string|max:10',
                'columns.*.type' => 'required|string|in:text,number',
                'columns.*.required' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $config = ProgramColumnConfig::updateOrCreate(
                ['program_id' => $program->id],
                ['columns' => $request->input('columns')]
            );

            return response()->json([
                'success' => true,
                'message' => 'Column configuration updated',
                'columns' => $config->columns,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramBuilderController@updateColumnConfig: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update program column configuration',
            ], 500);
        }
    }
    /**
     * Show the program builder interface
     * 
     * @param  \App\Models\Program  $program
     * @return \Illuminate\View\View
     */
    public function show(Program $program): View
    {
        $this->ensureTrainerOwnsModel($program);
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
    public function addWeek(StoreWeekRequest $request, Program $program): JsonResponse
    {
        $this->ensureTrainerOwnsModel($program);
        try {
            $validated = $request->validated();

            $week = Week::create([
                'program_id' => $program->id,
                'week_number' => $validated['week_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null
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
    public function addDay(StoreDayRequest $request, Week $week): JsonResponse
    {
        $this->ensureTrainerOwnsModel($week);
        try {
            $validated = $request->validated();

            $day = Day::create([
                'week_id' => $week->id,
                'day_number' => $validated['day_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'cool_down' => $validated['cool_down'] ?? null
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
    public function addCircuit(StoreCircuitRequest $request, Day $day): JsonResponse
    {
        $this->ensureTrainerOwnsModel($day);
        try {
            $validated = $request->validated();

            $circuit = Circuit::create([
                'day_id' => $day->id,
                'circuit_number' => $validated['circuit_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null
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
    public function addExercise(StoreExerciseRequest $request, Circuit $circuit): JsonResponse
    {
        $this->ensureTrainerOwnsModel($circuit);
        try {
            $validated = $request->validated();

            DB::beginTransaction();

            $programExercise = ProgramExercise::create([
                'circuit_id' => $circuit->id,
                'workout_id' => $validated['workout_id'] ?? null,
                'name' => $validated['name'] ?? null,
                'order' => $validated['order'],
                'tempo' => $validated['tempo'] ?? null,
                'rest_interval' => $validated['rest_interval'] ?? null,
                'notes' => $validated['notes'] ?? null
            ]);

            // Add exercise sets
            foreach ($validated['sets'] as $setData) {
                $weightKg = isset($setData['weight']) && $setData['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$setData['weight']) : null;
                ExerciseSet::create([
                    'program_exercise_id' => $programExercise->id,
                    'set_number' => $setData['set_number'],
                    'reps' => $setData['reps'],
                    'weight' => $weightKg
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
    public function updateExerciseWorkout(UpdateExerciseWorkoutRequest $request, ProgramExercise $programExercise): JsonResponse
    {
        $this->ensureTrainerOwnsModel($programExercise);
        try {
            $validated = $request->validated();

            $programExercise->update([
                'workout_id' => $validated['workout_id']
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
    public function updateExercise(UpdateExerciseRequest $request, ProgramExercise $programExercise): JsonResponse
    {
        $this->ensureTrainerOwnsModel($programExercise);
        try {
            // Debug: Log the program exercise ID to ensure it's not null
            Log::info('UpdateExercise called with ProgramExercise ID: ' . $programExercise->id);
            
            $validated = $request->validated();

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
                'name' => $validated['name'] ?? $programExercise->name,
                'tempo' => $validated['tempo'] ?? null,
                'rest_interval' => $validated['rest_interval'] ?? null,
                'notes' => $validated['notes'] ?? null
            ]);

            // Delete existing sets and recreate
            $programExercise->exerciseSets()->delete();

            foreach ($validated['sets'] as $setData) {
                // Debug: Log the program exercise ID before creating sets
                Log::info('Creating ExerciseSet with program_exercise_id: ' . $programExercise->id);
                
                $weightKg = isset($setData['weight']) && $setData['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$setData['weight']) : null;
                ExerciseSet::create([
                    'program_exercise_id' => $programExercise->id,
                    'set_number' => $setData['set_number'],
                    'reps' => $setData['reps'],
                    'weight' => $weightKg
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
        $this->ensureTrainerOwnsModel($programExercise);
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
        $this->ensureTrainerOwnsModel($circuit);
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
        $this->ensureTrainerOwnsModel($day);
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
        $this->ensureTrainerOwnsModel($week);
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
        $this->ensureTrainerOwnsModel($week);
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
        $this->ensureTrainerOwnsModel($week);
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
        $this->ensureTrainerOwnsModel($week);
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
                    'cool_down' => $originalDay->cool_down,
                    'custom_rows' => $originalDay->custom_rows
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
                            'name' => $originalExercise->name,
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

            // Reload the newly created week with nested relationships for UI rendering
            $newWeekLoaded = Week::with([
                'days.circuits.programExercises.exerciseSets'
            ])->find($newWeek->id);

            return response()->json([
                'success' => true,
                'message' => 'Week duplicated successfully',
                'week' => $newWeekLoaded
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
     * Duplicate a day with its circuits, exercises and sets
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Day  $day
     * @return \Illuminate\Http\JsonResponse
     */
    public function duplicateDay(Request $request, Day $day): JsonResponse
    {
        $this->ensureTrainerOwnsModel($day);
        try {
            $validator = Validator::make($request->all(), [
                'day_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'cool_down' => 'nullable|string',
                'custom_rows' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Enforce unique day_number within the same week
            $existingDay = Day::where('week_id', $day->week_id)
                              ->where('day_number', $request->day_number)
                              ->first();
            if ($existingDay) {
                return response()->json([
                    'success' => false,
                    'message' => 'Day number already exists in this week',
                    'errors' => ['day_number' => ['Day number already exists']]
                ], 422);
            }

            DB::beginTransaction();

            // Load original day with nested relations
            $originalDay = Day::with(['circuits.programExercises.exerciseSets'])->find($day->id);

            // Create the new day
            $newDay = Day::create([
                'week_id' => $originalDay->week_id,
                'day_number' => $request->day_number,
                'title' => $request->title ?: $originalDay->title,
                'description' => $request->description ?: $originalDay->description,
                'cool_down' => $request->cool_down ?: $originalDay->cool_down,
                'custom_rows' => $request->has('custom_rows') ? $request->custom_rows : $originalDay->custom_rows,
            ]);

            // Duplicate circuits
            foreach ($originalDay->circuits as $originalCircuit) {
                $newCircuit = Circuit::create([
                    'day_id' => $newDay->id,
                    'circuit_number' => $originalCircuit->circuit_number,
                    'title' => $originalCircuit->title,
                    'description' => $originalCircuit->description,
                ]);

                foreach ($originalCircuit->programExercises as $originalExercise) {
                    $newExercise = ProgramExercise::create([
                        'circuit_id' => $newCircuit->id,
                        'workout_id' => $originalExercise->workout_id,
                        'name' => $originalExercise->name,
                        'order' => $originalExercise->order,
                        'tempo' => $originalExercise->tempo,
                        'rest_interval' => $originalExercise->rest_interval,
                        'notes' => $originalExercise->notes,
                    ]);

                    foreach ($originalExercise->exerciseSets as $originalSet) {
                        ExerciseSet::create([
                            'program_exercise_id' => $newExercise->id,
                            'set_number' => $originalSet->set_number,
                            'reps' => $originalSet->reps,
                            'weight' => $originalSet->weight,
                        ]);
                    }
                }
            }

            DB::commit();

            // Load the duplicated day with relations for UI
            $newDayLoaded = Day::with(['circuits.programExercises.exerciseSets'])->find($newDay->id);

            return response()->json([
                'success' => true,
                'message' => 'Day duplicated successfully',
                'day' => $newDayLoaded,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramBuilderController@duplicateDay: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while duplicating the day'
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
        $this->ensureTrainerOwnsModel($day);
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
        $this->ensureTrainerOwnsModel($day);
        // Allow partial updates during autosave. Only validate fields that are present.
        $request->validate([
            'day_number' => 'sometimes|integer|min:1',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cool_down' => 'nullable|string',
            'custom_rows' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            // Build update payload only with keys explicitly present in the request.
            // This prevents unintended clearing of fields (e.g., cool_down) during autosave.
            $payload = [];
            if ($request->exists('day_number')) {
                $payload['day_number'] = $request->day_number;
            }
            if ($request->exists('title')) {
                $payload['title'] = $request->title;
            }
            if ($request->exists('description')) {
                $payload['description'] = $request->description;
            }
            if ($request->exists('cool_down')) {
                $payload['cool_down'] = $request->cool_down; // may be null to clear
            }
            if ($request->exists('custom_rows')) {
                $payload['custom_rows'] = $request->custom_rows; // array of strings or null
            }

            if (!empty($payload)) {
                $day->update($payload);
            }

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
        $this->ensureTrainerOwnsModel($circuit);
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
        $this->ensureTrainerOwnsModel($circuit);
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
        $this->ensureTrainerOwnsModel($exercise);
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
        $this->ensureTrainerOwnsModel($exercise);
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
        $this->ensureTrainerOwnsModel($programExercise);
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
        $this->ensureTrainerOwnsModel($exercise);
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
                $weightKg = isset($setData['weight']) && $setData['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$setData['weight']) : null;
                ExerciseSet::create([
                    'program_exercise_id' => $exercise->id,
                    'set_number' => $setData['set_number'],
                    'reps' => $setData['reps'] ?? null,
                    'weight' => $weightKg
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
        $this->ensureTrainerOwnsModel($program);
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
        $this->ensureTrainerOwnsModel($week);
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
        $this->ensureTrainerOwnsModel($day);
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
        $this->ensureTrainerOwnsModel($circuit);
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