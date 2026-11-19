<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
class EmployeeService
{
     public function __construct(
        private EmployeeRepositoryInterface $employeeRepository
    ) {}

    public function getAllEmployees(int $perPage = 10): LengthAwarePaginator
    {
        return $this->employeeRepository->getAll($perPage);
    }

    public function getEmployeeById(int $id): Employee
    {
        $employee = $this->employeeRepository->findById($id);

        if (!$employee) {
            throw (new ModelNotFoundException())
                ->setModel(Employee::class, [$id]);
        }

        return $employee;
    }

    private function generateEmployeeCode(): string
    {
        $latestEmployee = DB::table('employees')->latest('id')->first();
        $nextId = $latestEmployee ? $latestEmployee->id + 1 : 1;
        return 'EMP' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
     
    }
    public function createEmployee(array $data): array
    {
        $data['employeeCode'] = $this->generateEmployeeCode();
        $employeeId = $this->employeeRepository->create($data);
        $employee = DB::table('employees')->find($employeeId);

        return (array) $employee;
    }

    public function updateEmployee(int $id, array $data): Employee
    {
        $employee = $this->getEmployeeById($id);

        return $this->employeeRepository->update($employee, $data);
    }

    public function deleteEmployee(int $id): bool
    {
        $employee = $this->getEmployeeById($id);

       // return $this->employeeRepository->delete($employee);
       try {
        return $this->employeeRepository->delete($employee);

    } catch (QueryException $e) {

        if (($e->errorInfo[1] ?? null) == 1451) {
            throw ValidationException::withMessages([
                'employee' => 'This employee is assigned to a project and cannot be deleted.'
            ]);
        }

        throw $e;
    }
    }

    public function getAllEmployeesList():array{
        return $this->employeeRepository->getAllEmployees()->toArray();
    }

}