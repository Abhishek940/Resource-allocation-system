<?php
namespace App\Repositories;
interface AuthRepositoryInterface

{
    public function getUsername(string $username);

    public function getRole(int $roleId);
}

?>