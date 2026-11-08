<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class CompanyController extends Controller
{
    public function store(StoreCompanyRequest $request)
    {
        $company = DB::transaction(function () use ($request) {

            $company = Company::create($request->validated());

            $request->user()->companies()->attach($company->id, [
                'role' => 'owner',
            ]);

            $token = $request->user()->currentAccessToken();

            if (! $token instanceof PersonalAccessToken) {
                abort(401, 'No se pudo identificar el token de acceso.');
            }

            $token->active_company_id = $company->id;
            $token->save();

            return $company;
        });

        return (new CompanyResource($company))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Company $company)
    {
        $membership = $request->user()
            ->companies()
            ->whereKey($company->id)
            ->first();

        if (! $membership) {
            return response()->json([
                'message' => 'No tienes acceso a esta empresa.',
            ], 403);
        }

        if ($membership->pivot->status !== 'active') {
            return response()->json([
                'message' => 'Tu membresía en esta empresa está inactiva.',
            ], 403);
        }

        $company->load('projects');

        return new CompanyResource($company);
    }

    public function myCompanies(Request $request)
    {
        return CompanyResource::collection(
            $request->user()->companies
        );
    }

    public function select(Request $request, Company $company)
    {
        $user = $request->user();

        $membership = $user->companies()
            ->whereKey($company->id)
            ->first();

        if (! $membership) {
            return response()->json([
                'message' => 'No tienes acceso a esta empresa.',
            ], 403);
        }

        if ($membership->pivot->status !== 'active') {
            return response()->json([
                'message' => 'Tu membresía en esta empresa está inactiva.',
            ], 403);
        }

        $token = $user->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            abort(401, 'No se pudo identificar el token de acceso.');
        }
        $token->active_company_id = $company->id;
        $token->save();

        return response()->json([
            'message' => 'Empresa seleccionada correctamente.',
            'active_company_id' => $token->active_company_id,
        ]);
    }
}
