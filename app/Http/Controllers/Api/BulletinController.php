<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BulletinPost;
use Illuminate\Support\Facades\Auth;

class BulletinController extends Controller {
    
    // Fetch all posts for a specific class
    public function index($classID) {
        $posts = BulletinPost::where('classID', $classID)
            ->orderBy('created_at', 'desc')
            ->with('teacher')
            ->get();

        return response()->json($posts);
    }

    // Create a new bulletin post
    public function store(Request $request) {
        $teacher = Auth::user();

        if (!$teacher || !$teacher instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'classID' => 'required|integer',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $post = BulletinPost::create([
            'classID' => $request->classID,
            'teacherID' => $teacher->teacherID,
            'title' => $request->title,
            'message' => $request->message,
        ]);

        return response()->json($post, 201);
    }

    // Delete a bulletin post
    public function destroy($id) {
        $teacher = Auth::user();

        $post = BulletinPost::where('id', $id)->where('teacherID', $teacher->teacherID)->first();

        if (!$post) {
            return response()->json(['message' => 'Post not found or unauthorized'], 404);
        }

        $post->delete();
        return response()->json(['message' => 'Post deleted successfully']);
    }
}
