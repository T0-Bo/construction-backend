<?php

namespace App\Http\Controllers;

use App\Actions\CreateProjectAction;
use App\Actions\RequestReportAction;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Mappers\ProjectMapper;
use App\Models\Project;
use App\Services\AuditLogService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Project::class);

        $company = $request->attributes->get('active_company');

        return ProjectResource::collection(
            Project::with('company')
                ->where('company_id', $company->id)
                ->get()
        );
    }

    public function store(
        StoreProjectRequest $request,
        CreateProjectAction $createProjectAction,
        ProjectMapper $projectMapper
    ) {
        Gate::authorize('create', Project::class);

        $company = $request->attributes->get('active_company');

        $projectData = $projectMapper->toCreateData(
            $request->validated(),
            $company->id
        );

        $project = $createProjectAction->execute(
            $projectData
        );

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load([
            'company',
            'budget',
        ]);

        return new ProjectResource($project);
    }

    public function update(
        UpdateProjectRequest $request,
        Project $project,
        ProjectService $projectService,
        ProjectMapper $projectMapper,
        AuditLogService $auditLogService
    ) {
        Gate::authorize('update', $project);

        $projectData = $projectMapper->toUpdateData(
            $request->validated()
        );

        $project = $projectService->update(
            $project,
            $projectData,
            $auditLogService
        );

        return new ProjectResource($project);
    }

    public function destroy(
        Project $project,
        ProjectService $projectService
    ) {
        Gate::authorize('delete', $project);

        $projectService->delete($project);

        return response()->noContent();
    }

    public function restore(
        Project $project,
        ProjectService $projectService
    ) {
        Gate::authorize('restore', $project);

        $project = $projectService->restore($project);

        return new ProjectResource($project);
    }

    public function forceDelete(
        Project $project,
        ProjectService $projectService
    ) {
        Gate::authorize('forceDelete', $project);

        $projectService->forceDelete($project);

        return response()->noContent();
    }

    public function generateReport(
        Project $project,
        RequestReportAction $requestReportAction,
        Request $request
    ) {
        Gate::authorize('view', $project);

        $user = $request->user();

        $report = $requestReportAction->execute($project, $user);

        return response()->json([
            'message' => 'Reporte solicitado correctamente.',
            'report_id' => $report->id,
            'status' => $report->status,
        ], 202);
    }
}
