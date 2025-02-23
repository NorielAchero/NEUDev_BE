<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    /**
     * Get all classes
     */
    public function index()
    {
        return response()->json(Classroom::with('teacher', 'students')->get());
    }

    /**
     * Create a class (Only for Teachers)
     */
    public function store(Request $request)
    {
        \Log::info('Received Request Data:', $request->all());
    
        $request->validate([
            'className' => 'required|string|max:255',
        ]);
    
        $teacher = Auth::user();
    
        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $classroom = Classroom::create([
            'className' => $request->className,
            'teacherID' => $teacher->teacherID,
        ]);
    
        return response()->json($classroom, 201);
    }    

    /**
     * Get a specific class
     */
    public function show($id)
    {
        $classroom = Classroom::with('teacher', 'students')->find($id);

        if (!$classroom) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        return response()->json($classroom);
    }

    /**
     * Delete a class (Only for Teachers)
     */
    public function destroy($id)
    {
        $teacher = Auth::user();

        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $classroom = Classroom::where('classID', $id)->where('teacherID', $teacher->teacherID)->first();

        if (!$classroom) {
            return response()->json(['message' => 'Class not found or you are not authorized to delete this class'], 404);
        }

        $classroom->delete();

        return response()->json(['message' => 'Class deleted successfully']);
    }

    /**
     * Enroll a student in a class
     */
    public function enrollStudent(Request $request, $classID)
    {
        $student = Auth::user();

        if (!$student || !$student instanceof \App\Models\Student) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $classroom = Classroom::find($classID);

        if (!$classroom) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        // Check if student is already enrolled
        if ($classroom->students()->where('students.studentID', $student->studentID)->exists()) {
            return response()->json(['message' => 'Student is already enrolled in this class'], 409);
        }

        // Attach student to class
        $classroom->students()->attach($student->studentID);

        return response()->json(['message' => 'Enrolled successfully']);
    }

    /**
     * Unenroll a student from a class
     */
    public function unenrollStudent(Request $request, $classID)
    {
        $student = Auth::user();

        if (!$student || !$student instanceof \App\Models\Student) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $classroom = Classroom::find($classID);

        if (!$classroom) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        // Check if student is actually enrolled before detaching
        if (!$classroom->students()->where('students.studentID', $student->studentID)->exists()) {
            return response()->json(['message' => 'Student is not enrolled in this class'], 409);
        }

        // Detach student from class
        $classroom->students()->detach($student->studentID);

        return response()->json(['message' => 'Unenrolled successfully']);
    }
}