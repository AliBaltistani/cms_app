<?php

namespace App\Services;

use App\Models\Program;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ProgramPdfService
{
    public function renderHtml(Program $program): string
    {
        $program->load([
            'trainer:id,name,email,business_logo',
            'client:id,name,email',
            'weeks.days.circuits.programExercises.workout',
            'weeks.days.circuits.programExercises.exerciseSets',
        ]);

        $logoBase64 = $this->buildLogoBase64($program);

        return view('pdf.program', [
            'program' => $program,
            'logoBase64' => $logoBase64,
        ])->render();
    }

    public function stream(Program $program, array $options = [])
    {
        $paper = $options['paper'] ?? 'a4';
        $html = $this->renderHtml($program);
        return Pdf::loadHTML($html)->setPaper($paper)->stream('program-' . $program->id . '.pdf', ['Attachment' => false]);
    }

    public function generate(Program $program, array $options = []): array
    {
        $html = $this->renderHtml($program);

        $paper = $options['paper'] ?? 'a4';
        $pdf = Pdf::loadHTML($html)->setPaper($paper);

        $filename = 'program-pdfs/program-' . $program->id . '-' . time() . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());
        $url = Storage::url($filename);

        return [
            'path' => $filename,
            'url' => $url,
        ];
    }

    protected function buildLogoBase64(Program $program): ?string
    {
        if (!$program->trainer || !$program->trainer->business_logo) {
            return null;
        }

        $abs = storage_path('app/public/' . $program->trainer->business_logo);
        if (is_file($abs)) {
            $mime = function_exists('mime_content_type') ? mime_content_type($abs) : 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
        }

        return null;
    }
}