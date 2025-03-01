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

        // 1. Fetch upcoming activities (openDate > now)
        $upcomingActivities = Activity::with(['teacher', 'programmingLanguages'])
            ->whereIn('classID', $enrolledClassIDs)
            ->where('openDate', '>', $now)
            ->orderBy('openDate', 'asc')
            ->get();

        // 2. Fetch ongoing activities (openDate <= now && closeDate >= now)
        $ongoingActivities = Activity::with(['teacher', 'programmingLanguages'])
            ->whereIn('classID', $enrolledClassIDs)
            ->where('openDate', '<=', $now)
            ->where('closeDate', '>=', $now)
            ->orderBy('openDate', 'asc')
            ->get();

        // 3. Fetch completed activities (closeDate < now)
        $completedActivities = Activity::with(['teacher', 'programmingLanguages'])
            ->whereIn('classID', $enrolledClassIDs)
            ->where('closeDate', '<', $now)
            ->orderBy('closeDate', 'desc')
            ->get();

        // Attach student-specific details (Rank, Score, Duration, etc.)
        $upcomingActivities   = $this->attachStudentDetails($upcomingActivities, $student);
        $ongoingActivities    = $this->attachStudentDetails($ongoingActivities, $student);
        $completedActivities  = $this->attachStudentDetails($completedActivities, $student);

        // If no activities are found in any category, return an empty array
        if (
            $upcomingActivities->isEmpty() &&
            $ongoingActivities->isEmpty() &&
            $completedActivities->isEmpty()
        ) {
            return response()->json([
                'message' => 'No activities found.',
                'upcoming' => [],
                'ongoing' => [],
                'completed' => []
            ], 200);
        }

        // Return all three categories
        return response()->json([
            'upcoming'   => $upcomingActivities,
            'ongoing'    => $ongoingActivities,
            'completed'  => $completedActivities
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
    
            $rankQuery = ActivitySubmission::where('actID', $activity->actID)
                ->orderByDesc('score')
                ->pluck('studentID')
                ->toArray();
    
            $rankIndex = array_search($student->studentID, $rankQuery);
            $rank = $rankIndex !== false ? $rankIndex + 1 : null;
    
            return [
                'actID'               => $activity->actID,
                'actTitle'            => $activity->actTitle,
                'actDesc'             => $activity->actDesc,
                'classID'             => $activity->classID,
                'teacherName'         => optional($activity->teacher)->firstname . ' ' . optional($activity->teacher)->lastname,
                'actDifficulty'       => $activity->actDifficulty,
                'actDuration'         => $activity->actDuration,
                'programmingLanguages'=> $activity->programmingLanguages->isNotEmpty() 
                                         ? $activity->programmingLanguages->pluck('progLangName')->toArray() 
                                         : 'N/A',
                'openDate'            => $activity->openDate,
                'closeDate'           => $activity->closeDate,
                'rank'                => $rank,
                'overallScore'        => $submission->score ?? null,
                'maxPoints'           => $activity->maxPoints,
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
    
            // Validate input, including the new actDuration field
            $validator = \Validator::make($request->all(), [
                'progLangIDs'            => 'required|array',
                'progLangIDs.*'          => 'exists:programming_languages,progLangID',
                'actTitle'               => 'required|string|max:255',
                'actDesc'                => 'required|string',
                'actDifficulty'          => 'required|in:Beginner,Intermediate,Advanced',
                'actDuration'            => [
                    'required',
                    'regex:/^(0\d|1\d|2[0-3]):([0-5]\d):([0-5]\d)$/'
                ],
                'openDate'               => 'required|date',
                'closeDate'              => 'required|date|after:openDate',
                'maxPoints'              => 'required|integer|min:1',
                'questions'              => 'required|array|min:1|max:3',
                'questions.*.questionID' => 'required|exists:questions,questionID',
                'questions.*.itemTypeID' => 'required|exists:item_types,itemTypeID',
                'questions.*.actQuestionPoints' => 'required|integer|min:1',
            ]);            
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }
    
            // Create the activity including actDuration
            $activity = Activity::create([
                'classID'       => $request->classID,
                'teacherID'     => $teacher->teacherID,
                'actTitle'      => $request->actTitle,
                'actDesc'       => $request->actDesc,
                'actDifficulty' => $request->actDifficulty,
                'actDuration'   => $request->actDuration, // New field
                'openDate'      => $request->openDate,
                'closeDate'     => $request->closeDate,
                'maxPoints'     => $request->maxPoints, // temporary value; will be overridden
                'classAvgScore' => null,
                'highestScore'  => null,
            ]);
    
            // Attach programming languages
            $activity->programmingLanguages()->attach($request->progLangIDs);
    
            // Attach selected questions with points from teacher input
            foreach ($request->questions as $question) {
                ActivityQuestion::create([
                    'actID'      => $activity->actID,
                    'questionID' => $question['questionID'],
                    'itemTypeID' => $question['itemTypeID'],
                    'actQuestionPoints' => $question['actQuestionPoints'],
                ]);
            }
    
            // Automatically calculate the total points from the questions
            $totalPoints = array_sum(array_column($request->questions, 'actQuestionPoints'));
    
            // Update the activity's maxPoints to match the sum of question points
            $activity->update(['maxPoints' => $totalPoints]);
    
            return response()->json([
                'message'  => 'Activity created successfully',
                'activity' => $activity->load('questions.question', 'questions.itemType', 'programmingLanguages'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error while creating the activity.',
                'error'   => $e->getMessage(),
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
     * Get all activities for a specific class, categorized into Upcoming, Ongoing, and Completed.
     */
    public function showClassActivities($classID)
    {
        $now = now(); // Current time
    
        // Update `completed_at` for activities where closeDate has passed
        $updatedCount = Activity::where('classID', $classID)
            ->where('closeDate', '<', $now)
            ->whereNull('completed_at')
            ->update([
                'completed_at' => $now,
                'updated_at' => $now,
            ]);
    
        \Log::info("Activities marked as completed: $updatedCount");
    
        // Upcoming Activities: openDate is in the future
        $upcomingActivities = Activity::with([
                'teacher', 
                'programmingLanguages', 
                'questions.question',
                'questions.question.programmingLanguages',
                'questions.itemType'
            ])
            ->where('classID', $classID)
            ->where('openDate', '>', $now)
            ->orderBy('openDate', 'asc')
            ->get();
    
        // Ongoing Activities: openDate <= now && closeDate >= now && not completed
        $ongoingActivities = Activity::with([
                'teacher', 
                'programmingLanguages', 
                'questions.question',
                'questions.question.programmingLanguages', 
                'questions.itemType'
            ])
            ->where('classID', $classID)
            ->where('openDate', '<=', $now)
            ->where('closeDate', '>=', $now)
            ->whereNull('completed_at')
            ->orderBy('openDate', 'asc')
            ->get();
    
        // Completed Activities: only include those where both openDate and closeDate are in the past
        $completedActivities = Activity::with([
                'teacher', 
                'programmingLanguages', 
                'questions.question', 
                'questions.question.programmingLanguages',
                'questions.itemType'
            ])
            ->where('classID', $classID)
            ->whereNotNull('completed_at')
            ->where('openDate', '<=', $now)  // Only if activity already started
            ->where('closeDate', '<', $now)   // And finished
            ->orderBy('closeDate', 'desc')
            ->get();
    
        \Log::info("Fetched Activities", [
            'classID' => $classID,
            'upcomingCount' => $upcomingActivities->count(),
            'ongoingCount' => $ongoingActivities->count(),
            'completedCount' => $completedActivities->count(),
            'current_time' => $now->toDateTimeString(),
        ]);
    
        if ($upcomingActivities->isEmpty() && $ongoingActivities->isEmpty() && $completedActivities->isEmpty()) {
            return response()->json([
                'message' => 'No activities found.',
                'upcoming' => [],
                'ongoing' => [],
                'completed' => []
            ], 200);
        }
    
        return response()->json([
            'upcoming' => $upcomingActivities,
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
    
            // Validate input, including actDuration if provided
            $validator = \Validator::make($request->all(), [
                'progLangIDs'            => 'sometimes|required|array',
                'progLangIDs.*'          => 'exists:programming_languages,progLangID',
                'actTitle'               => 'sometimes|required|string|max:255',
                'actDesc'                => 'sometimes|required|string',
                'actDifficulty'          => 'sometimes|required|in:Beginner,Intermediate,Advanced',
                'actDuration'            => [
                    'sometimes',
                    'required',
                    'regex:/^(0\d|1\d|2[0-3]):([0-5]\d):([0-5]\d)$/'
                ],
                'openDate'               => 'sometimes|required|date',
                'closeDate'              => 'sometimes|required|date|after:openDate',
                'maxPoints'              => 'sometimes|required|integer|min:1',
                'questions'              => 'sometimes|required|array|min:1|max:3',
                'questions.*.questionID' => 'required_with:questions|exists:questions,questionID',
                'questions.*.itemTypeID' => 'required_with:questions|exists:item_types,itemTypeID',
                'questions.*.actQuestionPoints' => 'required_with:questions|integer|min:1',
            ]);            
    
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }
    
            // Clear completed_at if the new closeDate is in the future
            if ($request->has('closeDate') && \Carbon\Carbon::parse($request->closeDate)->gt(now())) {
                $activity->completed_at = null;
                $activity->updated_at = now();
                $activity->save();
            }
    
            // Update activity details including actDuration if provided
            $activity->update($request->only([
                'actTitle', 'actDesc', 'actDifficulty', 'actDuration', 'openDate', 'closeDate', 'maxPoints'
            ]));
    
            // Sync programming languages if provided
            if ($request->has('progLangIDs')) {
                $activity->programmingLanguages()->sync($request->progLangIDs);
            }
    
            // If questions are provided, update them including actQuestionPoints
            if ($request->has('questions')) {
                ActivityQuestion::where('actID', $activity->actID)->delete();
    
                foreach ($request->questions as $question) {
                    ActivityQuestion::create([
                        'actID'      => $activity->actID,
                        'questionID' => $question['questionID'],
                        'itemTypeID' => $question['itemTypeID'],
                        'actQuestionPoints' => $question['actQuestionPoints'],
                    ]);
                }
    
                // Recalculate total actQuestionPoints from the provided questions
                $totalPoints = array_sum(array_column($request->questions, 'actQuestionPoints'));
    
                // Update the activity's maxPoints accordingly
                $activity->update(['maxPoints' => $totalPoints]);
            }
    
            return response()->json([
                'message'  => 'Activity updated successfully',
                'activity' => $activity->load('questions.question', 'questions.question.programmingLanguages', 'questions.itemType', 'programmingLanguages'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error while updating the activity.',
                'error'   => $e->getMessage(),
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
    
        // Load the activity along with its related questions and item types.
        $activity = Activity::with([
            'questions.question',  // load the actual question
            'questions.itemType',  // load item type
            'classroom',
        ])->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        // Build the questions array for the front end.
        $questions = $activity->questions->map(function ($aq) use ($student, $activity) {
            $submission = ActivitySubmission::where('actID', $activity->actID)
                ->where('studentID', $student->studentID)
                ->where('questionID', $aq->question->questionID)
                ->first();
    
            return [
                'questionName'      => $aq->question->questionName ?? 'Unknown',
                'questionDifficulty'        => $aq->question->questionDifficulty ?? 'N/A',
                'itemTypeID'          => $aq->itemTypeID->itemTypeName ?? 'N/A',
                'actQuestionPoints' => $aq->actQuestionPoints,  // Pivot value from activity_questions
                'studentScore'      => $submission ? $submission->score : null,
                'studentTimeSpent'  => $submission ? $submission->timeSpent . ' min' : '-',
                'submissionStatus'  => $submission ? 'Submitted' : 'Not Attempted',
            ];
        });
    
        return response()->json([
            'activityName' => $activity->actTitle,
            'actDesc'      => $activity->actDesc,
            'maxPoints'    => $activity->maxPoints,
            'questions'    => $questions,
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
            'questions.question.programmingLanguages',
            'questions.itemType',
            'classroom',
        ])->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        $questions = $activity->questions->map(function ($aq) use ($activity) {
            $question = $aq->question;
    
            // Programming languages
            $programmingLanguages = $question->programmingLanguages->map(function ($lang) {
                return [
                    'progLangID'   => $lang->progLangID,
                    'progLangName' => $lang->progLangName,
                ];
            })->values()->all();
    
            return [
                'questionName'        => $question->questionName ?? 'Unknown',
                'questionDesc'        => $question->questionDesc ?? '',
                'questionDifficulty'          => $question->questionDifficulty ?? 'N/A',
                'programming_languages' => $programmingLanguages,
                'itemType'            => $aq->itemType->itemTypeName ?? 'N/A',
                'testCases'           => $question->testCases->map(function ($tc) {
                    return [
                        'inputData'      => $tc->inputData,
                        'expectedOutput' => $tc->expectedOutput,
                        'testCasePoints' => $tc->testCasePoints,
                    ];
                }),
                'avgStudentScore'     => $this->calculateAverageScore($question->questionID, $activity->actID),
                'avgStudentTimeSpent' => $this->calculateAverageTimeSpent($question->questionID, $activity->actID),
                'actQuestionPoints'   => $aq->actQuestionPoints,
            ];
        });
    
        return response()->json([
            'activityName' => $activity->actTitle,
            'actDesc'      => $activity->actDesc,     // <--- ADD THIS
            'maxPoints'    => $activity->maxPoints,
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
            'openDate' => $activity->openDate,
            'closeDate' => $activity->closeDate,
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