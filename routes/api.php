<?php
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\V1\IssueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('/students', StudentController::class);
Route::apiResource('/v1/issues', IssueController::class);
