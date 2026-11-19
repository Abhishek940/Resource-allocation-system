<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DashboardContrller;
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

Route::middleware('auth:api')->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/project/{id}',[ProjectController:: class,'getProjectById']);
    Route::post('/create/project', [ProjectController::class, 'createProject']);
    Route::post('/update/project/{id}',[ProjectController::class,'update']);
    Route::delete('/delete/project/{id}',[ProjectController::class,'delete']);
    Route::get('/projectdropdown',[ProjectController::class,'getDropdownProject']);
    // dashboard route
    Route::get('/dashboard',[DashboardContrller::class,'getDashboardcount']);
    Route::get('/dashboard/matching-resources', [DashboardContrller::class, 'getMatchingResources']);
    route::get('/dashboard/strong-match-candidates',[DashboardContrller::class,'getstrongMatchCandidates']);
    Route::get('/dashboard/upskilling-candidates',[DashboardContrller::class, 'getUpskillingCandidates']);

   
  
});
