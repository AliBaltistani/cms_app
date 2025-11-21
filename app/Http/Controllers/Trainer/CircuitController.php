<?php

namespace App\Http\Controllers\Trainer;

use App\Models\Day;
use App\Models\Circuit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CircuitController extends Controller
{
    public function __construct()
    {
        $this->middleware('trainer');
    }

    public function store(Request $request, Day $day): JsonResponse
    {
        $this->authorizeTrainerForDay($day);

        $validated = $request->validate([
            'circuit_number' => 'required|integer|min:1'
        ]);

        $circuit = $day->circuits()->create([
            'circuit_number' => $validated['circuit_number']
        ]);

        return response()->json(['circuit' => $circuit]);
    }

    public function show(Circuit $circuit): JsonResponse
    {
        $this->authorizeTrainerForCircuit($circuit);
        return response()->json(['circuit' => $circuit->load('exercises.sets')]);
    }

    public function update(Request $request, Circuit $circuit): JsonResponse
    {
        $this->authorizeTrainerForCircuit($circuit);

        $validated = $request->validate([
            'circuit_number' => 'nullable|integer|min:1'
        ]);

        $circuit->update($validated);

        return response()->json(['success' => true, 'circuit' => $circuit]);
    }

    public function destroy(Circuit $circuit): JsonResponse
    {
        $this->authorizeTrainerForCircuit($circuit);
        $circuit->delete();
        return response()->json(['success' => true, 'message' => 'Circuit deleted']);
    }

    private function authorizeTrainerForDay(Day $day): void
    {
        $program = $day->week->program;
        if ($program->trainer_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }

    private function authorizeTrainerForCircuit(Circuit $circuit): void
    {
        $program = $circuit->day->week->program;
        if ($program->trainer_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }
}
