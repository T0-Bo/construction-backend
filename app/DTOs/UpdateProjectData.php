<?php

namespace App\DTOs;

class UpdateProjectData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public string $status,
    ) {}
}
