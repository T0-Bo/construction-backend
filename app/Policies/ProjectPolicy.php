<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    private function activeCompanyId(): ?int
    {
        return request()->attributes->get('active_company')?->id;
    }

    private function hasRole(User $user, array $roles): bool
    {
        $companyId = $this->activeCompanyId();

        if ($companyId === null) {
            return false;
        }

        $role = $user->companies()
            ->whereKey($companyId)
            ->first()?->pivot?->role;

        return in_array($role, $roles, true);
    }

    private function belongsToActiveCompany(Project $project): bool
    {
        return $this->activeCompanyId() === $project->company_id;
    }

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [
            'owner',
            'admin',
            'employee',
        ]);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->belongsToActiveCompany($project)
            && $this->hasRole($user, ['owner', 'admin', 'employee']);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, ['owner', 'admin']);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->belongsToActiveCompany($project)
            && $this->hasRole($user, ['owner', 'admin']);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->belongsToActiveCompany($project)
            && $this->hasRole($user, ['owner', 'admin']);
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->belongsToActiveCompany($project)
            && $this->hasRole($user, ['owner', 'admin']);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $this->belongsToActiveCompany($project)
            && $this->hasRole($user, ['owner']);
    }

    public function manageBudget(User $user, Project $project): bool
    {
        return $this->belongsToActiveCompany($project)
            && $this->hasRole($user, ['owner', 'admin']);
    }
}
