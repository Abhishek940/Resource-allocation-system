<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AuthController;
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

Route::post('/login',[AuthController::class,'login']);
Route::middleware('auth:api')->group(function () {
    Route::get('/profile', [Authcontroller::class,'me']);
    Route::post('/logout', [AuthController::class, 'logout']);


Route::prefix('roles')->group(function (){
     Route::match(['get', 'post'], '/', [RoleController::class, 'index']);
     Route::post('/create/role',[RoleController::class,'store']);
     Route::get('/role/{id}',[RoleController::class,'show']);
     Route::match(['post', 'put'], '/update/role/{id}',[RoleController::class,'update']);
     Route::match(['delete', 'post'], '/delete/role/{id}',[RoleController::class,'destroy']);
});

Route::prefix('employees')->group(function (){
     Route::match(['get', 'post'], '/', [EmployeeController::class, 'index']);
     Route::post('/create/employee',[EmployeeController::class,'store']);
     Route::get('/employee/{id}',[EmployeeController:: class,'show']);
     Route::match(['post', 'put'], '/update/employee/{id}',[EmployeeController::class,'update']);
     Route::match(['delete', 'post'], '/delete/employee/{id}',[EmployeeController::class,'destroy']);
     Route::get('/employeeslist',[EmployeeController::class,'getAllEmployeesList']);
});

});