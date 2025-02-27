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
        $teacher = Auth::user();
    
        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        // Fetch only classes created by this teacher
        $classes = Classroom::where('teacherID', $teacher->teacherID)->with('students')->get();
    
        return response()->json($classes);
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
            'classSection' => $request->classSection,
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
    
        return response()->json([
            'classID' => $classroom->classID,
            'className' => $classroom->className,
            'classSection' => $classroom->classSection, 
            'teacher' => [
                'teacherID' => $classroom->teacher->teacherID,
                'teacherName' => "{$classroom->teacher->firstname} {$classroom->teacher->lastname}",
            ],
            'students' => $classroom->students->map(function ($student) {
                return [
                    'studentID' => $student->studentID,
                    'firstname' => $student->firstname,
                    'lastname' => $student->lastname,
                    'email' => $student->email,
                ];
            }),
        ]);
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


    /**
     * Get only the classes a student is enrolled in
     */
    public function getStudentClasses()
    {
        $student = Auth::user();
    
        if (!$student || !$student instanceof \App\Models\Student) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        // Fetch only the classes where the student is enrolled, with teacher details
        $classes = $student->classes()->with('teacher')->get(); 
    
        // Transform response to include teacher's full name
        $formattedClasses = $classes->map(function ($class) {
            return [
                'classID' => $class->classID,
                'className' => $class->className,
                'section' => $class->classsection, 
                'teacherName' => $class->teacher ? "{$class->teacher->firstname} {$class->teacher->lastname}" : 'Unknown Teacher'
            ];
        });
    
        return response()->json($formattedClasses);
    }

}