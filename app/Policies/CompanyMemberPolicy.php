<?php

namespace App\Policies;

use App\Models\User;

class CompanyMemberPolicy
{
    private function activeCompanyId(): ?int
    {
        return request()->attributes->get('active_company')?->id;
    }

    private function isOwner(User $user): bool
    {
        $companyId = $this->activeCompanyId();

        if ($companyId === null) {
            return false;
        }

        $role = $user->companies()
            ->whereKey($companyId)
            ->first()?->pivot?->role;

        return $role === 'owner';
    }

    public function viewAny(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function updateRole(User $user, User $member): bool
    {
        $companyId = $this->activeCompanyId();

        if ($companyId === null || ! $this->isOwner($user)) {
            return false;
        }

        $membership = $member->companies()
            ->whereKey($companyId)
            ->first();

        if (! $membership) {
            return false;
        }

        return $membership->pivot->role !== 'owner';
    }

    public function updateStatus(User $user, User $member): bool
    {
        $companyId = $this->activeCompanyId();

        if ($companyId === null || ! $this->isOwner($user)) {
            return false;
        }

        $membership = $member->companies()
            ->whereKey($companyId)
            ->first();

        if (! $membership) {
            return false;
        }

        return $membership->pivot->role !== 'owner';
    }

    public function remove(User $user, User $member): bool
    {
        $companyId = $this->activeCompanyId();

        if ($companyId === null || ! $this->isOwner($user)) {
            return false;
        }

        $membership = $member->companies()
            ->whereKey($companyId)
            ->first();

        if (! $membership) {
            return false;
        }

        return $membership->pivot->role !== 'owner';
    }
}
