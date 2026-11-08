<?php

namespace App\Actions;

use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RespondToCompanyInvitationAction
{
    public function execute(
        User $user,
        string $token,
        bool $accept
    ): CompanyInvitation {
        $invitation = DB::transaction(function () use (
            $user,
            $token,
            $accept
        ) {
            $invitation = CompanyInvitation::where(
                'token_hash',
                hash('sha256', $token)
            )->lockForUpdate()->first();

            if (! $invitation) {
                throw ValidationException::withMessages([
                    'token' => 'La invitación no existe o el token no es válido.',
                ]);
            }

            if (strtolower($user->email) !== strtolower($invitation->email)) {
                throw ValidationException::withMessages([
                    'email' => 'Esta invitación pertenece a otro correo electrónico.',
                ]);
            }

            if ($invitation->status !== 'pending') {
                throw ValidationException::withMessages([
                    'invitation' => 'Esta invitación ya fue procesada.',
                ]);
            }

            if ($invitation->expires_at->isPast()) {
                $invitation->update([
                    'status' => 'expired',
                ]);

                return null;
            }

            if ($accept) {
                $alreadyMember = $invitation->company
                    ->users()
                    ->whereKey($user->id)
                    ->exists();

                if ($alreadyMember) {
                    throw ValidationException::withMessages([
                        'invitation' => 'Ya pertenecés a esta empresa.',
                    ]);
                }

                $invitation->company->users()->attach($user->id, [
                    'role' => $invitation->role,
                ]);

                $invitation->update([
                    'status' => 'accepted',
                ]);
            } else {
                $invitation->update([
                    'status' => 'rejected',
                ]);
            }

            return $invitation;
        });

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'invitation' => 'La invitación ha vencido.',
            ]);
        }

        return $invitation;
    }
}
