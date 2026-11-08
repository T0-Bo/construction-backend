<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
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

    public function view(User $user, Report $report): bool
    {
        return $this->activeCompanyId() === $report->project->company_id
            && $this->hasRole($user, ['owner', 'admin', 'employee']);
    }
}
