<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class TrainerProgramController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $query = Program::query()->where('trainer_id', $trainerId)->withCount(['weeks']);

            if ($request->filled('search')) {
                $s = trim((string) $request->input('search'));
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('description', 'like', "%{$s}%");
                });
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            $perPage = (int) $request->input('per_page', 15);
            $programs = $query->paginate($perPage);

            return $this->sendResponse([
                'data' => $programs->items(),
                'pagination' => [
                    'total' => $programs->total(),
                    'per_page' => $programs->perPage(),
                    'current_page' => $programs->currentPage(),
                    'last_page' => $programs->lastPage(),
                    'from' => $programs->firstItem(),
                    'to' => $programs->lastItem(),
                    'has_more_pages' => $programs->hasMorePages(),
                ]
            ], 'Programs retrieved successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramController@index failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve programs'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'duration' => 'required|integer|min:1|max:52',
                'description' => 'nullable|string',
                'client_id' => 'nullable|integer|exists:users,id',
                'is_active' => 'boolean'
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $trainerId = Auth::id();
            $clientId = $request->input('client_id');
            if ($clientId) {
                $client = User::find($clientId);
                if (!$client || $client->role !== 'client') {
                    return $this->sendError('Validation Error', ['client_id' => ['Selected user is not a client']], 422);
                }
            }

            $program = Program::create([
                'trainer_id' => $trainerId,
                'client_id' => $clientId,
                'name' => $request->input('name'),
                'duration' => (int) $request->input('duration'),
                'description' => $request->input('description'),
                'is_active' => (bool) $request->boolean('is_active', true),
            ]);

            return $this->sendResponse(['program' => $program], 'Program created successfully', 201);
        } catch (\Exception $e) {
            Log::error('TrainerProgramController@store failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Creation Failed', ['error' => 'Unable to create program'], 500);
        }
    }

    public function show(Program $program): JsonResponse
    {
        try {
            if ($program->trainer_id !== Auth::id()) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }

            $program->load(['trainer:id,name,email', 'client:id,name,email', 'weeks.days.circuits.programExercises.workout', 'weeks.days.circuits.programExercises.exerciseSets']);

            return $this->sendResponse(['program' => $program], 'Program retrieved successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramController@show failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'program_id' => $program->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to retrieve program'], 500);
        }
    }

    public function update(Request $request, Program $program): JsonResponse
    {
        try {
            if ($program->trainer_id !== Auth::id()) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'duration' => 'required|integer|min:1|max:52',
                'description' => 'nullable|string',
                'client_id' => 'nullable|integer|exists:users,id',
                'is_active' => 'boolean'
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $clientId = $request->input('client_id');
            if ($clientId) {
                $client = User::find($clientId);
                if (!$client || $client->role !== 'client') {
                    return $this->sendError('Validation Error', ['client_id' => ['Selected user is not a client']], 422);
                }
            }

            $program->update([
                'name' => $request->input('name'),
                'duration' => (int) $request->input('duration'),
                'description' => $request->input('description'),
                'client_id' => $clientId,
                'is_active' => (bool) $request->boolean('is_active', $program->is_active),
            ]);

            return $this->sendResponse(['program' => $program], 'Program updated successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramController@update failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'program_id' => $program->id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Update Failed', ['error' => 'Unable to update program'], 500);
        }
    }

    public function destroy(Program $program): JsonResponse
    {
        try {
            if ($program->trainer_id !== Auth::id()) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $program->delete();
            return $this->sendResponse(['deleted' => true], 'Program deleted successfully');
        } catch (\Exception $e) {
            Log::error('TrainerProgramController@destroy failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'program_id' => $program->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Deletion Failed', ['error' => 'Unable to delete program'], 500);
        }
    }

    public function duplicate(Program $program): JsonResponse
    {
        try {
            if ($program->trainer_id !== Auth::id()) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }

            DB::beginTransaction();

            $newProgram = Program::create([
                'trainer_id' => $program->trainer_id,
                'client_id' => null,
                'name' => $program->name . ' (Copy)',
                'duration' => $program->duration,
                'description' => $program->description,
                'is_active' => $program->is_active
            ]);

            $program->load(['weeks.days.circuits.programExercises.exerciseSets']);

            foreach ($program->weeks as $week) {
                $dupWeek = $newProgram->weeks()->create([
                    'week_number' => $week->week_number,
                    'title' => $week->title,
                    'description' => $week->description
                ]);
                foreach ($week->days as $day) {
                    $dupDay = $dupWeek->days()->create([
                        'day_number' => $day->day_number,
                        'title' => $day->title,
                        'description' => $day->description,
                        'cool_down' => $day->cool_down,
                        'custom_rows' => $day->custom_rows,
                    ]);
                    foreach ($day->circuits as $circuit) {
                        $dupCircuit = $dupDay->circuits()->create([
                            'circuit_number' => $circuit->circuit_number,
                            'title' => $circuit->title,
                            'description' => $circuit->description
                        ]);
                        foreach ($circuit->programExercises as $exercise) {
                            $dupExercise = $dupCircuit->programExercises()->create([
                                'workout_id' => $exercise->workout_id,
                                'name' => $exercise->name,
                                'order' => $exercise->order,
                                'tempo' => $exercise->tempo,
                                'rest_interval' => $exercise->rest_interval,
                                'notes' => $exercise->notes
                            ]);
                            foreach ($exercise->exerciseSets as $set) {
                                $dupExercise->exerciseSets()->create([
                                    'set_number' => $set->set_number,
                                    'reps' => $set->reps,
                                    'weight' => $set->weight
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return $this->sendResponse(['program' => $newProgram], 'Program duplicated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramController@duplicate failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'program_id' => $program->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Duplication Failed', ['error' => 'Unable to duplicate program'], 500);
        }
    }

    public function assign(Program $program, Request $request): JsonResponse
    {
        try {
            if ($program->trainer_id !== Auth::id()) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'client_id' => 'required|integer|exists:users,id'
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $client = User::findOrFail($request->input('client_id'));
            if ($client->role !== 'client') {
                return $this->sendError('Validation Error', ['client_id' => ['Selected user is not a client']], 422);
            }

            DB::beginTransaction();

            $assignedProgram = Program::create([
                'trainer_id' => $program->trainer_id,
                'client_id' => $client->id,
                'name' => $program->name . ' - ' . $client->name,
                'duration' => $program->duration,
                'description' => $program->description,
                'is_active' => true
            ]);

            $program->load(['weeks.days.circuits.programExercises.exerciseSets']);

            foreach ($program->weeks as $week) {
                $newWeek = $assignedProgram->weeks()->create([
                    'week_number' => $week->week_number,
                    'title' => $week->title,
                    'description' => $week->description
                ]);
                foreach ($week->days as $day) {
                    $newDay = $newWeek->days()->create([
                        'day_number' => $day->day_number,
                        'title' => $day->title,
                        'description' => $day->description,
                        'cool_down' => $day->cool_down,
                        'custom_rows' => $day->custom_rows,
                    ]);
                    foreach ($day->circuits as $circuit) {
                        $newCircuit = $newDay->circuits()->create([
                            'circuit_number' => $circuit->circuit_number,
                            'title' => $circuit->title,
                            'description' => $circuit->description
                        ]);
                        foreach ($circuit->programExercises as $exercise) {
                            $newExercise = $newCircuit->programExercises()->create([
                                'workout_id' => $exercise->workout_id,
                                'name' => $exercise->name,
                                'order' => $exercise->order,
                                'tempo' => $exercise->tempo,
                                'rest_interval' => $exercise->rest_interval,
                                'notes' => $exercise->notes
                            ]);
                            foreach ($exercise->exerciseSets as $set) {
                                $newExercise->exerciseSets()->create([
                                    'set_number' => $set->set_number,
                                    'reps' => $set->reps,
                                    'weight' => $set->weight
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return $this->sendResponse([
                'assigned_program_id' => $assignedProgram->id,
                'client_name' => $client->name
            ], 'Program assigned successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TrainerProgramController@assign failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'program_id' => $program->id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Assignment Failed', ['error' => 'Unable to assign program'], 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $trainerId = Auth::id();
            $total = Program::where('trainer_id', $trainerId)->count();
            $active = Program::where('trainer_id', $trainerId)->where('is_active', true)->count();
            $assigned = Program::where('trainer_id', $trainerId)->whereNotNull('client_id')->count();
            $unassigned = Program::where('trainer_id', $trainerId)->whereNull('client_id')->count();

            return $this->sendResponse([
                'total_programs' => $total,
                'active_programs' => $active,
                'assigned_programs' => $assigned,
                'unassigned_programs' => $unassigned,
            ], 'Program statistics retrieved');
        } catch (\Exception $e) {
            Log::error('TrainerProgramController@stats failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Retrieval Failed', ['error' => 'Unable to load statistics'], 500);
        }
    }

    public function pdfData(Program $program): JsonResponse
    {
        try {
            if ($program->trainer_id !== Auth::id()) {
                return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
            }
            $program->load([
                'trainer:id,name,email,business_logo',
                'client:id,name,email',
                'weeks.days.circuits.programExercises.workout',
                'weeks.days.circuits.programExercises.exerciseSets'
            ]);

            $logoBase64 = null;
            if ($program->trainer && $program->trainer->business_logo) {
                $abs = storage_path('app/public/' . $program->trainer->business_logo);
                if (is_file($abs)) {
                    $mime = function_exists('mime_content_type') ? mime_content_type($abs) : 'image/png';
                    $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
                }
            }

            $html = view('pdf.program', [
                'program' => $program,
                'logoBase64' => $logoBase64,
            ])->render();

            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            $filename = 'program-pdfs/program-' . $program->id . '-' . time() . '.pdf';
            Storage::disk('public')->put($filename, $pdf->output());
            $url = Storage::url($filename);

            return $this->sendResponse([
                'pdf_view_url' => $url,
                'pdf_download_url' => $url,
            ], 'PDF generated');
        } catch (\Exception $e) {
            Log::error('TrainerProgramController@pdfData failed: ' . $e->getMessage(), [
                'trainer_id' => Auth::id(),
                'program_id' => $program->id,
            ]);
            return $this->sendError('Generation Failed', ['error' => 'Unable to generate PDF'], 500);
        }
    }
}