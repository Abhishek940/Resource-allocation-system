<?php

namespace App\Repositories;

use App\Repositories\ResourceEligibilityRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Override;

class ResourceEligibilityRepository implements ResourceEligibilityRepositoryInterface
{

    public function getProjects(): Collection
    {
        return DB::table('projects')
            ->where('deleted_at', 0)
            ->orderBy('projectName', 'asc')
            ->get();
    }

    public function getProjectById(int $projectId): ?object
    {
        return DB::table('projects')
            ->where('id', $projectId)
            ->where('deleted_at', 0)
            ->first();
    }

    public function getActiveEmployees(): Collection
    {
        return DB::table('employees')
            //->where('deleted_at', 0)
            //->whereNull('deleted_at')
             ->where(function ($query) {
            $query->whereNull('deleted_at')
             ->orWhere('deleted_at', 0);
        })
        ->get();
        
    }

    public function insertAllocation(array $data): int
    {
        return DB::table('employee_project_allocations')
            ->insertGetId([
                'employee_id' => $data['employee_id'],
                'project_id' => $data['project_id'],
                'allocation_status' => $data['allocation_status'],
                'allocation_start' => $data['allocation_start'],
                'allocation_end' => $data['allocation_end'],
                'allocation_percentage' => $data['allocation_percentage'],
            ]);
    }

   public function getProjectAllocationlist(): Collection
{
    return DB::table('employee_project_allocations as allocations')
        ->select(
            'allocations.id as allocationId',
            'allocations.employee_id as employeeId',
            'allocations.project_id as projectId',
            'employee.employeeCode',
            'employee.employeeName',
            'projects.projectCode',
            'projects.projectName',
            'allocations.allocation_start as startDate',
            'allocations.allocation_end as endDate',
            'allocations.allocation_percentage as allocationPercentage',
            'allocations.allocation_status as allocationStatus',
            'allocations.deleted_at'
        )
        ->join(
            'employees as employee',
            'employee.id',
            '=',
            'allocations.employee_id'
        )
        ->join(
            'projects',
            'projects.id',
            '=',
            'allocations.project_id'
        )
        ->where('allocations.deleted_at', 0)
        ->where('employee.deleted_at', 0)
        ->where('projects.deleted_at', 0)
        ->get();
}

    public function deleteProjectallocation(int $id): bool{

      return DB::table('employee_project_allocations')->where('id',$id)
             ->update([
                 'deleted_at' => 1,
                 'updated_at' => now(),
             ]);

    }

          
public function getConfirmedAllocations(
    int $employeeId,
    string $startDate,
    string $endDate
): Collection {

    return DB::table('employee_project_allocations as epa')
        ->leftJoin('projects as p', 'p.id', '=', 'epa.project_id')
        ->where('epa.employee_id', $employeeId)
        ->where('epa.deleted_at', 0)
        ->where('epa.allocation_status', 1)
        ->where('p.deleted_at', 0)
        ->whereDate('epa.allocation_start', '<=', $endDate)
        ->whereDate('epa.allocation_end', '>=', $startDate)
        ->select(
            'epa.id',
            'epa.employee_id',
            'epa.project_id',
            'p.projectCode as conflictingProjectCode',
            'p.projectName as conflictingProjectName',
            'epa.allocation_start',
            'epa.allocation_end',
            'epa.allocation_percentage',
            'epa.allocation_status'
        )
        ->get();
}

    public function getAllocationById(int $id): ?object
    {
        return DB::table('employee_project_allocations')
            ->where('id', $id)
            ->first();
    }

    private function getProjectEndDate(Carbon $start, string $duration): Carbon
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*year/i', $duration, $match)) {
            return $start->copy()->addMonths((int) round((float) $match[1] * 12))
                ->subDay()->startOfDay();
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*month/i', $duration, $match)) {
            return $start->copy()->addMonths((int) $match[1])
                ->subDay()->startOfDay();
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*day/i', $duration, $match)) {
            return $start->copy()->addDays((int) $match[1] - 1)
                ->startOfDay();
        }

        if (is_numeric(trim($duration)) && (int) $duration > 0) {
            return $start->copy()->addDays((int) $duration - 1)
                ->startOfDay();
        }

        throw new \InvalidArgumentException(
            'Project duration must be a valid number of days, months, or years.'
        );
    }


   private function normalizeSkills($value): array
{
    if (empty($value)) {
        return [];
    }

    if (is_array($value)) {
        $skills = $value;
    } else {
        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        // Check whether skills are stored as JSON
        $decoded = json_decode($value, true);

        $skills = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
            ? $decoded
            : explode(',', $value);
    }

    return array_values(array_unique(array_filter(
        array_map(
            fn($skill) => strtolower(trim((string) $skill)),
            $skills
        ),
        fn($skill) => $skill !== ''
    )));
}
    /**
     * Match employee primary, secondary, and tertiary skills
     * against project mandatory skills only.
     */
   private function matchEmployeeSkills(object $employee, object $project): array
{
    // Get all employee skills
    $employeeSkills = array_values(array_unique(array_merge(
        $this->normalizeSkills($employee->primaryskill ?? ''),
        $this->normalizeSkills($employee->secondarySkills ?? ''),
        $this->normalizeSkills($employee->tertiarySkills ?? '')
    )));

    // Get project mandatory skills
    $mandatorySkills = $this->normalizeSkills(
        $project->mandatorySkills ?? ''
    );

    // Match mandatory skills
    $matchedMandatory = array_values(
        array_intersect($mandatorySkills, $employeeSkills)
    );

    $missingMandatory = array_values(
        array_diff($mandatorySkills, $employeeSkills)
    );

    $mandatoryCount = count($mandatorySkills);

    // All mandatory skills must match
    $skillEligible = empty($missingMandatory);

    // Calculate matching percentage
    $matchPercentage = $mandatoryCount > 0
        ? round((count($matchedMandatory) / $mandatoryCount) * 100, 2)
        : 100;

    return [
        'skillEligible' => $skillEligible,
        'mandatoryMatchPercentage' => $matchPercentage,
        'employeeSkills' => $employeeSkills,
        'mandatorySkills' => $mandatorySkills,
        'matchedMandatorySkills' => $matchedMandatory,
        'missingMandatorySkills' => $missingMandatory,
        'skillReason' => $mandatoryCount === 0
            ? 'No mandatory skills configured for the project.'
            : (
                $skillEligible
                    ? 'All mandatory skills matched.'
                    : (
                        !empty($matchedMandatory)
                            ? 'Matched: ' . implode(', ', $matchedMandatory)
                                . '. Missing: ' . implode(', ', $missingMandatory)
                            : 'No mandatory skills matched. Missing: '
                                . implode(', ', $missingMandatory)
                    )
            ),
    ];
}

    private function checkExperienceAndProficiency(object $employee,
        object $project
    ): array {
        $employeeExperience = (float) ($employee->experience ?? 0);

        preg_match(
            '/\d+(?:\.\d+)?/',
            (string) ($project->minExperience ?? '0'),
            $experienceMatch
        );

        $requiredExperience = isset($experienceMatch[0])
            ? (float) $experienceMatch[0]
            : 0;

        // Both values are read dynamically from the database.
        $employeeProficiency = (int) ($employee->proficiency ?? 0);
        $requiredProficiency = (int) ($project->minProficiency ?? 0);

        return [
            'employeeExperience' => $employeeExperience,
            'requiredExperience' => $requiredExperience,
            'experienceEligible' =>
            $employeeExperience >= $requiredExperience,

            'employeeProficiency' => $employeeProficiency,
            'requiredProficiency' => $requiredProficiency,
            'proficiencyEligible' =>
            $employeeProficiency >= $requiredProficiency,
        ];
    }


  


public function checkProjectEligibility(int $projectId): array
{
    // ==========================================
    // 1. GET PROJECT DETAILS
    // ==========================================

    $project = $this->getProjectById($projectId);

    if (!$project) {
        throw new \InvalidArgumentException('Project not found.');
    }

    if (empty($project->startfrom) || empty($project->projectDuration)) {
        throw new \InvalidArgumentException(
            'Project joining date and duration are required.'
        );
    }

    $start = Carbon::parse($project->startfrom)->startOfDay();

    $end = $this->getProjectEndDate(
        $start,
        (string) $project->projectDuration
    );

    $eligible = [];
    $partial = [];
    $future = [];
    $conflicts = [];
    $excluded = [];

    // Normalize location:
    $normalizeLocation = function ($location): string {
        return strtolower(
            trim(explode(',', (string) $location)[0])
        );
    };

    // Normalize skill values into arrays.
    // Supports arrays, JSON strings and comma-separated strings.
    $normalizeSkillList = function ($skills): array {
        if ($skills instanceof \Illuminate\Support\Collection) {
            $skills = $skills->all();
        }

        if (is_string($skills)) {
            $decoded = json_decode($skills, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $skills = $decoded;
            } else {
                $skills = explode(',', $skills);
            }
        }

        if (!is_array($skills)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    fn ($skill) => trim((string) $skill),
                    $skills
                ),
                fn ($skill) => $skill !== ''
            )
        );
    };

    // ==========================================
    // 3. GET ACTIVE EMPLOYEES
    // ==========================================

    $employees = $this->getActiveEmployees();
    foreach ($employees as $employee) {

        $reasons = [];

        // ==========================================
        // 4. resource status
        // ==========================================

        $status = strtolower(
            trim((string) ($employee->resourceStatus ?? ''))
        );

        $isBench = $status === 'bench';

        $isAvailable = $status === 'available' || $isBench;

        $isAllocated = $status === 'allocated';

        $isPartiallyAvailable = $status === 'partially available';

        $isUnavailable = $status === 'unavailable';

        $validStatus =
            $isAvailable ||
            $isAllocated ||
            $isPartiallyAvailable;

        $resourceAvailability = trim(
            (string) ($employee->resourceStatus ?? '')
        );

        if ($resourceAvailability === '') {
            $resourceAvailability = 'Not specified';
        }

        $statusMatch = $validStatus && !$isUnavailable;

        if (!$statusMatch) {
            $reasons[] = $isUnavailable
                ? 'Employee is unavailable.'
                : 'Invalid or unspecified resource status: '
                    . $resourceAvailability;
        }

        if ($isPartiallyAvailable) {
            $reasons[] =
                'Employee is partially available. Resource status is Partially Available.';
        }

        // ==========================================
        // mandatory skill matching
        // ==========================================

        $skillMatch = $this->matchEmployeeSkills(
            $employee,
            $project
        );

        $matchedSkills = $normalizeSkillList(
            $skillMatch['matchedMandatorySkills'] ?? []
        );

        $missingSkills = $normalizeSkillList(
            $skillMatch['missingMandatorySkills'] ?? []
        );

        $mandatorySkills = $normalizeSkillList(
            $skillMatch['mandatorySkills'] ?? []
        );

        $skillEligible = (bool) (
            $skillMatch['skillEligible'] ?? false
        );

        $mandatoryMatchPercentage = (float) (
            $skillMatch['mandatoryMatchPercentage'] ?? 0
        );

        $matchedCount = count($matchedSkills);
        $missingCount = count($missingSkills);
        $mandatoryCount = count($mandatorySkills);

        // At least one mandatory skill must match.
        $hasAtLeastOneMatchingSkill = $matchedCount > 0;

        // Some mandatory skills match, but others are missing.
        $hasPartialSkills =
            $hasAtLeastOneMatchingSkill &&
            $missingCount > 0;

        // No mandatory skill matches.
        $hasNoSkills = !$hasAtLeastOneMatchingSkill;

        // Exclude employees with zero matching mandatory skills.
        // They will not appear in any classification category.
        if ($hasNoSkills) {
            continue;
        }

        // Add the partial skill reason only once.
        if ($hasPartialSkills) {
            $reasons[] =
                'Partial Skill Match: Matched '
                . $matchedCount
                . ' out of '
                . $mandatoryCount
                . ' mandatory technologies. Matched skills: '
                . implode(', ', $matchedSkills)
                . '. Missing skills: '
                . implode(', ', $missingSkills)
                . '.';
        }

        // ==========================================
        // 6. experience
        // ==========================================

        $experienceMatch = $this->checkExperienceAndProficiency(
            $employee,
            $project
        );

        $experienceEligible = (bool) (
            $experienceMatch['experienceEligible'] ?? false
        );

        $proficiencyEligible = (bool) (
            $experienceMatch['proficiencyEligible'] ?? false
        );

        if (!$experienceEligible) {
            $reasons[] = 'Minimum experience requirement not met.';
        }

        if (!$proficiencyEligible) {
            $reasons[] = 'Minimum proficiency requirement not met.';
        }

        // ==========================================
        // 7. availibilty
        // ==========================================

        $availableFrom = !empty($employee->availableFrom)
            ? Carbon::parse($employee->availableFrom)->startOfDay()
            : null;

        $availableAtStart = false;
        $availableDuringProject = false;
        $futureAvailability = false;
        $availableAfterProject = false;
        $availabilityMatch = true;

        if ($availableFrom === null) {

            $availabilityMatch = false;

            $reasons[] = 'Employee availability date is not found.';

        } elseif ($availableFrom->gt($end)) {

            // Employee becomes available after project completion.

            $futureAvailability = true;
            $availableAfterProject = true;
            $availableDuringProject = false;

            $reasons[] =
                'Available later: Employee will become available from '
                . $availableFrom->toDateString()
                . ', after the project end date '
                . $end->toDateString() . '.';

        } elseif ($availableFrom->gt($start)) {

            // Employee becomes available during the project.

            $futureAvailability = true;
            $availableDuringProject = true;

            $reasons[] =
                'Available later: Employee will become available from '
                . $availableFrom->toDateString()
                . ', after the project start date '
                . $start->toDateString() . '.';

        } else {

            $availableAtStart = true;
            $availableDuringProject = true;
        }

        // ==========================================
        // 8. location matching
        // ==========================================

        $projectLocation = $normalizeLocation(
            $project->location ?? ''
        );

        $employeeLocation = $normalizeLocation(
            $employee->location ?? ''
        );

        $locationMatch =
            empty($projectLocation) ||
            (
                !empty($employeeLocation) &&
                $projectLocation === $employeeLocation
            );

        if (!$locationMatch) {
            $reasons[] =
                'Employee location does not match project location.';
        }

        // ==========================================
        // 9. existing project allocations
        // ==========================================

        $allocations = $this->getConfirmedAllocations(
            (int) $employee->id,
            $start->toDateString(),
            $end->toDateString()
        );

        // Remove allocations belonging to the current project.
        $allocations = $allocations
            ->filter(function ($allocation) use ($projectId) {
                return (int) $allocation->project_id !== $projectId;
            })
            ->values();

        // ==========================================
        // 10. calculate remaining capacity
        // ==========================================

        $maxCommitted = 0.0;

        $capacityCheckStart = (
            $availableFrom !== null &&
            $availableFrom->gt($start)
        )
            ? $availableFrom->copy()
            : $start->copy();

        if ($capacityCheckStart->lte($end)) {

            for (
                $day = $capacityCheckStart->copy();
                $day->lte($end);
                $day->addDay()
            ) {
                $dailyCommitted = 0.0;

                foreach ($allocations as $allocation) {

                    $allocationStart = Carbon::parse(
                        $allocation->allocation_start
                    )->startOfDay();

                    $allocationEnd = Carbon::parse(
                        $allocation->allocation_end
                    )->startOfDay();

                    if (
                        $day->between(
                            $allocationStart,
                            $allocationEnd,
                            true
                        )
                    ) {
                        $dailyCommitted += (float)
                            $allocation->allocation_percentage;
                    }
                }

                $maxCommitted = max(
                    $maxCommitted,
                    $dailyCommitted
                );
            }
        }

        $remainingCapacity = max(
            0,
            100 - $maxCommitted
        );

        // ==========================================
        // 11. allocation details
        // ==========================================

        $allocationDetails = $allocations->map(
            function ($allocation) {
                return [
                    'projectId' => $allocation->project_id,
                    'projectCode' =>
                        $allocation->conflictingProjectCode ?? null,
                    'projectName' =>
                        $allocation->conflictingProjectName ?? null,
                    'allocationStart' =>
                        $allocation->allocation_start,
                    'allocationEnd' =>
                        $allocation->allocation_end,
                    'allocationPercentage' =>
                        (float) $allocation->allocation_percentage,
                    'allocationStatus' =>
                        $allocation->allocation_status,
                ];
            }
        )->values()->all();

        // ==========================================
        // 12. employee assessment evidence
        // ==========================================

        $evidence = $this->getEmployeeEvidence(
            (int) $employee->id
        );

        $assessmentScore = min(
            100,
            max(0, (float) ($evidence['assessmentScore'] ?? 0))
        );

        // ==========================================
        // 13. score calculation
        // ==========================================

        $skillScore = min(
            100,
            max(0, $mandatoryMatchPercentage)
        );

        $proficiencyScore = $proficiencyEligible ? 100.0 : 0.0;
        $experienceScore = $experienceEligible ? 100.0 : 0.0;
        $availabilityScore = min(
            100,
            max(0, (float) $remainingCapacity)
        );

        $suitabilityScore = round(
            ($skillScore * 0.40) +
            ($proficiencyScore * 0.25) +
            ($experienceScore * 0.15) +
            ($assessmentScore * 0.10) +
            ($availabilityScore * 0.10),
            2
        );

        // ==========================================
        // 14. employee result
        // ==========================================

        $result = [
            'employeeId' => $employee->id,
            'employeeCode' => $employee->employeeCode ?? null,
            'employeeName' => $employee->employeeName ?? null,
            'employeeDesignation' => $employee->designation ?? null,
            'resourceStatus' => $resourceAvailability,
            'isBench' => $isBench,
            'location' => $employee->location ?? null,
            'availableFrom' => $employee->availableFrom ?? null,
            'projectStartDate' => $start->toDateString(),
            'projectEndDate' => $end->toDateString(),
            'availableAtProjectStart' => $availableAtStart,
            'availableDuringProject' => $availableDuringProject,
            'availableAfterProject' => $availableAfterProject,
            'futureAvailability' => $futureAvailability,
            'availabilityCategory' => $availableAfterProject
                ? 'Available After Project'
                : (
                    $futureAvailability
                        ? 'Future Availability'
                        : 'Available At Project Start'
                ),

            'committedCapacity' => round($maxCommitted, 2),
            'remainingCapacity' => round($remainingCapacity, 2),

            'conflictingAllocations' => $allocationDetails,
            'allocatedProjects' => $allocationDetails,

            // Skill matching
            'skillEligible' => $skillEligible,
            'hasAtLeastOneMatchingSkill' =>
                $hasAtLeastOneMatchingSkill,
            'hasPartialSkills' => $hasPartialSkills,

            'mandatoryMatchPercentage' =>
                $mandatoryMatchPercentage,

            'employeeSkills' =>
                $skillMatch['employeeSkills'] ?? [],

            'mandatorySkills' => $mandatorySkills,
            'matchedMandatorySkills' => $matchedSkills,
            'missingMandatorySkills' => $missingSkills,

            'skillReason' => $skillMatch['skillReason'] ?? null,

            // Experience
            'employeeExperience' =>
                $experienceMatch['employeeExperience'] ?? 0,

            'requiredExperience' =>
                $experienceMatch['requiredExperience'] ?? 0,

            'experienceEligible' => $experienceEligible,

            // Proficiency
            'employeeProficiency' =>
                $experienceMatch['employeeProficiency'] ?? 0,

            'requiredProficiency' =>
                $experienceMatch['requiredProficiency'] ?? 0,

            'proficiencyEligible' => $proficiencyEligible,

            // Assessment
            'assessmentScore' => $assessmentScore,
            'certifications' => $evidence['certifications'] ?? null,
            'projectExperience' => $evidence['projectExperience'] ?? null,

            // Suitability
            'suitabilityScore' => $suitabilityScore,

            'score' => [
                'skillScore' => round($skillScore, 2),
                'proficiencyScore' => round($proficiencyScore, 2),
                'experienceScore' => round($experienceScore, 2),
                'assessmentScore' => round($assessmentScore, 2),
                'availabilityScore' => round($availabilityScore, 2),
            ],

            'recommendationExplanation' =>
                'Mandatory skills matched: '
                . round($skillScore, 2) . '%.'
                . ' Experience requirement: '
                . ($experienceEligible ? 'Met.' : 'Not met.')
                . ' Proficiency requirement: '
                . ($proficiencyEligible ? 'Met.' : 'Not met.')
                . ' Assessment score: '
                . round($assessmentScore, 2) . '/100.'
                . ' Remaining capacity: '
                . round($remainingCapacity, 2) . '%.'
                . ' Suitability score: '
                . $suitabilityScore . '/100.',

            'reasons' => $reasons,
        ];

        // ==========================================
        // 15. CLASSIFICATION
        // ==========================================

        // Hard eligibility checks.
        $hardFailure =
            !$statusMatch ||
            !$experienceEligible ||
            !$proficiencyEligible ||
            !$availabilityMatch ||
            !$locationMatch ||
            $availableFrom === null;

        // Category 1: Excluded due to hard eligibility failure.
        if ($hardFailure) {
            $excluded[] = $result;
            continue;
        }

        // Category 2: Existing overlapping project allocations.
        if ($allocations->isNotEmpty()) {

            $result['reasons'][] =
                'Employee has existing overlapping project allocations.';

            $conflicts[] = $result;
            continue;
        }

        // Category 3: Employee becomes available later.
        if ($futureAvailability) {
            $future[] = $result;
            continue;
        }

        // Category 4: Partial skill match or partial availability.
        if ($hasPartialSkills || $isPartiallyAvailable) {

            if ($isPartiallyAvailable) {
                $result['reasons'][] =
                    'Employee has limited availability because resource status is Partially Available.';
            }

            $partial[] = $result;
            continue;
        }

        // Category 5: Fully eligible employee.
        $minimumRequiredCapacity = 50.0;

        if ($remainingCapacity >= $minimumRequiredCapacity) {

            $eligible[] = $result;

        } else {

            $result['reasons'][] =
                'Remaining capacity is below 50%.';

            $excluded[] = $result;
        }
    }

    // ==========================================
    // 16. SORT EMPLOYEES BY SUITABILITY SCORE
    // ==========================================

    $sortByScore = function (&$employees) {

        usort(
            $employees,
            fn ($a, $b) =>
                ($b['suitabilityScore'] ?? 0)
                <=>
                ($a['suitabilityScore'] ?? 0)
        );

        foreach ($employees as $index => &$employee) {
            $employee['rank'] = $index + 1;
        }

        unset($employee);
    };

    $sortByScore($eligible);
    $sortByScore($partial);
    $sortByScore($future);
    $sortByScore($conflicts);
    $sortByScore($excluded);

    // ==========================================
    // 17. SUMMARY
    // ==========================================

    $totalEmployees =
        count($eligible) +
        count($partial) +
        count($future) +
        count($conflicts) +
        count($excluded);

    // ==========================================
    // 18. RETURN RESPONSE
    // ==========================================

    return [
        'project' => [
            'id' => $project->id,
            'projectCode' => $project->projectCode ?? null,
            'projectName' => $project->projectName ?? null,
            'location' => $project->location ?? null,
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'mandatorySkills' => $project->mandatorySkills ?? null,
            'projectDuration' => $project->projectDuration ?? null,
            'numberOfResources' =>
                (int) ($project->numberOfResources ?? 1),
        ],

        'summary' => [
            'totalEmployees' => $totalEmployees,
            'eligibleCount' => count($eligible),
            'partialCount' => count($partial),
            'futureCount' => count($future),
            'conflictCount' => count($conflicts),
            'excludedCount' => count($excluded),
        ],

        'eligible' => $eligible,
        'partialAvailability' => $partial,
        'futureAvailability' => $future,
        'conflicts' => $conflicts,
        'excluded' => $excluded,
    ];
}



    // Module 4: Get eligible resources
   public function getResourcesForMatching(int $projectId): array
{
    $eligibility = $this->checkProjectEligibility($projectId);

    return [
        'success' => true,
        'summary' => $eligibility['summary'],

        'eligible' => $eligibility['eligible'],

        'partialAvailability' =>
            $eligibility['partialAvailability'],

        'futureAvailability' =>
            $eligibility['futureAvailability'],

        'conflicts' => $eligibility['conflicts'],

        'excluded' => $eligibility['excluded'],
    ];
}

    // Module 4: Get employee assessment and skill evidence
    public function getEmployeeEvidence(int $employeeId): array
    {
        $employee = DB::table('employees')->where('id', $employeeId)
            ->select('assessmentScore', 'certifications', 'projectExperience')
            ->first();
        if (!$employee) {
            return [
                 'success'=>false,
                 'message'=>'Employee Not Found',
            ];
        }

        return [
            'assessmentScore' => $employee->assessmentScore,
            'certifications' => $employee->certifications,
            'projectExperience' => $employee->projectExperience,
        ];
    }

  
   // model 5 skill gap analysis

   private function parseSkills(?string $skills): array
  {
      if (empty($skills)) {
        return [];
    }

    return array_values(array_unique(array_filter(
        array_map('trim', explode(',', $skills))
    )));
}

    public function skillGapAnalysis(int $projectId,int $employeeId): array {

    $project = DB::table('projects')->where('id', $projectId)
    ->select([
        'id',
        'requiredTechnology as requiredTechnology',
        'mandatoryskills as mandatorySkill',
        'preferredSkills as preferableskill',
        'minProficiency as proficiency',
    ])->first();


    if (!$project) {
        throw new \InvalidArgumentException('Project not found.');
    }

    $employee = DB::table('employees')->where('id', $employeeId)
     ->select([
        'id',
        'primaryskill as primaryskill',
        'secondarySkills as secondaryskill',
        'tertiarySkills as teritartskill',
        'proficiency as proficiency',
    ])->first();

    if (!$employee) {
        throw new \InvalidArgumentException('Employee not found.');
    }

    // project skills
    $mandatorySkills = $this->parseSkills($project->mandatorySkill);
    $preferredSkills = $this->parseSkills($project->preferableskill);
    $requiredTechnology = $this->parseSkills($project->requiredTechnology );

    $projectSkills = collect(array_merge(
        $mandatorySkills,
        $preferredSkills,
        $requiredTechnology
    ))
    ->filter()
    ->unique(fn($skill) => strtolower(trim($skill)))
    ->values()
    ->all();

    // employee skills
    $primarySkills = $this->parseSkills($employee->primaryskill);
    $secondarySkills = $this->parseSkills($employee->secondaryskill);
    $tertiarySkills = $this->parseSkills($employee->teritartskill );
    $employeeSkills = array_unique(array_merge(
        $primarySkills,
        $secondarySkills,
        $tertiarySkills
    ));

    // proficiency
    $requiredProficiency = (int) $project->proficiency;
    $actualProficiency = (int) $employee->proficiency;

    // Compare skills
    $gaps = [];

    foreach ($projectSkills as $skill) {
        $normalizedSkill = strtolower(trim($skill));
        $matched = in_array($normalizedSkill,
            array_map(fn($s) => strtolower(trim($s)),
                $employeeSkills
            ),
            true
        );

            $isMandatory = in_array(
            strtolower(trim($skill)),
            array_map(
                fn($s) => strtolower(trim($s)),
                $mandatorySkills
            ),
            true
        );

        $isPreferred = in_array(
            strtolower(trim($skill)),
            array_map(
                fn($s) => strtolower(trim($s)),
                $preferredSkills
            ),
            true
        );

        if (!$matched) {
            $gapType = 'Missing';
            $gap = null;
        } elseif ($actualProficiency < $requiredProficiency) {
            $gapType = 'Underdeveloped';
            $gap = $requiredProficiency - $actualProficiency;
        } else {
            $gapType = 'Matched';
            $gap = 0;
        }

        $gaps[] = [
            'skill' => $skill,
            'requiredProficiency' => $requiredProficiency,
            'actualProficiency' => $matched
                ? $actualProficiency
                : null,
            'gap' => $gap,
            'gapType' => $gapType,
            'mandatory' => $isMandatory,
            'preferred' => $isPreferred,
            'priority' => $isMandatory ? 'High' : 'Medium'
        ];
    }

    // Summary
    return [
        'projectId' => $projectId,
        'employeeId' => $employeeId,
        'totalRequiredSkills' => count($gaps),

        'matchedSkills' => count(array_filter(
            $gaps,fn($item) => $item['gapType'] === 'Matched'
        )),

        'missingSkills' => count(array_filter(
            $gaps,fn($item) => $item['gapType'] === 'Missing'
        )),

        'underdevelopedSkills' => count(array_filter(
            $gaps,fn($item) => $item['gapType'] === 'Underdeveloped'
        )),

        'gaps' => array_values($gaps)
    ];
}


    public function askAgent(string $prompt): ?string
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful autonomous AI agent.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $result = $this->callApi($messages);

        if (isset($result['error'])) {
            return null;
        }

        return data_get($result, 'choices.0.message.content');
    }

    
    public function checkStatus(): array
    {
        // Correct dot notation for nested config/ai.php array
        $apiKey = config('ai.openai.api_key');
        $endpoint = config('ai.openai.endpoint');
        $model = config('ai.openai.model', 'llama-3.3-70b-versatile');
        $timeout = config('ai.openai.timeout', 15);

        $activeProvider = str_contains($endpoint, 'gemini') ? 'gemini' : (str_contains($endpoint, 'groq') ? 'groq' : 'openai');

        try {
            $response = Http::withToken($apiKey)
                ->timeout($timeout)
                ->post($endpoint, [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say "OK" if online.'],
                    ],
                    'max_tokens' => 5,
                ]);

            $isSuccessful = $response->successful();
            $statusCode = $response->status();
            $responseData = $response->json();

            $maskedKey = $apiKey ? substr($apiKey, 0, 6) . '...' . substr($apiKey, -4) : null;

            if ($isSuccessful) {
                return [
                    'success' => true,
                    'activeProvider' => $activeProvider,
                    'geminiConfigured' => !empty($apiKey),
                    'apiKey' => $maskedKey,
                    'model' => $model,
                    'httpStatus' => $statusCode,
                    'message' => 'API token is valid and working correctly.',
                ];
            }

            return [
                'success' => false,
                'activeProvider' => $activeProvider,
                'geminiConfigured' => !empty($apiKey),
                'apiKey' => $maskedKey,
                'model' => $model,
                'httpStatus' => $statusCode,
                'errorDetails' => $responseData,
                'message' => 'API rejected the token or returned an error.',
                'fallbackActive' => true,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'activeProvider' => $activeProvider,
                'geminiConfigured' => !empty($apiKey),
                'apiKey' => null,
                'model' => $model,
                'httpStatus' => 500,
                'errorDetails' => ['error' => $e->getMessage()],
                'message' => 'Connection failed or network timeout while contacting the AI endpoint.',
                'fallbackActive' => true,
            ];
        }
    }

    protected function callApi(array $messages, ?int $maxTokens = null): ?array
    {
        try {
            $payload = [
                'model' => config('ai.openai.model'),
                'messages' => $messages,
            ];

            if ($maxTokens) {
                $payload['max_tokens'] = $maxTokens;
            }

            $response = Http::withToken(config('ai.openai.api_key'))
                ->timeout(config('ai.openai.timeout', 15))
                ->post(config('ai.openai.endpoint'), $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('AI Repository API Error: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('AI Repository Exception: ' . $e->getMessage());
            return null;
        }
    }
   
}
