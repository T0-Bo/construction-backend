<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
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

    private function belongsToActiveCompany(Budget $budget): bool
    {
        return $this->activeCompanyId() === $budget->project->company_id;
    }

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [
            'owner',
            'admin',
            'employee',
        ]);
    }

    public function view(User $user, Budget $budget): bool
    {
        return $this->belongsToActiveCompany($budget)
            && $this->hasRole($user, ['owner', 'admin', 'employee']);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, ['owner', 'admin']);
    }

    public function update(User $user, Budget $budget): bool
    {
        return false;
    }

    public function delete(User $user, Budget $budget): bool
    {
        return false;
    }

    public function restore(User $user, Budget $budget): bool
    {
        return false;
    }

    public function forceDelete(User $user, Budget $budget): bool
    {
        return false;
    }
}
