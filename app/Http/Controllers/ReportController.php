<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function show(Report $report)
    {
        Gate::authorize('view', $report);

        return new ReportResource($report);
    }
}
