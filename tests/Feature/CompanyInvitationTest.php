<?php

namespace Tests\Feature;

use App\Actions\RespondToCompanyInvitationAction;
use App\Actions\SendCompanyInvitationAction;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompanyInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_send_two_pending_invitations_to_same_email(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $company = Company::create([
            'name' => 'Empresa de prueba',
            'email' => 'empresa@example.com',
        ]);

        $company->users()->attach($owner->id, [
            'role' => 'owner',
            'status' => 'active',
        ]);

        $action = app(SendCompanyInvitationAction::class);

        // Act
        $action->execute(
            $company,
            'empleado@example.com',
            'employee',
            $owner->id
        );

        // Assert
        try {
            $action->execute(
                $company,
                'empleado@example.com',
                'employee',
                $owner->id
            );

            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        $this->assertDatabaseCount('company_invitations', 1);
    }

    public function test_can_send_new_invitation_when_previous_one_expired(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $company = Company::create([
            'name' => 'Empresa de prueba',
            'email' => 'empresa@example.com',
        ]);

        $company->users()->attach($owner->id, [
            'role' => 'owner',
            'status' => 'active',
        ]);

        $expiredInvitation = CompanyInvitation::create([
            'company_id' => $company->id,
            'email' => 'empleado@example.com',
            'role' => 'employee',
            'token_hash' => hash('sha256', 'token-anterior'),
            'invited_by' => $owner->id,
            'status' => 'pending',
            'expires_at' => now()->subDay(),
        ]);

        // Act
        $action = app(SendCompanyInvitationAction::class);

        $result = $action->execute(
            $company,
            'empleado@example.com',
            'employee',
            $owner->id
        );

        // Assert
        $this->assertDatabaseHas('company_invitations', [
            'id' => $expiredInvitation->id,
            'status' => 'expired',
        ]);

        $this->assertDatabaseHas('company_invitations', [
            'id' => $result['invitation']->id,
            'status' => 'pending',
            'email' => 'empleado@example.com',
        ]);

        $this->assertDatabaseCount('company_invitations', 2);
    }

    public function test_user_can_accept_invitation_and_become_company_member(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $employee = User::factory()->create([
            'email' => 'empleado@example.com',
        ]);

        $company = Company::create([
            'name' => 'Empresa de prueba',
            'email' => 'empresa@example.com',
        ]);

        $company->users()->attach($owner->id, [
            'role' => 'owner',
            'status' => 'active',
        ]);

        $invitation = CompanyInvitation::create([
            'company_id' => $company->id,
            'email' => $employee->email,
            'role' => 'employee',
            'token_hash' => hash('sha256', 'token-valido'),
            'invited_by' => $owner->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Act
        $action = app(RespondToCompanyInvitationAction::class);

        $action->execute($employee, 'token-valido', true);

        // Assert
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $employee->id,
            'role' => 'employee',
        ]);

        $this->assertDatabaseHas('company_invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_cannot_accept_invitation_for_another_email(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $employee = User::factory()->create([
            'email' => 'empleado@example.com',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'otro@example.com',
        ]);

        $company = Company::create([
            'name' => 'Empresa de prueba',
            'email' => 'empresa@example.com',
        ]);

        $company->users()->attach($owner->id, [
            'role' => 'owner',
            'status' => 'active',
        ]);

        $invitation = CompanyInvitation::create([
            'company_id' => $company->id,
            'email' => $employee->email,
            'role' => 'employee',
            'token_hash' => hash('sha256', 'token-valido'),
            'invited_by' => $owner->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Act & Assert
        try {
            app(RespondToCompanyInvitationAction::class)
                ->execute($otherUser, 'token-valido', true);

            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
        }

        $this->assertDatabaseHas('company_invitations', [
            'id' => $invitation->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('company_user', [
            'company_id' => $company->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_user_can_reject_invitation(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $employee = User::factory()->create([
            'email' => 'empleado@example.com',
        ]);

        $company = Company::create([
            'name' => 'Empresa de prueba',
            'email' => 'empresa@example.com',
        ]);

        $company->users()->attach($owner->id, [
            'role' => 'owner',
            'status' => 'active',
        ]);

        $invitation = CompanyInvitation::create([
            'company_id' => $company->id,
            'email' => $employee->email,
            'role' => 'employee',
            'token_hash' => hash('sha256', 'token-valido'),
            'invited_by' => $owner->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Act
        app(RespondToCompanyInvitationAction::class)
            ->execute($employee, 'token-valido', false);

        // Assert
        $this->assertDatabaseHas('company_invitations', [
            'id' => $invitation->id,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseMissing('company_user', [
            'company_id' => $company->id,
            'user_id' => $employee->id,
        ]);
    }
}
