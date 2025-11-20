<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
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
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TrainerProgramBuilderController extends ApiBaseController
{
    private function ownsProgram(Program $program): bool
    {
        return $program->trainer_id === Auth::id();
    }

    private function ownsWeek(Week $week): bool
    {
        $week->loadMissing('program');
        return $week->program && $week->program->trainer_id === Auth::id();
    }

    private function ownsDay(Day $day): bool
    {
        $day->loadMissing('week.program');
        return $day->week && $day->week->program && $day->week->program->trainer_id === Auth::id();
    }

    private function ownsCircuit(Circuit $circuit): bool
    {
        $circuit->loadMissing('day.week.program');
        return $circuit->day && $circuit->day->week && $circuit->day->week->program && $circuit->day->week->program->trainer_id === Auth::id();
    }

    private function ownsExercise(ProgramExercise $exercise): bool
    {
        $exercise->loadMissing('circuit.day.week.program');
        return $exercise->circuit && $exercise->circuit->day && $exercise->circuit->day->week && $exercise->circuit->day->week->program && $exercise->circuit->day->week->program->trainer_id === Auth::id();
    }

    public function getColumnConfig(Program $program): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $config = ProgramColumnConfig::where('program_id', $program->id)->first();
            if (!$config) {
                $config = ProgramColumnConfig::create([
                    'program_id' => $program->id,
                    'columns' => [
                        [ 'id' => 'exercise', 'name' => 'Exercise', 'width' => '25%', 'type' => 'text', 'required' => true ],
                        [ 'id' => 'set1',    'name' => 'Set 1 - rep / w',  'width' => '12%', 'type' => 'text', 'required' => false ],
                        [ 'id' => 'set2',    'name' => 'Set 2 - rep / w',  'width' => '12%', 'type' => 'text', 'required' => false ],
                        [ 'id' => 'set3',    'name' => 'Set 3 - rep / w',  'width' => '12%', 'type' => 'text', 'required' => false ],
                        [ 'id' => 'set4',    'name' => 'Set 4 - reps / w', 'width' => '12%', 'type' => 'text', 'required' => false ],
                        [ 'id' => 'set5',    'name' => 'Set 5 - reps / w', 'width' => '12%', 'type' => 'text', 'required' => false ],
                        [ 'id' => 'notes',   'name' => 'Notes',            'width' => '15%', 'type' => 'text', 'required' => false ],
                    ],
                ]);
            }
            return $this->sendResponse(['columns' => $config->columns], 'Column configuration loaded');
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@getColumnConfig failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(), 'program_id' => $program->id
            ]);
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to load configuration'], 500);
        }
    }

    public function updateColumnConfig(Request $request, Program $program): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'columns' => 'required|array|min:1',
                'columns.*.id' => 'required|string|max:100',
                'columns.*.name' => 'required|string|max:255',
                'columns.*.width' => 'required|string|max:10',
                'columns.*.type' => 'required|string|in:text,number',
                'columns.*.required' => 'required|boolean',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $config = ProgramColumnConfig::updateOrCreate(
                ['program_id' => $program->id],
                ['columns' => $request->input('columns')]
            );
            return $this->sendResponse(['columns' => $config->columns], 'Column configuration updated');
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@updateColumnConfig failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(), 'program_id' => $program->id
            ]);
            return $this->sendError('Update Failed', ['error' => 'Unable to update configuration'], 500);
        }
    }

    public function addWeek(Request $request, Program $program): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'week_number' => 'required|integer|min:1',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $validated = $validator->validated();
            $exists = Week::where('program_id', $program->id)
                ->where('week_number', $validated['week_number'])
                ->exists();
            if ($exists) {
                return $this->sendError('Validation Error', [
                    'week_number' => ['Week number already exists for this program']
                ], 422);
            }
            $week = Week::create([
                'program_id' => $program->id,
                'week_number' => $validated['week_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);
            return $this->sendResponse(['week' => $week], 'Week added successfully', 201);
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@addWeek failed: ' . $e->getMessage());
            if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'weeks_program_id_week_number_unique')) {
                return $this->sendError('Validation Error', [
                    'week_number' => ['Week number already exists for this program']
                ], 422);
            }
            return $this->sendError('Creation Failed', ['error' => 'Unable to add week'], 500);
        }
    }

    public function removeWeek(Request $request): JsonResponse
    {
        try {
            $week = Week::findOrFail($request->input('id'));
            if (!$this->ownsWeek($week)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            DB::beginTransaction();
            $deletedWeekNumber = $week->week_number;
            $programId = $week->program_id;
            
            // Delete the week
            $week->delete();
            
            // Reorder remaining weeks - shift all weeks after the deleted one down by 1
            Week::where('program_id', $programId)
                ->where('week_number', '>', $deletedWeekNumber)
                ->decrement('week_number');
            
            DB::commit();
            return $this->sendResponse(['deleted' => true], 'Week removed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@removeWeek failed: ' . $e->getMessage());
            return $this->sendError('Deletion Failed', ['error' => 'Unable to remove week'], 500);
        }
    }

    public function updateWeek(Request $request): JsonResponse
    {
        try {
            $week = Week::findOrFail($request->input('id'));
            if (!$this->ownsWeek($week)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'week_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string'
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $validated = $validator->validated();
            $newNumber = $validated['week_number'];
            if ($newNumber != $week->week_number) {
                $exists = Week::where('program_id', $week->program_id)
                    ->where('week_number', $newNumber)
                    ->where('id', '!=', $week->id)
                    ->exists();
                if ($exists) {
                    return $this->sendError('Validation Error', [
                        'week_number' => ['Week number already exists for this program']
                    ], 422);
                }
            }
            DB::beginTransaction();
            $week->update([
                'week_number' => $validated['week_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);
            DB::commit();
            return $this->sendResponse(['week' => $week], 'Week updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@updateWeek failed: ' . $e->getMessage());
            if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'weeks_program_id_week_number_unique')) {
                return $this->sendError('Validation Error', [
                    'week_number' => ['Week number already exists for this program']
                ], 422);
            }
            return $this->sendError('Update Failed', ['error' => 'Unable to update week'.$e->getMessage()], 500);
        }
    }

    public function duplicateWeek(Request $request): JsonResponse
    {
        try {
            $week = Week::findOrFail($request->input('id'));
            if (!$this->ownsWeek($week)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'week_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string'
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $existing = Week::where('program_id', $week->program_id)->where('week_number', $request->week_number)->first();
            if ($existing) {
                return $this->sendError('Validation Error', ['week_number' => ['Week number already exists']], 422);
            }
            DB::beginTransaction();
            $original = Week::with(['days.circuits.programExercises.exerciseSets'])->find($week->id);
            $newWeek = Week::create([
                'program_id' => $original->program_id,
                'week_number' => $request->week_number,
                'title' => $request->title ?: $original->title,
                'description' => $request->description ?: $original->description,
            ]);
            foreach ($original->days as $origDay) {
                $newDay = Day::create([
                    'week_id' => $newWeek->id,
                    'day_number' => $origDay->day_number,
                    'title' => $origDay->title,
                    'description' => $origDay->description,
                    'cool_down' => $origDay->cool_down,
                    'custom_rows' => $origDay->custom_rows,
                ]);
                foreach ($origDay->circuits as $origCircuit) {
                    $newCircuit = Circuit::create([
                        'day_id' => $newDay->id,
                        'circuit_number' => $origCircuit->circuit_number,
                        'title' => $origCircuit->title,
                        'description' => $origCircuit->description,
                    ]);
                    foreach ($origCircuit->programExercises as $origEx) {
                        $newEx = ProgramExercise::create([
                            'circuit_id' => $newCircuit->id,
                            'workout_id' => $origEx->workout_id,
                            'name' => $origEx->name,
                            'order' => $origEx->order,
                            'tempo' => $origEx->tempo,
                            'rest_interval' => $origEx->rest_interval,
                            'notes' => $origEx->notes,
                        ]);
                        foreach ($origEx->exerciseSets as $origSet) {
                            ExerciseSet::create([
                                'program_exercise_id' => $newEx->id,
                                'set_number' => $origSet->set_number,
                                'reps' => $origSet->reps,
                                'weight' => $origSet->weight,
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            $newWeekLoaded = Week::with(['days.circuits.programExercises.exerciseSets'])->find($newWeek->id);
            return $this->sendResponse(['week' => $newWeekLoaded], 'Week duplicated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@duplicateWeek failed: ' . $e->getMessage());
            return $this->sendError('Duplication Failed', ['error' => 'Unable to duplicate week'], 500);
        }
    }

    public function addDay(Request $request): JsonResponse
    {
        try {
            $week = Week::findOrFail($request->input('week_id'));
            if (!$this->ownsWeek($week)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'day_number' => 'required|integer|min:1',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cool_down' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $validated = $validator->validated();
            $exists = Day::where('week_id', $week->id)
                ->where('day_number', $validated['day_number'])
                ->exists();
            if ($exists) {
                return $this->sendError('Validation Error', [
                    'day_number' => ['Day number already exists for this week']
                ], 422);
            }
            $day = Day::create([
                'week_id' => $week->id,
                'day_number' => $validated['day_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'cool_down' => $validated['cool_down'] ?? null,
            ]);
            return $this->sendResponse(['day' => $day], 'Day added successfully', 201);
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@addDay failed: ' . $e->getMessage());
            return $this->sendError('Creation Failed', ['error' => 'Unable to add day'], 500);
        }
    }

    public function removeDay(Request $request): JsonResponse
    {
        try {
            $day = Day::findOrFail($request->input('day_id'));
            if (!$this->ownsDay($day)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            DB::beginTransaction();
            $deletedDayNumber = $day->day_number;
            $weekId = $day->week_id;
            
            // Delete the day
            $day->delete();
            
            // Reorder remaining days - shift all days after the deleted one down by 1
            Day::where('week_id', $weekId)
                ->where('day_number', '>', $deletedDayNumber)
                ->decrement('day_number');
            
            DB::commit();
            return $this->sendResponse(['deleted' => true], 'Day removed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@removeDay failed: ' . $e->getMessage());
            return $this->sendError('Deletion Failed', ['error' => 'Unable to remove day'], 500);
        }
    }

    public function duplicateDay(Request $request, Day $day): JsonResponse
    {
        try {
            if (!$this->ownsDay($day)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'day_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'cool_down' => 'nullable|string',
                'custom_rows' => 'nullable|array',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $existingDay = Day::where('week_id', $day->week_id)->where('day_number', $request->day_number)->first();
            if ($existingDay) {
                return $this->sendError('Validation Error', ['day_number' => ['Day number already exists']], 422);
            }
            DB::beginTransaction();
            $originalDay = Day::with(['circuits.programExercises.exerciseSets'])->find($day->id);
            $newDay = Day::create([
                'week_id' => $originalDay->week_id,
                'day_number' => $request->day_number,
                'title' => $request->title ?: $originalDay->title,
                'description' => $request->description ?: $originalDay->description,
                'cool_down' => $request->cool_down ?: $originalDay->cool_down,
                'custom_rows' => $request->has('custom_rows') ? $request->custom_rows : $originalDay->custom_rows,
            ]);
            foreach ($originalDay->circuits as $origCircuit) {
                $newCircuit = Circuit::create([
                    'day_id' => $newDay->id,
                    'circuit_number' => $origCircuit->circuit_number,
                    'title' => $origCircuit->title,
                    'description' => $origCircuit->description,
                ]);
                foreach ($origCircuit->programExercises as $origEx) {
                    $newEx = ProgramExercise::create([
                        'circuit_id' => $newCircuit->id,
                        'workout_id' => $origEx->workout_id,
                        'name' => $origEx->name,
                        'order' => $origEx->order,
                        'tempo' => $origEx->tempo,
                        'rest_interval' => $origEx->rest_interval,
                        'notes' => $origEx->notes,
                    ]);
                    foreach ($origEx->exerciseSets as $origSet) {
                        ExerciseSet::create([
                            'program_exercise_id' => $newEx->id,
                            'set_number' => $origSet->set_number,
                            'reps' => $origSet->reps,
                            'weight' => $origSet->weight,
                        ]);
                    }
                }
            }
            DB::commit();
            $newDayLoaded = Day::with(['circuits.programExercises.exerciseSets'])->find($newDay->id);
            return $this->sendResponse(['day' => $newDayLoaded], 'Day duplicated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@duplicateDay failed: ' . $e->getMessage());
            return $this->sendError('Duplication Failed', ['error' => 'Unable to duplicate day'], 500);
        }
    }

    public function updateDay(Request $request): JsonResponse
    {
        try {
            $day = Day::findOrFail($request->input('day_id'));
            if (!$this->ownsDay($day)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'day_number' => 'sometimes|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'cool_down' => 'nullable|string',
                'custom_rows' => 'nullable|array',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            if ($request->exists('day_number')) {
                $newNumber = (int) $request->day_number;
                if ($newNumber != $day->day_number) {
                    $exists = Day::where('week_id', $day->week_id)
                        ->where('day_number', $newNumber)
                        ->where('id', '!=', $day->id)
                        ->exists();
                    if ($exists) {
                        return $this->sendError('Validation Error', [
                            'day_number' => ['Day number already exists for this week']
                        ], 422);
                    }
                }
            }
            DB::beginTransaction();
            $payload = [];
            if ($request->exists('day_number')) { $payload['day_number'] = $request->day_number; }
            if ($request->exists('title')) { $payload['title'] = $request->title; }
            if ($request->exists('description')) { $payload['description'] = $request->description; }
            if ($request->exists('cool_down')) { $payload['cool_down'] = $request->cool_down; }
            if ($request->exists('custom_rows')) { $payload['custom_rows'] = $request->custom_rows; }
            if (!empty($payload)) { $day->update($payload); }
            DB::commit();
            return $this->sendResponse(['day' => $day], 'Day updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@updateDay failed: ' . $e->getMessage());
            if ($e instanceof \Illuminate\Database\QueryException) {
                return $this->sendError('Validation Error', [
                    'day_number' => ['Day number already exists for this week']
                ], 422);
            }
            return $this->sendError('Update Failed', ['error' => 'Unable to update day'], 500);
        }
    }

    public function addCircuit(Request $request): JsonResponse
    {
        try {
            $day = Day::findOrFail($request->input('day_id'));
            if (!$this->ownsDay($day)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'circuit_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $validated = $validator->validated();
            $exists = Circuit::where('day_id', $day->id)
                ->where('circuit_number', $validated['circuit_number'])
                ->exists();
            if ($exists) {
                return $this->sendError('Validation Error', [
                    'circuit_number' => ['Circuit number already exists for this day']
                ], 422);
            }
            $circuit = Circuit::create([
                'day_id' => $day->id,
                'circuit_number' => $validated['circuit_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);
            return $this->sendResponse(['circuit' => $circuit], 'Circuit added successfully', 201);
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@addCircuit failed: ' . $e->getMessage());
            if ($e instanceof \Illuminate\Database\QueryException) {
                return $this->sendError('Validation Error', [
                    'circuit_number' => ['Circuit number already exists for this day']
                ], 422);
            }
            return $this->sendError('Creation Failed', ['error' => 'Unable to add circuit'], 500);
        }
    }

    public function removeCircuit(Request $request): JsonResponse
    {
        try {
            $circuit = Circuit::findOrFail($request->input('circuit_id'));
            if (!$this->ownsCircuit($circuit)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            DB::beginTransaction();
            $deletedCircuitNumber = $circuit->circuit_number;
            $dayId = $circuit->day_id;
            
            // Delete the circuit
            $circuit->delete();
            
            // Reorder remaining circuits - shift all circuits after the deleted one down by 1
            Circuit::where('day_id', $dayId)
                ->where('circuit_number', '>', $deletedCircuitNumber)
                ->decrement('circuit_number');
            
            DB::commit();
            return $this->sendResponse(['deleted' => true], 'Circuit removed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@removeCircuit failed: ' . $e->getMessage());
            return $this->sendError('Deletion Failed', ['error' => 'Unable to remove circuit'], 500);
        }
    }

    public function updateCircuit(Request $request): JsonResponse
    {
        try {
              $circuit = Circuit::findOrFail($request->input('circuit_id'));
            if (!$this->ownsCircuit($circuit)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'circuit_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $validated = $validator->validated();
            $newNumber = $validated['circuit_number'];
            if ($newNumber != $circuit->circuit_number) {
                $exists = Circuit::where('day_id', $circuit->day_id)
                    ->where('circuit_number', $newNumber)
                    ->where('id', '!=', $circuit->id)
                    ->exists();
                if ($exists) {
                    return $this->sendError('Validation Error', [
                        'circuit_number' => ['Circuit number already exists for this day']
                    ], 422);
                }
            }
            DB::beginTransaction();
            $circuit->update([
                'circuit_number' => $validated['circuit_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);
            DB::commit();
            return $this->sendResponse(['circuit' => $circuit], 'Circuit updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@updateCircuit failed: ' . $e->getMessage());
            if ($e instanceof \Illuminate\Database\QueryException) {
                return $this->sendError('Validation Error', [
                    'circuit_number' => ['Circuit number already exists for this day']
                ], 422);
            }
            return $this->sendError('Update Failed', ['error' => 'Unable to update circuit'], 500);
        }
    }

    public function reorderWeeks(Request $request, Program $program): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'weeks' => 'required|array',
                'weeks.*.id' => 'required|exists:weeks,id',
                'weeks.*.week_number' => 'required|integer|min:1',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            DB::beginTransaction();
            foreach ($request->weeks as $w) {
                Week::where('id', $w['id'])->where('program_id', $program->id)->update(['week_number' => $w['week_number']]);
            }
            DB::commit();
            return $this->sendResponse(['success' => true], 'Weeks reordered successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@reorderWeeks failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to reorder weeks'], 500);
        }
    }

    public function reorderDays(Request $request): JsonResponse
    {
        try {
            $week = Week::findOrFail($request->input('week_id'));
            if (!$this->ownsWeek($week)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'days' => 'required|array',
                'days.*.id' => 'required|exists:days,id',
                'days.*.day_number' => 'required|integer|min:1',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            DB::beginTransaction();
            foreach ($request->days as $d) {
                Day::where('id', $d['id'])->where('week_id', $week->id)->update(['day_number' => $d['day_number']]);
            }
            DB::commit();
            return $this->sendResponse(['success' => true], 'Days reordered successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@reorderDays failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to reorder days'], 500);
        }
    }

    public function reorderCircuits(Request $request): JsonResponse
    {
        try {
            $day = Day::findOrFail($request->input('day_id'));
            if (!$this->ownsDay($day)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'circuits' => 'required|array',
                'circuits.*.id' => 'required|exists:circuits,id',
                'circuits.*.circuit_number' => 'required|integer|min:1',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            DB::beginTransaction();
            foreach ($request->circuits as $c) {
                Circuit::where('id', $c['id'])->where('day_id', $day->id)->update(['circuit_number' => $c['circuit_number']]);
            }
            DB::commit();
            return $this->sendResponse(['success' => true], 'Circuits reordered successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@reorderCircuits failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to reorder circuits'], 500);
        }
    }

    public function addExercise(Request $request): JsonResponse
    {
        try {
            $circuit = Circuit::findOrFail($request->input('circuit_id'));
            if (!$this->ownsCircuit($circuit)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'program_id' => 'nullable|integer|exists:programs,id',
                'name' => 'required|string|max:255',
                'order' => 'required|integer|min:0',
                'tempo' => 'nullable|string|max:255',
                'rest_interval' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'sets' => 'required|array|min:1',
                'sets.*.set_number' => 'required|integer|min:1',
                'sets.*.reps' => 'required|integer|min:0',
                'sets.*.weight' => 'nullable|numeric|min:0',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $validated = $validator->validated();
            
            // Validate that set_numbers are unique
            $setNumbers = collect($validated['sets'])->pluck('set_number')->toArray();
            if (count($setNumbers) !== count(array_unique($setNumbers))) {
                return $this->sendError('Validation Error', ['sets' => ['Duplicate set numbers are not allowed'. implode(', ', $setNumbers)]], 422);
            }
            DB::beginTransaction();
            $exercise = ProgramExercise::create([
                'circuit_id' => $circuit->id,
                'workout_id' => $validated['program_id'] ?? null,
                'name' => $validated['name'] ?? null,
                'order' => $validated['order'],
                'tempo' => $validated['tempo'] ?? null,
                'rest_interval' => $validated['rest_interval'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            foreach ($validated['sets'] as $setData) {
                $weightKg = isset($setData['weight']) && $setData['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$setData['weight']) : null;
                ExerciseSet::create([
                    'program_exercise_id' => $exercise->id,
                    'set_number' => $setData['set_number'],
                    'reps' => $setData['reps'],
                    'weight' => $weightKg,
                ]);
            }
            DB::commit();
            $exercise->load(['workout', 'exerciseSets']);
            return $this->sendResponse(['program_exercise' => $exercise], 'Exercise added successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@addExercise failed: ' . $e->getMessage());
            return $this->sendError('Creation Failed', ['error' => 'Unable to add exercise'.$e->getMessage()], 500);
        }
    }


    public function updateExercise(Request $request): JsonResponse
    {
        try {
            $programExercise = ProgramExercise::findOrFail($request->input('program_exercise_id'));
            if (!$this->ownsExercise($programExercise)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'tempo' => 'nullable|string|max:255',
                'rest_interval' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'sets' => 'required|array|min:1',
                'sets.*.set_number' => 'required|integer|min:1',
                'sets.*.reps' => 'required|integer|min:0',
                'sets.*.weight' => 'nullable|numeric|min:0',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $validated = $validator->validated();
            
            // Validate that set_numbers are unique
            $setNumbers = collect($validated['sets'])->pluck('set_number')->toArray();
            if (count($setNumbers) !== count(array_unique($setNumbers))) {
                return $this->sendError('Validation Error', ['sets' => ['Duplicate set numbers are not allowed']], 422);
            }
            DB::beginTransaction();
            $programExercise->update([
                'name' => $validated['name'] ?? $programExercise->name,
                'tempo' => $validated['tempo'] ?? null,
                'rest_interval' => $validated['rest_interval'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            $programExercise->exerciseSets()->delete();
            foreach ($validated['sets'] as $setData) {
                $weightKg = isset($setData['weight']) && $setData['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$setData['weight']) : null;
                ExerciseSet::create([
                    'program_exercise_id' => $programExercise->id,
                    'set_number' => $setData['set_number'],
                    'reps' => $setData['reps'],
                    'weight' => $weightKg,
                ]);
            }
            DB::commit();
            $programExercise->load(['workout', 'exerciseSets']);
            return $this->sendResponse(['program_exercise' => $programExercise], 'Exercise updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@updateExercise failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to update exercise'], 500);
        }
    }

    public function removeExercise(Request $request): JsonResponse
    {
        try {
            $programExercise = ProgramExercise::findOrFail($request->input('program_exercise_id'));
            if (!$this->ownsExercise($programExercise)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            DB::beginTransaction();
            $deletedOrder = $programExercise->order;
            $circuitId = $programExercise->circuit_id;
            
            // Delete the exercise
            $programExercise->delete();
            
            // Reorder remaining exercises - shift all exercises after the deleted one down by 1
            ProgramExercise::where('circuit_id', $circuitId)
                ->where('order', '>', $deletedOrder)
                ->decrement('order');
            
            DB::commit();
            return $this->sendResponse(['deleted' => true], 'Exercise removed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@removeExercise failed: ' . $e->getMessage());
            return $this->sendError('Deletion Failed', ['error' => 'Unable to remove exercise'], 500);
        }
    }

    public function getExerciseSets(Request $request): JsonResponse
    {
        try {
            $exercise = ProgramExercise::findOrFail($request->input('program_exercise_id'));
            if (!$this->ownsExercise($exercise)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $exercise->load(['workout', 'exerciseSets']);
            return $this->sendResponse(['exercise' => $exercise], 'Exercise sets loaded');
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@getExerciseSets failed: ' . $e->getMessage());
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to load exercise sets'], 500);
        }
    }

    public function reorderExercises(Request $request, Circuit $circuit): JsonResponse
    {
        try {
            if (!$this->ownsCircuit($circuit)) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'exercises' => 'required|array',
                'exercises.*.id' => 'required|exists:program_exercises,id',
                'exercises.*.order' => 'required|integer|min:0',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            DB::beginTransaction();
            foreach ($request->exercises as $ex) {
                ProgramExercise::where('id', $ex['id'])->where('circuit_id', $circuit->id)->update(['order' => $ex['order']]);
            }
            DB::commit();
            return $this->sendResponse(['success' => true], 'Exercises reordered successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@reorderExercises failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to reorder exercises'], 500);
        }
    }

    /**
     * =========================================================================
     * NESTED RESTful CRUD ROUTES
     * Dynamic routes following pattern: /programs/{program}/builder/weeks/{week}/days/{day}/...
     * =========================================================================
     */

    // =====================================================================
    // WEEK CRUD - Nested under Program
    // =====================================================================
    public function storeWeek(Request $request, Program $program): JsonResponse
    {
        return $this->addWeek($request, $program);
    }

    public function showWeek(Program $program, Week $week): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $week->load(['days.circuits.programExercises.exerciseSets', 'days.circuits.programExercises.workout']);
            return $this->sendResponse(['week' => $week], 'Week retrieved successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@showWeek failed: ' . $e->getMessage());
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve week'], 500);
        }
    }

    public function putWeek(Request $request, Program $program, Week $week): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $validator = Validator::make($request->all(), [
                'week_number' => 'sometimes|integer|min:1',
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string'
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $validated = $validator->validated();
            if (isset($validated['week_number']) && $validated['week_number'] != $week->week_number) {
                $exists = Week::where('program_id', $program->id)
                    ->where('week_number', $validated['week_number'])
                    ->where('id', '!=', $week->id)
                    ->exists();
                if ($exists) {
                    return $this->sendError('Validation Error', [
                        'week_number' => ['Week number already exists for this program']
                    ], 422);
                }
            }
            
            DB::beginTransaction();
            $week->update($validated);
            DB::commit();
            return $this->sendResponse(['week' => $week], 'Week updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@putWeek failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to update week'], 500);
        }
    }

    public function destroyWeek(Program $program, Week $week): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            DB::beginTransaction();
            $deletedWeekNumber = $week->week_number;
            $programId = $week->program_id;
            
            // Delete the week
            $week->delete();
            
            // Reorder remaining weeks - shift all weeks after the deleted one down by 1
            Week::where('program_id', $programId)
                ->where('week_number', '>', $deletedWeekNumber)
                ->decrement('week_number');
            
            DB::commit();
            return $this->sendResponse(['deleted' => true], 'Week deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@destroyWeek failed: ' . $e->getMessage());
            return $this->sendError('Deletion Failed', ['error' => 'Unable to delete week'], 500);
        }
    }

    // =====================================================================
    // DAY CRUD - Nested under Week
    // =====================================================================
    public function storeDay(Request $request, Program $program, Week $week): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'day_number' => 'required|integer|min:1',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cool_down' => 'nullable|string',
                'custom_rows' => 'nullable|array',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $validated = $validator->validated();
            $exists = Day::where('week_id', $week->id)
                ->where('day_number', $validated['day_number'])
                ->exists();
            if ($exists) {
                return $this->sendError('Validation Error', [
                    'day_number' => ['Day number already exists for this week']
                ], 422);
            }
            
            DB::beginTransaction();
            $day = Day::create([
                'week_id' => $week->id,
                'day_number' => $validated['day_number'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'cool_down' => $validated['cool_down'] ?? null,
                'custom_rows' => $validated['custom_rows'] ?? null,
            ]);
            DB::commit();
            return $this->sendResponse(['day' => $day], 'Day created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@storeDay failed: ' . $e->getMessage());
            return $this->sendError('Creation Failed', ['error' => 'Unable to create day'], 500);
        }
    }

    public function showDay(Program $program, Week $week, Day $day): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $day->load(['circuits.programExercises.exerciseSets', 'circuits.programExercises.workout']);
            return $this->sendResponse(['day' => $day], 'Day retrieved successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@showDay failed: ' . $e->getMessage());
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve day'], 500);
        }
    }

    public function putDay(Request $request, Program $program, Week $week, Day $day): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'day_number' => 'sometimes|integer|min:1',
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'cool_down' => 'nullable|string',
                'custom_rows' => 'nullable|array',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $validated = $validator->validated();
            if (isset($validated['day_number']) && $validated['day_number'] != $day->day_number) {
                $exists = Day::where('week_id', $week->id)
                    ->where('day_number', $validated['day_number'])
                    ->where('id', '!=', $day->id)
                    ->exists();
                if ($exists) {
                    return $this->sendError('Validation Error', [
                        'day_number' => ['Day number already exists for this week']
                    ], 422);
                }
            }
            
            DB::beginTransaction();
            $day->update($validated);
            DB::commit();
            return $this->sendResponse(['day' => $day], 'Day updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@putDay failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to update day'], 500);
        }
    }

    public function destroyDay(Program $program, Week $week, Day $day): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            DB::beginTransaction();
            $deletedDayNumber = $day->day_number;
            $weekId = $day->week_id;
            
            // Delete the day
            $day->delete();
            
            // Reorder remaining days - shift all days after the deleted one down by 1
            Day::where('week_id', $weekId)
                ->where('day_number', '>', $deletedDayNumber)
                ->decrement('day_number');
            
            DB::commit();
            return $this->sendResponse(['deleted' => true], 'Day deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@destroyDay failed: ' . $e->getMessage());
            return $this->sendError('Deletion Failed', ['error' => 'Unable to delete day'], 500);
        }
    }

    // =====================================================================
    // CIRCUIT CRUD - Nested under Day
    // =====================================================================
    public function storeCircuit(Request $request, Program $program, Week $week, Day $day): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'circuit_number' => 'required|integer|min:1',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $validated = $validator->validated();
            $exists = Circuit::where('day_id', $day->id)
                ->where('circuit_number', $validated['circuit_number'])
                ->exists();
            if ($exists) {
                return $this->sendError('Validation Error', [
                    'circuit_number' => ['Circuit number already exists for this day']
                ], 422);
            }
            
            DB::beginTransaction();
            $circuit = Circuit::create([
                'day_id' => $day->id,
                'circuit_number' => $validated['circuit_number'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);
            DB::commit();
            return $this->sendResponse(['circuit' => $circuit], 'Circuit created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@storeCircuit failed: ' . $e->getMessage());
            return $this->sendError('Creation Failed', ['error' => 'Unable to create circuit'], 500);
        }
    }

    public function showCircuit(Program $program, Week $week, Day $day, Circuit $circuit): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id || $circuit->day_id !== $day->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $circuit->load(['programExercises.exerciseSets', 'programExercises.workout']);
            return $this->sendResponse(['circuit' => $circuit], 'Circuit retrieved successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@showCircuit failed: ' . $e->getMessage());
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve circuit'], 500);
        }
    }

    public function putCircuit(Request $request, Program $program, Week $week, Day $day, Circuit $circuit): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id || $circuit->day_id !== $day->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'circuit_number' => 'sometimes|integer|min:1',
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $validated = $validator->validated();
            if (isset($validated['circuit_number']) && $validated['circuit_number'] != $circuit->circuit_number) {
                $exists = Circuit::where('day_id', $day->id)
                    ->where('circuit_number', $validated['circuit_number'])
                    ->where('id', '!=', $circuit->id)
                    ->exists();
                if ($exists) {
                    return $this->sendError('Validation Error', [
                        'circuit_number' => ['Circuit number already exists for this day']
                    ], 422);
                }
            }
            
            DB::beginTransaction();
            $circuit->update($validated);
            DB::commit();
            return $this->sendResponse(['circuit' => $circuit], 'Circuit updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@putCircuit failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to update circuit'], 500);
        }
    }

    public function destroyCircuit(Program $program, Week $week, Day $day, Circuit $circuit): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id || $circuit->day_id !== $day->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            DB::beginTransaction();
            $deletedCircuitNumber = $circuit->circuit_number;
            $dayId = $circuit->day_id;
            
            // Delete the circuit
            $circuit->delete();
            
            // Reorder remaining circuits - shift all circuits after the deleted one down by 1
            Circuit::where('day_id', $dayId)
                ->where('circuit_number', '>', $deletedCircuitNumber)
                ->decrement('circuit_number');
            
            DB::commit();
            return $this->sendResponse(['deleted' => true], 'Circuit deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@destroyCircuit failed: ' . $e->getMessage());
            return $this->sendError('Deletion Failed', ['error' => 'Unable to delete circuit'], 500);
        }
    }

    // =====================================================================
    // EXERCISE CRUD - Nested under Circuit
    // =====================================================================
    public function storeExercise(Request $request, Program $program, Week $week, Day $day, Circuit $circuit): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id || $circuit->day_id !== $day->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'workout_id' => 'nullable|integer|exists:workouts,id',
                'order' => 'required|integer|min:0',
                'tempo' => 'nullable|string|max:255',
                'rest_interval' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'sets' => 'required|array|min:1',
                'sets.*.set_number' => 'required|integer|min:1',
                'sets.*.reps' => 'nullable|integer|min:0',
                'sets.*.weight' => 'nullable|numeric|min:0',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $validated = $validator->validated();
            $setNumbers = collect($validated['sets'])->pluck('set_number')->toArray();
            if (count($setNumbers) !== count(array_unique($setNumbers))) {
                return $this->sendError('Validation Error', ['sets' => ['Duplicate set numbers are not allowed']], 422);
            }
            
            DB::beginTransaction();
            $exercise = ProgramExercise::create([
                'circuit_id' => $circuit->id,
                'workout_id' => $validated['workout_id'] ?? null,
                'name' => $validated['name'],
                'order' => $validated['order'],
                'tempo' => $validated['tempo'] ?? null,
                'rest_interval' => $validated['rest_interval'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            
            foreach ($validated['sets'] as $setData) {
                $weightKg = isset($setData['weight']) && $setData['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$setData['weight']) : null;
                ExerciseSet::create([
                    'program_exercise_id' => $exercise->id,
                    'set_number' => $setData['set_number'],
                    'reps' => $setData['reps'] ?? null,
                    'weight' => $weightKg,
                ]);
            }
            DB::commit();
            $exercise->load(['workout', 'exerciseSets']);
            return $this->sendResponse(['exercise' => $exercise], 'Exercise created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@storeExercise failed: ' . $e->getMessage());
            return $this->sendError('Creation Failed', ['error' => 'Unable to create exercise'], 500);
        }
    }

    public function showExercise(Program $program, Week $week, Day $day, Circuit $circuit, ProgramExercise $exercise): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id || $circuit->day_id !== $day->id || $exercise->circuit_id !== $circuit->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $exercise->load(['workout', 'exerciseSets']);
            return $this->sendResponse(['exercise' => $exercise], 'Exercise retrieved successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@showExercise failed: ' . $e->getMessage());
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve exercise'], 500);
        }
    }

    public function putExercise(Request $request, Program $program, Week $week, Day $day, Circuit $circuit, ProgramExercise $exercise): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id || $circuit->day_id !== $day->id || $exercise->circuit_id !== $circuit->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'workout_id' => 'sometimes|nullable|integer|exists:workouts,id',
                'order' => 'sometimes|integer|min:0',
                'tempo' => 'sometimes|nullable|string|max:255',
                'rest_interval' => 'sometimes|nullable|string|max:255',
                'notes' => 'sometimes|nullable|string',
                'sets' => 'sometimes|array|min:1',
                'sets.*.set_number' => 'required_with:sets|integer|min:1',
                'sets.*.reps' => 'sometimes|nullable|integer|min:0',
                'sets.*.weight' => 'sometimes|nullable|numeric|min:0',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $validated = $validator->validated();
            
            if (isset($validated['sets'])) {
                $setNumbers = collect($validated['sets'])->pluck('set_number')->toArray();
                if (count($setNumbers) !== count(array_unique($setNumbers))) {
                    return $this->sendError('Validation Error', ['sets' => ['Duplicate set numbers are not allowed']], 422);
                }
            }
            
            DB::beginTransaction();
            $updateData = [];
            if (isset($validated['name'])) $updateData['name'] = $validated['name'];
            if (isset($validated['workout_id'])) $updateData['workout_id'] = $validated['workout_id'];
            if (isset($validated['order'])) $updateData['order'] = $validated['order'];
            if (isset($validated['tempo'])) $updateData['tempo'] = $validated['tempo'];
            if (isset($validated['rest_interval'])) $updateData['rest_interval'] = $validated['rest_interval'];
            if (isset($validated['notes'])) $updateData['notes'] = $validated['notes'];
            
            if (!empty($updateData)) {
                $exercise->update($updateData);
            }
            
            if (isset($validated['sets'])) {
                $exercise->exerciseSets()->delete();
                foreach ($validated['sets'] as $setData) {
                    $weightKg = isset($setData['weight']) && $setData['weight'] !== null ? \App\Support\UnitConverter::lbsToKg((float)$setData['weight']) : null;
                    ExerciseSet::create([
                        'program_exercise_id' => $exercise->id,
                        'set_number' => $setData['set_number'],
                        'reps' => $setData['reps'] ?? null,
                        'weight' => $weightKg,
                    ]);
                }
            }
            
            DB::commit();
            $exercise->load(['workout', 'exerciseSets']);
            return $this->sendResponse(['exercise' => $exercise], 'Exercise updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@putExercise failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to update exercise'], 500);
        }
    }

    public function destroyExercise(Program $program, Week $week, Day $day, Circuit $circuit, ProgramExercise $exercise): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id || $circuit->day_id !== $day->id || $exercise->circuit_id !== $circuit->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            DB::beginTransaction();
            $deletedOrder = $exercise->order;
            $circuitId = $exercise->circuit_id;
            
            // Delete the exercise
            $exercise->delete();
            
            // Reorder remaining exercises - shift all exercises after the deleted one down by 1
            ProgramExercise::where('circuit_id', $circuitId)
                ->where('order', '>', $deletedOrder)
                ->decrement('order');
            
            DB::commit();
            return $this->sendResponse(['deleted' => true], 'Exercise deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramBuilderController@destroyExercise failed: ' . $e->getMessage());
            return $this->sendError('Deletion Failed', ['error' => 'Unable to delete exercise'], 500);
        }
    }

    // =====================================================================
    // DAY SPECIAL FIELDS - cool_down and custom_rows
    // =====================================================================
    public function updateDayCoolDown(Request $request, Program $program, Week $week, Day $day): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'cool_down' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $day->update(['cool_down' => $request->input('cool_down') ?? null]);
            return $this->sendResponse(['day' => $day], 'Cool down updated successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@updateDayCoolDown failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to update cool down'], 500);
        }
    }

    public function updateDayCustomRows(Request $request, Program $program, Week $week, Day $day): JsonResponse
    {
        try {
            if (!$this->ownsProgram($program) || $week->program_id !== $program->id || $day->week_id !== $week->id) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'custom_rows' => 'nullable|array',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            
            $day->update(['custom_rows' => $request->input('custom_rows') ?? null]);
            return $this->sendResponse(['day' => $day], 'Custom rows updated successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramBuilderController@updateDayCustomRows failed: ' . $e->getMessage());
            return $this->sendError('Update Failed', ['error' => 'Unable to update custom rows'], 500);
        }
    }
}