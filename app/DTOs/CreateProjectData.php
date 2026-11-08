<?php

namespace App\DTOs;

class CreateProjectData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public string $status,
        public int $companyId,
        public string $budget,
    ) {}
}
