<?php

namespace App\Listeners;

use App\Events\ReportCompleted;
use App\Notifications\ReportCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyReportCompleted implements ShouldQueue
{
    public int $tries = 3;

    public function handle(ReportCompleted $event): void
    {
        $report = $event->report;
        $user = $report->requestedBy;

        if (! $user) {
            Log::warning(
                "No se pudo notificar el reporte {$report->id}: ".
                'el usuario solicitante no existe.',
                [
                    'report_id' => $report->id,
                    'requested_by' => $report->requested_by,
                ]
            );

            return;
        }

        $user->notify(
            new ReportCompletedNotification($report->id)
        );

        Log::info(
            "Reporte {$report->id} completado. ".
            "Solicitante y destinatario: usuario {$user->id}.",
            [
                'report_id' => $report->id,
                'requested_by' => $user->id,
                'recipient_id' => $user->id,
            ]
        );
    }
}
