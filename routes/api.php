<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FirmController;
use App\Http\Controllers\API\FirmDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Middleware\ApiAuthMiddleware;

Route::post('/registerStudent', [UserController::class, 'registerStudent']);
Route::post('/registerFirm',    [FirmController::class, 'registerFirm']);
Route::post('/login',           [AuthController::class, 'login']);

Route::middleware([ApiAuthMiddleware::class])->group(function () {
    Route::post('/updateProfile',    [UserController::class, 'updateProfile']);
    Route::post('/getProfile',       [UserController::class, 'getProfile']);
    Route::post('/updateProfileImage',       [UserController::class, 'updateProfileImage']);

    Route::post('/candidates',       [FirmDashboardController::class, 'getCandidates']);
    Route::post('/candidate/{id}',   [FirmDashboardController::class, 'candidateDetail']);
    Route::post('/downloadFile',     [FirmDashboardController::class, 'downloadFile']);

    Route::post('/firm_profile_update',       [FirmController::class, 'firm_profile_update']);
    Route::post('/getFirmProfileDetails',     [FirmController::class, 'getFirmProfileDetails']);
    Route::post('/getCompanies',              [FirmController::class, 'getCompanies']);
    Route::post('/getCompanyDetails/{id}',    [FirmController::class, 'getCompanyDetails']);
    Route::post('/createJob',                 [FirmController::class, 'createJob']);
    Route::post('/getFirmJobs',               [FirmController::class, 'getFirmJobs']);
    Route::post('/getFirmJobDetails/{id}',    [FirmController::class, 'getFirmJobDetails']);
    Route::post('/updateJobStatus/{id}',      [FirmController::class, 'updateJobStatus']);
    Route::post('/deleteFirmJob/{id}',        [FirmController::class, 'deleteFirmJob']);
});
