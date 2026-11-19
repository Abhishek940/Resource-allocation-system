<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    protected AuthRepositoryInterface $repository;

    public function __construct(AuthRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function login(array $credentials): array
    {
        $user = $this->repository->getUsername(
            $credentials['username']
        );

        if (!$user ||
            !Hash::check($credentials['password'], $user->password)
        ) {
            throw new HttpException(401, 'Invalid username or password');
        }

        if ((int) $user->status !== 1) {
            throw new HttpException(403, 'Account is inactive');
        }

        $roleName = $this->repository->getRole(
            (int) $user->roleId
        );

        if (!$roleName) {
            throw new HttpException(403, 'User role not found');
        }

        $jwtUser = User::query()->find($user->id);

        if (!$jwtUser) {
            throw new HttpException(401, 'User not found');
        }

        $token = JWTAuth::claims([
            'role_id' => (int) $user->roleId,
            'role' => $roleName,
        ])->fromUser($jwtUser);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'roleId' => (int) $user->roleId,
                'roleName' => $roleName,
            ],
        ];
    }
}