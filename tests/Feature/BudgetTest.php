<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_a_second_budget_returns_conflict(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'juan@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $project = $company->projects()->create([
            'name' => 'Nueva obra',
            'description' => 'Construcción de un depósito',
            'status' => 'active',
        ]);

        Budget::create([
            'project_id' => $project->id,
            'amount' => 2500,
        ]);

        $response = $this
            ->postJson("/api/projects/{$project->id}/budget", [
                'amount' => 5000,
            ]);

        $response->assertConflict()
            ->assertJson([
                'message' => 'El proyecto ya tiene un presupuesto.',
            ]);

        $this->assertDatabaseCount('budgets', 1);
    }

    public function test_access_denied_exception_returns_custom_json_message(): void
    {
        Route::get(
            '/testing/access-denied',
            function () {
                throw new AccessDeniedHttpException;
            }
        );

        $response = $this->getJson('/testing/access-denied');

        $response->assertForbidden()
            ->assertExactJson([
                'message' => 'No tienes permiso para realizar esta acción.',
            ]);
    }

    public function test_unverified_email_returns_custom_json_message(): void
    {
        Route::get(
            '/api/testing/unverified',
            function () {
                abort(403, 'Your email address is not verified.');
            }
        );

        $response = $this->getJson('/api/testing/unverified');

        $response->assertForbidden()
            ->assertExactJson([
                'message' => 'Debés verificar tu correo electrónico para acceder.',
            ]);
    }

    public function test_throttle_exception_returns_custom_json_message(): void
    {
        Route::get(
            '/api/testing/throttled',
            function () {
                throw new ThrottleRequestsException(
                    'Too many requests.',
                    null,
                    ['Retry-After' => '30'],
                    0
                );
            }
        );

        $response = $this->getJson('/api/testing/throttled');

        $response->assertStatus(429)
            ->assertExactJson([
                'message' => 'Has realizado demasiadas solicitudes en poco tiempo. Espera un momento antes de volver a intentarlo.',
                'retry_after' => 30,
            ]);
    }

    public function test_user_cannot_create_budget_for_project_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $companyB = Company::create([
            'name' => 'Empresa B',
            'email' => 'empresa-b@example.com',
        ]);

        $user = User::factory()->create();

        $this->authenticateAsOwner($user, $companyA);

        $project = $companyB->projects()->create([
            'name' => 'Obra de otra empresa',
            'description' => 'Proyecto privado',
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/projects/{$project->id}/budget", [
            'amount' => 5000,
        ]);
        $response->assertForbidden();

        $this->assertDatabaseCount('budgets', 0);
    }

    private function authenticateAsOwner(User $user, Company $company): void
    {
        $user->companies()->attach($company->id, [
            'role' => 'owner',
            'status' => 'active',
        ]);

        $token = $user->createToken('test-token');

        $token->accessToken->forceFill([
            'active_company_id' => $company->id,
        ])->save();

        $this->withToken($token->plainTextToken);
    }
}
