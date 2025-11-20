<?php

namespace App\Http\Controllers\Api;

use App\Models\Program;
use App\Models\ProgramVideo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * API Program Video Controller
 * 
 * API endpoints for program videos
 * Returns programs with program_plans and videos
 */
class ApiProgramVideoController extends Controller
{
    /**
     * Get program with videos (in program_plans structure)
     */
    public function getProgramPlan($programId)
    {
        try {
            $program = Program::with([
                'weeks.days.circuits.programExercises.exerciseSets',
                'videos'
            ])->findOrFail($programId);

            // Build response with program_plans
            $response = [
                'success' => true,
                'data' => [
                    'program' => $this->formatProgramResponse($program)
                ]
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get all programs for authenticated trainer (with videos)
     */
    public function indexWithVideos(Request $request)
    {
        try {
            $trainer = auth('sanctum')->user();
            
            $programs = Program::where('trainer_id', $trainer->id)
                ->with([
                    'weeks.days.circuits.programExercises.exerciseSets',
                    'videos'
                ])
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            $formattedPrograms = $programs->map(fn($prog) => $this->formatProgramResponse($prog));

            return response()->json([
                'success' => true,
                'data' => $formattedPrograms,
                'meta' => [
                    'total' => $programs->total(),
                    'per_page' => $programs->perPage(),
                    'current_page' => $programs->currentPage(),
                    'last_page' => $programs->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching programs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get program videos
     */
    public function getVideos($programId)
    {
        try {
            $program = Program::findOrFail($programId);
            
            $videos = $program->videos()
                ->orderBy('order')
                ->get()
                ->map(fn($v) => $this->formatVideoResponse($v));

            return response()->json([
                'success' => true,
                'data' => $videos,
                'count' => count($videos)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Format program response with program_plans structure
     */
    private function formatProgramResponse($program)
    {
        return [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->description,
            'duration' => $program->duration,
            'trainer_id' => $program->trainer_id,
            'client_id' => $program->client_id,
            'is_active' => $program->is_active,
            'created_at' => $program->created_at,
            'updated_at' => $program->updated_at,
            'program_plans' => [
                'weeks' => $program->weeks->map(fn($week) => [
                    'id' => $week->id,
                    'week_number' => $week->week_number,
                    'title' => $week->title,
                    'description' => $week->description,
                    'days' => $week->days->map(fn($day) => [
                        'id' => $day->id,
                        'day_number' => $day->day_number,
                        'title' => $day->title,
                        'circuits' => $day->circuits->map(fn($circuit) => [
                            'id' => $circuit->id,
                            'circuit_number' => $circuit->circuit_number,
                            'title' => $circuit->title,
                            'description' => $circuit->description,
                            'exercises' => $circuit->programExercises->map(fn($ex) => [
                                'id' => $ex->id,
                                'name' => $ex->name,
                                'workout_id' => $ex->workout_id,
                                'workout' => $ex->workout ? [
                                    'id' => $ex->workout->id,
                                    'name' => $ex->workout->name,
                                    'title' => $ex->workout->name
                                ] : null,
                                'order' => $ex->order,
                                'notes' => $ex->notes,
                                'sets' => $ex->exerciseSets->map(fn($set) => [
                                    'id' => $set->id,
                                    'set_number' => $set->set_number,
                                    'reps' => $set->reps,
                                    'weight' => $set->weight
                                ])->toArray()
                            ])->toArray()
                        ])->toArray()
                    ])->toArray()
                ])->toArray(),
                'videos' => $program->videos->map(fn($v) => $this->formatVideoResponse($v))->toArray()
            ]
        ];
    }

    /**
     * Format video response
     */
    private function formatVideoResponse($video)
    {
        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'video_type' => $video->video_type,
            'video_url' => $video->video_url,
            'embed_url' => $video->embed_url,
            'thumbnail' => $video->thumbnail ? asset('storage/' . $video->thumbnail) : null,
            'duration' => $video->duration,
            'formatted_duration' => $video->formatted_duration,
            'order' => $video->order,
            'is_preview' => $video->is_preview,
            'created_at' => $video->created_at,
            'updated_at' => $video->updated_at
        ];
    }
}
