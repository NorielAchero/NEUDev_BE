<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ItemType;

class ItemTypeController extends Controller
{
    /**
     * Get all available item types.
     */
    public function index()
    {
        try {
            $itemTypes = ItemType::all(); // ✅ Fetch all item types

            if ($itemTypes->isEmpty()) {
                return response()->json(['message' => 'No item types found.'], 404);
            }

            return response()->json($itemTypes, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while fetching item types.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}