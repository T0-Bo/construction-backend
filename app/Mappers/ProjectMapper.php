<?php

namespace App\Mappers;

use App\DTOs\CreateProjectData;
use App\DTOs\UpdateProjectData;

class ProjectMapper
{
    public function toCreateData(
        array $data,
        int $companyId
    ): CreateProjectData {
        return new CreateProjectData(
            name: $data['name'],
            description: $data['description'] ?? null,
            status: $data['status'],
            companyId: $companyId,
            budget: $data['budget'],
        );
    }

    public function toUpdateData(
        array $data
    ): UpdateProjectData {
        return new UpdateProjectData(
            name: $data['name'],
            description: $data['description'] ?? null,
            status: $data['status'],
        );
    }
}
