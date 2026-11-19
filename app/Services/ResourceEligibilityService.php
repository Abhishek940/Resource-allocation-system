<?php

namespace App\Services;
use App\Repositories\ResourceEligibilityRepositoryInterface;
use Illuminate\Support\Collection;
use App\Services\GenerativeAiService;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
class ResourceEligibilityService
{
    protected ResourceEligibilityRepositoryInterface $repository;
    protected GenerativeAiService $aiService;

    public function __construct(
        ResourceEligibilityRepositoryInterface $repository,
        ?GenerativeAiService $aiService = null
    ) {
        $this->repository = $repository;
        $this->aiService = $aiService ?? app(GenerativeAiService::class);
    }

    public function getProjects(): Collection
    {
        return $this->repository->getProjects();
     }

    public function getProjectById(int $projectId): ?object
    {
        return $this->repository->getProjectById($projectId);
    }

    public function getActiveEmployees(): Collection
    {
        return $this->repository->getActiveEmployees();
    }

    public function checkProjectEligibility(int $projectId): array
    {
        return $this->repository->checkProjectEligibility($projectId);
    }

    public function insertAllocation(array $data): int
    {
        return $this->repository->insertAllocation($data);
    }

    public function getProjectAllocationlist():collection{
        return $this->repository->getProjectAllocationlist();
    }

    public function deleteProjectallocation(int $id):int {
        return $this->repository->deleteProjectallocation($id);
    }

    public function getAllocationById(int $id): ?object
    {
        return $this->repository->getAllocationById($id);
    }
    
    public function getConfirmedAllocations(int $employeeId,string $startDate,string $endDate): Collection {
        return $this->repository->getConfirmedAllocations(
            $employeeId,
            $startDate,
            $endDate
        );
    }



// module 4

 // Module 4: Resource matching
    // Module 4: Retrieve resources and their evidence
   // =====================================================
// MODULE 4: AI-BASED SKILL MATCHING ENGINE
// =====================================================

 public function getResourceMatches(int $projectId, bool $forceRefresh = false): array {
    // 1. Get project requirements
    $project = $this->repository->getProjectById($projectId);

    if (!$project) {
        throw new \InvalidArgumentException('Project not found.');
    }

    // 2. Get eligibility results
    $eligibilityResponse = $this->repository->checkProjectEligibility($projectId);
   

    //  both array HTTP responses
    if ($eligibilityResponse instanceof \Illuminate\Http\Client\Response) {
        $eligibilityResponse = $eligibilityResponse->json();
    }

    $eligibility = $eligibilityResponse['data'] ?? $eligibilityResponse;

    // 3. Extract every eligibility category
    $eligibleResources = $eligibility['eligible'] ?? [];
    $futureResources = $eligibility['futureAvailability'] ?? [];
    $partialResources = $eligibility['partialAvailability'] ?? [];
    $conflictResources = $eligibility['conflicts'] ?? [];
    $afterProjectResources = $eligibility['availableAfterProject'] ?? [];
    $excluded = $eligibility['excluded'] ?? [];

    // 4. Combine candidates without duplicates
    $resources = collect($eligibleResources)
        ->concat($futureResources)
        ->concat($partialResources)
        ->concat($conflictResources)
        ->concat($afterProjectResources)
        ->unique(function ($resource) {
            return $this->value($resource, 'employeeId');
        })
        ->filter(function ($resource) {
            return $this->value($resource, 'employeeId') !== null;
        })
        ->values()
        ->all();

   Log::info('skill matches', [
    'eligible_count' => count($eligibleResources),
    'future_count' => count($futureResources),
    'partial_count' => count($partialResources),
    'conflict_count' => count($conflictResources),
    'after_project_count' => count($afterProjectResources),
    'excluded_count' => count($excluded),
    'combined_count' => count($resources),
]);

    $matches = [];

    // 5. Module 4.4 scoring weights
    $weights = [
        'skill'        => 40,
        'proficiency'  => 25,
        'experience'   => 15,
        'evidence'     => 10,
        'availability' => 10,
    ];

    if (array_sum($weights) !== 100) {
        throw new \Exception('Scoring weights must total 100.');
    }

    // Evaluate every candidate
    foreach ($resources as $resource) {
        $employeeId = $this->value($resource, 'employeeId');
        if ($employeeId === null) {
            continue;
        }
        // Employee evidence
        $evidence = $this->repository->getEmployeeEvidence(
            (int) $employeeId
        );

        $evidence = $evidence ?? [];
        $getEvidenceValue = function (
            string $key,
            $default = null
        ) use ($evidence) {

            if (is_array($evidence)) {
                return $evidence[$key] ?? $default;
            }

            if (is_object($evidence)) {
                return $evidence->{$key} ?? $default;
            }

            return $default;
        };

        //  Normalize skills
        $employeeSkills = $this->normalizeSkills(
            $this->value($resource, 'employeeSkills', [])
        );

        $mandatorySkills = $this->normalizeSkills(
            $this->value(
                $resource,
                'mandatorySkills',
                $this->value($project, 'mandatorySkills', [])
            )
        );

        $preferredSkills = $this->normalizeSkills(
            $this->value($project, 'preferredSkills', [])
        );

        $matchedMandatory = array_values(
            array_intersect($mandatorySkills, $employeeSkills)
        );

        $missingMandatory = array_values(
            array_diff($mandatorySkills, $employeeSkills)
        );

        $matchedPreferred = array_values(
            array_intersect($preferredSkills, $employeeSkills)
        );

        // Transferable skills
        $transferableSkills = $this->findTransferableSkills(
            $missingMandatory,
            $employeeSkills
        );

        // Skill score (40%)
        $skillScore = (float) $this->value(
            $resource,
            'mandatoryMatchPercentage',
            0
        );

        $skillScore = max(0, min(100, $skillScore));

        //  Proficiency score (25%)
        $employeeProficiency = (float) $this->value(
            $resource,
            'employeeProficiency',
            0
        );

        $requiredProficiency = (float) $this->value(
            $resource,
            'requiredProficiency',
            0
        );

        $employeeExperience = (float) $this->value(
            $resource,
            'employeeExperience',
            0
        );

        $requiredExperience = (float) $this->value(
            $resource,
            'requiredExperience',
            0
        );

        $proficiencyEligible = (bool) $this->value(
            $resource,
            'proficiencyEligible',
            false
        );

        $proficiencyScore = $proficiencyEligible ? 100.0 : 0.0;

        //  Experience score (15%)
        $experienceEligible = (bool) $this->value(
            $resource,
            'experienceEligible',
            false
        );

        $experienceScore = $experienceEligible ? 100.0 : 0.0;

        //  Evidence score (10%)
        $assessmentValue = $getEvidenceValue(
            'assessmentScore',
            $getEvidenceValue(
                'assessment_score',
                $this->value($resource, 'assessmentScore', 0)
            )
        );

        $assessmentScore = is_numeric($assessmentValue)
            ? max(0, min(100, (float) $assessmentValue))
            : 0.0;

        $certifications = $getEvidenceValue('certifications', []);
        $projectExperience = $getEvidenceValue('projectExperience', []);

        $hasCertification = !empty($certifications);
        $hasProjectEvidence = !empty($projectExperience);

        // Assessment score is currently used as the evidence score
        $evidenceScore = $assessmentScore;

        // Availability score (10%)
        $availabilityScore = (float) $this->value(
            $resource,
            'remainingCapacity',
            0
        );

        $availabilityScore = max(
            0,
            min(100, $availabilityScore)
        );

        // Weighted suitability score
        $weightedScores = [
            'skill' => $skillScore * ($weights['skill'] / 100),
            'proficiency' => $proficiencyScore * ($weights['proficiency'] / 100),
            'experience' => $experienceScore * ($weights['experience'] / 100),
            'evidence' => $evidenceScore * ($weights['evidence'] / 100),
            'availability' => $availabilityScore * ($weights['availability'] / 100),
        ];

        $suitabilityScore = round(
            array_sum($weightedScores),
            2
        );

        //  Mandatory constraints
        $mandatoryConstraintsMet = empty($missingMandatory);

        $repositoryConstraintFlag = $this->value(
            $resource,
            'mandatoryConstraintsMet',
            $this->value(
                $resource,
                'mandatory_constraints_met',
                null
            )
        );

        if ($repositoryConstraintFlag !== null) {
            $mandatoryConstraintsMet =
                $mandatoryConstraintsMet &&
                (bool) $repositoryConstraintFlag;
        }

        // Respect the eligibility service's skill flag, if provided
        $skillEligible = $this->value(
            $resource,
            'skillEligible',
            null
        );

        if ($skillEligible !== null && !(bool) $skillEligible) {
            $mandatoryConstraintsMet = false;
        }

        // 15. Determine availability and conflict status
        $availableAtProjectStart = (bool) $this->value(
            $resource,
            'availableAtProjectStart',
            false
        );

        $availableDuringProject = (bool) $this->value(
            $resource,
            'availableDuringProject',
            false
        );

        $availableAfterProject = (bool) $this->value(
            $resource,
            'availableAfterProject',
            false
        );

        $hasConflict = (bool) $this->value(
            $resource,
            'hasConflict',
            $this->value($resource, 'conflict', false)
        );

        $conflictingAllocations = $this->value(
            $resource,
            'conflictingAllocations',
            []
        );

        if (!empty($conflictingAllocations)) {
            $hasConflict = true;
        }

        // 16. Deployment eligibility
        $deploymentEligible =
            $availableAtProjectStart &&
            $availableDuringProject &&
            !$availableAfterProject &&
            $availabilityScore >= 50 &&
            !$hasConflict &&
            $mandatoryConstraintsMet &&
            $experienceEligible &&
            $proficiencyEligible;

        // 17. Candidate availability category
        if ($availableAfterProject) {
            $availabilityStatus = 'Available After Project';
        } elseif ($hasConflict) {
            $availabilityStatus = 'Allocation Conflict';
        } elseif (!$availableAtProjectStart && $availableDuringProject) {
            $availabilityStatus = 'Future Availability';
        } elseif (!$availableAtProjectStart) {
            $availabilityStatus = 'Not Available At Project Start';
        } else {
            $availabilityStatus = 'Available';
        }

        // 18. Match category
        if (!$mandatoryConstraintsMet) {
            $category = 'Partial Skill Match';
        } elseif ($hasConflict) {
            $category = 'Allocation Conflict';
        } elseif (!$availableAtProjectStart && $availableDuringProject) {
            $category = 'Future Availability';
        } elseif ($availableAfterProject) {
            $category = 'Available After Project';
        } elseif ($deploymentEligible) {
            $category = 'Eligible';
        } else {
            $category = 'Not Immediately Deployable';
        }

        // 19. Explanation
        $explanation = $this->buildMatchExplanation(
            $matchedMandatory,
            $missingMandatory,
            $transferableSkills,
            $employeeExperience,
            $requiredExperience,
            $employeeProficiency,
            $requiredProficiency,
            $assessmentScore,
            $availabilityScore,
            $mandatoryConstraintsMet
        );

        // 20. AI candidate recommendation
        $aiCandidateData = [
            'employee' => $resource,
            'suitabilityScore' => $suitabilityScore,
            'mandatoryConstraintsMet' => $mandatoryConstraintsMet,
            'missingMandatorySkills' => $missingMandatory,
            'matchedMandatorySkills' => $matchedMandatory,
            'availabilityStatus' => $availabilityStatus,
            'deploymentEligible' => $deploymentEligible,
        ];

        $aiDeployment = $this->aiService->generateCandidateRecommendation(
            (array) $project,
            $aiCandidateData,
            $evidence,
            $forceRefresh
        );

        // Merge AI semantic transferable skills
        if (!empty($aiDeployment['transferableSkillAnalysis'])) {

            foreach ($aiDeployment['transferableSkillAnalysis'] as $ts) {

                $alreadyPresent = false;

                foreach ($transferableSkills as $existing) {
                    if (
                        strcasecmp(
                            $existing['requiredSkill'] ?? '',
                            $ts['requiredSkill'] ?? ''
                        ) === 0
                    ) {
                        $alreadyPresent = true;
                        break;
                    }
                }

                if (!$alreadyPresent) {
                    $transferableSkills[] = [
                        'requiredSkill' => $ts['requiredSkill'] ?? '',
                        'relatedEmployeeSkill' => $ts['candidateSkill'] ?? '',
                        'note' => $ts['aiReasoning']
                            ?? 'AI semantic transferability identified.',
                    ];
                }
            }
        }

        // 21. Recommendation
        if (!$mandatoryConstraintsMet) {
            $recommendation = 'Partial Match - Mandatory skills missing';
        } elseif ($hasConflict) {
            $recommendation = 'Not deployable - Allocation conflict';
        } elseif (!$availableAtProjectStart && $availableDuringProject) {
            $recommendation = 'Future Available - For stakeholder review';
        } elseif ($availableAfterProject) {
            $recommendation = 'Available After Project';
        } elseif (!$deploymentEligible) {
            $recommendation = 'Not Immediately Deployable - For stakeholder review';
        } elseif ($suitabilityScore >= 80) {
            $recommendation = 'Strong Match - For stakeholder review';
        } elseif ($suitabilityScore >= 65) {
            $recommendation = 'Potential Match - For stakeholder review';
        } elseif ($suitabilityScore >= 50) {
            $recommendation = 'Development Candidate - For stakeholder review';
        } else {
            $recommendation = 'Low Match - For stakeholder review';
        }

        // 22. Build candidate result
        $matches[] = [
            'employee' => $resource,
            'evidence' => $evidence,

            'skillAnalysis' => [
                'matchedMandatorySkills' => $matchedMandatory,
                'matchedPreferredSkills' => $matchedPreferred,
                'missingMandatorySkills' => $missingMandatory,
                'transferableSkills' => $transferableSkills,
            ],

            'scores' => [
                'skillScore' => round($skillScore, 2),
                'proficiencyScore' => round($proficiencyScore, 2),
                'experienceScore' => round($experienceScore, 2),
                'assessmentScore' => round($assessmentScore, 2),
                'evidenceScore' => round($evidenceScore, 2),
                'availabilityScore' => round($availabilityScore, 2),
                'hasCertification' => $hasCertification,
                'hasProjectEvidence' => $hasProjectEvidence,
                'weightedScores' => $weightedScores,
            ],

            'suitabilityScore' => $suitabilityScore,
            'category' => $category,
            'availabilityStatus' => $availabilityStatus,
            'deploymentEligible' => $deploymentEligible,
            'hasConflict' => $hasConflict,
            'mandatoryConstraintsMet' => $mandatoryConstraintsMet,
            'recommendation' => $recommendation,
            'explanation' => $explanation,
            'aiDeployment' => $aiDeployment,
        ];
    }

    // 23. Rank candidates:
    // Deployment-ready first, then mandatory skill match, then score
    usort($matches, function (array $a, array $b): int {

        if ($a['deploymentEligible'] !== $b['deploymentEligible']) {
            return $a['deploymentEligible'] ? -1 : 1;
        }

        if (
            $a['mandatoryConstraintsMet'] !==
            $b['mandatoryConstraintsMet']
        ) {
            return $a['mandatoryConstraintsMet'] ? -1 : 1;
        }

        return $b['suitabilityScore'] <=> $a['suitabilityScore'];
    });

    foreach ($matches as $index => &$match) {
        $match['rank'] = $index + 1;
    }

    unset($match);

    // 24. Only deployment-ready candidates go to AI squad recommendation
    $deploymentMatches = array_values(
        array_filter($matches, function (array $match): bool {
            return $match['deploymentEligible'] === true;
        })
    );

    $aiTeamDeployment = $this->aiService->generateProjectTeamRecommendation(
        (array) $project,
        $deploymentMatches,
        $forceRefresh
    );

    // 25. Project status
    $statusMap = [
        1 => 'pending',
        2 => 'active',
        3 => 'hold',
        4 => 'completed',
        5 => 'rejected',
        6 => 'draft',
    ];

    $projectStatus = $this->value($project, 'status');

    $status = is_numeric($projectStatus)
        ? ($statusMap[(int) $projectStatus] ?? 'unknown')
        : ($projectStatus ?? 'unknown');

    // 26. Counts
    $eligibleCount = count(array_filter(
        $matches,
        fn (array $match): bool => $match['deploymentEligible'] === true
    ));

    $futureCount = count(array_filter(
        $matches,
        fn (array $match): bool =>
            $match['availabilityStatus'] === 'Future Availability'
    ));

    $conflictCount = count(array_filter(
        $matches,
        fn (array $match): bool =>
            $match['availabilityStatus'] === 'Allocation Conflict'
    ));

    $partialCount = count(array_filter(
        $matches,
        fn (array $match): bool =>
            $match['mandatoryConstraintsMet'] === false
    ));

    // 27. Final response
    return [
        'success' => true,
        'projectId' => $projectId,
        'projectCode' => $this->value($project, 'projectCode'),
        'status' => $status,
        'projectName' => $this->value($project, 'projectName'),
        'ProjectStartDate' => $this->value(
            $project,
            'startDate',
            $this->value($project, 'startfrom')
        ),
        'MandatorySkill' => $this->value($project, 'mandatorySkills'),
        'PreferredSkills' => $this->value($project, 'preferredSkills'),
        'numberOfResources' => (int) $this->value(
            $project,
            'numberOfResources',
            1
        ),

        'scoringWeights' => $weights,

        'totalMatches' => count($matches),
        'eligibleMatches' => $eligibleCount,
        'futureMatches' => $futureCount,
        'conflictMatches' => $conflictCount,
        'partialMatches' => $partialCount,

        'aiTeamDeployment' => $aiTeamDeployment,

        // All evaluated candidates, including future/partial/conflict
        'data' => $matches,

        // Only employees rejected by the eligibility service
        'excluded' => $excluded,
    ];
}


// Safely read fields from an array or object.
private function value(
    $source,
    string $key,
    $default = null
) {
    if (is_array($source)) {
        return $source[$key] ?? $default;
    }

    if (is_object($source)) {
        return $source->{$key} ?? $default;
    }

    return $default;
}


// Normalize skill strings or arrays
private function normalizeSkills($skills): array
{
    if (is_string($skills)) {
        $decoded = json_decode($skills, true);

        $skills = is_array($decoded)
            ? $decoded
            : preg_split('/[,;\n]+/', $skills);
    }

    if (!is_array($skills)) {
        return [];
    }

    $normalized = [];

    foreach ($skills as $skill) {
        if (!is_string($skill) && !is_numeric($skill)) {
            continue;
        }

        $skill = strtolower(trim((string) $skill));
        $skill = preg_replace('/[[:punct:]]+$/u', '', $skill);

        if ($skill !== '') {
            $normalized[] = $skill;
        }
    }

    return array_values(array_unique($normalized));
}

//  transferable-skill mappings.
// these potential relevance, not skill equivalence.
    private function findTransferableSkills(
        array $missingSkills,
        array $employeeSkills
    ): array {
        $skillMap = [
            'laravel' => ['php'],
            'spring boot' => ['java'],
            'angular' => ['typescript', 'javascript'],
            'react' => ['javascript', 'typescript'],
            'node.js' => ['javascript', 'typescript'],
            'rest api' => ['api development'],
        ];

        $transferable = [];

        foreach ($missingSkills as $missingSkill) {
            $relatedSkills = $skillMap[$missingSkill] ?? [];

            foreach ($relatedSkills as $relatedSkill) {
                if (in_array($relatedSkill, $employeeSkills, true)) {
                    $transferable[] = [
                        'requiredSkill' => $missingSkill,
                        'relatedEmployeeSkill' => $relatedSkill,
                        'note' => 'Potentially transferable; validation required.',
                    ];
                }
            }
        }

        return $transferable;
    }

    private function getMatchCategory(float $score): string
    {
        if ($score >= 80) {
            return 'Strong Match';
        }

        if ($score >= 65) {
            return 'Potential Match';
        }

        if ($score >= 50) {
            return 'Development Candidate';
        }

        return 'Low Match';
    }

        private function buildMatchExplanation(
            array $matchedSkills,
            array $missingSkills,
            array $transferableSkills,
            float $employeeExperience,
            float $requiredExperience,
            float $employeeProficiency,
            float $requiredProficiency,
            float $evidenceScore,
            float $availabilityScore,
            bool $constraintsMet
        ): string {
            $parts = [];

            $parts[] = 'Matched mandatory skills: ' .
                ($matchedSkills ? implode(', ', $matchedSkills) : 'none');

            $parts[] = 'Missing mandatory skills: ' .
                ($missingSkills ? implode(', ', $missingSkills) : 'none');

            if ($transferableSkills) {
                $related = [];

                foreach ($transferableSkills as $skill) {
                    $related[] = $skill['relatedEmployeeSkill'] .
                        ' may support ' . $skill['requiredSkill'];
                }

                $parts[] = 'Potential transferable skills: ' .
                    implode('; ', $related) .
                    '. Validation required.';
            }

            $parts[] = "Experience: {$employeeExperience} years; " .
                "required: {$requiredExperience} years.";

            $parts[] = "Proficiency: {$employeeProficiency}; " .
                "required: {$requiredProficiency}.";

            $parts[] = 'Evidence score: ' . round($evidenceScore, 2) . '/100.';
            $parts[] = 'Availability score: ' .
                round($availabilityScore, 2) . '/100.';

            $parts[] = $constraintsMet
                ? 'Mandatory skill constraints are met.'
                : 'Mandatory skill constraints are NOT met.';

            return implode(' ', $parts);
        }

    /**
     * Generative AI Project Deployment Recommendation.
     */
    public function getAiDeploymentRecommendation(int $projectId): array
    {
        $matchesData = $this->getResourceMatches($projectId);
        $project = $this->repository->getProjectById($projectId);

        if (!$project) {
            throw new \InvalidArgumentException('Project not found.');
        }

        return [
            'success' => true,
            'projectId' => $projectId,
            'projectName' => $project->projectName ?? '',
            'aiTeamDeployment' => $matchesData['aiTeamDeployment'] ?? null,
            'eligibleCandidates' => array_values(array_slice(array_filter($matchesData['data'] ?? [], fn($m) => $m['mandatoryConstraintsMet'] ?? false), 0, 10)),
        ];
    }

    /**
     * Generative AI insights for an individual Resources.
     */
            public function getCandidateAiInsights(
        int $projectId,
        int $employeeId
    ): array {

    $project = $this->repository->getProjectById($projectId);

    if (!$project) {
        throw new \InvalidArgumentException('Project not found.');
    }

    $evidence = $this->repository->getEmployeeEvidence($employeeId) ?? [];

    $activeEmployees = $this->repository->getActiveEmployees();

    $employee = $activeEmployees->firstWhere('id', $employeeId);

    if (!$employee) {
        throw new \InvalidArgumentException('Employee not found.');
    }

    // Get the selected employee's data.
    $candidate = [
        'employee' => (array) $employee,
        'suitabilityScore' => 50,
        'mandatoryConstraintsMet' => false,
    ];

    // Get the latest matching result if available.
    $matchesData = $this->getResourceMatches($projectId);

    foreach ($matchesData['data'] as $match) {

        $candidateId = $this->value(
            $match['employee'],
            'employeeId'
        ) ?? $this->value(
            $match['employee'],
            'id'
        );

        if ((int) $candidateId === $employeeId) {
            $candidate = $match;
            break;
        }
    }

    // Always generate a fresh recommendation for this employee.
    $insights = $this->aiService->generateCandidateRecommendation(
        (array) $project,
        $candidate,
        $evidence,
        true
    );

    return [
        'success' => true,
        'projectId' => $projectId,
        'employeeId' => $employeeId,
        'employeeName' => $employee->employeeName ?? '',
        'aiInsights' => $insights,
    ];
}
    /**
     * Semantic Skill Matching between required skills and candidate skills using AI.
     */
    public function matchSkillsWithAi(array $requiredSkills, array $candidateSkills): array
    {
        return $this->aiService->matchSkillsSemantically($requiredSkills, $candidateSkills);
    }

    public function askAgent(string $prompt): ?string
    {
        return $this->repository->askAgent($prompt);
    }

   public function checkStatus(): array
    {
        return $this->repository->checkStatus();
    }


   public function skillGapAnalysis(
    int $projectId,
    int $employeeId
): array {

    if ($projectId <= 0 || $employeeId <= 0) {
        throw new \InvalidArgumentException(
            'Invalid project or employee ID.'
        );
    }

    // Get repository result
    $result = $this->repository->skillGapAnalysis(
        $projectId,
        $employeeId
    );

    if (empty($result)) {
        throw new \InvalidArgumentException(
            'No required skills found for this project.'
        );
    }

    // Support both repository return structures
    $gaps = isset($result['gaps'])
        ? $result['gaps']
        : $result;

    if (empty($gaps)) {
        throw new \InvalidArgumentException(
            'No required skills found for this project.'
        );
    }

    $matched = 0;
    $missing = 0;
    $underdeveloped = 0;

    foreach ($gaps as $gap) {

        if (!is_array($gap)) {
            continue;
        }

        switch ($gap['gapType'] ?? null) {

            case 'Matched':
                $matched++;
                break;

            case 'Missing':
                $missing++;
                break;

            case 'Underdeveloped':
                $underdeveloped++;
                break;
        }
    }

    return [
        'projectId' => $projectId,
        'employeeId' => $employeeId,
        'totalRequiredSkills' => count($gaps),
        'matchedSkills' => $matched,
        'missingSkills' => $missing,
        'underdevelopedSkills' => $underdeveloped,
        'gaps' => array_values($gaps)
    ];
}
      

}



?>