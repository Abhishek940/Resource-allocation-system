<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\ResourceEligibilityService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Throwable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
class ResourceEligibilityController extends Controller
{
    protected ResourceEligibilityService $eligibilityService;

    public function __construct(
        ResourceEligibilityService $eligibilityService
    ) {
        $this->eligibilityService = $eligibilityService;
    }



public function getProjects(): JsonResponse
{
    try {
        $projects = $this->eligibilityService->getProjects();

        return response()->json([
            'success' => true,
            'message' => 'Projects fetched successfully.',
            'data' => $projects,
        ], 200);

    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'message' => 'Unable to fetch projects.',
        ], 500);
    }
}

   public function getProjectById(string $projectId): JsonResponse
    {
        try {
            $project = $this->eligibilityService
                ->getProjectById($projectId);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Project details fetched successfully.',
                'data' => $project,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch project details.',
            ], 500);
        }
    }

   public function getActiveEmployees(): JsonResponse
    {
        try {
            $employees = $this->eligibilityService
                ->getActiveEmployees();

            return response()->json([
                'success' => true,
                'message' => 'Employees fetched successfully.',
                'data' => $employees,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch employees.',
            ], 500);
        }
    }

      public function insertAllocation(Request $request): JsonResponse
        {
            $validated = $request->validate([
                'employee_id' => ['required', 'integer'],
                'project_id' => ['required', 'integer'],
                'allocation_status' => ['required'],
                'allocation_start' => ['required','date_format:d-m-Y'],
                'allocation_end' => ['required','date_format:d-m-Y','after_or_equal:allocation_start',],
                'allocation_percentage' => ['required','numeric','gt:0','max:100',
                ],
            ]);

            try {
                 // Convert DD-MM-YYYY to YYYY-MM-DD 
            $validated['allocation_start'] = Carbon::createFromFormat(
                'd-m-Y',
                $validated['allocation_start']
            )->format('Y-m-d');

            $validated['allocation_end'] = Carbon::createFromFormat(
                'd-m-Y',
                $validated['allocation_end']
            )->format('Y-m-d');


                $id = $this->eligibilityService->insertAllocation($validated);
                $data = DB::table('employee_project_allocations')
                ->where('id', $id)
                ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Allocation created successfully.',
                    'data' => $data,
                ], 201);

            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to create allocation.',
                ], 500);
            }
        }

    public function getProjectAllocationlist(){

       try {
            $employees = $this->eligibilityService->getProjectAllocationlist();

            return response()->json([
                'success' => true,
                'message' => 'Data fetched successfully.',
                'data' => $employees,
            ], 200);

        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch employees.',
            ], 500);
        }
    }
    
   public function deleteProjectAllocation(int $id){

       try{
        
        $data = $this->eligibilityService->deleteProjectallocation($id);

        if(!$data){
            return response()->json([
                'success'=>true,
                'message'=>'Record not found'
            ],404);
        }
        return response()->json([
            'success'=>true,
            'message'=>'Record deleted successfully'
        ],200);

       }catch(Throwable $e){
        return response()->json([
            'success'=>false,
            'message'=>'unable to delete record',
            'error'=>$e->getMessage()
        ],500);
       }
     }

       public function getConfirmedAllocations(Request $request,int $employeeId): JsonResponse
          {
                $validated = $request->validate([
                    'startDate' => ['required'],
                    'endDate' => [
                        'required',
                        'after_or_equal:startDate',
                    ],
                ]);

                try {
                    $allocations = $this->eligibilityService->getConfirmedAllocations(
                        $employeeId,
                        $validated['startDate'],
                        $validated['endDate']
                    );

                    return response()->json([
                        'success' => true,
                        'message' => 'Allocations fetched successfully.',
                        'data' => $allocations,
                    ], 200);

                } catch (Throwable $e) {
                    report($e);

                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to fetch allocations.',
                    ], 500);
                }
            }

   
  
    public function checkProjectEligibility(int $projectId): JsonResponse
    {
        try {
            $result = $this->eligibilityService->checkProjectEligibility($projectId);

            return response()->json([
                'success' => true,
                'message' => 'Resource eligibility checked successfully.',
                'data' => $result,
            ], 200);

        } catch (InvalidArgumentException $e) {
            $statusCode = $e->getMessage() === 'Project not found.'
                ? 404: 422;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to check resource eligibility.',
            ], 500);
        }
    }

  
   // Module 4: Get resource matches for a project
    public function rankResources(int $projectId): JsonResponse
    {
        $matches = $this->eligibilityService->getResourceMatches($projectId);

        return response()->json([
           // 'success' => true,
          //  'projectId' => $projectId,
           // 'totalMatches' => count($matches),
            'data' => $matches,
        ]);
    }

    /**
     * Get Generative AI Project Squad Deployment Recommendation.
     */
    public function getAiDeploymentRecommendation(int $projectId): JsonResponse
    {
        try {
            $recommendation = $this->eligibilityService->getAiDeploymentRecommendation($projectId);

            return response()->json([
                'success' => true,
                'message' => 'AI deployment recommendation generated successfully.',
                'data' => $recommendation,
            ], 200);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate AI deployment recommendation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Generative AI for an Individual Resource.
     */
    public function getCandidateAiInsights(int $projectId, int $employeeId): JsonResponse
    {
        try {
            $insights = $this->eligibilityService->getCandidateAiInsights($projectId, $employeeId);

            return response()->json([
                'success' => true,
                'message' => 'Candidate AI deployment insights generated successfully.',
                'data' => $insights,
            ], 200);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate candidate AI insights: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Semantic Skill Matching between required skills and candidate skills using AI.
     */
    public function matchSkillsWithAi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requiredSkills' => ['required', 'array'],
            'candidateSkills' => ['required', 'array'],
        ]);

        try {
            $analysis = $this->eligibilityService->matchSkillsWithAi(
                $validated['requiredSkills'],
                $validated['candidateSkills']
            );

            return response()->json([
                'success' => true,
                'message' => 'AI semantic skill analysis completed successfully.',
                'data' => $analysis,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to complete AI skill analysis: ' . $e->getMessage(),
            ], 500);
        }
    }

   
   /*  public function getAiStatus(): JsonResponse
    {
        $status = $this->eligibilityService->checkAiStatus();
        return response()->json($status);
    } */


   public function askAgent(Request $request): JsonResponse
    {
        $prompt = $request->input('prompt', 'Hello, write a short status update.');
        $answer = $this->eligibilityService->askAgent($prompt);

        if (!$answer) {
            return response()->json(['error' => 'Failed to fetch response from AI Agent.'], 500);
        }

        return response()->json([
            'status' => 'success',
            'agent_response' => $answer
        ]);
    }

   public function getAiStatus(): JsonResponse
{
    $status = $this->eligibilityService->checkStatus();
    $httpCode = ($status['success'] === true) ? 200 : ($status['httpStatus'] ?? 500);

    return response()->json($status, $httpCode);
}

// model 5

 public function skillGapAnalysis(int $projectId,int $employeeId): JsonResponse {

    try {

        $result = $this->eligibilityService->skillGapAnalysis($projectId,$employeeId );

        return response()->json([
            'success' => true,
            'message' => 'Skill gap analysis .',
            'data' => $result,
        ], 200);

    } catch (InvalidArgumentException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);

    } catch (Throwable $e) {

        report($e);

        return response()->json([
            'success' => false,
            'message' => 'Unable to perform skill gap analysis.',
        ], 500);
    }
}
    
}
