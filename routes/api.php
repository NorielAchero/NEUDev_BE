<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileStudentController;
use App\Http\Controllers\Api\ProfileTeacherController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ItemController; // Renamed from QuestionController if applicable
use App\Http\Controllers\Api\ItemTypeController;
use App\Http\Controllers\Api\ProgrammingLanguageController;
use App\Http\Controllers\AssessmentController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// Fallback route for undefined API calls
Route::fallback(function () {
    Log::warning('Invalid API Request:', [
        'url' => request()->url(),
        'method' => request()->method(),
        'headers' => request()->header(),
        'body' => request()->all()
    ]);
    return response()->json([
        'message' => 'Route not found. Please check your API endpoint.',
        'requested_url' => request()->url(),
        'status' => 404
    ], 404);
});

// Authentication Routes (Public)
Route::controller(AuthController::class)->group(function () {
    Route::post('/register/student', 'registerStudent');
    Route::post('/register/teacher', 'registerTeacher');
    Route::post('/login', 'login');
});

// Protected Routes (Requires Authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => $user,
            'user_type' => $user instanceof \App\Models\Student ? 'student' :
                ($user instanceof \App\Models\Teacher ? 'teacher' : 'unknown'),
        ]);
    });

    // -------------------------------
    // Student Routes
    // -------------------------------
    Route::prefix('student')->group(function () {
        Route::controller(ProfileStudentController::class)->group(function () {
            Route::get('/profile/{student}', 'show');
            Route::put('/profile/{student}', 'update');
            Route::delete('/profile/{student}', 'destroy');
        });

        Route::prefix('class')->group(function () {
            Route::post('{classID}/enroll', [ClassroomController::class, 'enrollStudent']);
            Route::delete('{classID}/unenroll', [ClassroomController::class, 'unenrollStudent']);
        });

        Route::get('/classes', [ClassroomController::class, 'getStudentClasses']);

        // Student Activity endpoints
        Route::controller(ActivityController::class)->group(function () {
            Route::get('/activities', 'showStudentActivities');
        });
        Route::get('/activities/{actID}/items', [ActivityController::class, 'showActivityItemsByStudent']);
        Route::get('/activities/{actID}/leaderboard', [ActivityController::class, 'showActivityLeaderboardByStudent']);
    });

    // -------------------------------
    // Teacher Routes
    // -------------------------------
    Route::prefix('teacher')->group(function () {
        Route::controller(ProfileTeacherController::class)->group(function () {
            Route::get('/profile/{teacher}', 'show');
            Route::put('/profile/{teacher}', 'update');
            Route::delete('/profile/{teacher}', 'destroy');
        });

        Route::controller(ClassroomController::class)->group(function () {
            Route::get('/classes', 'index');
            Route::post('/class', 'store');
            Route::get('/class/{id}', 'show');
            Route::get('/class-info/{id}', 'showClassInfo');
            Route::put('/class/{id}', 'update');
            Route::delete('/class/{id}', 'destroy');
            Route::get('/class/{classID}/students', 'getClassStudents');
            Route::delete('/class/{classID}/unenroll/{studentID}', 'unenrollStudent');
        });

        // Teacher Activity endpoints
        Route::controller(ActivityController::class)->group(function () {
            Route::post('/activities', 'store');
            Route::get('/class/{classID}/activities', 'showClassActivities');
            Route::get('/activities/{actID}', 'show');
            Route::put('/activities/{actID}', 'update');
            Route::delete('/activities/{actID}', 'destroy');
        });

        // Item Controller endpoints
        // Note: endpoints updated from /questions to /items for consistency.
        Route::controller(ItemController::class)->group(function () {
            Route::get('/items/itemType/{itemTypeID}', 'getByItemType');
            Route::post('/items', 'store');
            Route::get('/items/{itemID}', 'show');
            Route::put('/items/{itemID}', 'update');
            Route::delete('/items/{itemID}', 'destroy');
        });

        Route::get('/itemTypes', [ItemTypeController::class, 'index']);

        Route::controller(ProgrammingLanguageController::class)->group(function () {
            Route::get('/programmingLanguages', 'get');
            Route::post('/programmingLanguages', 'store');
            Route::get('/programmingLanguages/{id}', 'show');
            Route::put('/programmingLanguages/{id}', 'update');
            Route::delete('/programmingLanguages/{id}', 'destroy');
        });

        Route::get('/activities/{actID}/items', [ActivityController::class, 'showActivityItemsByTeacher']);
        Route::get('/activities/{actID}/leaderboard', [ActivityController::class, 'showActivityLeaderboardByTeacher']);
        Route::get('/activities/{actID}/settings', [ActivityController::class, 'showActivitySettingsByTeacher']);
        Route::put('/activities/{actID}/settings', [ActivityController::class, 'updateActivitySettingsByTeacher']);
    });

    // -------------------------------
    // Assessment Routes
    // -------------------------------
    Route::controller(AssessmentController::class)->group(function () {
        Route::get('/assessments', 'index');
        Route::post('/assessments', 'store');
        Route::get('/assessments/{id}', 'show');
        Route::put('/assessments/{id}', 'update');
        Route::delete('/assessments/{id}', 'destroy');
    });
});