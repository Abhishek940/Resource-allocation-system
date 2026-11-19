<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

   public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        return response()->json(
            $this->employeeService->getAllEmployees($perPage)
        );
    }
    public function store(EmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->createEmployee(
            $request->validated()
        );

        return response()->json([
            'message' => 'Employee created successfully',
            'data' => $employee,
        ], 201);
    }

   public function show(int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployeeById($id);
        if (!$employee) {
        return response()->json([
            'message' => 'Employee not found',
        ], 404);
    }

        return response()->json([
            'message' => 'Employee retrieved successfully',
            'data' => $employee,
        ]);
    }

    public function update(EmployeeRequest $request, $id): JsonResponse {
        $employee = $this->employeeService->getEmployeeById($id);
        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found',
            ], 404);
        }

        $data = $this->employeeService->updateEmployee($id,
            $request->validated()
        );

        return response()->json([
            'message' => 'Employee updated successfully',
            'data' => $data,
        ]);
    }

   public function destroy(int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployeeById($id);
        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found',
            ], 404);
        }

        $this->employeeService->deleteEmployee($id);

        return response()->json([
            'message' => 'Employee deleted successfully',
        ]);
    }

    public function getAllEmployeesList():JsonResponse{
        $employees = $this->employeeService->getAllEmployeesList();
        return response()->json([
            'message' => 'Employees retrieved successfully',
            'data' => $employees
        ]);
    }
}