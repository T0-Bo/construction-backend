<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Events\ReportCompleted;
use App\Models\Report;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function markAsProcessing(Report $report): Report
    {
        return DB::transaction(function () use ($report) {

            $report = Report::whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureStatus(
                $report,
                ReportStatus::PENDING
            );

            $report->update([
                'status' => ReportStatus::PROCESSING,
            ]);

            return $report;
        });
    }

    public function markAsCompleted(Report $report): Report
    {
        return DB::transaction(function () use ($report) {

            $report = Report::whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureStatus(
                $report,
                ReportStatus::PROCESSING
            );

            $report->update([
                'status' => ReportStatus::COMPLETED,
            ]);

            ReportCompleted::dispatch($report);

            return $report;
        });
    }

    public function markAsFailed(Report $report): Report
    {
        return DB::transaction(function () use ($report) {

            $report = Report::whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureStatus(
                $report,
                ReportStatus::PROCESSING
            );

            $report->update([
                'status' => ReportStatus::FAILED,
            ]);

            return $report;
        });
    }

    private function ensureStatus(
        Report $report,
        ReportStatus $expected
    ): void {
        if ($report->status !== $expected) {
            throw new \LogicException(
                "El reporte {$report->id} no está en el estado esperado."
            );
        }
    }

    public function resetForRetry(Report $report): Report
    {
        return DB::transaction(function () use ($report) {
            $report = Report::whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureStatus(
                $report,
                ReportStatus::FAILED
            );

            $report->update([
                'status' => ReportStatus::PENDING,
            ]);

            return $report;
        });
    }
}
