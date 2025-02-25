<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProgrammingLanguage;

class ProgrammingLanguageController extends Controller
{
    /**
     * ✅ Get all programming languages
     */
    public function get()
    {
        $languages = ProgrammingLanguage::orderBy('progLangName', 'asc')->get();
        return response()->json($languages, 200);
    }

    /**
     * ✅ Store a new programming language
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'progLangName' => 'required|string|unique:programming_languages,progLangName|max:255',
        ]);

        $language = ProgrammingLanguage::create($validatedData);

        return response()->json([
            'message' => 'Programming language added successfully.',
            'data' => $language
        ], 201);
    }

    /**
     * ✅ Get a single programming language by ID
     */
    public function show($id)
    {
        $language = ProgrammingLanguage::find($id);

        if (!$language) {
            return response()->json(['message' => 'Programming language not found'], 404);
        }

        return response()->json($language);
    }

    /**
     * ✅ Update a programming language
     */
    public function update(Request $request, $id)
    {
        $language = ProgrammingLanguage::find($id);

        if (!$language) {
            return response()->json(['message' => 'Programming language not found'], 404);
        }

        $validatedData = $request->validate([
            'progLangName' => 'required|string|unique:programming_languages,progLangName,' . $id . ',progLangID|max:255',
        ]);

        $language->update($validatedData);

        return response()->json([
            'message' => 'Programming language updated successfully.',
            'data' => $language
        ], 200);
    }

    /**
     * ✅ Delete a programming language
     */
    public function destroy($id)
    {
        $language = ProgrammingLanguage::find($id);

        if (!$language) {
            return response()->json(['message' => 'Programming language not found'], 404);
        }

        $language->delete();

        return response()->json(['message' => 'Programming language deleted successfully'], 200);
    }
}