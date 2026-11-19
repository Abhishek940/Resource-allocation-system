<?php

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
class EmployeeRepository implements EmployeeRepositoryInterface
{
    protected string $table = 'employees';

    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return Employee::query()
            ->where('deleted_at', 0)
            ->latest('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Employee
    {
        return Employee::find($id);
    }

    public function create(array $data):int
    {
        return DB::table($this->table)->insertGetId($data);
    }

    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee->refresh();
    }

    public function delete(Employee $employee): bool
    {
       // return (bool) $employee->delete();
       return (bool) $employee->update([
        'deleted_at' => 1
    ]);
    }

    public function getAllEmployees(){
        return DB:: table($this->table)->select('id', 'employeeName')->where('deleted_at','0')->get();
    }
    
}