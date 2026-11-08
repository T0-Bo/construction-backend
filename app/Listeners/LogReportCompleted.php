<?php

namespace App\Listeners;

use App\Events\ReportCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogReportCompleted implements ShouldQueue
{
    public int $tries = 3;

    public function handle(ReportCompleted $event): void
    {
        Log::info(
            "Listener: el reporte {$event->report->id} fue completado."
        );
    }
}
