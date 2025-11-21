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
        $service = app(\App\Services\ProgramPdfService::class);
        $result = $service->generate($program);
        return $this->sendResponse([
            'pdf_view_url' => $result['url'],
            'pdf_download_url' => $result['url'],
        ], 'PDF generated');
      } catch (\Exception $e) {
        return $this->sendError('Generation Failed', ['error' => 'Unable to generate PDF'], 500);
      }
    }
}