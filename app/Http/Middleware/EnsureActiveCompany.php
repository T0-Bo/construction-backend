<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = $request->user();

        $token = $user?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            abort(401, 'No se pudo identificar el token de acceso.');
        }

        $companyId = $token->active_company_id;

        if ($companyId === null) {
            return response()->json([
                'message' => 'Debes seleccionar una empresa antes de continuar.',
            ], 409);
        }

        $membership = $user->companies()
            ->whereKey($companyId)
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

        $company = $membership;

        $request->attributes->set('active_company', $company);

        return $next($request);
    }
}
