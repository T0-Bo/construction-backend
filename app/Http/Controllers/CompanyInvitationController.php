<?php

namespace App\Http\Controllers;

use App\Actions\RespondToCompanyInvitationAction;
use App\Actions\SendCompanyInvitationAction;
use App\Http\Requests\RespondToCompanyInvitationRequest;
use App\Http\Requests\StoreCompanyInvitationRequest;
use App\Http\Resources\CompanyInvitationResource;
use App\Models\CompanyInvitation;
use App\Notifications\CompanyInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class CompanyInvitationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', CompanyInvitation::class);

        $company = $request->attributes->get('active_company');

        return CompanyInvitationResource::collection(
            $company->invitations()
                ->latest()
                ->get()
        );
    }

    public function store(
        StoreCompanyInvitationRequest $request,
        SendCompanyInvitationAction $sendInvitationAction
    ) {
        Gate::authorize('create', CompanyInvitation::class);

        $company = $request->attributes->get('active_company');

        $result = $sendInvitationAction->execute(
            $company,
            $request->validated()['email'],
            $request->validated()['role'],
            $request->user()->id
        );
        Notification::route('mail', $result['invitation']->email)
            ->notify(
                new CompanyInvitationNotification(
                    $result['invitation'],
                    $result['token']
                )
            );

        return response()->json([
            'message' => 'Invitación creada correctamente.',
            'data' => new CompanyInvitationResource(
                $result['invitation']
            ),
        ], 201);
    }

    public function accept(
        RespondToCompanyInvitationRequest $request,
        RespondToCompanyInvitationAction $action
    ) {
        $invitation = $action->execute(
            $request->user(),
            $request->validated()['token'],
            true
        );

        return response()->json([
            'message' => 'Te uniste a la empresa correctamente.',
            'data' => new CompanyInvitationResource($invitation),
        ]);
    }

    public function reject(
        RespondToCompanyInvitationRequest $request,
        RespondToCompanyInvitationAction $action
    ) {
        $invitation = $action->execute(
            $request->user(),
            $request->validated()['token'],
            false
        );

        return response()->json([
            'message' => 'Rechazaste la invitación.',
            'data' => new CompanyInvitationResource($invitation),
        ]);
    }
}
