<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ClientProgramController extends ApiBaseController
{
    public function pdfData(Program $program): JsonResponse
    {
      try {
        if ($program->client_id !== Auth::id()) {
            return $this->sendError('Unauthorized', ['error' => 'Access denied'], 403);
        }
        $program->load([
            'trainer:id,name,email,business_logo',
            'client:id,name,email',
            'weeks.days.circuits.programExercises.workout',
            'weeks.days.circuits.programExercises.exerciseSets'
        ]);
        return $this->sendResponse(['program' => $program], 'PDF data generated');
      } catch (\Exception $e) {
        return $this->sendError('Generation Failed', ['error' => 'Unable to generate PDF data'], 500);
      }
    }
}