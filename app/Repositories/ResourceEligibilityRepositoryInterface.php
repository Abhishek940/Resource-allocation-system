<?php 
namespace App\Repositories;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
interface ResourceEligibilityRepositoryInterface
{
    public function getProjects(): Collection;
    public function getProjectById(int $projectId): ?object;
    public function getActiveEmployees(): Collection;
    public function checkProjectEligibility(int $projectId): array;
    public function insertAllocation(array $data): int;
   
    public function getConfirmedAllocations(
        int $employeeId,
        string $startDate,
        string $endDate
    ): Collection;
   
 public function getAllocationById(int $id): ?object;

 public function getProjectAllocationlist():collection;

 public function deleteProjectallocation(int $id): bool;
  // Module 4
    public function getResourcesForMatching(int $projectId): array;

    // Module 4: Employee assessment and skills evidence
    public function getEmployeeEvidence(int $employeeId): array;

    // model 5
    public function skillGapAnalysis(int $projectId, int $employeeId):array;

    public function askAgent(string $prompt): ?string;
    public function checkStatus(): array;

  }


    
?>