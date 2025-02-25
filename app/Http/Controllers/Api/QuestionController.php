<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\TestCase;
use App\Models\ActivityQuestion;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    /**
     * Get all questions for a specific item type (with test cases & programming languages).
     */
    public function getByItemType(Request $request, $itemTypeID)
    {
        $query = Question::where('itemTypeID', $itemTypeID)
            ->with(['testCases', 'programmingLanguages']) // ✅ Load multiple programming languages
            ->orderBy('created_at', 'desc');

        // ✅ If `progLangID` is provided, filter by programming languages
        if ($request->has('progLangID')) {
            $query->whereHas('programmingLanguages', function ($q) use ($request) {
                $q->whereIn('progLangID', (array) $request->progLangID);
            });
        }

        return response()->json($query->get(), 200);
    }

    /**
     * Create a new question with multiple programming languages and optional test cases.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'itemTypeID' => 'required|exists:item_types,itemTypeID',
            'progLangIDs' => 'required|array', // ✅ Multiple programming languages
            'progLangIDs.*' => 'exists:programming_languages,progLangID', // Ensure each exists
            'questionName' => 'required|string|max:255',
            'questionDesc' => 'required|string',
            'difficulty' => 'required|in:Beginner,Intermediate,Advanced',
            'testCases' => 'nullable|array',
            'testCases.*.inputData' => 'nullable|string',
            'testCases.*.expectedOutput' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // ✅ Create the question
            $question = Question::create([
                'itemTypeID' => $validatedData['itemTypeID'],
                'questionName' => $validatedData['questionName'],
                'questionDesc' => $validatedData['questionDesc'],
                'difficulty' => $validatedData['difficulty'],
            ]);

            // ✅ Attach multiple programming languages
            $question->programmingLanguages()->attach($validatedData['progLangIDs']);

            // ✅ Attach test cases if provided
            if (!empty($validatedData['testCases'])) {
                foreach ($validatedData['testCases'] as $testCase) {
                    TestCase::create([
                        'questionID' => $question->questionID,
                        'inputData' => $testCase['inputData'] ?? "",
                        'expectedOutput' => $testCase['expectedOutput'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Question created successfully',
                'data' => $question->load(['testCases', 'programmingLanguages']),
                'created_at' => $question->created_at->toDateTimeString(),
                'updated_at' => $question->updated_at->toDateTimeString(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single question by ID (with test cases & programming languages).
     */
    public function show($questionID)
    {
        $question = Question::with(['testCases', 'programmingLanguages'])->find($questionID);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        return response()->json([
            'question' => $question,
            'created_at' => $question->created_at->toDateTimeString(),
            'updated_at' => $question->updated_at->toDateTimeString()
        ]);
    }

    /**
     * Update a question, including multiple programming languages and test cases.
     */
    public function update(Request $request, $questionID)
    {
        $question = Question::find($questionID);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $validatedData = $request->validate([
            'itemTypeID' => 'required|exists:item_types,itemTypeID',
            'progLangIDs' => 'required|array', // ✅ Multiple programming languages
            'progLangIDs.*' => 'exists:programming_languages,progLangID',
            'questionName' => 'required|string|max:255',
            'questionDesc' => 'required|string',
            'difficulty' => 'required|in:Beginner,Intermediate,Advanced',
            'testCases' => 'nullable|array',
            'testCases.*.inputData' => 'nullable|string',
            'testCases.*.expectedOutput' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // ✅ Update question details
            $question->update([
                'itemTypeID' => $validatedData['itemTypeID'],
                'questionName' => $validatedData['questionName'],
                'questionDesc' => $validatedData['questionDesc'],
                'difficulty' => $validatedData['difficulty'],
            ]);

            // ✅ Sync multiple programming languages
            $question->programmingLanguages()->sync($validatedData['progLangIDs']);

            // ✅ If test cases are provided, delete existing and add new ones
            if ($request->has('testCases')) {
                TestCase::where('questionID', $questionID)->delete();
                foreach ($validatedData['testCases'] as $testCase) {
                    TestCase::create([
                        'questionID' => $question->questionID,
                        'inputData' => $testCase['inputData'] ?? "",
                        'expectedOutput' => $testCase['expectedOutput'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Question updated successfully',
                'data' => $question->load(['testCases', 'programmingLanguages']),
                'created_at' => $question->created_at->toDateTimeString(),
                'updated_at' => $question->updated_at->toDateTimeString(),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a question (and its test cases).
     */
    public function destroy($questionID)
    {
        $question = Question::find($questionID);

        if (!$question) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        // ✅ Prevent deleting questions linked to an activity
        if (ActivityQuestion::where('questionID', $questionID)->exists()) {
            return response()->json(['message' => 'Cannot delete: Question is linked to an activity.'], 403);
        }

        DB::beginTransaction();
        try {
            // ✅ Detach programming languages before deleting
            $question->programmingLanguages()->detach();

            $question->delete();
            DB::commit();

            return response()->json(['message' => 'Question deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete question',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}