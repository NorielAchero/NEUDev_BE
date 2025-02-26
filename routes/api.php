<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileStudentController;
use App\Http\Controllers\Api\ProfileTeacherController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\ItemTypeController;
use App\Http\Controllers\Api\ProgrammingLanguageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// 📌 Fallback route for undefined API calls
Route::fallback(function () {
    Log::warning('⚠️ Invalid API Request:', [
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

// 📌 Authentication Routes (Public)
Route::controller(AuthController::class)->group(function () {
    Route::post('/register/student', 'registerStudent'); // Register Student
    Route::post('/register/teacher', 'registerTeacher'); // Register Teacher
    Route::post('/login', 'login');                      // Login (both roles)
});

// 📌 Protected Routes (Requires Authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // 🔹 Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // 🔹 Get Authenticated User Info
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => $user,
            'user_type' => $user instanceof \App\Models\Student ? 'student' :
                          ($user instanceof \App\Models\Teacher ? 'teacher' : 'unknown'),
        ]);
    });

    // 📌 Student Routes
    Route::prefix('student')->group(function () {
        Route::controller(ProfileStudentController::class)->group(function () {
            Route::get('/profile/{student}', 'show');     // View student profile
            Route::put('/profile/{student}', 'update');   // Update student profile
            Route::delete('/profile/{student}', 'destroy'); // Delete student profile
        });

        // 📌 Student Enrollment Routes
        Route::prefix('class')->group(function () {
            Route::post('{classID}/enroll', [ClassroomController::class, 'enrollStudent']); // Enroll student
            Route::delete('{classID}/unenroll', [ClassroomController::class, 'unenrollStudent']); // Unenroll student
        });

        Route::controller(ActivityController::class)->group(function () {
            Route::get('/activities', 'showStudentActivities'); // Get all student activities
        });
        Route::get('/activities/{actID}/items', [ActivityController::class, 'showActivityItemsByStudent']);
        Route::get('/activities/{actID}/leaderboard', [ActivityController::class, 'showActivityLeaderboardByStudent']);

    });

    // 📌 Teacher Routes
    Route::prefix('teacher')->group(function () {
        Route::controller(ProfileTeacherController::class)->group(function () {
            Route::get('/profile/{teacher}', 'show');     // View teacher profile
            Route::put('/profile/{teacher}', 'update');   // Update teacher profile
            Route::delete('/profile/{teacher}', 'destroy'); // Delete teacher profile
        });

        Route::controller(ClassroomController::class)->group(function () {
            Route::get('/classes', 'index'); // Get all classes
            Route::post('/class', 'store'); // Create a class
            Route::get('/class/{id}', 'show'); // Get class details
            Route::delete('/class/{id}', 'destroy'); // Delete a class
        });

        // CLASS MANAGEMENT PAGE VIA ACTIVITY
        Route::controller(ActivityController::class)->group(function () {
            Route::post('/activities', 'store'); // Create an activity
            Route::get('/class/{classID}/activities', 'showClassActivities'); // Get activities for a class
            Route::get('/activities/{actID}', 'show'); // Get a specific activity
            Route::put('/activities/{actID}', 'update'); // Edit an activity
            Route::delete('/activities/{actID}', 'destroy'); // Delete an activity
        });

        Route::controller(QuestionController::class)->group(function () {
            Route::get('/questions/itemType/{itemTypeID}', 'getByItemType'); // Get preset questions by item type
            Route::post('/questions', 'store'); // ✅ Create a question (with test cases)
            Route::get('/questions/{questionID}', 'show'); // ✅ Get a single question (with test cases)
            Route::put('/questions/{questionID}', 'update'); // ✅ Update a question (and test cases)
            Route::delete('/questions/{questionID}', 'destroy'); // ✅ Delete a question (removes test cases)
        });
        
        // ITEM TYPES
        Route::get('/itemTypes', [ItemTypeController::class, 'index']);

        // PROGRAMMING LANGUAGES
        Route::controller(ProgrammingLanguageController::class)->group(function () {
            Route::get('/programmingLanguages', 'get'); // ✅ Get all programming languages
            Route::post('/programmingLanguages', 'store'); // ✅ Add a new programming language
            Route::get('/programmingLanguages/{id}', 'show'); // ✅ Get a single language by ID
            Route::put('/programmingLanguages/{id}', 'update'); // ✅ Update a programming language
            Route::delete('/programmingLanguages/{id}', 'destroy'); // ✅ Delete a programming language
        });

        // ACTIVITY MANAGEMENT PAGE
        Route::get('/activities/{actID}/items', [ActivityController::class, 'showActivityItemsByTeacher']);
        Route::get('/activities/{actID}/leaderboard', [ActivityController::class, 'showActivityLeaderboardByTeacher']);
        Route::get('/activities/{actID}/settings', [ActivityController::class, 'showActivitySettingsByTeacher']); // Fetch settings
        Route::put('/activities/{actID}/settings', [ActivityController::class, 'updateActivitySettingsByTeacher']); // Update settings

    });
});