<?php 

namespace App\Services;

use App\Models\Role;
use App\Repositories\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository
    ) {}

    public function getAllRoles(int $perPage = 10): LengthAwarePaginator
    {
        return $this->roleRepository->getAll($perPage);
    }

    public function getRoleById(int $id): Role
    {
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            throw (new ModelNotFoundException())
                ->setModel(Role::class, [$id]);
        }

        return $role;
    }

    public function createRole(array $data): object
    {
       return $this->roleRepository->create($data);
    }

    public function updateRole(int $id, array $data): Role
    {
        $role = $this->getRoleById($id);

        return $this->roleRepository->update($role, $data);
    }

    public function deleteRole(int $id): bool
    {
        $role = $this->getRoleById($id);

        return $this->roleRepository->delete($role);
    }
}
?>