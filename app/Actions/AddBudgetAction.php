<?php

namespace App\Actions;

use App\Exceptions\ProjectAlreadyHasBudgetException;
use App\Models\Budget;
use App\Models\Project;
use Illuminate\Database\UniqueConstraintViolationException;

class AddBudgetAction
{
    public function execute(
        Project $project,
        string $amount
    ): Budget {
        if ($project->budget()->exists()) {
            throw new ProjectAlreadyHasBudgetException(
                'El proyecto ya tiene un presupuesto.'
            );
        }

        try {
            return Budget::create([
                'project_id' => $project->id,
                'amount' => $amount,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            throw new ProjectAlreadyHasBudgetException(
                'El proyecto ya tiene un presupuesto.',
                previous: $exception
            );
        }
    }
}
