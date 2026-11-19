<?php

namespace App\Repositories;
use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RoleRepository implements RoleRepositoryInterface

{

 protected string $table = 'roles';

 public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return Role::query()
            ->leftJoin('employees', 'roles.emp_id', '=', 'employees.id')
            ->select('roles.*', 'employees.employeeName as employeeName')
            ->latest('roles.id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Role
    {
        return Role::find($id);
    }

    public function create(array $data):  object
    {
        $id = DB::table($this->table)->insertGetId($data);
        
        return DB::table($this->table)->where('id', $id)->first();
    }

    

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role->refresh();
    }

    public function delete(Role $role): bool
    {
        return (bool) $role->delete();
    }



}
?>