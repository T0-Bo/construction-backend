<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateProjectReport implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function backoff(): int
    {
        return 10;
    }

    public function __construct(
        public int $projectId,
        public int $reportId
    ) {}

    public function handle(ReportService $reportService): void
    {
        $report = Report::findOrFail($this->reportId);

        $project = Project::with([
            'company',
            'budget',
        ])->findOrFail($this->projectId);

        if ($report->project_id !== $project->id) {
            throw new \RuntimeException(
                'El reporte no pertenece al proyecto indicado.'
            );
        }

        $report = $reportService->markAsProcessing($report);

        if (! $project->company || ! $project->budget) {
            throw new \RuntimeException(
                "El proyecto {$project->id} no tiene compañía o presupuesto asociado."
            );
        }

        Log::info(
            "Reporte generado para proyecto {$project->id}: {$project->name}, ".
            "compañía: {$project->company->name}, ".
            "presupuesto: {$project->budget->amount}"
        );

        $reportService->markAsCompleted($report);
    }

    public function failed(?Throwable $exception): void
    {
        $report = Report::find($this->reportId);

        if ($report) {
            $reportService = app(ReportService::class);

            $report = $reportService->markAsFailed($report);
        }

        Log::error(
            "Falló definitivamente el reporte {$this->reportId}.",
            [
                'exception' => $exception?->getMessage(),
            ]
        );
    }
}
