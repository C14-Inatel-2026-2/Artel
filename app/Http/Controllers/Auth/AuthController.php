<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return $this->tokenResponse($user, 'api', 'Usuário cadastrado com sucesso.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => 'E-mail ou senha inválidos.'], 401);
        }

        $deviceName = $request->validated('device_name') ?? 'api';

        return $this->tokenResponse($user, $deviceName, 'Login realizado com sucesso.', 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.'], 200);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user(), 200);
    }

    private function tokenResponse(User $user, string $tokenName, string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'user' => $user,
            'token' => $user->createToken($tokenName)->plainTextToken,
            'token_type' => 'Bearer',
        ], $status);
    }
}
