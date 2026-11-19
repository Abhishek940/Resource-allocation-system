<?php 
namespace App\Repositories;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
interface EmployeeRepositoryInterface
{
   public function getAll(int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Employee;

    public function create(array $data):int;

    public function update(Employee $employee, array $data): Employee;

    public function delete(Employee $employee): bool;

    public function getAllEmployees();

}
    
?>