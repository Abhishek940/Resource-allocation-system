<?php

namespace App\Repositories;
use Illuminate\Support\Facades\DB;

class AuthRepository implements AuthRepositoryInterface
{
    
    public function getUsername(string $username)
    {
        return DB::table('users')
            ->where('username', $username)
            ->first();
    }

    public function getRole(int $roleId)
    {
        return DB::table('roles')
            ->where('id', $roleId)
            ->value('roleName');
    }
}