<?php

namespace App\Http\Controllers\Trainer;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgramController extends Controller
{
    /**
     * Trainer can only view and manage their own programs
     */
    public function __construct()
    {
        $this->middleware('trainer');
    }

    /**
     * Display a listing of trainer's programs
     */
    public function index(Request $request)
    {
        try {
            // Handle DataTable AJAX request
            if ($request->ajax() || $request->has('draw')) {
                return $this->getDataTableData($request);
            }

            // Return view for web request
            return view('trainer.programs.index');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in ProgramController@index: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading programs.');
        }
    }

    /**
     * Get DataTable data for programs listing
     */
    private function getDataTableData(Request $request): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $draw = $request->get('draw');
            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search')['value'] ?? '';

            $query = Program::where('trainer_id', $trainerId)->with(['weeks', 'client', 'trainer']);

            // Apply search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('client', function ($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $totalRecords = Program::where('trainer_id', $trainerId)->count();
            $filteredRecords = $query->count();

            $programs = $query->skip($start)->take($length)->orderBy('created_at', 'desc')->get();

            $data = $programs->map(function ($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'trainer' => $program->trainer ? $program->trainer->name : '',
                    'client' => $program->client ? $program->client->name : '<span class="badge bg-secondary">Template</span>',
                    'duration' => $program->duration . ' weeks',
                    'weeks_count' => $program->weeks->count(),
                    'status' => $program->is_active ? 
                        '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Active</span>' : 
                        '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full">Draft</span>',
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
            \Illuminate\Support\Facades\Log::error('Error in ProgramController@getDataTableData: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load programs data'], 500);
        }
    }

    /**
     * Generate action buttons for DataTable
     */
    private function getActionButtons(Program $program): string
    {
        $buttons = '<div class="btn-group" role="group">';
        
        $buttons .= '<a href="' . route('trainer.programs.show', $program->id) . '" 
                        class="btn btn-sm btn-outline-primary me-1" title="View Program">
                        <i class="ri-eye-line"></i>
                    </a>';
        
        $buttons .= '<a href="' . route('trainer.programs.edit', $program->id) . '" 
                        class="btn btn-sm btn-outline-success me-1" title="Edit Program">
                        <i class="ri-edit-2-line"></i>
                    </a>';

        $buttons .= '<button type="button" class="btn btn-sm btn-outline-secondary me-1 program-pdf-show" 
                        data-program-id="' . $program->id . '" title="Show PDF">
                        <i class="ri-file-pdf-line"></i>
                    </button>';

        $buttons .= '<button type="button" class="btn btn-sm btn-outline-dark me-1 program-pdf-download" 
                        data-program-id="' . $program->id . '" title="Download PDF">
                        <i class="ri-download-2-line"></i>
                    </button>';
        
        $buttons .= '<button onclick="deleteProgram(' . $program->id . ')" 
                        class="btn btn-sm btn-outline-danger" title="Delete Program">
                        <i class="ri-delete-bin-5-line"></i>
                    </button>';
        
        $buttons .= '</div>';
        
        return $buttons;
    }

    /**
     * Generate PDF data for a program
     */
    public function pdfData(Program $program): JsonResponse
    {
        try {
            $this->authorizeTrainer($program);
            $service = app(\App\Services\ProgramPdfService::class);
            $result = $service->generate($program);
            return response()->json([
                'success' => true,
                'data' => [
                    'pdf_view_url' => $result['url'],
                    'pdf_download_url' => $result['url'],
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in ProgramController@pdfData: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to generate PDF'], 500);
        }
    }

    public function pdfInline(Program $program)
    {
        $this->authorizeTrainer($program);
        $service = app(\App\Services\ProgramPdfService::class);
        return $service->stream($program);
    }

    public function pdfView(Program $program)
    {
        $this->authorizeTrainer($program);
        $service = app(\App\Services\ProgramPdfService::class);
        return $service->stream($program);
    }

    /**
     * Show the form for creating a new program
     */
    public function create(): View
    {
        $trainer = Auth::user();
        $clients = $trainer->subscriptionsAsTrainer()->with('client')->get()->pluck('client');
        
        return view('trainer.programs.create', [
            'clients' => $clients,
            'workouts' => \App\Models\Workout::all() // for builder reference
        ]);
    }

    /**
     * Store a newly created program
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration' => 'required|integer|min:1|max:52',
            'client_id' => 'nullable|integer',
            'description' => 'nullable|string'
        ]);

        $program = Program::create([
            'trainer_id' => Auth::id(),
            'name' => $validated['name'],
            'duration' => $validated['duration'],
            'client_id' => $validated['client_id'],
            'description' => $validated['description'],
            'is_active' => false
        ]);

        return redirect()->route('trainer.programs.edit', $program)
            ->with('success', 'Program created successfully. Build your program using the builder.');
    }

    /**
     * Display the specified program
     */
    public function show(Program $program): View
    {
        $this->authorizeTrainer($program);
        
        $program->load(['trainer', 'client', 'weeks.days.circuits.programExercises.workout', 'weeks.days.circuits.programExercises.exerciseSets']);
        $totalDays = $program->weeks->sum(function($week){ return $week->days->count(); });
        $totalCircuits = $program->weeks->sum(function($week){ return $week->days->sum(function($day){ return $day->circuits->count(); }); });
        $totalExercises = $program->weeks->sum(function($week){ return $week->days->sum(function($day){ return $day->circuits->sum(function($circuit){ return $circuit->programExercises->count(); }); }); });

        return view('trainer.programs.show', [
            'program' => $program,
            'totalDays' => $totalDays,
            'totalCircuits' => $totalCircuits,
            'totalExercises' => $totalExercises
        ]);
    }

    /**
     * Show the form for editing the program
     */
    public function edit(Program $program): View
    {
        $this->authorizeTrainer($program);
        
        $trainer = Auth::user();
        $clients = $trainer->subscriptionsAsTrainer()->with('client')->get()->pluck('client');

        return view('trainer.programs.edit', [
            'program' => $program->load('weeks'),
            'clients' => $clients,
            'workouts' => \App\Models\Workout::all()
        ]);
    }

    /**
     * Display the program builder
     */
    public function builder(Program $program): View
    {
        $this->authorizeTrainer($program);

        return view('trainer.programs.builder', [
            'program' => $program->load(['trainer', 'client', 'weeks.days.circuits.programExercises.workout', 'weeks.days.circuits.programExercises.exerciseSets']),
            'workouts' => \App\Models\Workout::all()
        ]);
    }

    /**
     * Update the specified program
     */
    public function update(Request $request, Program $program): RedirectResponse
    {
        $this->authorizeTrainer($program);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration' => 'required|integer|min:1|max:52',
            'client_id' => 'nullable|integer',
            'description' => 'nullable|string'
        ]);

        $program->update($validated);

        return redirect()->route('trainer.programs.edit', $program)
            ->with('success', 'Program updated successfully.');
    }

    /**
     * Delete the specified program
     */
    public function destroy(Program $program): JsonResponse
    {
        $this->authorizeTrainer($program);

        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Program deleted successfully'
        ]);
    }

    /**
     * Get program statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $trainerId = Auth::id();

            $totalPrograms = Program::where('trainer_id', $trainerId)->count();
            $activePrograms = Program::where('trainer_id', $trainerId)
                ->where('is_active', true)->count();
            $templatePrograms = Program::where('trainer_id', $trainerId)
                ->whereNull('client_id')->count();

            $totalWeeks = DB::table('weeks')
                ->join('programs', 'weeks.program_id', '=', 'programs.id')
                ->where('programs.trainer_id', $trainerId)
                ->count();

            return response()->json([
                'total_programs' => $totalPrograms,
                'active_programs' => $activePrograms,
                'template_programs' => $templatePrograms,
                'total_weeks' => $totalWeeks
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in ProgramController@getStats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load statistics'], 500);
        }
    }

    /**
     * Duplicate a program
     */
    public function duplicate(Program $program): RedirectResponse
    {
        $this->authorizeTrainer($program);

        $newProgram = $program->replicate();
        $newProgram->name = $program->name . ' (Copy)';
        $newProgram->save();

        return redirect()->route('trainer.programs.edit', $newProgram)
            ->with('success', 'Program duplicated successfully.');
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
