<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\ActivityQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{

    ///////////////////////////////////////////////////
    // ALL FUNCTIONS IN CLASS MANAGEMENT PAGE VIA ACTIVITY
    ///////////////////////////////////////////////////
    /**
     * Get all activities for the authenticated student, categorized into Ongoing and Completed.
     */
    public function showStudentActivities()
    {
        $student = Auth::user();
    
        if (!$student || !$student instanceof \App\Models\Student) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $enrolledClassIDs = $student->classes()->pluck('class_student.classID');
    
        if ($enrolledClassIDs->isEmpty()) {
            return response()->json(['message' => 'You are not enrolled in any class.'], 404);
        }
    
        $now = now();
    
        // Fetch ongoing and completed activities
        $ongoingActivities = Activity::with(['teacher', 'programmingLanguages'])
            ->whereIn('classID', $enrolledClassIDs)
            ->where('startDate', '<=', $now)
            ->where('endDate', '>=', $now)
            ->orderBy('startDate', 'asc')
            ->get();
    
        $completedActivities = Activity::with(['teacher', 'programmingLanguages'])
            ->whereIn('classID', $enrolledClassIDs)
            ->where('endDate', '<', $now)
            ->orderBy('endDate', 'desc')
            ->get();
    
        // Attach student-specific details (Rank, Score, Duration)
        $ongoingActivities = $this->attachStudentDetails($ongoingActivities, $student);
        $completedActivities = $this->attachStudentDetails($completedActivities, $student);
    
        // ✅ Return a proper response if there are no activities
        if ($ongoingActivities->isEmpty() && $completedActivities->isEmpty()) {
            return response()->json([
                'message' => 'No activities found.',
                'ongoing' => [],
                'completed' => []
            ], 200);
        }
    
        return response()->json([
            'ongoing' => $ongoingActivities,
            'completed' => $completedActivities
        ]);
    }    


    /**
     * Attach Rank, Overall Score, and Duration for the student in the activity list.
     */
    private function attachStudentDetails($activities, $student)
    {
        return $activities->map(function ($activity) use ($student) {
            $submission = ActivitySubmission::where('actID', $activity->actID)
                ->where('studentID', $student->studentID)
                ->first();

            // Fetch all scores for ranking
            $rankQuery = ActivitySubmission::where('actID', $activity->actID)
                ->orderByDesc('score')
                ->pluck('studentID')
                ->toArray();

            // Determine rank (ensure correct handling when student is missing)
            $rankIndex = array_search($student->studentID, $rankQuery);
            $rank = $rankIndex !== false ? $rankIndex + 1 : null;

            return [
                'actID' => $activity->actID,
                'actTitle' => $activity->actTitle,
                'teacherName' => optional($activity->teacher)->firstname . ' ' . optional($activity->teacher)->lastname,
                // Extract programming language names as an array (or 'N/A' if none)
                'programmingLanguages' => $activity->programmingLanguages->isNotEmpty() 
                    ? $activity->programmingLanguages->pluck('progLangName')->toArray() 
                    : 'N/A',
                'startDate' => $activity->startDate,
                'endDate' => $activity->endDate,
                'rank' => $rank,
                'overallScore' => $submission->score ?? null,
                'duration' => $submission ? $submission->duration . 'm' : null,
            ];
        });
    }

    /**
     * Create an activity (Only for Teachers)
     */
    public function store(Request $request)
    {
        try {
            $teacher = Auth::user();

            if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // ✅ Validate input
            $validator = \Validator::make($request->all(), [
                'progLangIDs' => 'required|array', // ✅ Supports multiple languages
                'progLangIDs.*' => 'exists:programming_languages,progLangID',
                'actTitle' => 'required|string|max:255',
                'actDesc' => 'required|string',
                'difficulty' => 'required|in:Beginner,Intermediate,Advanced',
                'startDate' => 'required|date',
                'endDate' => 'required|date|after:startDate',
                'maxPoints' => 'required|integer|min:1',
                'questions' => 'required|array|min:1|max:3',
                'questions.*.questionID' => 'required|exists:questions,questionID',
                'questions.*.itemTypeID' => 'required|exists:item_types,itemTypeID',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // ✅ Create the activity
            $activity = Activity::create([
                'classID' => $request->classID,
                'teacherID' => $teacher->teacherID,
                'actTitle' => $request->actTitle,
                'actDesc' => $request->actDesc,
                'difficulty' => $request->difficulty,
                'startDate' => $request->startDate,
                'endDate' => $request->endDate,
                'maxPoints' => $request->maxPoints,
                'classAvgScore' => null,
                'highestScore' => null,
            ]);

            // ✅ Attach programming languages (Many-to-Many)
            $activity->programmingLanguages()->attach($request->progLangIDs);

            // ✅ Attach selected questions
            foreach ($request->questions as $question) {
                ActivityQuestion::create([
                    'actID' => $activity->actID,
                    'questionID' => $question['questionID'],
                    'itemTypeID' => $question['itemTypeID'],
                ]);
            }

            return response()->json([
                'message' => 'Activity created successfully',
                'activity' => $activity->load('questions.question', 'questions.itemType', 'programmingLanguages'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error while creating the activity.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
       
    /**
     * Get a specific activity by ID.
     */
    public function show($actID)
    {
        $activity = Activity::with([
            'classroom',
            'teacher',
            'programmingLanguages', // ✅ Load multiple languages
            'questions.question.testCases',
        ])->find($actID);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        return response()->json($activity);
    }

    /**
     * Get all activities for a specific class, categorized into Ongoing and Completed.
     */
    public function showClassActivities($classID)
    {
        $now = now(); // ✅ Using Asia/Manila time (make sure it's set in config/app.php)

        // ✅ Update `completed_at` for activities where endDate has passed
        $updatedCount = Activity::where('classID', $classID)
            ->where('endDate', '<', $now)
            ->whereNull('completed_at')
            ->update([
                'completed_at' => $now,
                'updated_at' => $now, // ✅ Ensure timestamps are updated
            ]);

        // 🔥 Log how many activities were updated
        \Log::info("🔥 Activities marked as completed: $updatedCount");

        // ✅ Fetch Ongoing Activities (StartDate <= now && EndDate >= now && not completed)
        $ongoingActivities = Activity::with(['teacher', 'programmingLanguages', 'questions.question', 'questions.itemType'])
            ->where('classID', $classID)
            ->where('startDate', '<=', $now)
            ->where('endDate', '>=', $now)
            ->whereNull('completed_at') // ✅ Exclude completed activities
            ->orderBy('startDate', 'asc')
            ->get();

        // ✅ Fetch Completed Activities (Has a `completed_at` timestamp)
        $completedActivities = Activity::with(['teacher', 'programmingLanguages', 'questions.question', 'questions.itemType'])
            ->where('classID', $classID)
            ->whereNotNull('completed_at') // ✅ Only fetch explicitly completed activities
            ->orderBy('endDate', 'desc')
            ->get();

        // ✅ Log debugging info
        \Log::info("✅ Fetched Activities", [
            'classID' => $classID,
            'ongoingCount' => $ongoingActivities->count(),
            'completedCount' => $completedActivities->count(),
            'current_time' => $now->toDateTimeString(),
        ]);

        if ($ongoingActivities->isEmpty() && $completedActivities->isEmpty()) {
            return response()->json([
                'message' => 'No activities found.',
                'ongoing' => [],
                'completed' => []
            ], 200);
        }

        return response()->json([
            'ongoing' => $ongoingActivities,
            'completed' => $completedActivities
        ]);
    }

    /**
     * Update an existing activity (Only for Teachers)
     */
    public function update(Request $request, $actID)
    {
        try {
            $teacher = Auth::user();

            if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $activity = Activity::where('actID', $actID)
                ->where('teacherID', $teacher->teacherID)
                ->first();

            if (!$activity) {
                return response()->json(['message' => 'Activity not found or unauthorized'], 404);
            }

            // ✅ Validate input
            $validator = \Validator::make($request->all(), [
                'progLangIDs' => 'sometimes|required|array', // ✅ Multiple languages
                'progLangIDs.*' => 'exists:programming_languages,progLangID',
                'actTitle' => 'sometimes|required|string|max:255',
                'actDesc' => 'sometimes|required|string',
                'difficulty' => 'sometimes|required|in:Beginner,Intermediate,Advanced',
                'startDate' => 'sometimes|required|date',
                'endDate' => 'sometimes|required|date|after:startDate',
                'maxPoints' => 'sometimes|required|integer|min:1',
                'questions' => 'sometimes|required|array|min:1|max:3',
                'questions.*.questionID' => 'required_with:questions|exists:questions,questionID',
                'questions.*.itemTypeID' => 'required_with:questions|exists:item_types,itemTypeID',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // ✅ Update activity details
            $activity->update($request->only([
                'actTitle', 'actDesc', 'difficulty', 'startDate', 'endDate', 'maxPoints'
            ]));

            // ✅ Sync programming languages
            if ($request->has('progLangIDs')) {
                $activity->programmingLanguages()->sync($request->progLangIDs);
            }

            // ✅ If questions are provided, update them
            if ($request->has('questions')) {
                ActivityQuestion::where('actID', $activity->actID)->delete();

                foreach ($request->questions as $question) {
                    ActivityQuestion::create([
                        'actID' => $activity->actID,
                        'questionID' => $question['questionID'],
                        'itemTypeID' => $question['itemTypeID'],
                    ]);
                }
            }

            return response()->json([
                'message' => 'Activity updated successfully',
                'activity' => $activity->load('questions.question', 'questions.itemType', 'programmingLanguages'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error while updating the activity.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Delete an activity (Only for Teachers).
     */
    public function destroy($actID)
    {
        $teacher = Auth::user();

        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activity = Activity::where('actID', $actID)
            ->where('teacherID', $teacher->teacherID)
            ->first();

        if (!$activity) {
            return response()->json(['message' => 'Activity not found or unauthorized'], 404);
        }

        $activity->delete();

        return response()->json(['message' => 'Activity deleted successfully']);
    }
    ///////////////////////////////////////////////////
    // ALL FUNCTIONS IN ACTIVITY MANAGEMENT PAGE FOR TEACHERS
    ///////////////////////////////////////////////////

    public function showActivityItemsByStudent(Request $request, $actID)
    {
        $student = Auth::user();
        
        if (!$student || !$student instanceof \App\Models\Student) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activity = Activity::with([
            'questions.question',
            'questions.itemType',
            'classroom',
        ])->find($actID);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        // Retrieve the student's specific submissions
        $questions = $activity->questions->map(function ($aq) use ($student, $activity) {
            $submission = ActivitySubmission::where('actID', $activity->actID)
                ->where('studentID', $student->studentID)
                ->where('questionID', $aq->question->questionID)
                ->first();

            return [
                'questionName' => $aq->question->questionName ?? 'Unknown',
                'difficulty' => $aq->question->difficulty ?? 'N/A',
                'itemType' => $aq->itemType->itemTypeName ?? 'N/A',
                'studentScore' => $submission ? $submission->score . '/' . $activity->maxPoints : '-/' . $activity->maxPoints,
                'studentTimeSpent' => $submission ? $submission->timeSpent . ' min' : '-',
                'submissionStatus' => $submission ? 'Submitted' : 'Not Attempted',
            ];
        });

        return response()->json([
            'activityName' => $activity->actTitle,
            'maxPoints' => $activity->maxPoints,
            'questions' => $questions
        ]);
    }

    public function showActivityLeaderboardByStudent($actID)
    {
        // Fetch the activity
        $activity = Activity::with('classroom')->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        // Fetch all student submissions for the activity
        $submissions = ActivitySubmission::where('actID', $actID)
            ->join('students', 'activity_submissions.studentID', '=', 'students.studentID')
            ->select('students.studentID', 'students.firstname', 'students.lastname', 'students.program', 'activity_submissions.score')
            ->orderByDesc('activity_submissions.score') // Order by highest score
            ->get();
    
        // ✅ Check if there are no submissions
        if ($submissions->isEmpty()) {
            return response()->json([
                'activityName' => $activity->actTitle,
                'message' => 'No students have submitted this activity yet.',
                'leaderboard' => []
            ]);
        }
    
        // Calculate rank based on score
        $rankedSubmissions = $submissions->map(function ($submission, $index) {
            return [
                'studentName' => strtoupper($submission->lastname) . ", " . $submission->firstname,
                'program' => $submission->program ?? 'N/A',
                'averageScore' => $submission->score . '%',
                'rank' => ($index + 1) . '%'
            ];
        });
    
        return response()->json([
            'activityName' => $activity->actTitle,
            'leaderboard' => $rankedSubmissions
        ]);
    }


    ///////////////////////////////////////////////////
    // ALL FUNCTIONS IN ACTIVITY MANAGEMENT PAGE FOR TEACHERS
    ///////////////////////////////////////////////////

    /**
     * Show the activity details.
     */
    public function showActivityItemsByTeacher($actID)
    {
        $activity = Activity::with([
            'questions.question.programmingLanguages', // Eager load question languages
            'questions.itemType',
            'classroom',
        ])->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        // Retrieve all related questions for this activity
        $questions = $activity->questions->map(function ($aq) use ($activity) {
            $question = $aq->question;
    
            // Convert each programming language to a small array of { progLangID, progLangName }
            $programmingLanguages = $question->programmingLanguages->map(function ($lang) {
                return [
                    'progLangID'   => $lang->progLangID,
                    'progLangName' => $lang->progLangName,
                ];
            })->values()->all();
    
            return [
                'questionName' => $question->questionName ?? 'Unknown',
                'questionDesc' => $question->questionDesc ?? '',
                'difficulty'   => $question->difficulty ?? 'N/A',
    
                // Return an array of objects for the languages
                'programming_languages' => $programmingLanguages,
    
                'itemType' => $aq->itemType->itemTypeName ?? 'N/A',
    
                // Test cases
                'testCases' => $question->testCases->map(function ($tc) {
                    return [
                        'inputData'      => $tc->inputData,
                        'expectedOutput' => $tc->expectedOutput,
                    ];
                }),
    
                'avgStudentScore'      => $this->calculateAverageScore($question->questionID, $activity->actID),
                'avgStudentTimeSpent'  => $this->calculateAverageTimeSpent($question->questionID, $activity->actID),
            ];
        });
    
        return response()->json([
            'activityName' => $activity->actTitle,
            'maxPoints'    => $activity->maxPoints,
    
            // If you want the activity's own programmingLanguages as well:
            // 'programming_languages' => $activity->programmingLanguages->map(...),
    
            'questions'    => $questions
        ]);
    }
    

    /**
     * Calculate the average student score for an activity question.
     */
    private function calculateAverageScore($questionID, $actID)
    {
        return ActivitySubmission::where('actID', $actID)
            ->whereHas('activity.questions', function ($query) use ($questionID) {
                $query->where('questionID', $questionID);
            })
            ->avg('score') ?? '-';
    }

    /**
     * Calculate the average time spent by students on an activity question.
     */
    private function calculateAverageTimeSpent($questionID, $actID)
    {
        return ActivitySubmission::where('actID', $actID)
            ->whereHas('activity.questions', function ($query) use ($questionID) {
                $query->where('questionID', $questionID);
            })
            ->avg('timeSpent') ?? '-';
    }


    public function showActivityLeaderboardByTeacher($actID)
    {
        // Fetch the activity
        $activity = Activity::with('classroom')->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        // Fetch all student submissions for the activity
        $submissions = ActivitySubmission::where('actID', $actID)
            ->join('students', 'activity_submissions.studentID', '=', 'students.studentID')
            ->select('students.studentID', 'students.firstname', 'students.lastname', 'students.program', 'activity_submissions.score')
            ->orderByDesc('activity_submissions.score') // Order by highest score
            ->get();
    
        // ✅ Check if there are no submissions
        if ($submissions->isEmpty()) {
            return response()->json([
                'activityName' => $activity->actTitle,
                'message' => 'No students have submitted this activity yet.',
                'leaderboard' => []
            ]);
        }
    
        // Calculate rank based on score
        $rankedSubmissions = $submissions->map(function ($submission, $index) {
            return [
                'studentName' => strtoupper($submission->lastname) . ", " . $submission->firstname,
                'program' => $submission->program ?? 'N/A',
                'averageScore' => $submission->score . '%',
                'rank' => ($index + 1) . '%'
            ];
        });
    
        return response()->json([
            'activityName' => $activity->actTitle,
            'leaderboard' => $rankedSubmissions
        ]);
    }

    /**
     * Show activity settings.
     */
    public function showActivitySettingsByTeacher($actID)
    {
        $activity = Activity::with('classroom')->find($actID);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        return response()->json([
            'activityName' => $activity->actTitle,
            'maxPoints' => $activity->maxPoints,
            'className' => optional($activity->classroom)->className ?? 'N/A',
            'startDate' => $activity->startDate,
            'endDate' => $activity->endDate,
            'settings' => [
                'examMode' => (bool) $activity->examMode, // Records tab/window switches
                'randomizedItems' => (bool) $activity->randomizedItems, // Random question order
                'disableReviewing' => (bool) $activity->disableReviewing, // Prevent students from reviewing answers
                'hideLeaderboard' => (bool) $activity->hideLeaderboard, // Hide leaderboard
                'delayGrading' => (bool) $activity->delayGrading, // Manual grading required
            ]
        ]);
    }

    /**
     * Update activity settings.
     */
    public function updateActivitySettingsByTeacher(Request $request, $actID)
    {
        $teacher = Auth::user();

        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activity = Activity::where('actID', $actID)
            ->where('teacherID', $teacher->teacherID)
            ->first();

        if (!$activity) {
            return response()->json(['message' => 'Activity not found or unauthorized'], 404);
        }

        $validatedData = $request->validate([
            'examMode' => 'boolean',
            'randomizedItems' => 'boolean',
            'disableReviewing' => 'boolean',
            'hideLeaderboard' => 'boolean',
            'delayGrading' => 'boolean',
        ]);

        $activity->update($validatedData);

        return response()->json(['message' => 'Activity settings updated successfully.']);
    }

}