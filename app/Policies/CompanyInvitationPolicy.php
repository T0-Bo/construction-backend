<?php

namespace App\Policies;

use App\Models\CompanyInvitation;
use App\Models\User;

class CompanyInvitationPolicy
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

    public function create(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function view(User $user, CompanyInvitation $invitation): bool
    {
        return $this->activeCompanyId() === $invitation->company_id
            && $this->isOwner($user);
    }

    public function delete(User $user, CompanyInvitation $invitation): bool
    {
        return $this->activeCompanyId() === $invitation->company_id
            && $this->isOwner($user);
    }
}
