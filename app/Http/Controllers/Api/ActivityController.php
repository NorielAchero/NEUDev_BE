<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\ActivityItem; // formerly ActivityQuestion
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    ///////////////////////////////////////////////////
    // FUNCTIONS FOR CLASS MANAGEMENT PAGE VIA ACTIVITY
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
        $upcomingActivities  = $this->attachStudentDetails($upcomingActivities, $student);
        $ongoingActivities   = $this->attachStudentDetails($ongoingActivities, $student);
        $completedActivities = $this->attachStudentDetails($completedActivities, $student);

        if (
            $upcomingActivities->isEmpty() &&
            $ongoingActivities->isEmpty() &&
            $completedActivities->isEmpty()
        ) {
            return response()->json([
                'message'  => 'No activities found.',
                'upcoming' => [],
                'ongoing'  => [],
                'completed'=> []
            ], 200);
        }

        return response()->json([
            'upcoming'  => $upcomingActivities,
            'ongoing'   => $ongoingActivities,
            'completed' => $completedActivities
        ]);
    }

    /**
     * Attach Rank, Overall Score, and other details for the student in the activity list.
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

            // Validate input. Note: we now expect an "items" array instead of "questions".
            $validator = \Validator::make($request->all(), [
                'progLangIDs'           => 'required|array',
                'progLangIDs.*'         => 'exists:programming_languages,progLangID',
                'actTitle'              => 'required|string|max:255',
                'actDesc'               => 'required|string',
                'actDifficulty'         => 'required|in:Beginner,Intermediate,Advanced',
                'actDuration'           => [
                    'required',
                    'regex:/^(0\d|1\d|2[0-3]):([0-5]\d):([0-5]\d)$/'
                ],
                'openDate'              => 'required|date',
                'closeDate'             => 'required|date|after:openDate',
                'maxPoints'             => 'required|integer|min:1',
                // Updated: "items" instead of "questions"
                'items'                 => 'required|array|min:1',
                'items.*.itemID'        => 'required|exists:items,itemID',
                'items.*.itemTypeID'    => 'required|exists:item_types,itemTypeID',
                'items.*.actItemPoints' => 'required|integer|min:1',
            ]);            

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Create the activity.
            $activity = Activity::create([
                'classID'       => $request->classID,
                'teacherID'     => $teacher->teacherID,
                'actTitle'      => $request->actTitle,
                'actDesc'       => $request->actDesc,
                'actDifficulty' => $request->actDifficulty,
                'actDuration'   => $request->actDuration,
                'openDate'      => $request->openDate,
                'closeDate'     => $request->closeDate,
                'maxPoints'     => $request->maxPoints, // temporary; will recalc
                'classAvgScore' => null,
                'highestScore'  => null,
            ]);

            // Attach programming languages.
            $activity->programmingLanguages()->attach($request->progLangIDs);

            // Attach selected items with points.
            foreach ($request->items as $item) {
                ActivityItem::create([
                    'actID'         => $activity->actID,
                    'itemID'        => $item['itemID'],
                    'itemTypeID'    => $item['itemTypeID'],
                    'actItemPoints' => $item['actItemPoints'],
                ]);
            }

            // Automatically calculate the total points from the provided items.
            $totalPoints = array_sum(array_column($request->items, 'actItemPoints'));

            // Update the activity's maxPoints accordingly.
            $activity->update(['maxPoints' => $totalPoints]);

            return response()->json([
                'message'  => 'Activity created successfully',
                'activity' => $activity->load([
                    'items.item', 
                    'items.itemType', 
                    'programmingLanguages'
                ]),
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
            'programmingLanguages',
            'items.item.testCases',
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
        $now = now();

        // Mark activities as completed if closeDate has passed.
        $updatedCount = Activity::where('classID', $classID)
            ->where('closeDate', '<', $now)
            ->whereNull('completed_at')
            ->update([
                'completed_at' => $now,
                'updated_at'   => $now,
            ]);

        \Log::info("Activities marked as completed: $updatedCount");

        // Upcoming Activities.
        $upcomingActivities = Activity::with([
                'teacher', 
                'programmingLanguages', 
                'items.item',
                'items.item.programmingLanguages',
                'items.itemType'
            ])
            ->where('classID', $classID)
            ->where('openDate', '>', $now)
            ->orderBy('openDate', 'asc')
            ->get();

        // Ongoing Activities.
        $ongoingActivities = Activity::with([
                'teacher', 
                'programmingLanguages', 
                'items.item',
                'items.item.programmingLanguages', 
                'items.itemType'
            ])
            ->where('classID', $classID)
            ->where('openDate', '<=', $now)
            ->where('closeDate', '>=', $now)
            ->whereNull('completed_at')
            ->orderBy('openDate', 'asc')
            ->get();

        // Completed Activities.
        $completedActivities = Activity::with([
                'teacher', 
                'programmingLanguages', 
                'items.item', 
                'items.item.programmingLanguages',
                'items.itemType'
            ])
            ->where('classID', $classID)
            ->whereNotNull('completed_at')
            ->where('openDate', '<=', $now)
            ->where('closeDate', '<', $now)
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
            'upcoming'  => $upcomingActivities,
            'ongoing'   => $ongoingActivities,
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

            // Validate input. Note: we now expect an "items" array.
            $validator = \Validator::make($request->all(), [
                'progLangIDs'           => 'sometimes|required|array',
                'progLangIDs.*'         => 'exists:programming_languages,progLangID',
                'actTitle'              => 'sometimes|required|string|max:255',
                'actDesc'               => 'sometimes|required|string',
                'actDifficulty'         => 'sometimes|required|in:Beginner,Intermediate,Advanced',
                'actDuration'           => [
                    'sometimes',
                    'required',
                    'regex:/^(0\d|1\d|2[0-3]):([0-5]\d):([0-5]\d)$/'
                ],
                'openDate'              => 'sometimes|required|date',
                'closeDate'             => 'sometimes|required|date|after:openDate',
                'maxPoints'             => 'sometimes|required|integer|min:1',
                // Updated: "items" instead of "questions"
                'items'                 => 'sometimes|required|array|min:1',
                'items.*.itemID'        => 'required_with:items|exists:items,itemID',
                'items.*.itemTypeID'    => 'required_with:items|exists:item_types,itemTypeID',
                'items.*.actItemPoints' => 'required_with:items|integer|min:1',
            ]);            

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // If a new closeDate is provided and is in the future, clear completed_at.
            if ($request->has('closeDate') && \Carbon\Carbon::parse($request->closeDate)->gt(now())) {
                $activity->completed_at = null;
                $activity->updated_at = now();
                $activity->save();
            }

            // Update activity details.
            $activity->update($request->only([
                'actTitle', 'actDesc', 'actDifficulty', 'actDuration', 'openDate', 'closeDate', 'maxPoints'
            ]));

            // Sync programming languages if provided.
            if ($request->has('progLangIDs')) {
                $activity->programmingLanguages()->sync($request->progLangIDs);
            }

            // Update items if provided.
            if ($request->has('items')) {
                // Delete existing ActivityItem records.
                ActivityItem::where('actID', $activity->actID)->delete();

                foreach ($request->items as $item) {
                    ActivityItem::create([
                        'actID'         => $activity->actID,
                        'itemID'        => $item['itemID'],
                        'itemTypeID'    => $item['itemTypeID'],
                        'actItemPoints' => $item['actItemPoints'],
                    ]);
                }

                // Recalculate total points from provided items.
                $totalPoints = array_sum(array_column($request->items, 'actItemPoints'));
                $activity->update(['maxPoints' => $totalPoints]);
            }

            return response()->json([
                'message'  => 'Activity updated successfully',
                'activity' => $activity->load([
                    'items.item',
                    'items.item.programmingLanguages',
                    'items.itemType',
                    'programmingLanguages'
                ]),
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
    // FUNCTIONS FOR ACTIVITY MANAGEMENT PAGE FOR STUDENTS
    ///////////////////////////////////////////////////

    public function showActivityItemsByStudent(Request $request, $actID)
    {
        $student = Auth::user();
        
        if (!$student || !$student instanceof \App\Models\Student) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        // Load the activity with related items.
        $activity = Activity::with([
            'items.item',    // load the actual item details
            'items.itemType',
            'classroom',
        ])->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        // Build the items array for the front end.
        $items = $activity->items->map(function ($ai) use ($student, $activity) {
            $submission = ActivitySubmission::where('actID', $activity->actID)
                ->where('itemID', $ai->item->itemID)
                ->first();
    
            return [
                'itemName'         => $ai->item->itemName ?? 'Unknown',
                'itemDifficulty'   => $ai->item->itemDifficulty ?? 'N/A',
                'itemType'         => $ai->itemType->itemTypeName ?? 'N/A',
                'actItemPoints'    => $ai->actItemPoints,  // pivot value from activity_items
                'studentScore'     => $submission ? $submission->score : null,
                'studentTimeSpent' => $submission ? $submission->timeSpent . ' min' : '-',
                'submissionStatus' => $submission ? 'Submitted' : 'Not Attempted',
            ];
        });
    
        return response()->json([
            'activityName' => $activity->actTitle,
            'actDesc'      => $activity->actDesc,
            'maxPoints'    => $activity->maxPoints,
            'items'        => $items,
        ]);
    }
    
    public function showActivityLeaderboardByStudent($actID)
    {
        // Fetch the activity.
        $activity = Activity::with('classroom')->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        // Fetch all student submissions for the activity.
        $submissions = ActivitySubmission::where('actID', $actID)
            ->join('students', 'activity_submissions.studentID', '=', 'students.studentID')
            ->select('students.studentID', 'students.firstname', 'students.lastname', 'students.program', 'activity_submissions.score')
            ->orderByDesc('activity_submissions.score')
            ->get();
    
        if ($submissions->isEmpty()) {
            return response()->json([
                'activityName' => $activity->actTitle,
                'message'      => 'No students have submitted this activity yet.',
                'leaderboard'  => []
            ]);
        }
    
        // Calculate rank based on score.
        $rankedSubmissions = $submissions->map(function ($submission, $index) {
            return [
                'studentName'  => strtoupper($submission->lastname) . ", " . $submission->firstname,
                'program'      => $submission->program ?? 'N/A',
                'averageScore' => $submission->score . '%',
                'rank'         => ($index + 1)
            ];
        });
    
        return response()->json([
            'activityName' => $activity->actTitle,
            'leaderboard'  => $rankedSubmissions
        ]);
    }

    ///////////////////////////////////////////////////
    // FUNCTIONS FOR ACTIVITY MANAGEMENT PAGE FOR TEACHERS
    ///////////////////////////////////////////////////

    public function showActivityItemsByTeacher($actID)
    {
        $activity = Activity::with([
            'items.item.programmingLanguages',
            'items.itemType',
            'classroom',
        ])->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        $items = $activity->items->map(function ($ai) use ($activity) {
            $item = $ai->item;
    
            $programmingLanguages = $item->programmingLanguages->map(function ($lang) {
                return [
                    'progLangID'   => $lang->progLangID,
                    'progLangName' => $lang->progLangName,
                ];
            })->values()->all();
    
            return [
                'itemName'        => $item->itemName ?? 'Unknown',
                'itemDesc'        => $item->itemDesc ?? '',
                'itemDifficulty'  => $item->itemDifficulty ?? 'N/A',
                'programming_languages' => $programmingLanguages,
                'itemType'        => $ai->itemType->itemTypeName ?? 'N/A',
                'testCases'       => $item->testCases->map(function ($tc) {
                    return [
                        'inputData'      => $tc->inputData,
                        'expectedOutput' => $tc->expectedOutput,
                        'testCasePoints' => $tc->testCasePoints,
                        'isHidden'       => $tc->isHidden,
                    ];
                }),
                'avgStudentScore'     => $this->calculateAverageScore($item->itemID, $activity->actID),
                'avgStudentTimeSpent' => $this->calculateAverageTimeSpent($item->itemID, $activity->actID),
                'actItemPoints'       => $ai->actItemPoints,
            ];
        });
    
        return response()->json([
            'activityName' => $activity->actTitle,
            'actDesc'      => $activity->actDesc,
            'maxPoints'    => $activity->maxPoints,
            'items'        => $items
        ]);
    }        
    
    /**
     * Calculate the average student score for an activity item.
     */
    private function calculateAverageScore($itemID, $actID)
    {
        return ActivitySubmission::where('actID', $actID)
            ->where('itemID', $itemID)
            ->avg('score') ?? '-';
    }

    /**
     * Calculate the average time spent by students on an activity item.
     */
    private function calculateAverageTimeSpent($itemID, $actID)
    {
        return ActivitySubmission::where('actID', $actID)
            ->where('itemID', $itemID)
            ->avg('timeSpent') ?? '-';
    }

    public function showActivityLeaderboardByTeacher($actID)
    {
        // Fetch the activity.
        $activity = Activity::with('classroom')->find($actID);
    
        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }
    
        $submissions = ActivitySubmission::where('actID', $actID)
            ->join('students', 'activity_submissions.studentID', '=', 'students.studentID')
            ->select('students.studentID', 'students.firstname', 'students.lastname', 'students.program', 'activity_submissions.score')
            ->orderByDesc('activity_submissions.score')
            ->get();
    
        if ($submissions->isEmpty()) {
            return response()->json([
                'activityName' => $activity->actTitle,
                'message'      => 'No students have submitted this activity yet.',
                'leaderboard'  => []
            ]);
        }
    
        $rankedSubmissions = $submissions->map(function ($submission, $index) {
            return [
                'studentName'  => strtoupper($submission->lastname) . ", " . $submission->firstname,
                'program'      => $submission->program ?? 'N/A',
                'averageScore' => $submission->score . '%',
                'rank'         => ($index + 1)
            ];
        });
    
        return response()->json([
            'activityName' => $activity->actTitle,
            'leaderboard'  => $rankedSubmissions
        ]);
    }

    /**
     * Show the activity settings.
     */
    public function showActivitySettingsByTeacher($actID)
    {
        $activity = Activity::with('classroom')->find($actID);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        return response()->json([
            'activityName' => $activity->actTitle,
            'maxPoints'    => $activity->maxPoints,
            'className'    => optional($activity->classroom)->className ?? 'N/A',
            'openDate'     => $activity->openDate,
            'closeDate'    => $activity->closeDate,
            'settings'     => [
                'examMode'         => (bool)$activity->examMode,
                'randomizedItems'  => (bool)$activity->randomizedItems,
                'disableReviewing' => (bool)$activity->disableReviewing,
                'hideLeaderboard'  => (bool)$activity->hideLeaderboard,
                'delayGrading'     => (bool)$activity->delayGrading,
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
            'examMode'         => 'boolean',
            'randomizedItems'  => 'boolean',
            'disableReviewing' => 'boolean',
            'hideLeaderboard'  => 'boolean',
            'delayGrading'     => 'boolean',
        ]);

        $activity->update($validatedData);

        return response()->json(['message' => 'Activity settings updated successfully.']);
    }
}