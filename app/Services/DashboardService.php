<?php


namespace App\Services;

use App\Repositories\DashboardRepositoryInterface;
use Illuminate\Support\Facades\Http;

class DashboardService
{
    protected $dashboardRepository;

    public function __construct(
        DashboardRepositoryInterface $dashboardRepository
    ) {
        $this->dashboardRepository = $dashboardRepository;
    }

    public function getDashboardCounts(): array
    {
        // Active projects
        $projects = $this->dashboardRepository->getActiveProjects();
      //  dd($projects);
        $totalActiveProjects = $projects->count();
        // Bench resources
        $totalBenchResources = $this->dashboardRepository->getBenchResourceCount();
        $matchingEmployees = [];
        $strongMatchEmployees = [];
        $upskillingEmployees = [];
        $totalSkillOccurrences = 0;

        foreach ($projects as $project) {
           // dd($project);

            // Count mandatory skills
            $skills = $this->parseSkills($project->mandatoryskills ?? '' );
            $totalSkillOccurrences += count($skills);
            // Check project eligibility through resource eligibility service
            $eligibility = $this->checkProjectEligibility((int) $project->id );
           // dd($eligibility);
            // Eligible resources
            /* foreach ($eligibility['eligible'] ?? [] as $employee) {
                $employeeId = $employee['employeeId'];
                $matchingEmployees[$employeeId] = true;
                if ($employee['mandatoryMatchPercentage'] >= 100) {
                    $strongMatchEmployees[$employeeId] = true;
                }
            } */

                // Eligible resources and strong-match candidates.
                foreach ($eligibility['eligible'] ?? [] as $employee) {

                    $employeeId = (int) ($employee['employeeId'] ?? 0);

                    if (!$employeeId) {
                        continue;
                    }

                    // Count unique employees matching at least one project.
                    $matchingEmployees[$employeeId] = true;

                    // Count unique employees satisfying all strong-match conditions.
                    if ($this->isStrongMatchCandidate($employee)) {
                        $strongMatchEmployees[$employeeId] = true;
                    }
                }

            // Candidates requiring upskilling
            foreach ($eligibility['partialAvailability'] ?? [] as $employee) {

                if (!empty($employee['missingMandatorySkills'])) {
                    $employeeId = $employee['employeeId'];
                    $upskillingEmployees[$employeeId] = true;
                }
            }
        }

        return [
            'totalActiveProjects' => $totalActiveProjects,
            'totalBenchResources' => $totalBenchResources,
            'resourcesMatchingRequirements' => count($matchingEmployees),
            'strongMatchCandidates' => count($strongMatchEmployees),
            'candidatesRequiringUpskilling' => count($upskillingEmployees),
            'frequentlyRequestedSkills' => $totalSkillOccurrences,
        ];
    }

      private function checkProjectEligibility(int $projectId): array
        {
            $baseUrl = config('services.resource_eligibility.url');

            if (empty($baseUrl)) {
                throw new \RuntimeException(
                    'Resource Eligibility service URL is not found.'
                );
            }

            $response = Http::timeout(30)->get(
                rtrim($baseUrl, '/') . '/api/projects/' . $projectId . '/eligibility'
            );

            $response->throw();

            //return $response->json();
            $result = $response->json();
            return $result['data'] ?? [];
        }



    private function parseSkills($skills): array
        {
            if (empty($skills)) {
                return [];
            }

            return collect(explode(',', $skills))
                ->map(fn($skill) => trim($skill))
                ->filter()
                ->unique(fn($skill) => strtolower($skill))
                ->values()
                ->all();
        }


        public function getMatchingResources(): array
        {
            $projects = $this->dashboardRepository->getActiveProjects();

            $matchingEmployees = [];

            foreach ($projects as $project) {
                $eligibility = $this->checkProjectEligibility((int) $project->id);
                $projectDetails = $eligibility['project'] ?? [];
                foreach ($eligibility['eligible'] ?? [] as $employee) {
                $employeeId = (int) ($employee['employeeId'] ?? 0);

                    if (!$employeeId) {
                        continue;
                    }

                  if (!isset($matchingEmployees[$employeeId])) {

                    $matchingEmployees[$employeeId] = [
                            'employeeId' => $employeeId,
                            'employeeCode' => $employee['employeeCode'] ?? '',
                            'employeeName' => $employee['employeeName'] ?? '',
                            'designation' => $employee['employeeDesignation'] ?? '',
                            'location' => $employee['location'] ?? '',
                            'resourceStatus' => $employee['resourceStatus'] ?? '',
                            'employeeExperience' => $employee['employeeExperience'] ?? 0,
                            'employeeSkills' => $employee['employeeSkills'] ?? [],
                            'matchedProjects' => []
                        ];
                    }

                    // Add every project for which this employee is eligible.
                    $matchingEmployees[$employeeId]['matchedProjects'][] = [
                        'projectId' => $projectDetails['id'] ?? $project->id,
                        'projectCode' => $projectDetails['projectCode'] ?? '',
                        'projectName' => $projectDetails['projectName'] ?? '',
                        'startDate' => $projectDetails['startDate'] ?? '',
                        'endDate' => $projectDetails['endDate'] ?? '',
                        'suitabilityScore' => $employee['suitabilityScore'] ?? 0,
                        'mandatoryMatchPercentage' =>
                            $employee['mandatoryMatchPercentage'] ?? 0,
                        'matchedMandatorySkills' =>
                            $employee['matchedMandatorySkills'] ?? [],
                        'recommendationExplanation' =>
                            $employee['recommendationExplanation'] ?? ''
                    ];
                }
            }

            $resources = array_values($matchingEmployees);

            return [
                'total' => count($resources),
                'resources' => $resources
            ];
        }


   
public function getStrongMatchCandidates(): array
{
    $projects = $this->dashboardRepository->getActiveProjects();

    $strongMatchEmployees = [];

    foreach ($projects as $project) {

        $eligibility = $this->checkProjectEligibility((int) $project->id);

        $projectDetails = $eligibility['project'] ?? [];

        foreach ($eligibility['eligible'] ?? [] as $employee) {

            $employeeId = (int) ($employee['employeeId'] ?? 0);

            if (!$employeeId) {
                continue;
            }

            // Mandatory skills must match 100%.
            $mandatoryMatchPercentage = (float) (
                $employee['mandatoryMatchPercentage'] ?? 0
            );

            //  Availability must be 100%.
            $availabilityPercentage = (float) (
                $employee['remainingCapacity'] ?? 0
            );

            //  Experience requirement.
            $experienceEligible = (bool) (
                $employee['experienceEligible'] ?? false
            );

            //  Proficiency requirement.
            $proficiencyEligible = (bool) (
                $employee['proficiencyEligible'] ?? false
            );

            //  Assessment score must be greater than 60%.
            $assessmentScore = (float) (
                $employee['assessmentScore'] ?? 0
            );

            // Check all strong-match conditions.
            $isStrongMatch =
                $mandatoryMatchPercentage >= 100 &&
                $availabilityPercentage >= 100 &&
                ($employee['availableAtProjectStart'] ?? false) === true &&
                ($employee['availableDuringProject'] ?? false) === true &&
                $experienceEligible &&
                $proficiencyEligible &&
                $assessmentScore > 60;

            if (!$isStrongMatch) {
                continue;
            }

            // Initialize employee only once.
            if (!isset($strongMatchEmployees[$employeeId])) {

                $strongMatchEmployees[$employeeId] = [
                    'employeeId' => $employeeId,
                    'employeeCode' => $employee['employeeCode'] ?? '',
                    'employeeName' => $employee['employeeName'] ?? '',
                    'designation' => $employee['employeeDesignation'] ?? '',
                    'location' => $employee['location'] ?? '',
                    'resourceStatus' => $employee['resourceStatus'] ?? '',
                    'employeeExperience' => $employee['employeeExperience'] ?? 0,
                    'employeeSkills' => $employee['employeeSkills'] ?? [],
                    'matchedProjects' => []
                ];
            }

            // Add project-specific strong-match details.
            $strongMatchEmployees[$employeeId]['matchedProjects'][] = [

                'projectId' => $projectDetails['id'] ?? $project->id,
                'projectCode' => $projectDetails['projectCode'] ?? '',
                'projectName' => $projectDetails['projectName'] ?? '',
                'startDate' => $projectDetails['startDate'] ?? '',
                'endDate' => $projectDetails['endDate'] ?? '',

                // Matching percentages and scores.
                'mandatoryMatchPercentage' => $mandatoryMatchPercentage,
                'availabilityPercentage' => $availabilityPercentage,
                'experienceEligible' => $experienceEligible,
                'proficiencyEligible' => $proficiencyEligible,
                'assessmentScore' => $assessmentScore,

                'suitabilityScore' => $employee['suitabilityScore'] ?? 0,

                'matchedMandatorySkills' =>
                    $employee['matchedMandatorySkills'] ?? [],

                'recommendationExplanation' =>
                    $employee['recommendationExplanation'] ?? '',

                // Strong-match criteria.
                'strongMatchCriteria' => [
                    'mandatorySkillsMatched' =>
                        $mandatoryMatchPercentage >= 100,

                    'availabilityMatched' =>
                        $availabilityPercentage >= 100,

                    'experienceMatched' =>
                        $experienceEligible,

                    'proficiencyMatched' =>
                        $proficiencyEligible,

                    'assessmentPassed' =>
                        $assessmentScore > 60,
                ],

                'matchCategory' => 'Strong Match'
            ];
        }
    }

    $resources = array_values($strongMatchEmployees);

    return [
        'total' => count($resources),
        'resources' => $resources
    ];
}


  private function isStrongMatchCandidate(array $employee): bool
{
    return
        (float) ($employee['mandatoryMatchPercentage'] ?? 0) >= 100 &&

        // Must have 100% remaining capacity.
        (float) ($employee['remainingCapacity'] ?? 0) >= 100 &&

        // Must be available throughout the project.
        ($employee['availableAtProjectStart'] ?? false) === true &&
        ($employee['availableDuringProject'] ?? false) === true &&

        // Experience and proficiency requirements.
        ($employee['experienceEligible'] ?? false) === true &&
        ($employee['proficiencyEligible'] ?? false) === true &&

        // Assessment score must be greater than 60%.
        (float) ($employee['assessmentScore'] ?? 0) > 60;
}



public function getUpskillingCandidates(): array
{
    // Get all active projects
    $projects = $this->dashboardRepository->getActiveProjects();

    $upskillingEmployees = [];

    foreach ($projects as $project) {

        // Check employee eligibility for each project
        $eligibility = $this->checkProjectEligibility(
            (int) $project->id
        );

        $projectDetails = $eligibility['project'] ?? [];

        // Get partially available employees
        foreach ($eligibility['partialAvailability'] ?? [] as $employee) {

            $employeeId = (int) ($employee['employeeId'] ?? 0);

            if (!$employeeId) {
                continue;
            }

            // Get missing mandatory skills
            $missingSkills = $employee['missingMandatorySkills'] ?? [];

            // Include only employees requiring upskilling
            if (empty($missingSkills)) {
                continue;
            }

            // Create unique employee record
            if (!isset($upskillingEmployees[$employeeId])) {

                $upskillingEmployees[$employeeId] = [
                    'employeeId' => $employeeId,
                    'employeeCode' => $employee['employeeCode'] ?? '',
                    'employeeName' => $employee['employeeName'] ?? '',
                    'designation' => $employee['employeeDesignation'] ?? '',
                    'location' => $employee['location'] ?? '',
                    'resourceStatus' => $employee['resourceStatus'] ?? '',
                    'employeeExperience' => $employee['employeeExperience'] ?? 0,
                    'employeeSkills' => $employee['employeeSkills'] ?? [],
                    'matchedProjects' => []
                ];
            }

            // Add project details for this employee
            $upskillingEmployees[$employeeId]['matchedProjects'][] = [

                'projectId' => $projectDetails['id'] ?? $project->id,

                'projectCode' => $projectDetails['projectCode'] ?? '',

                'projectName' => $projectDetails['projectName'] ?? '',

                'startDate' => $projectDetails['startDate'] ?? '',

                'endDate' => $projectDetails['endDate'] ?? '',

                'mandatoryMatchPercentage' =>
                    $employee['mandatoryMatchPercentage'] ?? 0,

                'matchedMandatorySkills' =>
                    $employee['matchedMandatorySkills'] ?? [],

                'missingMandatorySkills' => $missingSkills,

                'recommendationExplanation' =>
                    $employee['recommendationExplanation'] ?? ''
            ];
        }
    }

    // Convert associative array into indexed array
    $resources = array_values($upskillingEmployees);

    return [
        'total' => count($resources),
        'resources' => $resources
    ];
}


}
