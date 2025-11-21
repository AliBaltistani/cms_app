<?php

namespace App\Http\Controllers\Trainer;

use App\Models\Circuit;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExerciseController extends Controller
{
    public function __construct()
    {
        $this->middleware('trainer');
    }

    public function store(Request $request, Circuit $circuit): JsonResponse
    {
        $this->authorizeTrainerForCircuit($circuit);

        $validated = $request->validate([
            'name' => 'nullable|string',
            'workout_id' => 'nullable|integer',
            'order' => 'nullable|integer',
            'notes' => 'nullable|string',
            'sets' => 'nullable|array'
        ]);

        $exercise = $circuit->exercises()->create([
            'name' => $validated['name'],
            'workout_id' => $validated['workout_id'],
            'order' => $validated['order'] ?? 0,
            'notes' => $validated['notes']
        ]);

        // Add exercise sets
        if (!empty($validated['sets'])) {
            foreach ($validated['sets'] as $set) {
                $exercise->sets()->create([
                    'set_number' => $set['set_number'] ?? 1,
                    'reps' => $set['reps'] ?? null,
                    'weight' => $set['weight'] ?? null
                ]);
            }
        }

        return response()->json(['program_exercise' => $exercise]);
    }

    public function show(Exercise $exercise): JsonResponse
    {
        $this->authorizeTrainerForExercise($exercise);
        return response()->json(['exercise' => $exercise->load(['sets', 'workout'])]);
    }

    public function update(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorizeTrainerForExercise($exercise);

        $validated = $request->validate([
            'name' => 'nullable|string',
            'notes' => 'nullable|string',
            'order' => 'nullable|integer',
            'sets' => 'nullable|array'
        ]);

        $exercise->update([
            'name' => $validated['name'] ?? $exercise->name,
            'notes' => $validated['notes'] ?? $exercise->notes,
            'order' => $validated['order'] ?? $exercise->order
        ]);

        // Update sets if provided
        if (!empty($validated['sets'])) {
            $exercise->sets()->delete();
            foreach ($validated['sets'] as $set) {
                $exercise->sets()->create([
                    'set_number' => $set['set_number'] ?? 1,
                    'reps' => $set['reps'] ?? null,
                    'weight' => $set['weight'] ?? null
                ]);
            }
        }

        return response()->json(['success' => true, 'exercise' => $exercise]);
    }

    public function updateWorkout(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorizeTrainerForExercise($exercise);

        $validated = $request->validate([
            'workout_id' => 'nullable|integer'
        ]);

        $exercise->update(['workout_id' => $validated['workout_id']]);

        return response()->json(['success' => true, 'exercise' => $exercise]);
    }

    public function destroy(Exercise $exercise): JsonResponse
    {
        $this->authorizeTrainerForExercise($exercise);
        $exercise->delete();
        return response()->json(['success' => true, 'message' => 'Exercise deleted']);
    }

    public function reorder(Request $request, Circuit $circuit): JsonResponse
    {
        $this->authorizeTrainerForCircuit($circuit);

        $validated = $request->validate([
            'exercises' => 'required|array'
        ]);

        foreach ($validated['exercises'] as $item) {
            Exercise::where('id', $item['id'])
                ->where('circuit_id', $circuit->id)
                ->update(['order' => $item['order']]);
        }

        return response()->json(['success' => true, 'message' => 'Exercise order updated']);
    }

    private function authorizeTrainerForCircuit(Circuit $circuit): void
    {
        $program = $circuit->day->week->program;
        if ($program->trainer_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }

    private function authorizeTrainerForExercise(Exercise $exercise): void
    {
        $program = $exercise->circuit->day->week->program;
        if ($program->trainer_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }
}
