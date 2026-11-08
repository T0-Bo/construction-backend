<?php

namespace App\Http\Controllers;

use App\Actions\AddBudgetAction;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Budget::class);

        $company = $request->attributes->get('active_company');

        $budgets = Budget::with('project')
            ->forCompany($company->id)
            ->get();

        return BudgetResource::collection($budgets);
    }

    public function show(Budget $budget)
    {
        Gate::authorize('view', $budget);

        return new BudgetResource($budget);
    }

    public function summary(Request $request)
    {
        Gate::authorize('viewAny', Budget::class);

        $company = $request->attributes->get('active_company');

        $summary = Budget::forCompany($company->id)
            ->selectRaw('
                SUM(amount) as total_budget,
                COUNT(*) as budget_count,
                AVG(amount) as average_budget
            ')
            ->first();

        return response()->json([
            'total_budget' => $summary->total_budget,
            'budget_count' => $summary->budget_count,
            'average_budget' => $summary->average_budget,
        ]);
    }

    public function store(
        StoreBudgetRequest $request,
        Project $project,
        AddBudgetAction $addBudgetAction
    ) {
        Gate::authorize('manageBudget', $project);

        $budget = $addBudgetAction->execute(
            $project,
            $request->validated()['amount']
        );

        return (new BudgetResource($budget))
            ->response()
            ->setStatusCode(201);
    }
}
