<?php 
namespace App\Repositories;
use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
interface RoleRepositoryInterface
{
    public function getAll(int $perPage =10): LengthAwarePaginator;
    public function findById(int $id): ?Role;
    public function create(array $data):object;
    public function update(Role $role, array $data):Role;
    public function delete(Role $role):bool;
}

?>