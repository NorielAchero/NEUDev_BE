<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileTeacherController extends Controller
{
    // 📌 GET Teacher Profile
    public function show(Teacher $teacher)
    {
        Log::info("Teacher Profile Accessed", ['teacherID' => $teacher->teacherID]);

        // Convert relative paths to full URLs using asset('storage/...')  
        $teacher->profileImage = $teacher->profileImage ? asset('storage/' . $teacher->profileImage) : null;
        $teacher->coverImage   = $teacher->coverImage ? asset('storage/' . $teacher->coverImage) : null;

        return response()->json($teacher, 200);
    }

    // 📌 UPDATE Teacher Profile
    public function update(Request $request, $teacherID)
    {
        Log::info("Update request received", ['teacherID' => $teacherID, 'requestData' => $request->all()]);

        // Find the teacher using teacherID
        $teacher = Teacher::where('teacherID', $teacherID)->first();
        if (!$teacher) {
            Log::warning("Teacher not found", ['teacherID' => $teacherID]);
            return response()->json(['error' => 'Teacher not found'], 404);
        }

        // Validate input
        $validated = $request->validate([
            'firstname'    => 'sometimes|string|max:255',
            'lastname'     => 'sometimes|string|max:255',
            'email'        => 'sometimes|email|unique:teachers,email,' . $teacher->teacherID . ',teacherID',
            'password'     => 'sometimes|string|min:8',
            'profileImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
            'coverImage'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048',
        ]);

        // Handle profile image upload if a new file is provided
        if ($request->hasFile('profileImage')) {
            if ($teacher->profileImage) {
                Storage::delete($teacher->profileImage);
            }
            $validated['profileImage'] = $request->file('profileImage')->store('profile_images', 'public');
        } else {
            if (isset($validated['profileImage']) && $validated['profileImage'] === "") {
                unset($validated['profileImage']);
            }
        }

        // Handle cover image upload if a new file is provided
        if ($request->hasFile('coverImage')) {
            if ($teacher->coverImage) {
                Storage::delete($teacher->coverImage);
            }
            $validated['coverImage'] = $request->file('coverImage')->store('cover_images', 'public');
        } else {
            if (isset($validated['coverImage']) && $validated['coverImage'] === "") {
                unset($validated['coverImage']);
            }
        }

        // Hash password if provided
        if ($request->filled('password')) {
            $validated['password'] = bcrypt($validated['password']);
        }

        // Remove any keys with empty values (optional)
        $validated = array_filter($validated, function ($value) {
            return $value !== "";
        });

        Log::info("Updating teacher with data", ['teacherID' => $teacher->teacherID, 'updateData' => $validated]);

        // Update the teacher with the validated (and filtered) data
        $teacher->update($validated);

        Log::info("Teacher profile updated successfully", ['teacherID' => $teacher->teacherID]);

        return response()->json(['message' => 'Profile updated successfully', 'teacher' => $teacher], 200);
    }

    // 📌 DELETE Teacher Profile
    public function destroy(Teacher $teacher)
    {
        // Delete stored images if they exist
        if ($teacher->profileImage) {
            Storage::delete($teacher->profileImage);
        }
        if ($teacher->coverImage) {
            Storage::delete($teacher->coverImage);
        }

        $teacher->delete();
        Log::warning("Teacher Profile Deleted", ['teacherID' => $teacher->teacherID]);

        return response()->json(['message' => 'Profile deleted successfully'], 200);
    }
}