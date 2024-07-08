<?php
use App\Http\Controllers\API\V1\IssueController;
use App\Http\Controllers\API\V1\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('/v1/issues', IssueController::class);
Route::apiResource('/v1/students', StudentController::class);
Route::apiResource('/v1/media', MediaController::class);
