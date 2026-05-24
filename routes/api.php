<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FirmController;
use App\Http\Controllers\API\FirmDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Controllers\API\MasterController;
use App\Http\Controllers\API\JobsController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\AdminController;

Route::post('/registerStudent', [UserController::class, 'registerStudent']);
Route::post('/registerFirm',    [FirmController::class, 'registerFirm']);
Route::post('/login',           [AuthController::class, 'login']);
Route::get('/me',               [AuthController::class, 'me']);


Route::middleware([ApiAuthMiddleware::class])->group(function () {
    Route::post('/updateProfile',    [UserController::class, 'updateProfile']);
    Route::post('/getProfile',       [UserController::class, 'getProfile']);
    Route::post('/updateProfileImage',       [UserController::class, 'updateProfileImage']);
    Route::post('/students/{id}/track-recruiter-action',       [UserController::class, 'trackRecruiterAction']);

    Route::post('/candidates',       [FirmDashboardController::class, 'getCandidates']);
    Route::post('/candidate/{id}',   [FirmDashboardController::class, 'candidateDetail']);
    Route::post('/downloadFile',     [FirmDashboardController::class, 'downloadFile']);
    Route::post('/notifications',     [FirmDashboardController::class, 'getNotifications']);

    Route::post('/firm_profile_update',       [FirmController::class, 'firm_profile_update']);
    Route::post('/getFirmProfileDetails',     [FirmController::class, 'getFirmProfileDetails']);
    Route::post('/getCompanies',              [FirmController::class, 'getCompanies']);
    Route::post('/getCompanyDetails/{id}',    [FirmController::class, 'getCompanyDetails']);
    Route::post('/createJob',                 [FirmController::class, 'createJob']);
    Route::post('/getFirmJobs',               [FirmController::class, 'getFirmJobs']);
    Route::get('/getJobs',                    [FirmController::class, 'getJobs']);



    Route::post('/getFirmJobDetails/{id}',    [FirmController::class, 'getFirmJobDetails']);
    Route::post('/updateJobStatus/{id}',      [FirmController::class, 'updateJobStatus']);
    Route::post('/deleteFirmJob/{id}',        [FirmController::class, 'deleteFirmJob']);
    Route::post('/updateJob/{id}',            [FirmController::class, 'updateJob']);
    Route::post('/searchFirms',               [FirmController::class, 'searchFirms']);


    // Jobs

    Route::post('/jobs/{id}/apply',                     [JobsController::class, 'applyJob']);
    Route::post('/jobs/{id}/save',                      [JobsController::class, 'saveJob']);
    Route::delete('/jobs/{id}/save',                    [JobsController::class, 'saveJob']);
    Route::post('/getAppliedJobs',                      [JobsController::class, 'getAppliedJobs']);
    Route::post('/getSavedJobs',                        [JobsController::class, 'getSavedJobs']);
    Route::post('/getApplications/{id}',                [JobsController::class, 'getApplications']);
    Route::post('/applications/{id}/updateStatus',      [JobsController::class, 'updateApplicationStatus']);
    Route::post('/applications/{id}/schedule-interview',     [JobsController::class, 'scheduleInterview']);
    Route::post('/getRecruiterActions',                        [JobsController::class, 'getRecruiterActions']);
    Route::post('/applications/{id}/respondInterview',                 [JobsController::class, 'respondInterview']);


    Route::post('/mark-read',        [NotificationController::class, 'markAsRead']);
});



Route::post('/admin/login',   [AdminController::class, 'login']);
Route::get('/admin/me',       [AdminController::class, 'me']);
Route::post('/admin/logout',  [AdminController::class, 'logout']);

Route::post('/master/cities',              [MasterController::class, 'getCities']);
Route::post('/master/companies',           [MasterController::class, 'getCompanies']);
Route::post('/admin/subscriptions',        [MasterController::class, 'getAdminSubscriptions']);
Route::post('/admin/addSubscriptions',     [MasterController::class, 'addSubscriptions']);
Route::post('/premium-requests',           [MasterController::class, 'submitPremiumRequest']);
Route::post('/admin/premium-requests',     [MasterController::class, 'getPremiumRequests']);
