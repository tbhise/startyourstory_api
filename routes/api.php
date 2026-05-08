<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FirmController;
use App\Http\Controllers\API\FirmDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/registerStudent', [UserController::class, 'registerStudent']);
Route::post('/registerFirm',    [FirmController::class, 'registerFirm']);
Route::post('/login',           [AuthController::class, 'login']);

Route::post('/updateProfile',    [UserController::class, 'updateProfile']);
Route::post('/getProfile',       [UserController::class, 'getProfile']);

Route::post('/candidates',       [FirmDashboardController::class, 'getCandidates']);
Route::post('/candidate/{id}',   [FirmDashboardController::class, 'candidateDetail']);
Route::post('/downloadFile',     [FirmDashboardController::class, 'downloadFile']);


Route::post('/firm_profile_update',     [FirmController::class, 'firm_profile_update']);
Route::post('/getFirmProfileDetails',     [FirmController::class, 'getFirmProfileDetails']);



