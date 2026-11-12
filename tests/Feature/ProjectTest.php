<?php

namespace Tests\Feature;

use App\Actions\CreateProjectAction;
use App\DTOs\CreateProjectData;
use App\Models\Budget;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_unauthenticated_user_cannot_list_projects(): void
    {
        // Arrange: no autenticamos ningún usuario.

        // Act: simulamos una petición GET al endpoint.
        $response = $this->getJson('/api/projects');

        // Assert: comprobamos que rechace el acceso.
        $response->assertUnauthorized();
    }

    public function test_user_without_company_cannot_list_projects(): void
    {
        // Arrange: no autenticamos ningún usuario.
        $user = User::factory()->create([
            'name' => 'name',
            'email' => 'email@example.com',
            'password' => 'password',

        ]);

        // Act: simulamos una petición GET al endpoint.
        $this->authenticateWithoutCompany($user);

        $response = $this->getJson('/api/projects');
        // Assert: comprobamos que rechace el acceso.
        $response->assertStatus(409);
    }

    public function test_user_can_only_list_projects_from_their_company(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);
        $companyB = Company::create([
            'name' => 'Empresa B',
            'email' => 'empresa-b@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'juan',
            'email' => 'email@example.com',
            'password' => 'password',

        ]);
        $this->authenticateAsOwner($user, $companyA);

        $companyA->projects()->create([
            'name' => 'Project de juan',
            'description' => 'project creado por juan para company A',
            'status' => 'Completed',
        ]);
        $companyA->projects()->create([
            'name' => 'Project de juan2',
            'description' => 'project creado por juan para company A',
            'status' => 'Completed',
        ]);
        $companyB->projects()->create([
            'name' => 'Project de otro',
            'description' => 'project creado por otrp para company C',
            'status' => 'Completed',
        ]);
        $response = $this->getJson('/api/projects');
        // Assert: comprobamos que rechace el acceso.
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'name' => 'Project de juan',
        ]);
        $response->assertJsonMissing([
            'name' => 'Project de otro',
        ]);
    }

    public function test_user_cannot_view_project_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);
        $companyB = Company::create([
            'name' => 'Empresa B',
            'email' => 'empresa-b@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'juan',
            'email' => 'email@example.com',
            'password' => 'password',

        ]);
        $this->authenticateAsOwner($user, $companyA);
        $project = $companyB->projects()->create([
            'name' => 'Project de otro',
            'description' => 'Proyecto de Empresa B',
            'status' => 'Completed',
        ]);
        $response = $this->getJson('/api/projects/'.$project->id);
        // Assert: comprobamos que rechace el acceso.
        $response->assertForbidden();
    }

    public function test_user_cannot_update_project_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);
        $companyB = Company::create([
            'name' => 'Empresa B',
            'email' => 'empresa-b@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'juan',
            'email' => 'email@example.com',
            'password' => 'password',

        ]);
        $this->authenticateAsOwner($user, $companyA);

        $project = $companyB->projects()->create([
            'name' => 'Project de otro',
            'description' => 'Proyecto de Empresa B',
            'status' => 'Completed',
        ]);
        $response = $this->patchJson('/api/projects/'.$project->id, [
            'name' => 'Intento de modificación',
            'status' => 'In Progress',
        ]);
        // Assert: comprobamos que rechace el acceso.
        $response->assertForbidden();

        // Confirmar que el proyecto sigue intacto en la base de datos
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Project de otro',
            'status' => 'Completed',
        ]);

    }

    public function test_user_can_update_project_from_their_company(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'juan',
            'email' => 'email@example.com',
            'password' => 'password',

        ]);
        $this->authenticateAsOwner($user, $companyA);

        $project = $companyA->projects()->create([
            'name' => 'Proyecto original',
            'description' => 'project creado por juan para company A',
            'status' => 'In Progress',
        ]);

        $response = $this->patchJson('/api/projects/'.$project->id, [
            'name' => 'Proyecto actualizado',
            'status' => 'Completed',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Proyecto actualizado',
            'status' => 'Completed',
        ]);
    }

    public function test_unauthenticated_user_cannot_view_project(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $project = $companyA->projects()->create([
            'name' => 'Proyecto original',
            'description' => 'project creado por juan para company A',
            'status' => 'In Progress',
        ]);
        $response = $this->getJson('/api/projects/'.$project->id);

        $response->assertUnauthorized();
    }

    public function test_user_can_create_project_for_their_company(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $response = $this->postJson('/api/projects', [
            'name' => 'Nueva obra',
            'description' => 'Construcción de un depósito',
            'status' => 'active',
            'budget' => 2500,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('projects', [
            'name' => 'Nueva obra',
            'description' => 'Construcción de un depósito',
            'company_id' => $company->id,
        ]);

        $project = Project::where('name', 'Nueva obra')->firstOrFail();

        $this->assertDatabaseHas('budgets', [
            'project_id' => $project->id,
            'amount' => 2500,
        ]);
    }

    public function test_user_can_view_project_from_their_company(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $project = $company->projects()->create([
            'name' => 'Proyecto de Juan',
            'description' => 'Construcción de un depósito',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/projects/'.$project->id);

        $response->assertOk();

        $response->assertJsonFragment([
            'name' => 'Proyecto de Juan',
        ]);
    }

    public function test_user_without_company_cannot_create_project(): void
    {
        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateWithoutCompany($user);

        $response = $this->postJson('/api/projects', [
            'name' => 'Nueva obra',
            'description' => 'Construcción de un depósito',
            'status' => 'active',
            'budget' => 2500,
        ]);

        $response->assertStatus(409);

        $this->assertDatabaseMissing('projects', [
            'name' => 'Nueva obra',
        ]);
    }

    public function test_user_cannot_create_project_without_name(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $response = $this->postJson('/api/projects', [
            'description' => 'Construcción de un depósito',
            'status' => 'active',
            'budget' => 2500,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);

        $this->assertDatabaseMissing('projects', [
            'description' => 'Construcción de un depósito',
        ]);
    }

    public function test_user_cannot_delete_project_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $companyB = Company::create([
            'name' => 'Empresa B',
            'email' => 'empresa-b@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $companyA);

        $project = $companyB->projects()->create([
            'name' => 'Proyecto privado',
            'description' => 'Proyecto de Empresa B',
            'status' => 'active',
        ]);

        $response = $this->deleteJson('/api/projects/'.$project->id);

        $response->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Proyecto privado',
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_delete_project_from_their_company(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $project = $company->projects()->create([
            'name' => 'Proyecto para eliminar',
            'description' => 'Proyecto de prueba',
            'status' => 'active',
        ]);

        $this->deleteJson('/api/projects/'.$project->id);

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_can_restore_project_from_their_company(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $project = $company->projects()->create([
            'name' => 'Proyecto eliminado',
            'description' => 'Proyecto de prueba',
            'status' => 'active',
        ]);

        $project->delete();

        $response = $this->patchJson('/api/projects/'.$project->id.'/restore');

        $response->assertOk();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_force_delete_project_from_their_company(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $project = $company->projects()->create([
            'name' => 'Proyecto para eliminar definitivamente',
            'description' => 'Proyecto de prueba',
            'status' => 'active',
        ]);

        $project->delete();

        $response = $this->deleteJson('/api/projects/'.$project->id.'/force');

        $response->assertNoContent();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_cannot_restore_project_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $companyB = Company::create([
            'name' => 'Empresa B',
            'email' => 'empresa-b@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $companyA);

        $project = $companyB->projects()->create([
            'name' => 'Proyecto privado',
            'description' => 'Proyecto de Empresa B',
            'status' => 'active',
        ]);

        $project->delete();

        $response = $this->patchJson('/api/projects/'.$project->id.'/restore');

        $response->assertForbidden();

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_cannot_force_delete_project_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $companyB = Company::create([
            'name' => 'Empresa B',
            'email' => 'empresa-b@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $companyA);

        $project = $companyB->projects()->create([
            'name' => 'Proyecto privado',
            'description' => 'Proyecto de Empresa B',
            'status' => 'active',
        ]);

        $project->delete();

        $response = $this->deleteJson('/api/projects/'.$project->id.'/force');

        $response->assertForbidden();

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_cannot_view_nonexistent_project(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $response = $this->getJson('/api/projects/99999');

        $response->assertNotFound();
    }

    public function test_user_cannot_update_nonexistent_project(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $response = $this->patchJson('/api/projects/99999', [
            'name' => 'Proyecto actualizado',
            'status' => 'Completed',
        ]);

        $response->assertNotFound();
    }

    public function test_user_cannot_delete_nonexistent_project(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan',
            'email' => 'email@example.com',
            'password' => 'password',
        ]);

        $this->authenticateAsOwner($user, $company);

        $response = $this->deleteJson('/api/projects/99999');

        $response->assertNotFound();
    }

public function test_project_creation_is_rolled_back_if_budget_creation_fails(): void
{
    $company = Company::create([
        'name' => 'Empresa A',
        'email' => 'empresa-a@example.com',
    ]);

    // Simulamos un error al insertar un presupuesto.
    if (DB::getDriverName() === 'sqlite') {
        DB::statement("
            CREATE TRIGGER fail_budget_insert
            BEFORE INSERT ON budgets
            BEGIN
                SELECT RAISE(ABORT, 'Simulated budget failure');
            END
        ");
    } elseif (DB::getDriverName() === 'mysql') {
        DB::statement("
            CREATE TRIGGER fail_budget_insert
            BEFORE INSERT ON budgets
            FOR EACH ROW
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Simulated budget failure'
        ");
    }

    try {
        $data = new CreateProjectData(
            name: 'Proyecto de prueba',
            description: null,
            status: 'pending',
            companyId: $company->id,
            budget: '2500',
        );

        try {
            app(CreateProjectAction::class)->execute($data);

            $this->fail('Se esperaba un error al crear el presupuesto.');
        } catch (QueryException $exception) {
            // El error es esperado: la inserción del presupuesto falló.
        }

        $this->assertDatabaseMissing('projects', [
            'name' => 'Proyecto de prueba',
            'company_id' => $company->id,
        ]);

        $this->assertDatabaseCount('budgets', 0);
    } finally {
        DB::statement('DROP TRIGGER IF EXISTS fail_budget_insert');
    }
}

    public function test_project_cannot_have_two_budgets(): void
    {
        $company = Company::create([
            'name' => 'Empresa A',
            'email' => 'empresa-a@example.com',
        ]);

        $project = $company->projects()->create([
            'name' => 'Nueva obra',
            'description' => 'Construcción de un depósito',
            'status' => 'active',
        ]);

        Budget::create([
            'project_id' => $project->id,
            'amount' => 2500,
        ]);

        $this->expectException(QueryException::class);

        Budget::create([
            'project_id' => $project->id,
            'amount' => 5000,
        ]);
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

    private function authenticateWithoutCompany(User $user): void
    {
        $token = $user->createToken('test-token');

        $this->withToken($token->plainTextToken);
    }
}
