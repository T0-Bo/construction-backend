<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'user' => $user,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $email = Str::lower($request->validated()['email']);

        $identity = sha1($email.'|'.$request->ip());

        $attemptKey = 'login:attempts:'.$identity;
        $lockoutKey = 'login:lockout:'.$identity;
        $lockoutCountKey = 'login:lockouts:'.$identity;

        // 1. Comprobar si hay un bloqueo activo.
        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            return response()->json([
                'message' => 'Demasiados intentos. Intenta nuevamente más tarde.',
                'retry_after' => RateLimiter::availableIn($lockoutKey),
            ], 429);
        }

        // 2. Buscar al usuario.
        $user = User::where('email', $email)->first();

        // 3. Verificar las credenciales.
        if (
            ! $user ||
            ! Hash::check(
                $request->validated()['password'],
                $user->password
            )
        ) {
            $attempts = RateLimiter::hit($attemptKey, 86400);

            if ($attempts >= 6) {
                $lockouts = RateLimiter::hit($lockoutCountKey, 86400);

                $duration = match (true) {
                    $lockouts === 1 => 60,
                    $lockouts === 2 => 120,
                    $lockouts === 3 => 300,
                    $lockouts === 4 => 600,
                    default => 900,
                };

                RateLimiter::hit($lockoutKey, $duration);

                // Reiniciamos los fallos para el próximo ciclo.
                RateLimiter::clear($attemptKey);

                return response()->json([
                    'message' => 'Demasiados intentos. Intenta nuevamente más tarde.',
                    'retry_after' => RateLimiter::availableIn($lockoutKey),
                ], 429);
            }

            return response()->json([
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        // 4. Si el login es correcto, reiniciar los contadores.
        RateLimiter::clear($attemptKey);
        RateLimiter::clear($lockoutKey);
        RateLimiter::clear($lockoutCountKey);

        // 5. Crear el token.
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login correcto',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Todas las sesiones se cerraron correctamente',
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        DB::transaction(function () use ($user, $request) {
            $user->update([
                'password' => $request->validated()['password'],
            ]);

            $user->tokens()->delete();
        });

        return response()->json([
            'message' => 'Contraseña actualizada. Todas las sesiones fueron cerradas.',
        ]);
    }
}
