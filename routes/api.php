<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyInvitationController;
use App\Http\Controllers\CompanyMemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'active.company'])->group(function () {

    Route::get('/projects', [ProjectController::class, 'index']);

    Route::get('/projects/{project}', [ProjectController::class, 'show']);

    Route::post('/projects', [ProjectController::class, 'store']);

    Route::patch('/projects/{project}', [ProjectController::class, 'update']);

    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    Route::patch('/projects/{project}/restore', [ProjectController::class, 'restore'])
        ->withTrashed();

    Route::delete('/projects/{project}/force', [ProjectController::class, 'forceDelete'])
        ->withTrashed();

    Route::post('/projects/{project}/report', [ProjectController::class, 'generateReport']);

});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/my-companies', [CompanyController::class, 'myCompanies']);

    Route::post('/companies/{company}/select', [CompanyController::class, 'select']);

    Route::post('/companies', [CompanyController::class, 'store']);

    Route::get('/companies/{company}', [CompanyController::class, 'show']);

});

Route::middleware(['auth:sanctum', 'verified', 'active.company'])->group(function () {

    // Invitaciones de la empresa activa
    Route::get('/company-invitations', [CompanyInvitationController::class, 'index']);

    Route::post('/company-invitations', [CompanyInvitationController::class, 'store']);

    Route::get('/company-members', [CompanyMemberController::class, 'index']);

    Route::patch('/company-members/{member}/role', [CompanyMemberController::class, 'updateRole']);

    Route::patch('/company-members/{member}/status', [CompanyMemberController::class, 'updateStatus']);

    Route::delete('/company-members/{member}', [CompanyMemberController::class, 'destroy']);

});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/company-invitations/accept', [CompanyInvitationController::class, 'accept']);
    Route::post('/company-invitations/reject', [CompanyInvitationController::class, 'reject']);

});

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:register');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::post('/logout-all', [AuthController::class, 'logoutAll'])
    ->middleware('auth:sanctum');

Route::post('/change-password', [AuthController::class, 'changePassword'])
    ->middleware('auth:sanctum');

Route::get('/me', function () {
    return Auth::user();
})->middleware(['auth:sanctum', 'verified']);

Route::middleware(['auth:sanctum', 'verified', 'active.company'])->group(function () {

    Route::get('/budgets', [BudgetController::class, 'index']);

    Route::get('/budgets/summary', [BudgetController::class, 'summary']);

    Route::get('/budgets/{budget}', [BudgetController::class, 'show']);

    Route::post('/projects/{project}/budget', [BudgetController::class, 'store']);

});

Route::middleware(['auth:sanctum', 'verified', 'active.company'])->group(function () {
    Route::get('/reports/{report}', [ReportController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/notifications', [NotificationController::class, 'index']);

    Route::get('/notifications/unread', [NotificationController::class, 'unread']);

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

});

Route::get('/email/verify/{id}/{hash}', function (
    Request $request,
    string $id,
    string $hash
) {
    $user = User::findOrFail($id);

    if (! hash_equals(
        sha1($user->getEmailForVerification()),
        $hash
    )) {
        return response()->json([
            'message' => 'El enlace de verificación no es válido.',
        ], 403);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'El correo ya estaba verificado.',
        ]);
    }

    $user->markEmailAsVerified();

    event(new Verified($user));

    return response()->json([
        'message' => 'Correo verificado correctamente.',
    ]);
})
    ->middleware('signed')
    ->name('verification.verify');
