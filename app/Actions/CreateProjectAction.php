<?php

namespace App\Actions;

use App\DTOs\CreateProjectData;
use App\Models\Budget;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class CreateProjectAction
{
    public function execute(CreateProjectData $data): Project
    {
        return DB::transaction(function () use ($data) {

            $project = Project::create([
                'name' => $data->name,
                'description' => $data->description,
                'status' => $data->status,
                'company_id' => $data->companyId,
            ]);

            Budget::create([
                'project_id' => $project->id,
                'amount' => $data->budget,
            ]);

            return $project;
        });
    }
}
