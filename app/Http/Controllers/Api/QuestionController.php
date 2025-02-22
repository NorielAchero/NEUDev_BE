<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Question;

class QuestionController extends Controller
{
    /**
     * Get preset questions based on item type (e.g., "Console App").
     */
    public function getByItemType($itemTypeID)
    {
        $questions = Question::where('itemTypeID', $itemTypeID)->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'No questions found for this item type.'], 404);
        }

        return response()->json($questions, 200);
    }
}