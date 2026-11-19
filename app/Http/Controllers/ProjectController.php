<?php

namespace App\Http\Controllers;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use App\Http\Requests\ProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
      public function __construct(
        private ProjectService $projectService
    ) {}

     public function index(Request $request): JsonResponse
    {
      
      $projects = $this->projectService->getAll();

        if ($projects->isEmpty()) {
            return response()->json([
                'message' => 'No records exist',
                'data' => [],
            ], 200);
        }

    return response()->json($projects);
    }

     public function createProject(ProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->createProject(
            $request->validated()
        );

        return response()->json([
            'message' => 'Project created successfully',
            'data' => $project,
        ], 201);
    }

        public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $data = $this->projectService->updateProject($id,$request->validated()
    );

        if (!$data) {
            return response()->json([
                'message' => 'Project not found',
            ], 404);
        }

        $data = $this->projectService->updateProject(
            $id,
            $request->validated()
        );

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => $data,
        ]);
    }
    public function delete(int $id): JsonResponse
    {
        $data = $this->projectService->deleteProject($id);
        if (!$data) {
            return response()->json([
                'message' => 'Project not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }


    public function getProjectById(int $id): JsonResponse
    {
        $project = $this->projectService->getProjectById($id);
        if (!$project) {
        return response()->json([
            'message' => 'Project not found',
        ], 404);
    }

        return response()->json([
            'message' => 'Project retrieved successfully',
            'data' => $project,
        ]);
    }


     public function getDropdownProject():JsonResponse{
        $project = $this->projectService->getDropdownProject();
        return response()->json([
            'message' => 'Project retrieved successfully',
            'data' => $project
        ]);
    }
}
