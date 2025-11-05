<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;

/**
 * Program Controller
 * 
 * Handles workout program management operations for admin interface
 * Provides complete CRUD operations for workout programs
 * 
 * @package     Laravel CMS App
 * @subpackage  Controllers
 * @category    Workout Exercise Management
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */
class ProgramController extends Controller
{
    /**
     * Display a listing of programs
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Handle DataTable AJAX request
            if ($request->ajax() || $request->has('draw')) {
                return $this->getDataTableData($request);
            }

            // Return view for web request
            return view('admin.programs.index');
        } catch (\Exception $e) {
            Log::error('Error in ProgramController@index: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading programs.');
        }
    }

    /**
     * Get DataTable data for programs listing
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function getDataTableData(Request $request): JsonResponse
    {
        try {
            $draw = $request->get('draw');
            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search')['value'] ?? '';

            $query = Program::with(['trainer', 'client', 'weeks']);

            // Apply search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('trainer', function ($trainerQuery) use ($search) {
                          $trainerQuery->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('client', function ($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $totalRecords = Program::count();
            $filteredRecords = $query->count();

            $programs = $query->skip($start)->take($length)->get();

            $data = $programs->map(function ($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'trainer' => $program->trainer ? $program->trainer->name : 'N/A',
                    'client' => $program->client ? $program->client->name : 'Unassigned',
                    'duration' => $program->duration . ' weeks',
                    'weeks_count' => $program->weeks->count(),
                    'status' => $program->is_active ? 
                        '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Active</span>' : 
                        '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Inactive</span>',
                    'created_at' => $program->created_at->format('M d, Y'),
                    'actions' => $this->getActionButtons($program)
                ];
            });

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramController@getDataTableData: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load programs data'], 500);
        }
    }

    /**
     * Generate action buttons for DataTable
     * 
     * @param  \App\Models\Program  $program
     * @return string
     */
    private function getActionButtons(Program $program): string
    {
        $buttons = '<div class="btn-group" role="group">';
        
        $buttons .= '<a href="' . route('programs.show', $program->id) . '" 
                        class="btn btn-sm btn-outline-primary me-1" title="View Program">
                        <i class="ri-eye-line"></i>
                    </a>';
        
        $buttons .= '<a href="' . route('programs.edit', $program->id) . '" 
                        class="btn btn-sm btn-outline-success me-1" title="Edit Program">
                        <i class="ri-edit-2-line"></i>
                    </a>';
        
        $buttons .= '<button onclick="deleteProgram(' . $program->id . ')" 
                        class="btn btn-sm btn-outline-danger" title="Delete Program">
                        <i class="ri-delete-bin-5-line"></i>
                    </button>';
        
        $buttons .= '</div>';
        
        return $buttons;
    }

    /**
     * Show the form for creating a new program
     * 
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $trainers = User::where('role', 'trainer')->get();
        $clients = User::where('role', 'client')->get();
        
        return view('admin.programs.create', compact('trainers', 'clients'));
    }

    /**
     * Store a newly created program
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'trainer_id' => 'required|exists:users,id',
                'client_id' => 'nullable|exists:users,id',
                'duration' => 'required|integer|min:1|max:52',
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $program = Program::create([
                'name' => $request->name,
                'trainer_id' => $request->trainer_id,
                'client_id' => $request->client_id,
                'duration' => $request->duration,
                'description' => $request->description,
                'is_active' => $request->has('is_active')
            ]);

            return redirect()->route('programs.index')
                           ->with('success', 'Program created successfully.');
        } catch (\Exception $e) {
            Log::error('Error in ProgramController@store: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while creating the program.')
                        ->withInput();
        }
    }

    /**
     * Display the specified program
     * 
     * @param  \App\Models\Program  $program
     * @return \Illuminate\View\View
     */
    public function show(Program $program): View
    {
        $program->load(['trainer', 'client', 'weeks.days.circuits.programExercises.workout', 'weeks.days.circuits.programExercises.exerciseSets']);
        
        // Calculate program statistics
        $totalDays = $program->weeks->sum(function ($week) {
            return $week->days->count();
        });
        
        $totalCircuits = $program->weeks->sum(function ($week) {
            return $week->days->sum(function ($day) {
                return $day->circuits->count();
            });
        });
        
        $totalExercises = $program->weeks->sum(function ($week) {
            return $week->days->sum(function ($day) {
                return $day->circuits->sum(function ($circuit) {
                    return $circuit->programExercises->count();
                });
            });
        });
        
        return view('admin.programs.show', compact('program', 'totalDays', 'totalCircuits', 'totalExercises'));
    }

    /**
     * Show the form for editing the specified program
     * 
     * @param  \App\Models\Program  $program
     * @return \Illuminate\View\View
     */
    public function edit(Program $program): View
    {
        $trainers = User::where('role', 'trainer')->get();
        $clients = User::where('role', 'client')->get();
        
        return view('admin.programs.edit', compact('program', 'trainers', 'clients'));
    }

    /**
     * Update the specified program
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Program  $program
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Program $program): RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'trainer_id' => 'required|exists:users,id',
                'client_id' => 'nullable|exists:users,id',
                'duration' => 'required|integer|min:1|max:52',
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $program->update([
                'name' => $request->name,
                'trainer_id' => $request->trainer_id,
                'client_id' => $request->client_id,
                'duration' => $request->duration,
                'description' => $request->description,
                'is_active' => $request->has('is_active')
            ]);

            return redirect()->route('programs.index')
                           ->with('success', 'Program updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error in ProgramController@update: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while updating the program.')
                        ->withInput();
        }
    }

    /**
     * Remove the specified program
     * 
     * @param  \App\Models\Program  $program
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Program $program): JsonResponse
    {
        try {
            $program->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Program deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProgramController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the program.'
            ], 500);
        }
    }

    /**
     * Duplicate a program with all its weeks, days, circuits, exercises and sets
     * 
     * @param  \App\Models\Program  $program
     * @return \Illuminate\Http\JsonResponse
     */
    public function duplicate(Program $program): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create a duplicate of the program (without client assignment)
            $duplicatedProgram = Program::create([
                'trainer_id' => $program->trainer_id,
                'client_id' => null, // Remove client assignment for template
                'name' => $program->name . ' (Copy)',
                'duration' => $program->duration,
                'description' => $program->description,
                'is_active' => $program->is_active
            ]);

            // Load the program with all its nested relationships
            $program->load([
                'weeks.days.circuits.programExercises.exerciseSets'
            ]);

            // Duplicate all weeks
            foreach ($program->weeks as $week) {
                $duplicatedWeek = $duplicatedProgram->weeks()->create([
                    'week_number' => $week->week_number,
                    'title' => $week->title,
                    'description' => $week->description
                ]);

                // Duplicate all days for this week
                foreach ($week->days as $day) {
                    $duplicatedDay = $duplicatedWeek->days()->create([
                        'day_number' => $day->day_number,
                        'title' => $day->title,
                        'description' => $day->description
                    ]);

                    // Duplicate all circuits for this day
                    foreach ($day->circuits as $circuit) {
                        $duplicatedCircuit = $duplicatedDay->circuits()->create([
                            'circuit_number' => $circuit->circuit_number,
                            'title' => $circuit->title,
                            'description' => $circuit->description,
                            'rounds' => $circuit->rounds,
                            'rest_between_rounds' => $circuit->rest_between_rounds
                        ]);

                        // Duplicate all program exercises for this circuit
                        foreach ($circuit->programExercises as $programExercise) {
                            $duplicatedProgramExercise = $duplicatedCircuit->programExercises()->create([
                                'workout_id' => $programExercise->workout_id,
                                'name' => $programExercise->name,
                                'order' => $programExercise->order,
                                'tempo' => $programExercise->tempo,
                                'rest_interval' => $programExercise->rest_interval,
                                'notes' => $programExercise->notes
                            ]);

                            // Duplicate all exercise sets for this program exercise
                            foreach ($programExercise->exerciseSets as $exerciseSet) {
                                $duplicatedProgramExercise->exerciseSets()->create([
                                    'set_number' => $exerciseSet->set_number,
                                    'reps' => $exerciseSet->reps,
                                    'weight' => $exerciseSet->weight
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Program duplicated successfully as a template.',
                'program_id' => $duplicatedProgram->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramController@duplicate: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while duplicating the program.'
            ], 500);
        }
    }

    /**
     * Assign a program template to a specific client
     * 
     * Creates a new program instance assigned to the selected client
     * while keeping the original as a template
     * 
     * @param  \App\Models\Program  $program
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assign(Program $program, Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'client_id' => 'required|exists:users,id',
            ]);

            // Verify the client has the correct role
            $client = User::findOrFail($request->client_id);
            if ($client->role !== 'client') {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is not a client.'
                ], 400);
            }

            DB::beginTransaction();

            // Create a new program instance assigned to the client
            $assignedProgram = Program::create([
                'trainer_id' => $program->trainer_id,
                'client_id' => $request->client_id,
                'name' => $program->name . ' - ' . $client->name,
                'duration' => $program->duration,
                'description' => $program->description,
                'is_active' => true
            ]);

            // Load the program with all its nested relationships
            $program->load([
                'weeks.days.circuits.programExercises.exerciseSets'
            ]);

            // Copy all weeks and their structure
            foreach ($program->weeks as $week) {
                $assignedWeek = $assignedProgram->weeks()->create([
                    'week_number' => $week->week_number,
                    'title' => $week->title,
                    'description' => $week->description
                ]);

                // Copy all days for this week
                foreach ($week->days as $day) {
                    $assignedDay = $assignedWeek->days()->create([
                        'day_number' => $day->day_number,
                        'title' => $day->title,
                        'description' => $day->description
                    ]);

                    // Copy all circuits for this day
                    foreach ($day->circuits as $circuit) {
                        $assignedCircuit = $assignedDay->circuits()->create([
                            'name' => $circuit->name,
                            'description' => $circuit->description,
                            'order' => $circuit->order
                        ]);

                        // Copy all program exercises for this circuit
                        foreach ($circuit->programExercises as $programExercise) {
                            $assignedProgramExercise = $assignedCircuit->programExercises()->create([
                                'workout_id' => $programExercise->workout_id,
                                'name' => $programExercise->name,
                                'order' => $programExercise->order,
                                'tempo' => $programExercise->tempo,
                                'rest_interval' => $programExercise->rest_interval,
                                'notes' => $programExercise->notes
                            ]);

                            // Copy all exercise sets
                            foreach ($programExercise->exerciseSets as $exerciseSet) {
                                $assignedProgramExercise->exerciseSets()->create([
                                    'set_number' => $exerciseSet->set_number,
                                    'reps' => $exerciseSet->reps,
                                    'weight' => $exerciseSet->weight,
                                    'duration' => $exerciseSet->duration,
                                    'distance' => $exerciseSet->distance,
                                    'notes' => $exerciseSet->notes
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Program successfully assigned to ' . $client->name,
                'data' => [
                    'assigned_program_id' => $assignedProgram->id,
                    'client_name' => $client->name
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in ProgramController@assign: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while assigning the program to the client.'
            ], 500);
        }
    }

    /**
     * Get statistics for programs dashboard
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_programs' => Program::count(),
                'active_programs' => Program::active()->count(),
                'assigned_programs' => Program::whereNotNull('client_id')->count(),
                'unassigned_programs' => Program::whereNull('client_id')->count()
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error in ProgramController@getStats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load statistics'], 500);
        }
    }
}