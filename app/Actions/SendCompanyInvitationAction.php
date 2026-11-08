<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\CompanyInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SendCompanyInvitationAction
{
    public function execute(
        Company $company,
        string $email,
        string $role,
        int $invitedBy
    ): array {
        return DB::transaction(function () use (
            $company,
            $email,
            $role,
            $invitedBy
        ) {
            $email = strtolower(trim($email));

            $existingUser = $company->users()
                ->where('users.email', $email)
                ->exists();

            if ($existingUser) {
                throw ValidationException::withMessages([
                    'email' => 'Este usuario ya pertenece a la empresa.',
                ]);
            }
            $company->invitations()
                ->where('email', $email)
                ->where('status', 'pending')
                ->where('expires_at', '<=', now())
                ->update([
                    'status' => 'expired',
                ]);
            $pendingInvitation = $company->invitations()
                ->where('email', $email)
                ->where('status', 'pending')
                ->exists();

            if ($pendingInvitation) {
                throw ValidationException::withMessages([
                    'email' => 'Ya existe una invitación pendiente para este correo.',
                ]);
            }

            $token = Str::random(64);

            $invitation = CompanyInvitation::create([
                'company_id' => $company->id,
                'email' => $email,
                'role' => $role,
                'token_hash' => hash('sha256', $token),
                'invited_by' => $invitedBy,
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
            ]);

            return [
                'invitation' => $invitation,
                'token' => $token,
            ];
        });
    }
}
