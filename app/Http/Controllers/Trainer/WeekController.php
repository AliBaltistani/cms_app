<?php

namespace App\Http\Controllers\Trainer;

use App\Models\Program;
use App\Models\Week;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class WeekController extends Controller
{
    public function __construct()
    {
        $this->middleware('trainer');
    }

    public function store(Request $request, Program $program): JsonResponse
    {
        $this->authorizeTrainer($program);

        $validated = $request->validate([
            'week_number' => 'required|integer|min:1',
            'title' => 'nullable|string'
        ]);

        $week = $program->weeks()->create([
            'week_number' => $validated['week_number'],
            'title' => $validated['title'] ?? 'Week ' . $validated['week_number']
        ]);

        return response()->json(['week' => $week]);
    }

    public function show(Week $week): JsonResponse
    {
        $this->authorizeTrainerForWeek($week);
        return response()->json(['week' => $week->load('days')]);
    }

    public function update(Request $request, Week $week): JsonResponse
    {
        $this->authorizeTrainerForWeek($week);

        $validated = $request->validate([
            'week_number' => 'nullable|integer|min:1',
            'title' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        $week->update($validated);

        return response()->json(['success' => true, 'week' => $week]);
    }

    public function destroy(Week $week): JsonResponse
    {
        $this->authorizeTrainerForWeek($week);
        $week->delete();
        return response()->json(['success' => true, 'message' => 'Week deleted']);
    }

    public function duplicate(Request $request, Week $week): JsonResponse
    {
        $this->authorizeTrainerForWeek($week);

        $validated = $request->validate(['week_number' => 'required|integer']);

        // Duplicate week with all nested data
        $newWeek = $week->replicate();
        $newWeek->week_number = $validated['week_number'];
        $newWeek->save();

        // Duplicate days and their exercises
        foreach ($week->days as $day) {
            $newDay = $day->replicate();
            $newDay->week_id = $newWeek->id;
            $newDay->save();

            foreach ($day->circuits as $circuit) {
                $newCircuit = $circuit->replicate();
                $newCircuit->day_id = $newDay->id;
                $newCircuit->save();

                foreach ($circuit->exercises as $exercise) {
                    $newExercise = $exercise->replicate();
                    $newExercise->circuit_id = $newCircuit->id;
                    $newExercise->save();

                    foreach ($exercise->sets as $set) {
                        $newSet = $set->replicate();
                        $newSet->exercise_id = $newExercise->id;
                        $newSet->save();
                    }
                }
            }
        }

        return response()->json(['week' => $newWeek->load(['days.circuits.exercises.sets'])]);
    }

    private function authorizeTrainerForWeek(Week $week): void
    {
        $program = $week->program;
        if ($program->trainer_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }
}
