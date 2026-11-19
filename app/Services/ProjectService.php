<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
class ProjectService
{
     public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {}

    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return $this->projectRepository->getAll($perPage);
    }

    public function getProjectById(int $id): Project
    {
        $project = $this->projectRepository->findById($id);

        if (!$project) {
            throw (new ModelNotFoundException())
                ->setModel(Project::class, [$id]);
        }

        return $project;
    }

    private function generateProjectCode(): string
    {
        $latestProject = DB::table('projects')->latest('id')->first();
        $nextId = $latestProject ? $latestProject->id + 1 : 1;
        return 'PROJ' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
     
    }
    public function createProject(array $data): array
    {
        $data['projectCode'] = $this->generateProjectCode();
        $projectId = $this->projectRepository->createProject($data);
        $project = DB::table('projects')->find($projectId);

        return (array) $project;
    }

    public function updateProject(int $id, array $data): ?array
    {
        return $this->projectRepository->update($id, $data);
    }

    public function deleteProject(int $id): bool
    {
         return $this->projectRepository->delete($id);
    }

     public function getDropdownProject():array{
        return $this->projectRepository->getDropdownProject()->toArray();
    }
   
}