<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    // Student Registration
    public function registerStudent(Request $request)
    {
        \Log::info('Student Register Request:', $request->all());

        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => [
                'required', 'email', 'regex:/@neu\.edu\.ph$/',
                Rule::unique('students', 'email'),
                Rule::unique('teachers', 'email'),
            ],
            'student_num' => ['required', 'regex:/^\d{2}-\d{5}-\d{3}$/'],
            'program' => ['required', Rule::in(['BSCS', 'BSIT', 'BSEMC', 'BSIS'])],
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Student::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'student_num' => $request->student_num,
            'program' => $request->program,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Student registered successfully',
            'user_type' => 'student',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    // Teacher Registration
    public function registerTeacher(Request $request)
    {
        \Log::info('Teacher Register Request:', $request->all());

        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => [
                'required', 'email', 'regex:/@neu\.edu\.ph$/',
                Rule::unique('students', 'email'),
                Rule::unique('teachers', 'email'),
            ],
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Teacher::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Teacher registered successfully',
            'user_type' => 'teacher',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    // Login Method
    public function login(Request $request)
    {
        \Log::info('Login Attempt:', ['email' => $request->email]);
    
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|regex:/@neu\.edu\.ph$/',
            'password' => 'required|string|min:8',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Check if user exists in either table
        $student = Student::where('email', $request->email)->first();
        $teacher = Teacher::where('email', $request->email)->first();
        $user = $student ?? $teacher;
        $userType = $student ? 'student' : ($teacher ? 'teacher' : null);
    
        \Log::info('User Data:', [
            'userType' => $userType,
            'studentID' => $student ? $student->studentID : null,
            'teacherID' => $teacher ? $teacher->teacherID : null,
            'email' => $user ? $user->email : 'Not Found'
        ]);
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            \Log::warning('Failed login attempt', ['email' => $request->email]);
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }
    
        try {
            $token = $user->createToken('auth_token')->plainTextToken;
        } catch (\Exception $e) {
            \Log::error('Token Creation Error:', ['email' => $request->email, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to generate token. Please try again.',
            ], 500);
        }
    
        \Log::info('Login Success', [
            'email' => $request->email,
            'user_type' => $userType,
            'studentID' => $student ? $student->studentID : null,
            'teacherID' => $teacher ? $teacher->teacherID : null

        ]);
    
        return response()->json([
            'message' => 'Login Successful',
            'user_type' => $userType,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'password' => $request->password, // TEMPORARY: Remove after debugging
            'studentID' => $student ? $student->studentID : null,  // Correct primary key
            'teacherID' => $teacher ? $teacher->teacherID : null   // Correct primary key
        ], 200);
    }
    

    // Logout Method
    public function logout()
    {
        Auth::user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }
}