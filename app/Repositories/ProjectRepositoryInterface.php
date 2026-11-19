<?php 
namespace App\Repositories;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
interface ProjectRepositoryInterface
{
   public function getAll(int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Project;

    public function createProject(array $data):int;

    public function update(int $id, array $data): ?array;

    public function delete(int $id): bool;

    public function getDropdownProject();

}
    
?>