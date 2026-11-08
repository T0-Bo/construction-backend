<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyMemberRoleRequest;
use App\Http\Requests\UpdateCompanyMemberStatusRequest;
use App\Http\Resources\CompanyMemberResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CompanyMemberController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $company = $request->attributes->get('active_company');

        return CompanyMemberResource::collection(
            $company->users()->get()
        );
    }

    public function updateRole(
        UpdateCompanyMemberRoleRequest $request,
        User $member
    ) {
        Gate::authorize('updateRole', [User::class, $member]);

        $company = $request->attributes->get('active_company');

        $company->users()->updateExistingPivot(
            $member->id,
            ['role' => $request->validated()['role']]
        );

        return response()->json([
            'message' => 'Rol del integrante actualizado correctamente.',
        ]);
    }

    public function updateStatus(
        UpdateCompanyMemberStatusRequest $request,
        User $member
    ) {
        Gate::authorize('updateStatus', [User::class, $member]);

        $company = $request->attributes->get('active_company');

        $company->users()->updateExistingPivot(
            $member->id,
            ['status' => $request->validated()['status']]
        );

        return response()->json([
            'message' => 'Estado del integrante actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, User $member)
    {
        Gate::authorize('remove', [User::class, $member]);

        $company = $request->attributes->get('active_company');

        $company->users()->detach($member->id);

        return response()->json([
            'message' => 'Integrante eliminado de la empresa correctamente.',
        ]);
    }
}
