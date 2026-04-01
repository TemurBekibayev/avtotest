<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\TestTemplateController;
use App\Http\Controllers\API\TestResultController;
use App\Http\Controllers\API\TestQuestionController;
use App\Http\Controllers\API\TrafficSignController;
use App\Http\Controllers\API\RoadSignController;
use App\Http\Controllers\API\InstructorController;

/* |-------------------------------------------------------------------------- | API Routes |-------------------------------------------------------------------------- */

Route::post('/register', [AuthController::class , 'register']);
Route::post('/login', [AuthController::class , 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Fallback for unauthenticated API requests to prevent "Route [login] not defined" error
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class , 'logout']);
    Route::get('/me', [AuthController::class , 'me']);

    Route::apiResource('students', StudentController::class);
    Route::apiResource('groups', GroupController::class);
    Route::apiResource('test-templates', TestTemplateController::class);
    Route::apiResource('test-results', TestResultController::class);
    Route::get('/test-questions/random', [TestQuestionController::class , 'random']);
    Route::apiResource('test-questions', TestQuestionController::class);
    Route::get('/traffic-signs', [TrafficSignController::class, 'index']);

    Route::apiResource('instructors', InstructorController::class);

    Route::get('/road-sign-types', [RoadSignController::class, 'getTypes']);
    Route::get('/road-signs', [RoadSignController::class, 'getSigns']);
});
