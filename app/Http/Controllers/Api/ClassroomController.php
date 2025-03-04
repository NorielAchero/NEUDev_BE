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


    // In ClassroomController.php

    /**
     * Update a class (Only for Teachers)
     */
    public function update(Request $request, $id)
    {
        $teacher = Auth::user();

        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find the class created by this teacher
        $classroom = Classroom::where('classID', $id)
            ->where('teacherID', $teacher->teacherID)
            ->first();

        if (!$classroom) {
            return response()->json(['message' => 'Class not found or you are not authorized to update this class'], 404);
        }

        // Validate incoming data
        $request->validate([
            'className'    => 'required|string|max:255',
            'classSection' => 'required|string|max:255'
        ]);

        // Update the fields
        $classroom->className    = $request->className;
        $classroom->classSection = $request->classSection;
        $classroom->save();

        return response()->json($classroom);
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
    public function unenrollStudent(Request $request, $classID, $studentID)
    {
        $teacher = Auth::user();
    
        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $classroom = Classroom::find($classID);
    
        if (!$classroom) {
            return response()->json(['message' => 'Class not found'], 404);
        }
    
        // Check if the student is enrolled
        if (!$classroom->students()->where('students.studentID', $studentID)->exists()) {
            return response()->json(['message' => 'Student is not enrolled in this class'], 409);
        }
    
        // Detach student from class
        $classroom->students()->detach($studentID);
    
        return response()->json(['message' => 'Student unenrolled successfully']);
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
                'classSection' => $class->classSection, 
                'teacherName' => $class->teacher ? "{$class->teacher->firstname} {$class->teacher->lastname}" : 'Unknown Teacher'
            ];
        });
    
        return response()->json($formattedClasses);

    }

    public function showClassInfo($id)
    {
        // Retrieve the class along with teacher details (and students if needed)
        $classroom = Classroom::with('teacher')->find($id);

        if (!$classroom) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        // Return only the necessary class info as JSON
        return response()->json([
            'classID'      => $classroom->classID,
            'className'    => $classroom->className,
            'classSection' => $classroom->classSection,
            'teacher'      => [
                'teacherID'   => $classroom->teacher->teacherID,
                'teacherName' => $classroom->teacher->firstname . ' ' . $classroom->teacher->lastname,
            ],
        ]);
    }

    public function getClassStudents($classID)
    {
        $classroom = Classroom::with('students')->find($classID);
    
        if (!$classroom) {
            return response()->json(['message' => 'Class not found'], 404);
        }
    
        // Fetch students with relevant details
        $students = $classroom->students->map(function ($student) {
            return [
                'studentID'     => $student->studentID,
                'firstname'     => $student->firstname,
                'lastname'      => $student->lastname,
                'studentNumber' => $student->student_num,
                'profileImage'  => $student->profileImage 
                    ? url('storage/' . $student->profileImage) 
                    : url('storage/profile_images/default-avatar.jpg'), // Use default if no image
                'averageScore'  => rand(70, 100) // Replace with actual computation
            ];
        });
    
        return response()->json($students);
    }
    

}

