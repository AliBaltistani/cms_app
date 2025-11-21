<?php

namespace App\Http\Controllers\Trainer;

use App\Models\Week;
use App\Models\Day;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DayController extends Controller
{
    public function __construct()
    {
        $this->middleware('trainer');
    }

    public function store(Request $request, Week $week): JsonResponse
    {
        $this->authorizeTrainerForWeek($week);

        $validated = $request->validate([
            'day_number' => 'required|integer|min:1',
            'title' => 'nullable|string'
        ]);

        $day = $week->days()->create([
            'day_number' => $validated['day_number'],
            'title' => $validated['title'] ?? 'Day ' . $validated['day_number']
        ]);

        return response()->json(['day' => $day]);
    }

    public function show(Day $day): JsonResponse
    {
        $this->authorizeTrainerForDay($day);
        return response()->json(['day' => $day->load(['circuits.exercises.sets', 'circuits.exercises.workout'])]);
    }

    public function update(Request $request, Day $day): JsonResponse
    {
        $this->authorizeTrainerForDay($day);

        $validated = $request->validate([
            'day_number' => 'nullable|integer|min:1',
            'title' => 'nullable|string',
            'cool_down' => 'nullable|string',
            'custom_rows' => 'nullable|array'
        ]);

        $day->update([
            'day_number' => $validated['day_number'] ?? $day->day_number,
            'title' => $validated['title'] ?? $day->title,
            'cool_down' => $validated['cool_down'] ?? $day->cool_down,
            'custom_rows' => isset($validated['custom_rows']) ? json_encode($validated['custom_rows']) : $day->custom_rows
        ]);

        return response()->json(['success' => true, 'day' => $day]);
    }

    public function destroy(Day $day): JsonResponse
    {
        $this->authorizeTrainerForDay($day);
        $day->delete();
        return response()->json(['success' => true, 'message' => 'Day deleted']);
    }

    public function duplicate(Request $request, Day $day): JsonResponse
    {
        $this->authorizeTrainerForDay($day);

        $validated = $request->validate(['day_number' => 'required|integer']);

        $newDay = $day->replicate();
        $newDay->day_number = $validated['day_number'];
        $newDay->save();

        // Duplicate circuits and exercises
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

        return response()->json(['day' => $newDay->load(['circuits.exercises.sets'])]);
    }

    private function authorizeTrainerForWeek(Week $week): void
    {
        $program = $week->program;
        if ($program->trainer_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }

    private function authorizeTrainerForDay(Day $day): void
    {
        $program = $day->week->program;
        if ($program->trainer_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }
}
