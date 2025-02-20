<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Get all activities for the authenticated student.
     */
    public function studentActivities()
    {
        $student = Auth::user();
    
        // Ensure the authenticated user is a student
        if (!$student || !$student instanceof \App\Models\Student) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        // Get all class IDs where the student is enrolled
        $enrolledClassIDs = $student->classes()->pluck('class_student.classID'); // Fix ambiguity
    
        if ($enrolledClassIDs->isEmpty()) {
            return response()->json(['message' => 'You are not enrolled in any class.'], 404);
        }
    
        // Get activities for the student's enrolled classes
        $activities = Activity::with('programmingLanguage')->whereIn('classID', $enrolledClassIDs)->get();
    
        if ($activities->isEmpty()) {
            return response()->json(['message' => 'No activities found for your classes.'], 404);
        }
    
        return response()->json($activities, 200);
    }    

    /**
     * Create an activity (Only for Teachers)
     */
    public function store(Request $request)
    {
        $teacher = Auth::user();

        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'classID' => 'required|exists:classes,classID',
            'progLangID' => 'required|exists:programming_languages,progLangID',
            'actTitle' => 'required|string|max:255',
            'actDesc' => 'required|string',
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
        ]);

        $activity = Activity::create([
            'classID' => $request->classID,
            'teacherID' => $teacher->teacherID,
            'progLangID' => $request->progLangID,
            'actTitle' => $request->actTitle,
            'actDesc' => $request->actDesc,
            'difficulty' => $request->difficulty,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
        ]);

        return response()->json(['message' => 'Activity created successfully', 'activity' => $activity], 201);
    }

    /**
     * Get all activities for a specific class.
     */
    public function showClassActivities($classID)
    {
        $activities = Activity::with(['classroom', 'programmingLanguage'])->where('classID', $classID)->get();
    
        if ($activities->isEmpty()) {
            return response()->json(['message' => 'No activities found for this class'], 404);
        }
    
        return response()->json($activities);
    }
    

    /**
     * Get a specific activity by ID.
     */
    public function show($actID)
    {
        $activity = Activity::with(['classroom', 'teacher', 'programmingLanguage'])->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        return response()->json($activity);
    }
    

    /**
     * Submit an activity answer (For Students)
     */
    public function submitActivity(Request $request, $actID)
    {
        $student = Auth::user();
    
        if (!$student || !$student instanceof \App\Models\Student) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        // Check if activity exists
        $activity = Activity::find($actID);
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        // Ensure the student is enrolled in this class
        if (!$student->classes()->where('class_student.classID', $activity->classID)->exists()) {
            return response()->json(['message' => 'You are not enrolled in this class.'], 403);
        }
    
        // Check if the student already submitted this activity
        $existingSubmission = ActivitySubmission::where('actID', $actID)
            ->where('studentID', $student->studentID)
            ->first();
    
        if ($existingSubmission) {
            return response()->json(['message' => 'You have already submitted this activity.'], 409);
        }
    
        $request->validate([
            'submissionFile' => 'required|string', // Ideally, use file uploads
        ]);
    
        $submission = ActivitySubmission::create([
            'actID' => $actID,
            'studentID' => $student->studentID,
            'submissionFile' => $request->submissionFile,
            'submitted_at' => now(),
        ]);
    
        return response()->json(['message' => 'Activity submitted successfully', 'submission' => $submission]);
    }      

    /**
     * Get all submissions for a specific activity (For Teachers)
     */
    public function activitySubmissions($actID)
    {
        $teacher = Auth::user();

        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if activity exists and if the teacher created it
        $activity = Activity::where('actID', $actID)
                            ->where('teacherID', $teacher->teacherID) // Restrict to teacher
                            ->with('submissions.student')
                            ->first();

        if (!$activity) {
            return response()->json(['message' => 'Activity not found or unauthorized'], 404);
        }

        // Check if any submissions exist
        if ($activity->submissions->isEmpty()) {
            return response()->json([
                'message' => 'No students have submitted this activity yet.',
                'activity' => $activity->actTitle,
                'submissions' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Submissions retrieved successfully.',
            'activity' => $activity->actTitle,
            'submissions' => $activity->submissions
        ], 200);
    }

    /**
     * Get a specific student's submission for an activity (For Teachers)
     */
    public function getStudentSubmission($actID, $studentID)
    {
        $teacher = Auth::user();

        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if the activity exists and belongs to the teacher
        $activity = Activity::where('actID', $actID)
                            ->where('teacherID', $teacher->teacherID)
                            ->first();

        if (!$activity) {
            return response()->json(['message' => 'Activity not found or unauthorized'], 404);
        }

        // Fetch the specific student's submission
        $submission = ActivitySubmission::where('actID', $actID)
                                        ->where('studentID', $studentID)
                                        ->with('student') // Include student details
                                        ->first();

        if (!$submission) {
            return response()->json(['message' => 'No submission found for this student'], 404);
        }

        return response()->json([
            'message' => 'Student submission retrieved successfully',
            'activity' => $activity->actTitle,
            'submission' => $submission
        ], 200);
    }


    /**
     * Delete an activity (Only for Teachers)
     */
    public function destroy($actID)
    {
        $teacher = Auth::user();
    
        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $activity = Activity::where('actID', $actID)
                            ->where('teacherID', $teacher->teacherID) // Only the creator can delete
                            ->first();
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found or unauthorized'], 404);
        }
    
        $activity->delete();
    
        return response()->json(['message' => 'Activity deleted successfully']);
    }    
}