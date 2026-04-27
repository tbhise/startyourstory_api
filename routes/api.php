<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FirmController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/registerStudent', [UserController::class, 'registerStudent']);
Route::post('/registerFirm',    [FirmController::class, 'registerFirm']);
Route::post('/login',       [AuthController::class, 'login']);
