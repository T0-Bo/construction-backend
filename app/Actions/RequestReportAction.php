<?php

namespace App\Actions;

use App\Enums\ReportStatus;
use App\Jobs\GenerateProjectReport;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;

class RequestReportAction
{
    public function execute(Project $project, User $user): Report
    {
        $report = Report::create([
            'project_id' => $project->id,
            'requested_by' => $user->id,
            'status' => ReportStatus::PENDING,
        ]);

        GenerateProjectReport::dispatch($project->id, $report->id);

        return $report;
    }
}
