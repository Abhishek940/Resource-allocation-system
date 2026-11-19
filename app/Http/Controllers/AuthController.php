<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\AuthService;
class AuthController extends Controller
{
     protected AuthService $authService;
   
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->authService->login($validated);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $result,
            ], 200);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }

    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
