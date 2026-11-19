<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
     public function __construct(
        private RoleService $roleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        return response()->json(
            $this->roleService->getAllRoles($perPage)
        );
    }
    public function store(RoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createRole(
            $request->validated()
        );

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $role,
        ], 201);
    }

       public function show(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleById($id);
        if (!$role) {
        return response()->json([
            'message' => 'Role not found',
        ], 404);
    }

        return response()->json([
            'message' => 'Role retrieved successfully',
            'data' => $role,
        ]);
    }

    public function update(
        RoleRequest $request,
        int $role
    ): JsonResponse {
        $data = $this->roleService->updateRole(
            $role,
            $request->validated()
        );

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $data,
        ]);
    }

    public function destroy(int $role): JsonResponse
    {
        $this->roleService->deleteRole($role);

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }
}
