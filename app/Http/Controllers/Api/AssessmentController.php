<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    /**
     * Display a listing of the assessments.
     */
    public function index()
    {
        $assessments = Assessment::all();
        return response()->json($assessments);
    }

    /**
     * Store a newly created assessment in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'actID'       => 'required|exists:activities,actID',
            'itemID'      => 'nullable|exists:items,itemID',
            'itemTypeID'  => 'nullable|exists:item_types,itemTypeID',
            'testCases'   => 'nullable|string',
            'submittedCode' => 'nullable|string',
            'result'      => 'nullable|string',
            'executionTime' => 'nullable|string',
            'progLang'    => 'nullable|string',
            'extraData'   => 'nullable|json',
        ]);

        $assessment = Assessment::create($validatedData);
        return response()->json($assessment, 201);
    }

    /**
     * Display the specified assessment.
     */
    public function show($id)
    {
        $assessment = Assessment::findOrFail($id);
        return response()->json($assessment);
    }

    /**
     * Update the specified assessment in storage.
     */
    public function update(Request $request, $id)
    {
        $assessment = Assessment::findOrFail($id);

        $validatedData = $request->validate([
            'testCases'     => 'nullable|string',
            'submittedCode' => 'nullable|string',
            'result'        => 'nullable|string',
            'executionTime' => 'nullable|string',
            'progLang'      => 'nullable|string',
            'extraData'     => 'nullable|json',
        ]);

        $assessment->update($validatedData);
        return response()->json($assessment);
    }

    /**
     * Remove the specified assessment from storage.
     */
    public function destroy($id)
    {
        $assessment = Assessment::findOrFail($id);
        $assessment->delete();
        return response()->json(null, 204);
    }
}