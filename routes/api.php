<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResourceEligibilityController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/* Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
}); */

//Route::middleware('auth:api')->group(function () {
    Route::get('/projects', [ResourceEligibilityController::class,'getProjects']);
    Route::get('/projects/{projectId}',[ResourceEligibilityController::class, 'getProjectById'])->whereNumber('projectId');
    Route::get('/employees/active',[ResourceEligibilityController::class, 'getActiveEmployees']);
    Route::post('/allocations',[ResourceEligibilityController::class, 'insertAllocation']);
    Route::get('/allocationslist',[ResourceEligibilityController::class, 'getProjectAllocationlist']);
    Route::delete('/delete/allocation/{id}',[ResourceEligibilityController::class,'deleteProjectAllocation']);
    Route::get('/employees/{employeeId}/allocations',[ResourceEligibilityController::class, 'getConfirmedAllocations']);
    Route::get('/projects/{projectId}/eligibility',[ResourceEligibilityController::class, 'checkProjectEligibility']);

    // module 4
    Route::get('/projects/{projectId}/resource-matches', [ResourceEligibilityController::class, 'rankResources']);
    Route::get('/projects/{projectId}/ai-deployment-recommendation', [ResourceEligibilityController::class, 'getAiDeploymentRecommendation']);
    Route::get('/projects/{projectId}/candidate/{employeeId}/ai-insights', [ResourceEligibilityController::class, 'getCandidateAiInsights']);
    Route::post('/ai/match-skills', [ResourceEligibilityController::class, 'matchSkillsWithAi']);
   // Route::get('/ai/status', [ResourceEligibilityController::class, 'getAiStatus']);

    //Route::get('/ai/status', [ResourceEligibilityController::class, 'getAiStatus']);
      Route::get('/ai/status', [ResourceEligibilityController::class, 'getAiStatus']);
    // skill gap route
    Route::get('/skill-gap-analysis/{projectId}/{employeeId}',[ResourceEligibilityController::class, 'skillGapAnalysis']);  

//});