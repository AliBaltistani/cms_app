<?php

namespace App\Http\Controllers\Trainer;

use App\Models\Program;
use App\Models\Week;
use App\Models\Day;
use App\Models\Circuit;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgramBuilderController extends Controller
{
    public function __construct()
    {
        $this->middleware('trainer');
    }

    /**
     * Display the program builder
     */
    public function show(Program $program): View
    {
        $this->authorizeTrainer($program);

        return view('trainer.programs.builder', [
            'program' => $program->load(['weeks.days.circuits.exercises.workout', 'weeks.days.circuits.exercises.sets']),
            'workouts' => \App\Models\Workout::all()
        ]);
    }

    /**
     * Get column configuration for a program
     */
    public function getColumnConfig(Program $program): JsonResponse
    {
        $this->authorizeTrainer($program);

        $config = $program->column_config ? json_decode($program->column_config, true) : null;

        if (!$config) {
            // Return default config
            $config = [
                ['id' => 'exercise', 'name' => 'Exercise', 'width' => '25%', 'type' => 'text', 'required' => true],
                ['id' => 'set1', 'name' => 'Set 1 - rep / w', 'width' => '12%', 'type' => 'text', 'required' => false],
                ['id' => 'set2', 'name' => 'Set 2 - rep / w', 'width' => '12%', 'type' => 'text', 'required' => false],
                ['id' => 'set3', 'name' => 'Set 3 - rep / w', 'width' => '12%', 'type' => 'text', 'required' => false],
                ['id' => 'set4', 'name' => 'Set 4 - reps / w', 'width' => '12%', 'type' => 'text', 'required' => false],
                ['id' => 'set5', 'name' => 'Set 5 - reps / w', 'width' => '12%', 'type' => 'text', 'required' => false],
                ['id' => 'notes', 'name' => 'Notes', 'width' => '15%', 'type' => 'text', 'required' => false]
            ];
        }

        return response()->json(['columns' => $config]);
    }

    /**
     * Update column configuration for a program
     */
    public function updateColumnConfig(Request $request, Program $program): JsonResponse
    {
        $this->authorizeTrainer($program);

        $validated = $request->validate([
            'columns' => 'required|array'
        ]);

        $program->update([
            'column_config' => json_encode($validated['columns'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Column configuration saved'
        ]);
    }

    /**
     * Authorize that the trainer owns this program
     */
    private function authorizeTrainer(Program $program): void
    {
        if ($program->trainer_id !== Auth::id()) {
            abort(403, 'Unauthorized to access this program.');
        }
    }
}
