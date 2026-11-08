<?php

namespace App\Services;

use App\DTOs\UpdateProjectData;
// use App\DTOs\CreateProjectData;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function update(
        Project $project,
        UpdateProjectData $data,
        AuditLogService $auditLogService
    ): Project {
        return DB::transaction(function () use (
            $project,
            $data,
            $auditLogService
        ) {
            $oldValues = [
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
            ];

            $project->update([
                'name' => $data->name,
                'description' => $data->description,
                'status' => $data->status,
            ]);

            $newValues = [
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
            ];

            $auditLogService->logUpdate(
                $project,
                $oldValues,
                $newValues
            );

            return $project;
        });
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function restore(Project $project): Project
    {
        $project->restore();

        return $project;
    }

    public function forceDelete(Project $project): void
    {
        $project->forceDelete();
    }
}
